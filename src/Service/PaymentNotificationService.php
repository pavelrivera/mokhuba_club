<?php
namespace App\Service;

use App\Entity\User;
use App\Entity\Payment;
use App\Entity\Subscription;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;
use Twig\Environment;

/**
 * Servicio para gestionar notificaciones de pagos
 * Compatible con PHP 7.4.33
 */
class PaymentNotificationService
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;
    private Environment $twig;
    private string $fromEmail;
    private array $membershipLevels = [
        'ruby' => 'Rubí',
        'gold' => 'Oro',
        'platinum' => 'Platino'
    ];

    public function __construct(
        MailerInterface $mailer,
        LoggerInterface $logger,
        Environment $twig,
        string $fromEmail = 'no-reply@mokhubaclub.com'
    ) {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->twig = $twig;
        $this->fromEmail = $fromEmail;
    }

    /**
     * Envía notificación de pago exitoso
     */
    public function sendPaymentSuccessNotification(Payment $payment): bool
    {
        try {
            $user = $payment->getUser();
            $subscription = $payment->getSubscription();

            $email = (new Email())
                ->from($this->fromEmail)
                ->to($user->getEmail())
                ->subject('Pago procesado exitosamente - Mokhuba Club')
                ->html($this->renderPaymentSuccessTemplate($user, $payment, $subscription));

            $this->mailer->send($email);

            $this->logger->info('Notificación de pago exitoso enviada', [
                'user_id' => $user->getId(),
                'payment_id' => $payment->getId(),
                'amount' => $payment->getAmount()
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Error enviando notificación de pago exitoso', [
                'user_id' => $payment->getUser()->getId(),
                'payment_id' => $payment->getId(),
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Envía notificación de pago fallido
     */
    public function sendPaymentFailedNotification(Payment $payment): bool
    {
        try {
            $user = $payment->getUser();

            $email = (new Email())
                ->from($this->fromEmail)
                ->to($user->getEmail())
                ->subject('Problema con tu pago - Mokhuba Club')
                ->html($this->renderPaymentFailedTemplate($user, $payment));

            $this->mailer->send($email);

            $this->logger->info('Notificación de pago fallido enviada', [
                'user_id' => $user->getId(),
                'payment_id' => $payment->getId(),
                'failure_reason' => $payment->getFailureReason()
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Error enviando notificación de pago fallido', [
                'user_id' => $payment->getUser()->getId(),
                'payment_id' => $payment->getId(),
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Envía notificación de suscripción activada
     */
    public function sendSubscriptionActivatedNotification(Subscription $subscription): bool
    {
        try {
            $user = $subscription->getUser();
            $membershipName = $this->membershipLevels[$subscription->getMembershipLevel()] ?? $subscription->getMembershipLevel();

            $email = (new Email())
                ->from($this->fromEmail)
                ->to($user->getEmail())
                ->subject("¡Bienvenido al nivel {$membershipName} - Mokhuba Club!")
                ->html($this->renderSubscriptionActivatedTemplate($user, $subscription, $membershipName));

            $this->mailer->send($email);

            $this->logger->info('Notificación de suscripción activada enviada', [
                'user_id' => $user->getId(),
                'subscription_id' => $subscription->getId(),
                'membership_level' => $subscription->getMembershipLevel()
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Error enviando notificación de suscripción activada', [
                'user_id' => $subscription->getUser()->getId(),
                'subscription_id' => $subscription->getId(),
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Envía notificación de suscripción cancelada
     */
    public function sendSubscriptionCancelledNotification(Subscription $subscription): bool
    {
        try {
            $user = $subscription->getUser();
            $membershipName = $this->membershipLevels[$subscription->getMembershipLevel()] ?? $subscription->getMembershipLevel();

            $email = (new Email())
                ->from($this->fromEmail)
                ->to($user->getEmail())
                ->subject('Suscripción cancelada - Mokhuba Club')
                ->html($this->renderSubscriptionCancelledTemplate($user, $subscription, $membershipName));

            $this->mailer->send($email);

            $this->logger->info('Notificación de suscripción cancelada enviada', [
                'user_id' => $user->getId(),
                'subscription_id' => $subscription->getId(),
                'membership_level' => $subscription->getMembershipLevel()
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Error enviando notificación de suscripción cancelada', [
                'user_id' => $subscription->getUser()->getId(),
                'subscription_id' => $subscription->getId(),
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Envía notificación de renovación próxima
     */
    public function sendRenewalReminderNotification(Subscription $subscription): bool
    {
        try {
            $user = $subscription->getUser();
            $membershipName = $this->membershipLevels[$subscription->getMembershipLevel()] ?? $subscription->getMembershipLevel();

            $email = (new Email())
                ->from($this->fromEmail)
                ->to($user->getEmail())
                ->subject("Tu membresía {$membershipName} se renovará pronto - Mokhuba Club")
                ->html($this->renderRenewalReminderTemplate($user, $subscription, $membershipName));

            $this->mailer->send($email);

            $this->logger->info('Notificación de renovación enviada', [
                'user_id' => $user->getId(),
                'subscription_id' => $subscription->getId(),
                'renewal_date' => $subscription->getCurrentPeriodEnd()->format('Y-m-d')
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Error enviando notificación de renovación', [
                'user_id' => $subscription->getUser()->getId(),
                'subscription_id' => $subscription->getId(),
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Renderiza template de pago exitoso
     */
    private function renderPaymentSuccessTemplate(User $user, Payment $payment, ?Subscription $subscription): string
    {
        return $this->twig->render('emails/payment_success.html.twig', [
            'user' => $user,
            'payment' => $payment,
            'subscription' => $subscription,
            'amount_formatted' => '$' . number_format($payment->getAmount() / 100, 2),
            'membership_name' => $subscription ? 
                ($this->membershipLevels[$subscription->getMembershipLevel()] ?? $subscription->getMembershipLevel()) : 
                null
        ]);
    }

    /**
     * Renderiza template de pago fallido
     */
    private function renderPaymentFailedTemplate(User $user, Payment $payment): string
    {
        return $this->twig->render('emails/payment_failed.html.twig', [
            'user' => $user,
            'payment' => $payment,
            'amount_formatted' => '$' . number_format($payment->getAmount() / 100, 2),
            'failure_reason' => $payment->getFailureReason()
        ]);
    }

    /**
     * Renderiza template de suscripción activada
     */
    private function renderSubscriptionActivatedTemplate(User $user, Subscription $subscription, string $membershipName): string
    {
        return $this->twig->render('emails/subscription_activated.html.twig', [
            'user' => $user,
            'subscription' => $subscription,
            'membership_name' => $membershipName,
            'membership_level' => $subscription->getMembershipLevel(),
            'period_end' => $subscription->getCurrentPeriodEnd(),
            'price_formatted' => '$' . number_format($subscription->getPriceAmount() / 100, 2)
        ]);
    }

    /**
     * Renderiza template de suscripción cancelada
     */
    private function renderSubscriptionCancelledTemplate(User $user, Subscription $subscription, string $membershipName): string
    {
        return $this->twig->render('emails/subscription_cancelled.html.twig', [
            'user' => $user,
            'subscription' => $subscription,
            'membership_name' => $membershipName,
            'access_until' => $subscription->getCurrentPeriodEnd()
        ]);
    }

    /**
     * Renderiza template de recordatorio de renovación
     */
    private function renderRenewalReminderTemplate(User $user, Subscription $subscription, string $membershipName): string
    {
        return $this->twig->render('emails/renewal_reminder.html.twig', [
            'user' => $user,
            'subscription' => $subscription,
            'membership_name' => $membershipName,
            'renewal_date' => $subscription->getCurrentPeriodEnd(),
            'price_formatted' => '$' . number_format($subscription->getPriceAmount() / 100, 2)
        ]);
    }

    /**
     * Envía notificación a administradores sobre evento importante
     */
    public function sendAdminNotification(string $subject, string $message, array $data = []): bool
    {
        try {
            // Lista de emails de administradores (configurable)
            $adminEmails = ['admin@mokhubaclub.com']; // Configurar en parameters

            foreach ($adminEmails as $adminEmail) {
                $email = (new Email())
                    ->from($this->fromEmail)
                    ->to($adminEmail)
                    ->subject("[Mokhuba Admin] {$subject}")
                    ->html($this->renderAdminNotificationTemplate($subject, $message, $data));

                $this->mailer->send($email);
            }

            $this->logger->info('Notificación de admin enviada', [
                'subject' => $subject,
                'recipients' => count($adminEmails)
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Error enviando notificación de admin', [
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Renderiza template de notificación para administradores
     */
    private function renderAdminNotificationTemplate(string $subject, string $message, array $data): string
    {
        return $this->twig->render('emails/admin_notification.html.twig', [
            'subject' => $subject,
            'message' => $message,
            'data' => $data,
            'timestamp' => new \DateTime()
        ]);
    }
}