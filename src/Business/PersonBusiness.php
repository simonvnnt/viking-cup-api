<?php

namespace App\Business;

use App\Dto\PersonDto;
use App\Entity\Person;
use App\Helper\LinkHelper;
use App\Repository\PersonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;

readonly class PersonBusiness
{
    public function __construct(
        private PersonRepository       $personRepository,
        private LinkHelper             $linkHelper,
        private EntityManagerInterface $em
    )
    {}

    public function getPersons(
        int $page,
        int $limit,
        ?string $sort = null,
        ?string $order = null,
        ?string $person = null
    ): array
    {
        $persons = $this->personRepository->findPersonsPaginated($sort, $order, $person);

        $adapter = new QueryAdapter($persons, false, false);
        $pager = new Pagerfanta($adapter);
        $totalItems = $pager->count();
        $pager->setMaxPerPage($limit);
        $pager->setCurrentPage($page);
        $persons = $pager->getCurrentPageResults();

        return [
            'pagination' => [
                'totalItems' => $totalItems,
                'pageIndex' => $page,
                'itemsPerPage' => $limit
            ],
            'persons' => $persons
        ];
    }

    public function createPerson(PersonDto $personDto): Person
    {
        $person = $this->personRepository->findOneBy(['email' => $personDto->email, 'firstName' => $personDto->firstName, 'lastName' => $personDto->lastName]);
        if ($person === null) {
            $person = new Person();
            $person->setEmail($personDto->email)
                ->setFirstName($personDto->firstName)
                ->setLastName($personDto->lastName);
        }

        $person->setPhone($personDto->phone)
            ->setAddress($personDto->address)
            ->setCity($personDto->city)
            ->setZipCode($personDto->zipCode)
            ->setCountry($personDto->country)
            ->setWarnings($personDto->warnings)
            ->setComment($personDto->comment);

        if (!empty($personDto->instagram)) {
            $this->linkHelper->upsertInstagramLink($person, $personDto->instagram);
        }

        $this->em->persist($person);
        $this->em->flush();

        return $person;
    }
}