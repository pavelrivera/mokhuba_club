<?php
// src/Repository/VitolariosRepository.php

namespace App\Repository;

use App\Entity\Vitolarios;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Vitolarios>
 *
 * @method Vitolarios|null find($id, $lockMode = null, $lockVersion = null)
 * @method Vitolarios|null findOneBy(array $criteria, array $orderBy = null)
 * @method Vitolarios[]    findAll()
 * @method Vitolarios[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VitolariosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vitolarios::class);
    }

    /**
     * Guarda un entity Vitolarios en la base de datos
     */
    public function save(Vitolarios $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Elimina un entity Vitolarios de la base de datos
     */
    public function remove(Vitolarios $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Encuentra todos los Vitolarios ordenados por nombre
     */
    public function findAllOrderedByNombre(): array
    {
        return $this->createQueryBuilder('v')
            ->orderBy('v.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra Vitolarios por cepo
     */
    public function findByCepo(int $cepo): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.cepo = :cepo')
            ->setParameter('cepo', $cepo)
            ->orderBy('v.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra Vitolarios por rango de diámetro
     */
    public function findByDiametroRange(float $minDiametro, float $maxDiametro): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.diametro BETWEEN :min AND :max')
            ->setParameter('min', $minDiametro)
            ->setParameter('max', $maxDiametro)
            ->orderBy('v.diametro', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra Vitolarios por fortaleza mínima
     */
    public function findByFortalezaMinima(int $fortalezaMinima): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.fortaleza >= :fortaleza')
            ->setParameter('fortaleza', $fortalezaMinima)
            ->orderBy('v.fortaleza', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Cuenta el total de registros de Vitolarios
     */
    public function countAll(): int
    {
        return $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Busca Vitolarios por nombre (búsqueda parcial)
     */
    public function searchByNombre(string $searchTerm): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.nombre LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('v.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}