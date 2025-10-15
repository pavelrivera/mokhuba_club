<?php
namespace App\Repository;

use App\Entity\WebhookEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WebhookEvent>
 * @method WebhookEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method WebhookEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method WebhookEvent[]    findAll()
 * @method WebhookEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WebhookEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WebhookEvent::class);
    }

    /**
     * Busca un evento por su ID de Stripe
     */
    public function findByStripeEventId(string $stripeEventId): ?WebhookEvent
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.stripeEventId = :stripe_event_id')
            ->setParameter('stripe_event_id', $stripeEventId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Busca eventos por tipo
     */
    public function findByEventType(string $eventType): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.eventType = :event_type')
            ->setParameter('event_type', $eventType)
            ->orderBy('w.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca eventos no procesados
     */
    public function findUnprocessedEvents(): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.processed = :processed')
            ->setParameter('processed', false)
            ->orderBy('w.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca eventos con errores de procesamiento
     */
    public function findFailedEvents(): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.processed = :processed')
            ->andWhere('w.processingError IS NOT NULL')
            ->setParameter('processed', false)
            ->orderBy('w.processingAttempts', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca eventos que necesitan reintento
     */
    public function findEventsNeedingRetry(int $maxAttempts = 3): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.processed = :processed')
            ->andWhere('w.processingAttempts < :max_attempts')
            ->andWhere('w.processingError IS NOT NULL')
            ->setParameter('processed', false)
            ->setParameter('max_attempts', $maxAttempts)
            ->orderBy('w.lastProcessingAttempt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca eventos por object_id (para tracking de objetos específicos)
     */
    public function findByObjectId(string $objectId): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.objectId = :object_id')
            ->setParameter('object_id', $objectId)
            ->orderBy('w.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca eventos en modo live vs test
     */
    public function findByLivemode(bool $livemode): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.livemode = :livemode')
            ->setParameter('livemode', $livemode)
            ->orderBy('w.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca eventos por versión de API
     */
    public function findByApiVersion(string $apiVersion): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.apiVersion = :api_version')
            ->setParameter('api_version', $apiVersion)
            ->orderBy('w.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca eventos en un rango de fechas
     */
    public function findByDateRange(\DateTime $from, \DateTime $to): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.createdAt BETWEEN :from AND :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('w.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtiene estadísticas de procesamiento de webhooks
     */
    public function getProcessingStats(): array
    {
        return $this->createQueryBuilder('w')
            ->select('
                COUNT(w.id) as total_events,
                COUNT(CASE WHEN w.processed = :processed THEN 1 END) as processed_events,
                COUNT(CASE WHEN w.processed = :not_processed THEN 1 END) as pending_events,
                COUNT(CASE WHEN w.processingError IS NOT NULL THEN 1 END) as failed_events,
                AVG(w.processingAttempts) as avg_attempts
            ')
            ->setParameter('processed', true)
            ->setParameter('not_processed', false)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Obtiene estadísticas por tipo de evento
     */
    public function getEventTypeStats(): array
    {
        return $this->createQueryBuilder('w')
            ->select('
                w.eventType as event_type,
                COUNT(w.id) as event_count,
                COUNT(CASE WHEN w.processed = :processed THEN 1 END) as processed_count,
                COUNT(CASE WHEN w.processingError IS NOT NULL THEN 1 END) as failed_count
            ')
            ->setParameter('processed', true)
            ->groupBy('w.eventType')
            ->orderBy('event_count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca eventos duplicados por stripe_event_id
     */
    public function findDuplicateEvents(): array
    {
        return $this->createQueryBuilder('w')
            ->select('w.stripeEventId, COUNT(w.id) as duplicate_count')
            ->groupBy('w.stripeEventId')
            ->having('COUNT(w.id) > 1')
            ->orderBy('duplicate_count', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca eventos antiguos para limpieza (older than X days)
     */
    public function findOldEvents(int $daysOld = 30): array
    {
        $cutoffDate = new \DateTime("-{$daysOld} days");
        
        return $this->createQueryBuilder('w')
            ->andWhere('w.createdAt < :cutoff_date')
            ->andWhere('w.processed = :processed')
            ->setParameter('cutoff_date', $cutoffDate)
            ->setParameter('processed', true)
            ->orderBy('w.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Cuenta eventos por estado
     */
    public function countByProcessingStatus(): array
    {
        $processed = $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->andWhere('w.processed = :processed')
            ->setParameter('processed', true)
            ->getQuery()
            ->getSingleScalarResult();

        $pending = $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->andWhere('w.processed = :processed')
            ->andWhere('w.processingError IS NULL')
            ->setParameter('processed', false)
            ->getQuery()
            ->getSingleScalarResult();

        $failed = $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->andWhere('w.processed = :processed')
            ->andWhere('w.processingError IS NOT NULL')
            ->setParameter('processed', false)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'processed' => (int)$processed,
            'pending' => (int)$pending,
            'failed' => (int)$failed,
            'total' => (int)($processed + $pending + $failed)
        ];
    }

    /**
     * Busca eventos relacionados con suscripciones
     */
    public function findSubscriptionEvents(): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.eventType LIKE :subscription_pattern')
            ->setParameter('subscription_pattern', 'customer.subscription.%')
            ->orderBy('w.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca eventos relacionados con pagos
     */
    public function findPaymentEvents(): array
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.eventType IN (:payment_events)')
            ->setParameter('payment_events', [
                'payment_intent.succeeded',
                'payment_intent.payment_failed',
                'payment_intent.requires_action',
                'payment_intent.processing',
                'invoice.payment_succeeded',
                'invoice.payment_failed'
            ])
            ->orderBy('w.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca los últimos N eventos
     */
    public function findRecentEvents(int $limit = 50): array
    {
        return $this->createQueryBuilder('w')
            ->orderBy('w.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Verifica si un evento ya existe (para evitar duplicados)
     */
    public function eventExists(string $stripeEventId): bool
    {
        return $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->andWhere('w.stripeEventId = :stripe_event_id')
            ->setParameter('stripe_event_id', $stripeEventId)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    /**
     * Persiste un evento webhook
     */
    public function save(WebhookEvent $webhookEvent, bool $flush = false): void
    {
        $this->getEntityManager()->persist($webhookEvent);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Elimina un evento webhook
     */
    public function remove(WebhookEvent $webhookEvent, bool $flush = false): void
    {
        $this->getEntityManager()->remove($webhookEvent);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Elimina eventos antiguos en lote (para limpieza automática)
     */
    public function removeOldEvents(int $daysOld = 90, int $batchSize = 100): int
    {
        $cutoffDate = new \DateTime("-{$daysOld} days");
        
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->delete(WebhookEvent::class, 'w')
           ->where('w.createdAt < :cutoff_date')
           ->andWhere('w.processed = :processed')
           ->setParameter('cutoff_date', $cutoffDate)
           ->setParameter('processed', true)
           ->setMaxResults($batchSize);

        return $qb->getQuery()->execute();
    }
}