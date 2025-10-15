<?php

namespace App\Repository;

use App\Entity\Invitation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invitation::class);
    }

    public function findValidByToken(string $token): ?Invitation
    {
        $qb = $this->createQueryBuilder('i')
            ->andWhere('i.token = :t')
            ->setParameter('t', $token)
            ->setMaxResults(1);

        /** @var Invitation|null $inv */
        $inv = $qb->getQuery()->getOneOrNullResult();

        if ($inv === null) return null;
        if ($inv->getStatus() !== 'pending') return null;
        if ($inv->isExpired()) return null;

        return $inv;
    }
}
