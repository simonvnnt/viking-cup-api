<?php

namespace App\Business;

use App\Dto\MediaDto;
use App\Dto\MediaSelectionDto;
use App\Entity\Media;
use App\Entity\Person;
use App\Entity\Round;
use App\Helper\FileHelper;
use App\Helper\EmailHelper;
use App\Helper\LinkHelper;
use App\Helper\PdfHelper;
use App\Repository\PersonRepository;
use App\Repository\RoundDetailRepository;
use App\Repository\RoundRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\SerializerInterface;
use TCPDF_FONTS;
use Twig\Environment;

readonly class MediaBusiness
{
    public function __construct(
        private PersonRepository       $personRepository,
        private RoundRepository        $roundRepository,
        private RoundDetailRepository  $roundDetailRepository,
        private FileHelper             $fileHelper,
        private EmailHelper            $emailHelper,
        private LinkHelper             $linkHelper,
        private Environment            $twig,
        private SerializerInterface    $serializer,
        private ParameterBagInterface  $parameterBag,
        private EntityManagerInterface $em
    )
    {}

    public function getMedias(
        int $page,
        int $limit,
        ?string $sort = null,
        ?string $order = null,
        ?int    $eventId = null,
        ?int    $roundId = null,
        ?string $name = null,
        ?string $email = null,
        ?string $phone = null,
        ?bool   $selected = null,
        ?bool   $selectedMailSent = null,
        ?bool   $eLearningMailSent = null,
        ?bool   $briefingSeen = null,
        ?bool   $generatePass = null
    ): array
    {
        $personIdsTotal = $this->personRepository->findFilteredMediaPersonIdsPaginated($page, $limit, $sort, $order, $eventId, $roundId, $name, $email, $phone, $selected, $selectedMailSent, $eLearningMailSent, $briefingSeen, $generatePass);
        $persons = $this->personRepository->findPersonsByIds($personIdsTotal['items']);

        $mediaPersons = [];
        /** @var Person $person */
        foreach ($persons as $person) {
            $personArray = $this->serializer->normalize($person, 'json', ['groups' => ['person', 'personRoundDetails', 'roundDetail', 'personLinks', 'link', 'linkLinkType', 'linkType']]);

            $medias = $person->getMedias()->filter(function (Media $media) use ($generatePass, $briefingSeen, $selectedMailSent, $eLearningMailSent, $selected, $roundId, $eventId) {
                return (!$eventId || $media->getRound()->getEvent()->getId() === $eventId) &&
                    (!$roundId || $media->getRound()->getId() === $roundId) &&
                    ($selected === null || $media->isSelected() === $selected) &&
                    ($selectedMailSent === null || $media->isSelectedMailSent() === $selectedMailSent) &&
                    ($eLearningMailSent === null || $media->isELearningMailSent() === $eLearningMailSent) &&
                    ($briefingSeen === null || $media->isBriefingSeen() === $briefingSeen) &&
                    ($generatePass === null || $media->isGeneratePass() === $generatePass);
            });

            $personArray['medias'] = array_values($medias->toArray());

            if (!empty($personArray['medias'])) {
                $mediaPersons[] = $personArray;
            }
        }

        return [
            'pagination' => [
                'totalItems' => $personIdsTotal['total'],
                'pageIndex' => $page,
                'itemsPerPage' => $limit
            ],
            'medias' => $mediaPersons
        ];
    }

    public function getMediaByUniqueId(string $uniqueId): ?Media
    {
        $now = new DateTime();
        $nextRound = $this->roundRepository->findRoundFromDate($now);
        if ($nextRound === null) {
            throw new Exception('Next round not found');
        }

        $person = $this->personRepository->findOneBy(['uniqueId' => $uniqueId]);

        $media = $person->getMedias()->filter(fn(Media $media) => $media->getRound()->getId() === $nextRound->getId())->first();
        if ($media === false) {
            return null;
        }

        return $media;
    }

    public function createPersonMedia(MediaDto $mediaDto, UploadedFile $insuranceFile, ?UploadedFile $bookFile): void
    {
        $now = new DateTime();
        $nextRound = $this->roundRepository->findRoundFromDate($now);

        $person = $this->createPerson($mediaDto, $nextRound);

        if (!empty($mediaDto->instagram)) {
            $this->linkHelper->upsertInstagramLink($person, $mediaDto->instagram);
        }

        $this->createMedia($person, $nextRound, $mediaDto->pilotFollow, $insuranceFile, $bookFile);

        $this->em->flush();

        $this->emailHelper->sendPreselectedEmail($mediaDto->email, $nextRound, $mediaDto->firstName);
    }

    public function createPerson(MediaDto $mediaDto, Round $round): Person
    {
        $person = $this->personRepository->findOneBy(['email' => $mediaDto->email]);
        if ($person === null) {
            $person = new Person();
            $person->setEmail($mediaDto->email);
        }

        $person->setFirstName($mediaDto->firstName)
            ->setLastName($mediaDto->lastName)
            ->setPhone($mediaDto->phone)
            ->addRound($round);

        foreach ($mediaDto->presence as $roundDetailId) {
            $roundDetail = $this->roundDetailRepository->find($roundDetailId);
            if ($roundDetail !== null) {
                $person->addRoundDetail($roundDetail);
            }
        }

        $this->em->persist($person);

        return $person;
    }

    public function createMedia(Person $person, Round $round, ?string $pilotFollow, ?UploadedFile $insuranceFile, ?UploadedFile $bookFile): Media
    {
        // get round media or create new one
        $media = $person->getMedias()->filter(fn($media) => $media->getRound()?->getId() === $round->getId())->first();
        if ($media === false) {
            $media = new Media();
            $media->setPerson($person)
                ->setRound($round);
        }

        if (!empty($pilotFollow)) {
            $media->setPilotFollow($pilotFollow);
        }

        $path = 'media/' . $round->getId() . '/' . $person->getUniqueId();

        if ($insuranceFile !== null) {
            $insuranceFile = $this->fileHelper->saveFile($insuranceFile, $path,  'assurance.' . $insuranceFile->getClientOriginalExtension());
            $media->setInsuranceFilePath($insuranceFile->getPathname());
        }

        if ($bookFile !== null) {
            $bookFile = $this->fileHelper->saveFile($bookFile, $path, 'book' . $bookFile->getClientOriginalExtension());
            $media->setBookFilePath($bookFile->getPathname());
        }

        $this->em->persist($media);

        return $media;
    }

    public function updatePersonMedia(Media $media, MediaDto $mediaDto, ?UploadedFile $insuranceFile, ?UploadedFile $bookFile): void
    {
        // update person
        $person = $media->getPerson();

        $person->setFirstName($mediaDto->firstName)
            ->setLastName($mediaDto->lastName)
            ->setEmail($mediaDto->email)
            ->setPhone($mediaDto->phone)
            ->setWarnings($mediaDto->warnings);

        // Supprimer les détails de rounds qui ne sont plus dans la liste de présence
        foreach ($person->getRoundDetails()->toArray() as $roundDetail) {
            if (!in_array($roundDetail->getId(), $mediaDto->presence)) {
                $person->removeRoundDetail($roundDetail);
            }
        }
        
        // Ajouter les nouveaux détails de rounds
        foreach ($mediaDto->presence as $roundDetailId) {
            // Vérifier si le détail de round existe déjà
            if ($person->getRoundDetails()->exists(fn($key, $rd) => $rd->getId() === $roundDetailId)) {
                continue;
            }

            $roundDetail = $this->roundDetailRepository->find($roundDetailId);
            if ($roundDetail !== null) {
                $person->addRoundDetail($roundDetail);
            }
        }
        

        $this->em->persist($person);

        // update instagram link
        if (!empty($mediaDto->instagram)) {
            $this->linkHelper->upsertInstagramLink($person, $mediaDto->instagram);
        }

        // update media
        $media->setPilotFollow($mediaDto->pilotFollow)
            ->setSelected($mediaDto->selected);

        if ($insuranceFile !== null || $bookFile !== null) {
            $path = 'media/' . $media->getRound()->getId() . '/' . $person->getUniqueId();

            if ($insuranceFile !== null) {
                $insuranceFile = $this->fileHelper->saveFile($insuranceFile, $path, 'assurance.' . $insuranceFile->getClientOriginalExtension());
                $media->setInsuranceFilePath($insuranceFile->getPathname());
            }

            if ($bookFile !== null) {
                $bookFile = $this->fileHelper->saveFile($bookFile, $path, 'book' . $bookFile->getClientOriginalExtension());
                $media->setBookFilePath($bookFile->getPathname());
            }
        }

        $this->em->persist($media);

        $this->em->flush();
    }

    public function updateMediaSelection(Media $media, MediaSelectionDto $mediaSelectionDto): void
    {
        $media->setSelected($mediaSelectionDto->selected);

        $this->em->persist($media);
        $this->em->flush();
    }

    public function deleteMedia(Media $media): void
    {
        $this->em->remove($media);
        $this->em->flush();
    }

    public function deleteMediaBook(Media $media): Media
    {
        $this->fileHelper->deleteFile($media->getBookFilePath());
        $media->setBookFilePath(null);

        $this->em->persist($media);
        $this->em->flush();

        return $media;
    }

    public function generatePass(Media $media): string
    {
        $html = $this->twig->render('pdf/pass-media/pass.html.twig', ['media' => $media]);

        $publicDir = $this->parameterBag->get('kernel.project_dir');

        $figtreeFont = TCPDF_FONTS::addTTFfont($publicDir . '/public/fonts/figtree.ttf', 'TrueTypeUnicode', '', 96);
        $figtreeLightFont = TCPDF_FONTS::addTTFfont($publicDir . '/public/fonts/figtree-light.ttf', 'TrueTypeUnicode', '', 96);
        $figtreeBoldFont = TCPDF_FONTS::addTTFfont($publicDir . '/public/fonts/figtree-bold.ttf', 'TrueTypeUnicode', '', 96);
        $finderFont = TCPDF_FONTS::addTTFfont($publicDir . '/public/fonts/finder.ttf', 'TrueTypeUnicode', '', 96);


        $pdf = new PdfHelper($this->twig, $media);
        $pdf->setTitle('Pass #' . $media->getId());
        $fileName = 'pass_media' . $media->getId() . '.pdf';

        $pdf->SetFooterMargin(28);
        $pdf->addFont($figtreeFont);
        $pdf->addFont($figtreeLightFont);
        $pdf->addFont($figtreeBoldFont);
        $pdf->addFont($finderFont);
        $pdf->SetHeaderMargin();
        $pdf->setMargins(10, 100, 10);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true);

        return $pdf->Output($fileName);
    }

    public function briefingSeen(Media $media): void
    {
        $media->setBriefingSeen(true);
        $this->em->persist($media);
        $this->em->flush();
    }

    public function passGenerated(Media $media): void
    {
        $media->setGeneratePass(true);
        $this->em->persist($media);
        $this->em->flush();
    }

    public function sendSelectedEmails(Round $round): array
    {
        $errors = [];
        $medias = $round->getMedias()->filter(fn(Media $media) => $media->isSelected() && !$media->isSelectedMailSent());

        foreach ($medias->toArray() as $media) {
            try {
                $this->emailHelper->sendSelectedEmail($media->getPerson()->getEmail(), $round, $media->getPerson()->getFirstName());
                $media->setSelectedMailSent(true);
                $this->em->persist($media);
            } catch (Exception $e) {
                $errors[] = [
                    'email' => $media->getPerson()?->getEmail(),
                    'error' => $e->getMessage()
                ];
            }
        }

        $this->em->flush();

        return $errors;
    }

    public function sendELearningEmails(Round $round): array
    {
        $errors = [];
        $medias = $round->getMedias()->filter(fn(Media $media) => $media->isSelected() && $media->isSelectedMailSent() && !$media->isELearningMailSent());

        foreach ($medias->toArray() as $media) {
            try {
                $this->emailHelper->sendELearningEmail($media->getPerson()->getEmail(), $round, $media->getPerson()->getFirstName(), $media->getPerson()->getUniqueId());
                $media->setELearningMailSent(true);
                $this->em->persist($media);
            } catch (Exception $e) {
                $errors[] = [
                    'email' => $media->getPerson()?->getEmail(),
                    'error' => $e->getMessage()
                ];
            }
        }

        $this->em->flush();

        return $errors;
    }
}