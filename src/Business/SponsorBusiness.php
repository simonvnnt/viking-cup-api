<?php

namespace App\Business;

use App\Dto\CreateSponsorDto;
use App\Dto\SponsorDto;
use App\Dto\SponsorLinkDto;
use App\Dto\SponsorshipCounterpartDto;
use App\Dto\SponsorshipDto;
use App\Entity\Link;
use App\Entity\LinkType;
use App\Entity\Person;
use App\Entity\Sponsor;
use App\Entity\Sponsorship;
use App\Entity\SponsorshipCounterpart;
use App\Helper\FileHelper;
use App\Repository\EventRepository;
use App\Repository\LinkTypeRepository;
use App\Repository\PersonRepository;
use App\Repository\RoundRepository;
use App\Repository\SponsorRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\SerializerInterface;

readonly class SponsorBusiness
{
    public function __construct(
        private SponsorRepository      $sponsorRepository,
        private PersonRepository       $personRepository,
        private EventRepository        $eventRepository,
        private RoundRepository        $roundRepository,
        private LinkTypeRepository     $linkTypeRepository,
        private FileHelper             $fileHelper,
        private SerializerInterface    $serializer,
        private EntityManagerInterface $em
    )
    {}

    public function getSponsors(
        int $page,
        int $limit,
        ?string $sort = null,
        ?string $order = null,
        ?string $name = null,
        ?string $contact = null,
        ?int    $eventId = null,
        ?int    $roundId = null,
        ?string $status = null,
        ?string $counterpartType = null,
        ?int    $minAmount = null,
        ?int    $maxAmount = null,
        ?string $otherCounterpart = null,
        ?bool   $hasContract = null
    ): array
    {
        $sponsorIdsTotal = $this->sponsorRepository->findFilteredSponsorIdsPaginated($page, $limit, $sort, $order, $name, $contact, $eventId, $roundId, $status, $counterpartType, $minAmount, $maxAmount, $otherCounterpart, $hasContract);
        $sponsors = $this->sponsorRepository->findSponsorsByIds($sponsorIdsTotal['items']);

        $sponsorPersons = [];
        /** @var Sponsor $sponsor */
        foreach ($sponsors as $sponsor) {
            $sponsorArray = $this->serializer->normalize($sponsor, null, ['groups' => ['sponsor', 'sponsorLinks', 'sponsorPerson', 'person', 'personRoundDetails', 'roundDetail', 'personLinks', 'link', 'linkLinkType', 'linkType']]);

            $sponsorships = $sponsor->getSponsorships()->filter(function (Sponsorship $sponsorship) use ($eventId, $roundId, $status, $counterpartType, $minAmount, $maxAmount, $otherCounterpart, $hasContract) {
                return (!$eventId || $sponsorship->getEvent()->getId() === $eventId) &&
                    (!$roundId || $sponsorship->getRound()->getId() === $roundId) &&
                    (!$status || strtolower($sponsorship->getStatus()->value) === strtolower($status)) &&
                    (!$counterpartType || $sponsorship->getSponsorshipCounterparts()->exists(function ($key, $counterpart) use ($counterpartType) {
                        return $counterpart->getCounterpartType()?->value === $counterpartType;
                    })) &&
                    (!$minAmount || $sponsorship->getSponsorshipCounterparts()->exists(function ($key, $counterpart) use ($minAmount) {
                        return $counterpart->getAmount() !== null && $counterpart->getAmount() >= $minAmount;
                    })) &&
                    (!$maxAmount || $sponsorship->getSponsorshipCounterparts()->exists(function ($key, $counterpart) use ($maxAmount) {
                        return $counterpart->getAmount() !== null && $counterpart->getAmount() <= $maxAmount;
                    })) &&
                    (!$otherCounterpart || $sponsorship->getSponsorshipCounterparts()->exists(function ($key, $counterpart) use ($otherCounterpart) {
                        return str_contains(strtolower($counterpart->getOtherCounterpart() ?? ''), strtolower($otherCounterpart));
                    })) &&
                    ($hasContract === null || ($hasContract && $sponsorship->getContractFilePath() !== null) || (!$hasContract && $sponsorship->getContractFilePath() === null));
            });

            $sponsorArray['sponsorships'] = array_values($sponsorships->toArray());

            $sponsorPersons[] = $sponsorArray;
        }

        return [
            'pagination' => [
                'totalItems' => $sponsorIdsTotal['total'],
                'pageIndex' => $page,
                'itemsPerPage' => $limit
            ],
            'sponsors' => $sponsorPersons
        ];
    }

    /**
     * Creates a new sponsor and its contact information.
     *
     * @param CreateSponsorDto $sponsorDto The data transfer object containing the sponsor information.
     * @param UploadedFile|null $sponsorImage An optional image file for the sponsor.
     * @param UploadedFile[] $contractFiles An array of contract files to be associated with the sponsorships.
     *
     * @return Sponsor The created sponsor entity.
     */
    public function createSponsor(CreateSponsorDto $sponsorDto, ?UploadedFile $sponsorImage, array $contractFiles): Sponsor
    {
        // create sponsor
        $sponsor = new Sponsor();
        $sponsor->setName($sponsorDto->name)
            ->setDescription($sponsorDto->description)
            ->setDisplayWebsite($sponsorDto->displayWebsite)
            ->setAlt($sponsorDto->alt);

        if ($sponsorDto->contactId !== null) {
            $person = $this->personRepository->find($sponsorDto->contactId);
            $sponsor->setContact($person);
        }

        if ($sponsorImage !== null) {
            $path = 'uploads/media/';
            $filename = $this->fileHelper->normalizeFilename($sponsorDto->name);
            $filename = empty($filename) ? $sponsorImage->getClientOriginalName() : $filename;

            $sponsorImage = $this->fileHelper->saveFile($sponsorImage, $path, $filename . '.' . $sponsorImage->getClientOriginalExtension());
            $sponsor->setFilePath($sponsorImage->getPathname());
        }

        $this->em->persist($sponsor);

        // update sponsorships
        $sponsorshipsDto = $this->serializer->denormalize($sponsorDto->sponsorships, SponsorshipDto::class . '[]');
        $this->updateSponsorships($sponsor, $sponsorshipsDto, $contractFiles);

        // update links
        $sponsorLinksDto = $this->serializer->denormalize($sponsorDto->links, SponsorLinkDto::class . '[]');
        $this->updateSponsorLinks($sponsor, $sponsorLinksDto);

        $this->em->flush();

        return $sponsor;
    }

    /**
     * Updates a sponsor and its contact information.
     *
     * @param Sponsor $sponsor The sponsor to update.
     * @param SponsorDto $sponsorDto The data transfer object containing the updated sponsor information.
     * @param UploadedFile|null $sponsorImage An optional image file for the sponsor.
     * @param UploadedFile[] $contractFiles An array of contract files to be associated with the sponsorships.
     */
    public function updatePersonSponsor(Sponsor $sponsor, SponsorDto $sponsorDto, ?UploadedFile $sponsorImage, array $contractFiles): void
    {
        // update sponsor
        $sponsor->setName($sponsorDto->name)
            ->setDescription($sponsorDto->description)
            ->setDisplayWebsite($sponsorDto->displayWebsite)
            ->setAlt($sponsorDto->alt);

        if ($sponsorImage !== null) {
            $path = 'uploads/media/';
            $filename = $this->fileHelper->normalizeFilename($sponsorDto->name);
            $filename = empty($filename) ? $sponsorImage->getClientOriginalName() : $filename;

            $sponsorImage = $this->fileHelper->saveFile($sponsorImage, $path, $filename . '.' . $sponsorImage->getClientOriginalExtension());
            $sponsor->setFilePath($sponsorImage->getPathname());
        }

        // update contact
        if (!empty($sponsorDto->firstName) && !empty($sponsorDto->lastName) && !empty($sponsorDto->email)) {
            $contact = $sponsor->getContact();

            if ($contact === null) {
                $contact = new Person();
                $sponsor->setContact($contact);
            }

            if (!empty($sponsorDto->phone)) {
                $contact->setPhone($sponsorDto->phone);
            }

            $contact->setFirstName($sponsorDto->firstName);
            $contact->setLastName($sponsorDto->lastName);
            $contact->setEmail($sponsorDto->email);
            $contact->setWarnings($sponsorDto->warnings)
                ->setComment($sponsorDto->comment);

            $this->em->persist($sponsor);
            $this->em->persist($contact);
        }
        $this->em->flush();

        // update sponsorships
        $sponsorshipsDto = $this->serializer->denormalize($sponsorDto->sponsorships, SponsorshipDto::class . '[]');
        $this->updateSponsorships($sponsor, $sponsorshipsDto, $contractFiles);

        // update links
        $sponsorLinksDto = $this->serializer->denormalize($sponsorDto->links, SponsorLinkDto::class . '[]');
        $this->updateSponsorLinks($sponsor, $sponsorLinksDto);

        $this->em->flush();
    }

    public function deleteSponsor(Sponsor $sponsor): void
    {
        $this->em->remove($sponsor);
        $this->em->flush();
    }

    public function deleteSponsorImage(Sponsor $sponsor): void
    {
        $this->fileHelper->deleteFile($sponsor->getFilePath());

        $sponsor->setFilePath(null);
        $this->em->persist($sponsor);
        $this->em->flush();
    }

    public function deleteSponsorshipContract(Sponsorship $sponsorship): void
    {
        $this->fileHelper->deleteFile($sponsorship->getContractFilePath());

        $sponsorship->setContractFilePath(null);
        $this->em->persist($sponsorship);
        $this->em->flush();
    }

    private function updateSponsorships(Sponsor $sponsor, array $sponsorshipsDto, array $contractFiles): void
    {
        $sponsorships = $sponsor->getSponsorships();

        // delete sponsorships that are not in the DTO
        $this->deleteSponsorships($sponsorships, $sponsorshipsDto);

        /** @var SponsorshipDto $sponsorshipDto */
        foreach ($sponsorshipsDto as $key => $sponsorshipDto) {
            if ($sponsorshipDto->id) {
                $sponsorship = $sponsorships->filter(fn(Sponsorship $s) => $s->getId() === $sponsorshipDto->id)->first();
                if ($sponsorship === false) {
                    continue;
                }
            } else {
                $sponsorship = new Sponsorship();
                $sponsorship->setSponsor($sponsor);
            }

            if ($sponsorshipDto->roundId) {
                $round = $this->roundRepository->find($sponsorshipDto->roundId);
                $path = 'sponsor/' . $sponsor->getId() . '/round' . $round->getId();
            } elseif ($sponsorshipDto->eventId) {
                $event = $this->eventRepository->find($sponsorshipDto->eventId);
                $path = 'sponsor/' . $sponsor->getId() . '/event' . $event->getId();
            }

            $sponsorship->setEvent($event ?? null)
                ->setRound($round ?? null)
                ->setStatus($sponsorshipDto->status);

            if (isset($contractFiles[$key])) {
                $contractFile = $this->fileHelper->saveFile(
                    $contractFiles[$key],
                    $path ?? 'sponsor/' . $sponsor->getId(),
                    'contract.' . $contractFiles[$key]->getClientOriginalExtension()
                );
                $sponsorship->setContractFilePath($contractFile->getPathname());
            }

            $counterpartsDto = $this->serializer->denormalize($sponsorshipDto->counterparts, SponsorshipCounterpartDto::class . '[]');
            $this->updateCounterparts($sponsorship, $counterpartsDto);

            $this->em->persist($sponsorship);
        }
    }

    /**
     * Deletes sponsorships from the database.
     *
     * @param Collection<int, Sponsorship> $sponsorships
     * @param SponsorshipDto[] $sponsorshipsDto
     */
    private function deleteSponsorships(Collection $sponsorships, array $sponsorshipsDto): void
    {
        $sponsorshipDtoIds = array_map(fn(SponsorshipDto $dto) => $dto->id, $sponsorshipsDto);
        $sponsorshipsToDelete = $sponsorships->filter(fn(Sponsorship $s) => !in_array($s->getId(), $sponsorshipDtoIds));

        foreach ($sponsorshipsToDelete as $sponsorship) {
            $this->em->remove($sponsorship);
        }
    }

    private function updateCounterparts(Sponsorship $sponsorship, array $counterpartsDto): void
    {
        $counterparts = $sponsorship->getSponsorshipCounterparts();

        // delete counterparts that are not in the DTO
        $this->deleteCounterparts($counterparts, $counterpartsDto);

        // update or create counterparts
        foreach ($counterpartsDto as $counterpartDto) {
            if ($counterpartDto->id) {
                $counterpart = $counterparts->filter(fn(SponsorshipCounterpart $c) => $c->getId() === $counterpartDto->id)->first();
                if ($counterpart === false) {
                    continue;
                }
            } else {
                $counterpart = new SponsorshipCounterpart();
                $counterpart->setSponsorship($sponsorship);
                $sponsorship->addSponsorCounterpart($counterpart);
            }

            $counterpart->setCounterpartType($counterpartDto->counterpartType)
                ->setAmount($counterpartDto->amount)
                ->setOtherCounterpart($counterpartDto->otherCounterpart);

            $this->em->persist($counterpart);
        }
    }

    private function deleteCounterparts(Collection $counterparts, array $counterpartsDto): void
    {
        $counterpartDtoIds = array_map(fn(SponsorshipCounterpartDto $dto) => $dto->id, $counterpartsDto);
        $counterpartsToDelete = $counterparts->filter(fn(SponsorshipCounterpart $c) => !in_array($c->getId(), $counterpartDtoIds));

        foreach ($counterpartsToDelete as $counterpart) {
            $this->em->remove($counterpart);
        }
    }

    /**
     * Updates the sponsor links.
     *
     * @param Sponsor $sponsor The sponsor entity to update.
     * @param SponsorLinkDto[] $linksDto The data transfer object containing the updated links information.
     */
    private function updateSponsorLinks(Sponsor $sponsor, array $linksDto): void
    {
        $links = $sponsor->getLinks();

        // delete links that are not in the DTO
        $this->deleteSponsorLinks($links, $linksDto);

        foreach ($linksDto as $linkDto) {
            if ($linkDto->id) {
                $link = $links->filter(fn($l) => $l->getId() === $linkDto->id)->first();
                if ($link === false) {
                    continue;
                }
            } else {
                $link = new Link();
                $sponsor->addLink($link);
            }

            $linkType = $this->linkTypeRepository->find($linkDto->linkTypeId);
            if ($linkType === null) {
                continue;
            }

            $baseUrl = $this->getLinkTypeBaseUrl($linkType);

            $link->setUrl($baseUrl . $linkDto->link)
                ->setLinkType($linkType);

            $this->em->persist($link);
        }
    }

    private function deleteSponsorLinks(Collection $links, array $linksDto): void
    {
        $linkDtoIds = array_map(fn(SponsorLinkDto $dto) => $dto->id, $linksDto);
        $linksToDelete = $links->filter(fn($l) => !in_array($l->getId(), $linkDtoIds));

        foreach ($linksToDelete as $link) {
            $this->em->remove($link);
        }
    }

    private function getLinkTypeBaseUrl(LinkType $linkType): string
    {
        return match ($linkType->getId()) {
            1 => 'https://www.instagram.com/',
            2 => 'https://www.facebook.com/',
            3 => 'https://www.youtube.com/',
            default => '',
        };
    }
}