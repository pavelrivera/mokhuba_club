<?php
// src/Repository/AnillosRepository.php

namespace App\Repository;

use App\Entity\Anillos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Anillos>
 *
 * @method Anillos|null find($id, $lockMode = null, $lockVersion = null)
 * @method Anillos|null findOneBy(array $criteria, array $orderBy = null)
 * @method Anillos[]    findAll()
 * @method Anillos[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnillosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Anillos::class);
    }

    /**
     * Guarda un entity Anillos en la base de datos
     */
    public function save(Anillos $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Elimina un entity Anillos de la base de datos
     */
    public function remove(Anillos $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Encuentra todos los Anillos ordenados por cantidad
     */
    public function findAllOrderedByCantidad(): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.cantidad', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra Anillos por cantidad
     */
    public function findByCantidad(int $cantidad): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.cantidad = :cantidad')
            ->setParameter('cantidad', $cantidad)
            ->orderBy('a.cantidad', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra Anillos por rango de cantidad
     */
    public function findByCantidadRange(int $minCantidad, int $maxCantidad): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.cantidad BETWEEN :min AND :max')
            ->setParameter('min', $minCantidad)
            ->setParameter('max', $maxCantidad)
            ->orderBy('a.cantidad', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra Anillos por forma
     */
    public function findByForma(string $forma): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.forma = :forma')
            ->setParameter('forma', $forma)
            ->orderBy('a.cantidad', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra Anillos por color
     */
    public function findByColor(string $color): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.color = :color')
            ->setParameter('color', $color)
            ->orderBy('a.forma', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra Anillos por color de bordes
     */
    public function findByColorBordes(string $colorBordes): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.color_bordes = :colorBordes')
            ->setParameter('colorBordes', $colorBordes)
            ->orderBy('a.forma', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca Anillos por texto (búsqueda en múltiples campos)
     */
    public function searchByText(string $searchTerm): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.texto LIKE :searchTerm OR a.forma LIKE :searchTerm OR a.color LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('a.cantidad', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra Anillos que tienen imagen asociada
     */
    public function findWithImages(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.imagen IS NOT NULL')
            ->orderBy('a.cantidad', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra Anillos sin imagen asociada
     */
    public function findWithoutImages(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.imagen IS NULL')
            ->orderBy('a.cantidad', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Cuenta el total de registros de Anillos
     */
    public function countAll(): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Obtiene estadísticas de anillos por forma
     */
    public function getEstadisticasPorForma(): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.forma, COUNT(a.id) as cantidad, AVG(a.cantidad) as promedio_cantidad')
            ->groupBy('a.forma')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtiene estadísticas de anillos por color
     */
    public function getEstadisticasPorColor(): array
    {
        return $this->createQueryBuilder('a')
            ->select('a.color, COUNT(a.id) as cantidad')
            ->groupBy('a.color')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra Anillos con los mismos colores principales y de bordes
     */
    public function findWithMatchingColors(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.color = a.color_bordes')
            ->orderBy('a.forma', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra los Anillos más recientes
     */
    public function findLatest(int $maxResults = 10): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.id', 'DESC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();
    }
}