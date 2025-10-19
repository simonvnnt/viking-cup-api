<?php

namespace App\Helper;

use App\Entity\Link;
use App\Entity\Person;
use App\Repository\LinkTypeRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class LinkHelper
{
    public function __construct(
        private LinkTypeRepository $linkTypeRepository,
        private EntityManagerInterface $em
    )
    {}

    public function upsertInstagramLink(Person $person, string $instagram): void
    {
        if ($person->getLinks()->isEmpty() || $person->getLinks()->filter(fn($link) => $link->getLinkType()->getName() === 'Instagram')->isEmpty()) {
            $instaLinkType = $this->linkTypeRepository->findOneBy(['name' => 'Instagram']);

            $instaLink = new Link();
            $instaLink->setLinkType($instaLinkType)
                ->setUrl(ltrim($instagram, '@'))
                ->addPerson($person);
        } else {
            $instaLink = $person->getLinks()->filter(fn($link) => $link->getLinkType()->getName() === 'Instagram')->first();
            $instaLink->setUrl(ltrim($instagram, '@'));
        }

        $this->em->persist($instaLink);
        $this->em->flush();
    }
}