<?php

namespace App\Business;

use App\Dto\CreateMediaDto;
use App\Dto\MediaDto;
use App\Dto\MediaPublicDto;
use App\Dto\MediaSelectionDto;
use App\Dto\PersonMediaDto;
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
use Doctrine\Common\Collections\Collection;
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

    public function createPersonMedia(MediaPublicDto $mediaPersonDto, UploadedFile $insuranceFile, ?UploadedFile $bookFile): void
    {
        $now = new DateTime();
        $nextRound = $this->roundRepository->findRoundFromDate($now);

        $person = $this->createPerson($mediaPersonDto, $nextRound);

        if (!empty($mediaPersonDto->instagram)) {
            $this->linkHelper->upsertInstagramLink($person, $mediaPersonDto->instagram);
        }

        $media = $person->getMedias()->filter(fn($media) => $media->getRound()?->getId() === $nextRound->getId())->first();

        $mediaDto = new MediaDto(
            roundId: $nextRound->getId(),
            id: $media !== false ? $media->getId() : null,
            selected: false,
            pilotFollow: $mediaPersonDto->pilotFollow
        );
        $this->updateMedias($person, [$mediaDto], [$insuranceFile], [$bookFile], false);

        $this->em->flush();

        $this->emailHelper->sendPreselectedEmail($mediaPersonDto->email, $nextRound, $mediaPersonDto->firstName);
    }

    public function createPerson(MediaPublicDto $mediaDto, Round $round): Person
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

    /**
     * Creates new medias associated with a person.
     *
     * @param CreateMediaDto $mediaDto
     * @param array $insuranceFiles
     * @param array $bookFiles
     * @return Person|null
     * @throws Exception
     */
    public function createMedia(CreateMediaDto $mediaDto, array $insuranceFiles, array $bookFiles): ?Person
    {
        $person = $this->personRepository->find($mediaDto->personId);
        if ($mediaDto->personId === null || $person === null) {
            throw new Exception('Person not found');
        }

        $this->updatePersonRoundDetails($person, $mediaDto->roundDetails);
        $this->em->persist($person);

        // update medias
        $mediaDto = $this->serializer->denormalize($mediaDto->medias, MediaDto::class . '[]');
        $this->updateMedias($person, $mediaDto, $insuranceFiles, $bookFiles, false);

        $this->em->flush();

        return $person;
    }

    public function updatePersonMedia(Person $person, PersonMediaDto $personMediaDto, array $insuranceFiles, array $bookFiles): void
    {
        // update person
        $person->setFirstName($personMediaDto->firstName)
            ->setLastName($personMediaDto->lastName)
            ->setEmail($personMediaDto->email)
            ->setPhone($personMediaDto->phone)
            ->setWarnings($personMediaDto->warnings)
            ->setComment($personMediaDto->comment);

        $this->updatePersonRoundDetails($person, $personMediaDto->roundDetails);

        $this->em->persist($person);

        // update instagram link
        if (!empty($personMediaDto->instagram)) {
            $this->linkHelper->upsertInstagramLink($person, $personMediaDto->instagram);
        }

        // update media
        $mediaDtos = $this->serializer->denormalize($personMediaDto->medias, MediaDto::class . '[]');
        $this->updateMedias($person, $mediaDtos, $insuranceFiles, $bookFiles);

        $this->em->flush();
    }

    public function updateMedias(Person $person, array $mediaDtos, array $insuranceFiles, array $bookFiles, bool $cleanExistent = true): void
    {
        $medias = $person->getMedias();

        if ($cleanExistent) {
            // delete medias that are not in the DTO
            $this->deleteMedias($medias, $mediaDtos);
        }

        /** @var MediaDto $mediaDto */
        foreach ($mediaDtos as $key => $mediaDto) {
            $round = $this->roundRepository->find($mediaDto->roundId);
            if ($round === null) {
                continue;
            }

            if ($mediaDto->id) {
                $media = $medias->filter(fn(Media $m) => $m->getId() === $mediaDto->id)->first();
                if ($media === false) {
                    continue;
                }
            } else {
                $media = $medias->filter(fn(Media $m) => $m->getRound()?->getId() === $round->getId())->first();
                if ($media === false) {
                    $media = new Media();
                    $media->setPerson($person);
                }
            }

            $media->setRound($round)
                ->setSelected($mediaDto->selected)
                ->setPilotFollow($mediaDto->pilotFollow);

            if (isset($insuranceFiles[$key]) || isset($bookFiles[$key])) {
                $path = 'media/' . $media->getRound()->getId() . '/' . $person->getUniqueId();

                if (isset($insuranceFiles[$key])) {
                    $insuranceFile = $insuranceFiles[$key];
                    $insuranceFile = $this->fileHelper->saveFile($insuranceFile, $path, 'assurance.' . $insuranceFile->getClientOriginalExtension());
                    $media->setInsuranceFilePath($insuranceFile->getPathname());
                }

                if (isset($bookFiles[$key])) {
                    $bookFile = $bookFiles[$key];
                    $bookFile = $this->fileHelper->saveFile($bookFile, $path, 'book' . $bookFile->getClientOriginalExtension());
                    $media->setBookFilePath($bookFile->getPathname());
                }
            }

            $this->em->persist($media);
        }
    }

    private function updatePersonRoundDetails(Person $person, array $roundDetails): void
    {
        // Supprimer les détails de rounds qui ne sont plus dans la liste de présence
        foreach ($person->getRoundDetails()->toArray() as $roundDetail) {
            if (!in_array($roundDetail->getId(), $roundDetails)) {
                $person->removeRoundDetail($roundDetail);
            }
        }

        // Ajouter les nouveaux détails de rounds
        foreach ($roundDetails as $roundDetailId) {
            // Vérifier si le détail de round existe déjà
            if ($person->getRoundDetails()->exists(fn($key, $rd) => $rd->getId() === $roundDetailId)) {
                continue;
            }

            $roundDetail = $this->roundDetailRepository->find($roundDetailId);
            if ($roundDetail !== null) {
                $person->addRoundDetail($roundDetail);

                if (!$person->getRounds()->contains($roundDetail->getRound())) {
                    $person->addRound($roundDetail->getRound());
                }
            }
        }
    }

    /**
     * Deletes medias from the database.
     *
     * @param Collection<int, Media> $medias
     * @param MediaDto[] $mediaDtos
     */
    private function deleteMedias(Collection $medias, array $mediaDtos): void
    {
        $mediaDtoIds = array_map(fn(MediaDto $dto) => $dto->id, $mediaDtos);
        $mediasToDelete = $medias->filter(fn(Media $m) => !in_array($m->getId(), $mediaDtoIds));

        foreach ($mediasToDelete as $media) {
            $this->em->remove($media);
        }
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