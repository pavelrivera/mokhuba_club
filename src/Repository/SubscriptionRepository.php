<?php
namespace App\Repository;

use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Subscription|null find($id, $lockMode = null, $lockVersion = null)
 * @method Subscription|null findOneBy(array $criteria, array $orderBy = null)
 * @method Subscription[]    findAll()
 * @method Subscription[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    /**
     * Busca una suscripción por su ID de Stripe
     */
    public function findByStripeId(string $stripeSubscriptionId): ?Subscription
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.stripeSubscriptionId = :stripe_id')
            ->setParameter('stripe_id', $stripeSubscriptionId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Obtiene la suscripción activa de un usuario
     */
    public function findActiveByUser(User $user): ?Subscription
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->andWhere('s.status IN (:active_statuses)')
            ->setParameter('user', $user)
            ->setParameter('active_statuses', ['active', 'trialing'])
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Obtiene todas las suscripciones de un usuario ordenadas por fecha
     */
    public function findByUserOrderedByDate(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca suscripciones por nivel de membresía
     */
    public function findByMembershipLevel(string $membershipLevel): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.membershipLevel = :level')
            ->andWhere('s.status IN (:active_statuses)')
            ->setParameter('level', $membershipLevel)
            ->setParameter('active_statuses', ['active', 'trialing'])
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Cuenta suscripciones activas por nivel
     */
    public function countActiveByLevel(): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s.membershipLevel as level, COUNT(s.id) as count')
            ->andWhere('s.status IN (:active_statuses)')
            ->setParameter('active_statuses', ['active', 'trialing'])
            ->groupBy('s.membershipLevel');

        $results = $qb->getQuery()->getResult();
        
        $counts = ['ruby' => 0, 'gold' => 0, 'platinum' => 0];
        foreach ($results as $result) {
            $counts[$result['level']] = (int)$result['count'];
        }
        
        return $counts;
    }

    /**
     * Busca por customer ID de Stripe
     */
    public function findByStripeCustomerId(string $customerId): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.stripeCustomerId = :customer_id')
            ->setParameter('customer_id', $customerId)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}