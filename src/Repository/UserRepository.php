<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{


    /**
     * @return User[]
     *
     * @return array
     * @throws \Exception
     */
    public function findSuperLiker() : array
    {
        $qb = $this->createQueryBuilder('u');

        $qb->where('u.tinder_refresh_token IS NOT NULL')
        ->andWhere('u.next_super_like IS NULL OR u.next_super_like < :dateTime')
        ->setParameter('dateTime',new \DateTime());

        return $qb->getQuery()->getResult();


    }

}