<?php
namespace App\Repository;

use App\Entity\Payment;
use App\Entity\User;
use App\Entity\Subscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Payment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Payment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Payment[]    findAll()
 * @method Payment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    /**
     * Busca un pago por su Payment Intent ID de Stripe
     */
    public function findByStripePaymentIntentId(string $paymentIntentId): ?Payment
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.stripePaymentIntentId = :pi_id')
            ->setParameter('pi_id', $paymentIntentId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Obtiene todos los pagos de un usuario ordenados por fecha
     */
    public function findByUserOrderedByDate(User $user, int $limit = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Obtiene pagos exitosos de un usuario
     */
    public function findSuccessfulByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->andWhere('p.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'succeeded')
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtiene pagos de una suscripción específica
     */
    public function findBySubscription(Subscription $subscription): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.subscription = :subscription')
            ->setParameter('subscription', $subscription)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca pagos por estado
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', $status)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca pagos fallidos para retry
     */
    public function findFailedPayments(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status IN (:failed_statuses)')
            ->setParameter('failed_statuses', ['requires_payment_method', 'payment_failed'])
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtiene el total de ingresos de un usuario
     */
    public function getTotalRevenueByUser(User $user): int
    {
        $result = $this->createQueryBuilder('p')
            ->select('SUM(p.amount) as total')
            ->andWhere('p.user = :user')
            ->andWhere('p.status = :status')
            ->andWhere('p.refunded = :not_refunded')
            ->setParameter('user', $user)
            ->setParameter('status', 'succeeded')
            ->setParameter('not_refunded', false)
            ->getQuery()
            ->getOneOrNullResult();

        return (int)($result['total'] ?? 0);
    }
}