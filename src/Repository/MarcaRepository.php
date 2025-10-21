<?php
// src/Repository/MarcaRepository.php

namespace App\Repository;

use App\Entity\Marcas;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Marca>
 *
 * @method Marca|null find($id, $lockMode = null, $lockVersion = null)
 * @method Marca|null findOneBy(array $criteria, array $orderBy = null)
 * @method Marca[]    findAll()
 * @method Marca[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MarcaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Marcas::class);
    }

    public function save(Marcas $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Marcas $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}