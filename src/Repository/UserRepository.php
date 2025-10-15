<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Obtiene estadísticas básicas de usuarios
     */
    public function getStats(): array
    {
        $em = $this->getEntityManager();
        
        // Total de usuarios activos
        $totalUsers = $em->createQuery('SELECT COUNT(u.id) FROM App\Entity\User u WHERE u.isActive = :active')
            ->setParameter('active', true)
            ->getSingleScalarResult();

        // Usuarios por tipo de membresía
        $membershipCounts = $em->createQuery('
            SELECT u.membershipLevel, COUNT(u.id) as count 
            FROM App\Entity\User u 
            WHERE u.isActive = :active 
            GROUP BY u.membershipLevel
        ')
        ->setParameter('active', true)
        ->getResult();

        // Convertir a array asociativo
        $membershipData = [
            'admin' => 0,
            'ruby' => 0,
            'gold' => 0,
            'platinum' => 0
        ];

        foreach ($membershipCounts as $item) {
            $level = $item['membershipLevel'] ?? 'guest';
            if (isset($membershipData[$level])) {
                $membershipData[$level] = (int) $item['count'];
            }
        }

        // Usuarios recientes (último mes)
        $recentUsers = $em->createQuery('
            SELECT COUNT(u.id) FROM App\Entity\User u 
            WHERE u.isActive = :active 
            AND u.createdAt >= :date
        ')
        ->setParameter('active', true)
        ->setParameter('date', new \DateTime('-30 days'))
        ->getSingleScalarResult();

        return [
            'total_users' => (int) $totalUsers,
            'active_users' => (int) $totalUsers,
            'recent_users' => (int) $recentUsers,
            'membership_counts' => $membershipData
        ];
    }

    /**
     * Cuenta usuarios por nivel de membresía
     */
    public function countByMembershipLevel(): array
    {
        $result = $this->createQueryBuilder('u')
            ->select('u.membershipLevel, COUNT(u.id) as count')
            ->where('u.isActive = :active')
            ->setParameter('active', true)
            ->groupBy('u.membershipLevel')
            ->getQuery()
            ->getResult();

        $counts = [
            'admin' => 0,
            'ruby' => 0,
            'gold' => 0,
            'platinum' => 0
        ];

        foreach ($result as $item) {
            $level = $item['membershipLevel'] ?? 'guest';
            if (isset($counts[$level])) {
                $counts[$level] = (int) $item['count'];
            }
        }

        return $counts;
    }

    /**
     * Encuentra usuarios activos por nivel de membresía
     */
    public function findActiveByMembershipLevel(string $level): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.isActive = :active')
            ->andWhere('u.membershipLevel = :level')
            ->setParameter('active', true)
            ->setParameter('level', $level)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca usuarios por email o nombre (MÉTODO ÚNICO - SIN DUPLICAR)
     */
    public function searchUsers(string $term): array
    {
        $qb = $this->createQueryBuilder('u');
        
        return $qb
            ->where($qb->expr()->orX(
                $qb->expr()->like('u.email', ':term'),
                $qb->expr()->like('u.firstName', ':term'),
                $qb->expr()->like('u.lastName', ':term'),
                $qb->expr()->like($qb->expr()->concat('u.firstName', $qb->expr()->literal(' '), 'u.lastName'), ':term')
            ))
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Verifica si un email ya está en uso
     */
    public function isEmailTaken(string $email, ?int $excludeUserId = null): bool
    {
        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.email = :email')
            ->setParameter('email', $email);

        if ($excludeUserId) {
            $qb->andWhere('u.id != :excludeUserId')
               ->setParameter('excludeUserId', $excludeUserId);
        }

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Verifica si un código único ya está en uso
     */
    public function isUniqueCodeTaken(string $code, ?int $excludeUserId = null): bool
    {
        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.uniqueCode = :code')
            ->setParameter('code', $code);

        if ($excludeUserId) {
            $qb->andWhere('u.id != :excludeUserId')
               ->setParameter('excludeUserId', $excludeUserId);
        }

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Busca usuario por Stripe Customer ID
     */
    public function findByStripeCustomerId(string $stripeCustomerId): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.stripeCustomerId = :stripeCustomerId')
            ->setParameter('stripeCustomerId', $stripeCustomerId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Encuentra usuarios con suscripción activa
     */
    public function findUsersWithActiveSubscription(): array
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.subscriptions', 's')
            ->where('s.status = :status')
            ->setParameter('status', 'active')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra usuarios por nivel de membresía
     */
    public function findByMembershipLevel(string $membershipLevel): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.membershipLevel = :membershipLevel')
            ->setParameter('membershipLevel', $membershipLevel)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra usuarios sin Stripe Customer ID
     */
    public function findUsersWithoutStripeCustomer(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.stripeCustomerId IS NULL')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra usuarios con pagos fallidos
     */
    public function findUsersWithFailedPayments(): array
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.payments', 'p')
            ->where('p.status = :status')
            ->setParameter('status', 'failed')
            ->groupBy('u.id')
            ->having('COUNT(p.id) >= 2')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra usuarios con suscripciones que expiran pronto
     */
    public function findUsersWithExpiringSoon(int $days = 7): array
    {
        $futureDate = new \DateTime("+{$days} days");
        
        return $this->createQueryBuilder('u')
            ->innerJoin('u.subscriptions', 's')
            ->where('s.status = :status')
            ->andWhere('s.endDate <= :futureDate')
            ->andWhere('s.endDate > :now')
            ->setParameter('status', 'active')
            ->setParameter('futureDate', $futureDate)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtiene estadísticas de usuarios por membresía
     */
    public function getUserStatsByMembership(): array
    {
        return $this->createQueryBuilder('u')
            ->select([
                'u.membershipLevel',
                'COUNT(u.id) as user_count',
                'u.subscriptionStatus'
            ])
            ->where('u.membershipLevel IS NOT NULL')
            ->groupBy('u.membershipLevel', 'u.subscriptionStatus')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra usuarios por estado de suscripción
     */
    public function findBySubscriptionStatus(string $subscriptionStatus): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.subscriptionStatus = :subscriptionStatus')
            ->setParameter('subscriptionStatus', $subscriptionStatus)
            ->orderBy('u.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra usuarios premium (con cualquier nivel de membresía)
     */
    public function findPremiumUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.membershipLevel IN (:levels)')
            ->andWhere('u.subscriptionStatus = :status')
            ->setParameter('levels', ['ruby', 'gold', 'platinum'])
            ->setParameter('status', 'active')
            ->orderBy('u.membershipLevel', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtiene el valor total de vida del cliente (LTV)
     */
    public function calculateCustomerLTV(User $user): float
    {
        $result = $this->createQueryBuilder('u')
            ->select('SUM(p.amount) as total_paid')
            ->innerJoin('u.payments', 'p')
            ->where('u.id = :userId')
            ->andWhere('p.status = :status')
            ->setParameter('userId', $user->getId())
            ->setParameter('status', 'succeeded')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Encuentra usuarios inactivos (sin pagos recientes)
     */
    public function findInactiveUsers(int $months = 3): array
    {
        $cutoffDate = new \DateTime("-{$months} months");
        
        return $this->createQueryBuilder('u')
            ->leftJoin('u.payments', 'p', 'WITH', 'p.createdAt > :cutoffDate AND p.status = :status')
            ->where('p.id IS NULL')
            ->andWhere('u.createdAt < :cutoffDate')
            ->andWhere('u.subscriptionStatus != :activeStatus')
            ->setParameter('cutoffDate', $cutoffDate)
            ->setParameter('status', 'succeeded')
            ->setParameter('activeStatus', 'active')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtiene usuarios con mayor gasto
     */
    public function getTopSpendingUsers(int $limit = 10): array
    {
        return $this->createQueryBuilder('u')
            ->select('u', 'SUM(p.amount) as total_spent')
            ->innerJoin('u.payments', 'p')
            ->where('p.status = :status')
            ->setParameter('status', 'succeeded')
            ->groupBy('u.id')
            ->orderBy('total_spent', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Genera un código único para el usuario
     */
    public function generateUniqueCode(): string
    {
        $maxAttempts = 10;
        $attempt = 0;

        do {
            // Generar código de 8 caracteres alfanuméricos
            $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            $attempt++;

            // Verificar si el código ya existe
            $exists = $this->isUniqueCodeTaken($code);

            if (!$exists) {
                return $code;
            }

        } while ($attempt < $maxAttempts);

        // Si después de 10 intentos no se genera un código único, usar timestamp
        return strtoupper(substr(bin2hex(random_bytes(4)), 0, 6) . substr((string)time(), -2));
    }
}