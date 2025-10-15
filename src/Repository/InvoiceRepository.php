<?php
namespace App\Repository;

use App\Entity\Invoice;
use App\Entity\User;
use App\Entity\Subscription;
use App\Entity\Payment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Invoice>
 * @method Invoice|null find($id, $lockMode = null, $lockVersion = null)
 * @method Invoice|null findOneBy(array $criteria, array $orderBy = null)
 * @method Invoice[]    findAll()
 * @method Invoice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    /**
     * Busca una factura por su ID de Stripe
     */
    public function findByStripeInvoiceId(string $stripeInvoiceId): ?Invoice
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.stripeInvoiceId = :stripe_invoice_id')
            ->setParameter('stripe_invoice_id', $stripeInvoiceId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Busca facturas por número de factura
     */
    public function findByInvoiceNumber(string $invoiceNumber): ?Invoice
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.invoiceNumber = :invoice_number')
            ->setParameter('invoice_number', $invoiceNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Obtiene todas las facturas de un usuario
     */
    public function findByUserOrderedByDate(User $user): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.user = :user')
            ->setParameter('user', $user)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtiene facturas de una suscripción específica
     */
    public function findBySubscription(Subscription $subscription): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.subscription = :subscription')
            ->setParameter('subscription', $subscription)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtiene facturas por estado
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.status = :status')
            ->setParameter('status', $status)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca facturas pendientes de pago
     */
    public function findPendingInvoices(): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.status IN (:pending_statuses)')
            ->setParameter('pending_statuses', ['draft', 'open'])
            ->orderBy('i.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca facturas vencidas
     */
    public function findOverdueInvoices(): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.status = :status')
            ->andWhere('i.dueDate < :now')
            ->setParameter('status', 'open')
            ->setParameter('now', new \DateTime())
            ->orderBy('i.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca facturas que vencen pronto
     */
    public function findInvoicesDueSoon(int $days = 3): array
    {
        $futureDate = new \DateTime("+{$days} days");
        
        return $this->createQueryBuilder('i')
            ->andWhere('i.status = :status')
            ->andWhere('i.dueDate BETWEEN :now AND :future_date')
            ->setParameter('status', 'open')
            ->setParameter('now', new \DateTime())
            ->setParameter('future_date', $futureDate)
            ->orderBy('i.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca facturas pagadas en un período
     */
    public function findPaidInPeriod(\DateTime $from, \DateTime $to): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.status = :status')
            ->andWhere('i.paidAt BETWEEN :from AND :to')
            ->setParameter('status', 'paid')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('i.paidAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtiene estadísticas de facturación
     */
    public function getInvoiceStats(): array
    {
        return $this->createQueryBuilder('i')
            ->select('
                COUNT(i.id) as total_invoices,
                COUNT(CASE WHEN i.status = :paid THEN 1 END) as paid_invoices,
                COUNT(CASE WHEN i.status = :open THEN 1 END) as open_invoices,
                COUNT(CASE WHEN i.status = :void THEN 1 END) as void_invoices,
                SUM(CASE WHEN i.status = :paid THEN i.totalAmount ELSE 0 END) as total_revenue,
                SUM(CASE WHEN i.status = :open THEN i.amountDue ELSE 0 END) as outstanding_amount,
                AVG(CASE WHEN i.status = :paid THEN i.totalAmount ELSE NULL END) as avg_invoice_amount
            ')
            ->setParameter('paid', 'paid')
            ->setParameter('open', 'open')
            ->setParameter('void', 'void')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Obtiene ingresos mensuales
     */
    public function getMonthlyRevenue(\DateTime $from, \DateTime $to): array
    {
        return $this->createQueryBuilder('i')
            ->select("
                DATE_FORMAT(i.paidAt, '%Y-%m') as month,
                SUM(i.totalAmount) as revenue,
                COUNT(i.id) as invoice_count
            ")
            ->andWhere('i.status = :status')
            ->andWhere('i.paidAt BETWEEN :from AND :to')
            ->setParameter('status', 'paid')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca facturas por payment_id
     */
    public function findByPayment(Payment $payment): ?Invoice
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.payment = :payment')
            ->setParameter('payment', $payment)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Obtiene el total facturado por usuario
     */
    public function getTotalInvoicedByUser(User $user): int
    {
        $result = $this->createQueryBuilder('i')
            ->select('SUM(i.totalAmount) as total')
            ->andWhere('i.user = :user')
            ->andWhere('i.status IN (:valid_statuses)')
            ->setParameter('user', $user)
            ->setParameter('valid_statuses', ['paid', 'open'])
            ->getQuery()
            ->getOneOrNullResult();

        return (int)($result['total'] ?? 0);
    }

    /**
     * Obtiene el total pendiente por usuario
     */
    public function getOutstandingByUser(User $user): int
    {
        $result = $this->createQueryBuilder('i')
            ->select('SUM(i.amountDue) as total')
            ->andWhere('i.user = :user')
            ->andWhere('i.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'open')
            ->getQuery()
            ->getOneOrNullResult();

        return (int)($result['total'] ?? 0);
    }

    /**
     * Busca facturas en un rango de fechas
     */
    public function findByDateRange(\DateTime $from, \DateTime $to): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.createdAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca facturas con impuestos aplicados
     */
    public function findWithTax(): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.taxAmount > 0')
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca facturas con descuentos aplicados
     */
    public function findWithDiscount(): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.discountAmount > 0')
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtiene la siguiente numeración de factura
     */
    public function getNextInvoiceNumber(): string
    {
        $lastInvoice = $this->createQueryBuilder('i')
            ->orderBy('i.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$lastInvoice) {
            return 'MKB-' . date('Y') . '-0001';
        }

        // Extraer el número de la última factura
        $lastNumber = $lastInvoice->getInvoiceNumber();
        $parts = explode('-', $lastNumber);
        
        if (count($parts) === 3) {
            $year = $parts[1];
            $sequence = (int)$parts[2];
            
            // Si es el mismo año, incrementar secuencia
            if ($year === date('Y')) {
                $sequence++;
                return 'MKB-' . $year . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
            }
        }
        
        // Si es nuevo año o formato diferente, empezar desde 1
        return 'MKB-' . date('Y') . '-0001';
    }

    /**
     * Busca facturas por customer email
     */
    public function findByCustomerEmail(string $email): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.customerEmail = :email')
            ->setParameter('email', $email)
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca facturas anuladas en un período
     */
    public function findVoidedInPeriod(\DateTime $from, \DateTime $to): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.status = :status')
            ->andWhere('i.updatedAt BETWEEN :from AND :to')
            ->setParameter('status', 'void')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('i.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtiene facturas recientes (últimas N)
     */
    public function findRecent(int $limit = 20): array
    {
        return $this->createQueryBuilder('i')
            ->orderBy('i.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Persiste una factura
     */
    public function save(Invoice $invoice, bool $flush = false): void
    {
        $this->getEntityManager()->persist($invoice);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Elimina una factura
     */
    public function remove(Invoice $invoice, bool $flush = false): void
    {
        $this->getEntityManager()->remove($invoice);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}