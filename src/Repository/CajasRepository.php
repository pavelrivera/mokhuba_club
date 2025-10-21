<?php
// src/Repository/CajasRepository.php

namespace App\Repository;

use App\Entity\Cajas;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cajas>
 *
 * @method Cajas|null find($id, $lockMode = null, $lockVersion = null)
 * @method Cajas|null findOneBy(array $criteria, array $orderBy = null)
 * @method Cajas[]    findAll()
 * @method Cajas[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CajasRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cajas::class);
    }

    /**
     * Guarda un entity Cajas en la base de datos
     */
    public function save(Cajas $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Elimina un entity Cajas de la base de datos
     */
    public function remove(Cajas $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Encuentra todas las Cajas ordenadas por cantidad de puros
     */
    public function findAllOrderedByCantidadPuros(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.cant_puros', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra Cajas por cantidad de puros
     */
    public function findByCantidadPuros(int $cantidadPuros): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.cant_puros = :cantidad')
            ->setParameter('cantidad', $cantidadPuros)
            ->orderBy('c.cant_puros', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra Cajas por rango de cantidad de puros
     */
    public function findByCantidadPurosRange(int $minCantidad, int $maxCantidad): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.cant_puros BETWEEN :min AND :max')
            ->setParameter('min', $minCantidad)
            ->setParameter('max', $maxCantidad)
            ->orderBy('c.cant_puros', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra Cajas por estilo
     */
    public function findByEstilo(string $estilo): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.estilo = :estilo')
            ->setParameter('estilo', $estilo)
            ->orderBy('c.cant_puros', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra Cajas por tipo de madera
     */
    public function findByMadera(string $madera): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.madera = :madera')
            ->setParameter('madera', $madera)
            ->orderBy('c.estilo', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra Cajas por color
     */
    public function findByColor(string $color): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.color = :color')
            ->setParameter('color', $color)
            ->orderBy('c.estilo', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca Cajas por texto (búsqueda en múltiples campos)
     */
    public function searchByText(string $searchTerm): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.texto LIKE :searchTerm OR c.estilo LIKE :searchTerm OR c.detalle_int LIKE :searchTerm OR c.detalle_ext LIKE :searchTerm OR c.madera LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('c.cant_puros', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca Cajas por detalles internos o externos
     */
    public function findByDetalles(string $detalle): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.detalle_int LIKE :detalle OR c.detalle_ext LIKE :detalle')
            ->setParameter('detalle', '%' . $detalle . '%')
            ->orderBy('c.estilo', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Cuenta el total de registros de Cajas
     */
    public function countAll(): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Obtiene estadísticas de cajas por estilo
     */
    public function getEstadisticasPorEstilo(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.estilo, COUNT(c.id) as cantidad, AVG(c.cant_puros) as promedio_puros')
            ->groupBy('c.estilo')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtiene estadísticas de cajas por madera
     */
    public function getEstadisticasPorMadera(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.madera, COUNT(c.id) as cantidad, AVG(c.cant_puros) as promedio_puros')
            ->groupBy('c.madera')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra Cajas con los detalles más recientes (últimas 10)
     */
    public function findLatest(int $maxResults = 10): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.id', 'DESC')
            ->setMaxResults($maxResults)
            ->getQuery()
            ->getResult();
    }
}