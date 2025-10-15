<?php

namespace App\Repository;

use App\Entity\Membresia;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Membresia>
 *
 * @method Membresia|null find($id, $lockMode = null, $lockVersion = null)
 * @method Membresia|null findOneBy(array $criteria, array $orderBy = null)
 * @method Membresia[]    findAll()
 * @method Membresia[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MembresiaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Membresia::class);
    }

    /**
     * Guarda una entidad Membresia en la base de datos
     */
    public function save(Membresia $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Elimina una entidad Membresia de la base de datos
     */
    public function remove(Membresia $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Crea una nueva membresía
     */
    public function create(string $nombre, $precio): Membresia
    {
        $membresia = new Membresia();
        $membresia->setNombre($nombre);
        $membresia->setPrecio($precio);
        
        $this->save($membresia, true);
        
        return $membresia;
    }

    /**
     * Actualiza una membresía existente
     */
    public function update(Membresia $membresia, string $nombre = null, $precio = null): Membresia
    {
        if ($nombre !== null) {
            $membresia->setNombre($nombre);
        }
        
        if ($precio !== null) {
            $membresia->setPrecio($precio);
        }
        
        $membresia->setUpdatedAt(new \DateTime());
        $this->save($membresia, true);
        
        return $membresia;
    }

    /**
     * Elimina una membresía por ID
     */
    public function deleteById(int $id): bool
    {
        $membresia = $this->find($id);
        
        if (!$membresia) {
            return false;
        }
        
        $this->remove($membresia, true);
        return true;
    }

    /**
     * Encuentra una membresía por ID
     */
    public function findById(int $id): ?Membresia
    {
        return $this->find($id);
    }

    /**
     * Encuentra todas las membresías ordenadas por nombre
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('m')
            ->orderBy('m.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra membresías por rango de precio
     */
    public function findByPriceRange(float $minPrice, float $maxPrice): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.precio >= :minPrice')
            ->andWhere('m.precio <= :maxPrice')
            ->setParameter('minPrice', $minPrice)
            ->setParameter('maxPrice', $maxPrice)
            ->orderBy('m.precio', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra membresías por nombre (búsqueda parcial)
     */
    public function findByNombreLike(string $searchTerm): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.nombre LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('m.nombre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtiene el precio promedio de todas las membresías
     */
    public function getAveragePrice(): float
    {
        return $this->createQueryBuilder('m')
            ->select('AVG(m.precio) as averagePrice')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    /**
     * Obtiene la membresía más cara
     */
    public function findMostExpensive(): ?Membresia
    {
        return $this->createQueryBuilder('m')
            ->orderBy('m.precio', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Obtiene la membresía más económica
     */
    public function findCheapest(): ?Membresia
    {
        return $this->createQueryBuilder('m')
            ->orderBy('m.precio', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Cuenta el total de membresías
     */
    public function countMembresias(): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Encuentra membresías creadas después de una fecha específica
     */
    public function findCreatedAfter(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.createdAt >= :date')
            ->setParameter('date', $date)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra membresías actualizadas recientemente
     */
    public function findRecentlyUpdated(int $days = 7): array
    {
        $date = new \DateTime();
        $date->modify("-$days days");

        return $this->createQueryBuilder('m')
            ->andWhere('m.updatedAt >= :date')
            ->setParameter('date', $date)
            ->orderBy('m.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}