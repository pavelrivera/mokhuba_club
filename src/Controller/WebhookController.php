<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Entity\Subscription;
use App\Entity\WebhookEvent;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Event;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class WebhookController extends AbstractController
{
    private $entityManager;
    private $stripeService;
    private $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        StripeService $stripeService,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->stripeService = $stripeService;
        $this->logger = $logger;
    }

    /**
     * @Route("/webhook/stripe", name="webhook_stripe", methods={"POST"})
     */
    public function handleStripeWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');
        
        // USAR $_ENV DIRECTAMENTE - NO INYECTAR EN CONSTRUCTOR
        $endpoint_secret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '';

        try {
            // Verificar la firma del webhook
            $event = Webhook::constructEvent($payload, $sigHeader, $endpoint_secret);
            
            // Guardar el evento
            $this->saveWebhookEvent($event);
            
            // Procesar según el tipo de evento
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentSucceeded($event);
                    break;
                    
                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailed($event);
                    break;
                    
                case 'invoice.payment_succeeded':
                    $this->handleInvoicePaymentSucceeded($event);
                    break;
                    
                case 'customer.subscription.created':
                case 'customer.subscription.updated':
                    $this->handleSubscriptionUpdate($event);
                    break;
                    
                case 'customer.subscription.deleted':
                    $this->handleSubscriptionCancellation($event);
                    break;
                    
                default:
                    $this->logger->info('Webhook no manejado: ' . $event->type);
            }

            return new JsonResponse(['status' => 'success']);

        } catch (SignatureVerificationException $e) {
            $this->logger->error('Webhook signature verification failed: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Invalid signature'], 400);
            
        } catch (\Exception $e) {
            $this->logger->error('Webhook processing failed: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Processing failed'], 500);
        }
    }

    private function saveWebhookEvent(Event $event): void
    {
        // Verificar si ya procesamos este evento
        $existingEvent = $this->entityManager->getRepository(WebhookEvent::class)
            ->findOneBy(['stripeEventId' => $event->id]);

        if ($existingEvent) {
            $this->logger->info('Evento ya procesado: ' . $event->id);
            return;
        }

        $webhookEvent = new WebhookEvent();
        $webhookEvent->setStripeEventId($event->id);
        $webhookEvent->setEventType($event->type);
        $webhookEvent->setEventData(json_encode($event->data));
        $webhookEvent->setProcessedAt(new \DateTime());

        $this->entityManager->persist($webhookEvent);
        $this->entityManager->flush();
    }

    private function handlePaymentSucceeded(Event $event): void
    {
        $paymentIntent = $event->data->object;
        
        $payment = $this->entityManager->getRepository(Payment::class)
            ->findOneBy(['stripePaymentIntentId' => $paymentIntent->id]);

        if ($payment) {
            $payment->setStatus('completed');
            $payment->setPaidAt(new \DateTime());
            
            // Si tiene suscripción asociada, activarla
            if ($payment->getSubscription()) {
                $subscription = $payment->getSubscription();
                $subscription->setStatus('active');
                $subscription->setStartDate(new \DateTime());
                
                // Calcular fecha de vencimiento según el plan
                $endDate = new \DateTime();
                $endDate->modify('+1 month'); // Por defecto mensual
                $subscription->setEndDate($endDate);
            }

            $this->entityManager->flush();
            $this->logger->info('Pago completado: ' . $paymentIntent->id);
        }
    }

    private function handlePaymentFailed(Event $event): void
    {
        $paymentIntent = $event->data->object;
        
        $payment = $this->entityManager->getRepository(Payment::class)
            ->findOneBy(['stripePaymentIntentId' => $paymentIntent->id]);

        if ($payment) {
            $payment->setStatus('failed');
            
            // Si tiene suscripción, marcarla como fallida
            if ($payment->getSubscription()) {
                $payment->getSubscription()->setStatus('payment_failed');
            }

            $this->entityManager->flush();
            $this->logger->info('Pago fallido: ' . $paymentIntent->id);
        }
    }

    private function handleInvoicePaymentSucceeded(Event $event): void
    {
        $invoice = $event->data->object;
        
        // Si es una suscripción recurrente
        if ($invoice->subscription) {
            $subscription = $this->entityManager->getRepository(Subscription::class)
                ->findOneBy(['stripeSubscriptionId' => $invoice->subscription]);

            if ($subscription) {
                $subscription->setStatus('active');
                
                // Extender la fecha de vencimiento
                $endDate = new \DateTime();
                $endDate->modify('+1 month');
                $subscription->setEndDate($endDate);
                
                $this->entityManager->flush();
                $this->logger->info('Renovación de suscripción: ' . $invoice->subscription);
            }
        }
    }

    private function handleSubscriptionUpdate(Event $event): void
    {
        $stripeSubscription = $event->data->object;
        
        $subscription = $this->entityManager->getRepository(Subscription::class)
            ->findOneBy(['stripeSubscriptionId' => $stripeSubscription->id]);

        if ($subscription) {
            $subscription->setStatus($stripeSubscription->status);
            
            if ($stripeSubscription->current_period_end) {
                $endDate = new \DateTime();
                $endDate->setTimestamp($stripeSubscription->current_period_end);
                $subscription->setEndDate($endDate);
            }

            $this->entityManager->flush();
            $this->logger->info('Suscripción actualizada: ' . $stripeSubscription->id);
        }
    }

    private function handleSubscriptionCancellation(Event $event): void
    {
        $stripeSubscription = $event->data->object;
        
        $subscription = $this->entityManager->getRepository(Subscription::class)
            ->findOneBy(['stripeSubscriptionId' => $stripeSubscription->id]);

        if ($subscription) {
            $subscription->setStatus('cancelled');
            $subscription->setCancelledAt(new \DateTime());

            $this->entityManager->flush();
            $this->logger->info('Suscripción cancelada: ' . $stripeSubscription->id);
        }
    }
}