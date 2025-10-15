<?php
namespace App\Repository;

use App\Entity\PaymentMethod;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PaymentMethod>
 * @method PaymentMethod|null find($id, $lockMode = null, $lockVersion = null)
 * @method PaymentMethod|null findOneBy(array $criteria, array $orderBy = null)
 * @method PaymentMethod[]    findAll()
 * @method PaymentMethod[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaymentMethodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaymentMethod::class);
    }

    /**
     * Busca un método de pago por su ID de Stripe
     */
    public function findByStripePaymentMethodId(string $stripePaymentMethodId): ?PaymentMethod
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.stripePaymentMethodId = :stripe_id')
            ->setParameter('stripe_id', $stripePaymentMethodId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Obtiene todos los métodos de pago de un usuario
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.user = :user')
            ->setParameter('user', $user)
            ->orderBy('pm.isDefault', 'DESC')
            ->addOrderBy('pm.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtiene el método de pago por defecto de un usuario
     */
    public function findDefaultByUser(User $user): ?PaymentMethod
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.user = :user')
            ->andWhere('pm.isDefault = :is_default')
            ->setParameter('user', $user)
            ->setParameter('is_default', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Busca métodos de pago por tipo
     */
    public function findByType(string $type): array
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.type = :type')
            ->setParameter('type', $type)
            ->orderBy('pm.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca métodos de pago por marca de tarjeta
     */
    public function findByCardBrand(string $cardBrand): array
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.cardBrand = :card_brand')
            ->setParameter('card_brand', $cardBrand)
            ->orderBy('pm.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca tarjetas que expiran pronto
     */
    public function findExpiringCards(int $monthsAhead = 2): array
    {
        $currentDate = new \DateTime();
        $currentYear = (int)$currentDate->format('Y');
        $currentMonth = (int)$currentDate->format('n');
        
        $expiryYear = $currentYear;
        $expiryMonth = $currentMonth + $monthsAhead;
        
        if ($expiryMonth > 12) {
            $expiryYear++;
            $expiryMonth -= 12;
        }

        return $this->createQueryBuilder('pm')
            ->andWhere('pm.type = :type')
            ->andWhere('
                (pm.cardExpYear < :expiry_year) OR 
                (pm.cardExpYear = :expiry_year AND pm.cardExpMonth <= :expiry_month)
            ')
            ->setParameter('type', 'card')
            ->setParameter('expiry_year', $expiryYear)
            ->setParameter('expiry_month', $expiryMonth)
            ->orderBy('pm.cardExpYear', 'ASC')
            ->addOrderBy('pm.cardExpMonth', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtiene estadísticas de métodos de pago
     */
    public function getPaymentMethodStats(): array
    {
        return $this->createQueryBuilder('pm')
            ->select('
                pm.type as method_type,
                COUNT(pm.id) as total_count,
                COUNT(CASE WHEN pm.isDefault = :is_default THEN 1 END) as default_count
            ')
            ->setParameter('is_default', true)
            ->groupBy('pm.type')
            ->orderBy('total_count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtiene estadísticas de marcas de tarjetas
     */
    public function getCardBrandStats(): array
    {
        return $this->createQueryBuilder('pm')
            ->select('
                pm.cardBrand as brand,
                COUNT(pm.id) as card_count
            ')
            ->andWhere('pm.type = :type')
            ->andWhere('pm.cardBrand IS NOT NULL')
            ->setParameter('type', 'card')
            ->groupBy('pm.cardBrand')
            ->orderBy('card_count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca métodos de pago por email de facturación
     */
    public function findByBillingEmail(string $email): array
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.billingEmail = :email')
            ->setParameter('email', $email)
            ->orderBy('pm.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca usuarios que tienen métodos de pago guardados
     */
    public function findUsersWithPaymentMethods(): array
    {
        return $this->createQueryBuilder('pm')
            ->select('DISTINCT IDENTITY(pm.user) as user_id')
            ->getQuery()
            ->getResult();
    }

    /**
     * Cuenta métodos de pago por usuario
     */
    public function countByUser(User $user): int
    {
        return $this->createQueryBuilder('pm')
            ->select('COUNT(pm.id)')
            ->andWhere('pm.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Busca métodos de pago creados en un rango de fechas
     */
    public function findByDateRange(\DateTime $from, \DateTime $to): array
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.createdAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('pm.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca métodos de pago duplicados por usuario
     */
    public function findDuplicatesByUser(User $user): array
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.user = :user')
            ->andWhere('pm.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', 'card')
            ->having('COUNT(pm.cardLast4) > 1')
            ->groupBy('pm.cardLast4, pm.cardBrand')
            ->getQuery()
            ->getResult();
    }

    /**
     * Establece un método de pago como predeterminado y desactiva los demás
     */
    public function setAsDefault(PaymentMethod $paymentMethod): void
    {
        $user = $paymentMethod->getUser();

        // Desactivar todos los métodos por defecto del usuario
        $this->createQueryBuilder('pm')
            ->update()
            ->set('pm.isDefault', ':false')
            ->andWhere('pm.user = :user')
            ->setParameter('false', false)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();

        // Activar el método seleccionado como predeterminado
        $paymentMethod->setIsDefault(true);
        $this->getEntityManager()->flush();
    }

    /**
     * Busca métodos de pago que no son por defecto
     */
    public function findNonDefaultByUser(User $user): array
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.user = :user')
            ->andWhere('pm.isDefault = :is_default')
            ->setParameter('user', $user)
            ->setParameter('is_default', false)
            ->orderBy('pm.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca métodos de pago por últimos 4 dígitos de tarjeta
     */
    public function findByCardLast4(string $last4): array
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.cardLast4 = :last4')
            ->setParameter('last4', $last4)
            ->orderBy('pm.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtiene métodos de pago recientes (últimos N)
     */
    public function findRecent(int $limit = 20): array
    {
        return $this->createQueryBuilder('pm')
            ->orderBy('pm.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca métodos de pago que expiran en un año específico
     */
    public function findExpiringInYear(int $year): array
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.type = :type')
            ->andWhere('pm.cardExpYear = :year')
            ->setParameter('type', 'card')
            ->setParameter('year', $year)
            ->orderBy('pm.cardExpMonth', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca métodos de pago por nombre de facturación
     */
    public function findByBillingName(string $name): array
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.billingName LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->orderBy('pm.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Elimina métodos de pago expirados (para limpieza)
     */
    public function removeExpiredCards(): int
    {
        $currentDate = new \DateTime();
        $currentYear = (int)$currentDate->format('Y');
        $currentMonth = (int)$currentDate->format('n');

        return $this->createQueryBuilder('pm')
            ->delete()
            ->andWhere('pm.type = :type')
            ->andWhere('
                (pm.cardExpYear < :current_year) OR 
                (pm.cardExpYear = :current_year AND pm.cardExpMonth < :current_month)
            ')
            ->setParameter('type', 'card')
            ->setParameter('current_year', $currentYear)
            ->setParameter('current_month', $currentMonth)
            ->getQuery()
            ->execute();
    }

    /**
     * Verifica si un usuario tiene métodos de pago guardados
     */
    public function userHasPaymentMethods(User $user): bool
    {
        return $this->createQueryBuilder('pm')
            ->select('COUNT(pm.id)')
            ->andWhere('pm.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * Persiste un método de pago
     */
    public function save(PaymentMethod $paymentMethod, bool $flush = false): void
    {
        $this->getEntityManager()->persist($paymentMethod);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Elimina un método de pago
     */
    public function remove(PaymentMethod $paymentMethod, bool $flush = false): void
    {
        $this->getEntityManager()->remove($paymentMethod);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}