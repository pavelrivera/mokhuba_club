<?php

namespace App\Service;

use App\Entity\Invitation;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailService
{
    private $mailer;
    private $logger;
    private $urlGenerator;
    private $senderEmail;
    private $senderName;
    private $isMockMode;

    public function __construct(
        MailerInterface $mailer,
        LoggerInterface $logger,
        UrlGeneratorInterface $urlGenerator,
        string $mailerDsn = 'null://null'
    ) {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->urlGenerator = $urlGenerator;
        
        // CONFIGURACIÓN DEL REMITENTE
        $this->senderEmail = 'contfin@soportecnico.org';
        $this->senderName = 'Mokhuba Club';
        
        // DETECCIÓN DE MODO MOCK
        $this->isMockMode = $mailerDsn === 'null://null' || empty($mailerDsn);
        
        if ($this->isMockMode) {
            $this->logger->info('📧 EmailService iniciado en MODO MOCK - Los emails se loguearán pero NO se enviarán');
        } else {
            $this->logger->info('📧 EmailService iniciado en MODO REAL - Los emails se enviarán via SMTP', [
                'sender' => $this->senderEmail
            ]);
        }
    }

    /**
     * Envía un email de invitación
     */
    public function sendInvitationEmail(Invitation $invitation): bool
    {
        try {
            // Generar URL de invitación
            $invitationUrl = $this->urlGenerator->generate(
                'invitation_accept',
                ['token' => $invitation->getToken()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $senderName = $invitation->getSender() 
                ? $invitation->getSender()->getFullName() 
                : 'Mokhuba Club';

            // Si estamos en modo mock, solo loguear
            if ($this->isMockMode) {
                $this->logger->info('📧 [MOCK] Email de invitación NO ENVIADO (modo desarrollo)', [
                    'to' => $invitation->getInviteeEmail(),
                    'from' => $senderName,
                    'token' => $invitation->getToken(),
                    'url' => $invitationUrl,
                    'expires_at' => $invitation->getExpiresAt()->format('Y-m-d H:i:s'),
                    'valid_days' => '3 días'
                ]);

                $this->logger->info('🔗 [MOCK] Link de invitación (copiar y usar en navegador): ' . $invitationUrl);
                
                return true;
            }

            // MODO REAL: Crear y enviar email con template
            $email = (new TemplatedEmail())
                ->from(new Address($this->senderEmail, $this->senderName))
                ->to(new Address($invitation->getInviteeEmail(), $invitation->getInviteeName()))
                ->subject('🚬 Te han invitado a unirte a Mokhuba Club - Club Premium de Tabaco')
                ->htmlTemplate('emails/invitation_email.html.twig')
                ->context([
                    'invitation' => $invitation,
                    'sender_name' => $senderName,
                    'invitation_url' => $invitationUrl,
                    'invitee_name' => $invitation->getInviteeName(),
                    'expires_at' => $invitation->getExpiresAt(),
                    'valid_days' => 3
                ]);

            // Enviar email
            $this->mailer->send($email);

            $this->logger->info('✅ Email de invitación ENVIADO correctamente', [
                'to' => $invitation->getInviteeEmail(),
                'token' => $invitation->getToken(),
                'from' => $this->senderEmail,
                'expires_in' => '3 días'
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('❌ Error enviando email de invitación', [
                'error' => $e->getMessage(),
                'to' => $invitation->getInviteeEmail(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Envía un email de bienvenida al nuevo usuario
     */
    public function sendWelcomeEmail($user): bool
    {
        try {
            if ($this->isMockMode) {
                $this->logger->info('📧 [MOCK] Email de bienvenida NO ENVIADO (modo desarrollo)', [
                    'to' => $user->getEmail(),
                    'name' => $user->getFullName()
                ]);
                return true;
            }

            $email = (new TemplatedEmail())
                ->from(new Address($this->senderEmail, $this->senderName))
                ->to(new Address($user->getEmail(), $user->getFullName()))
                ->subject('¡Bienvenido a Mokhuba Club! 🚬 Tu cuenta está lista')
                ->htmlTemplate('emails/welcome_email.html.twig')
                ->context([
                    'user' => $user
                ]);

            $this->mailer->send($email);

            $this->logger->info('✅ Email de bienvenida ENVIADO correctamente', [
                'to' => $user->getEmail()
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('❌ Error enviando email de bienvenida', [
                'error' => $e->getMessage(),
                'to' => $user->getEmail()
            ]);

            return false;
        }
    }

    /**
     * Verifica si estamos en modo mock
     */
    public function isMockMode(): bool
    {
        return $this->isMockMode;
    }

    /**
     * Envía un email de notificación al remitente cuando se acepta una invitación
     */
    public function sendInvitationAcceptedEmail(Invitation $invitation): bool
    {
        try {
            if (!$invitation->getSender()) {
                return false;
            }

            if ($this->isMockMode) {
                $this->logger->info('📧 [MOCK] Email de invitación aceptada NO ENVIADO (modo desarrollo)', [
                    'to' => $invitation->getSender()->getEmail(),
                    'invitee' => $invitation->getInviteeName()
                ]);
                return true;
            }

            $email = (new TemplatedEmail())
                ->from(new Address($this->senderEmail, $this->senderName))
                ->to(new Address(
                    $invitation->getSender()->getEmail(), 
                    $invitation->getSender()->getFullName()
                ))
                ->subject('¡Tu invitación ha sido aceptada! 🎉 - Mokhuba Club')
                ->htmlTemplate('emails/invitation_accepted_email.html.twig')
                ->context([
                    'invitation' => $invitation,
                    'sender' => $invitation->getSender(),
                    'invitee_name' => $invitation->getInviteeName()
                ]);

            $this->mailer->send($email);

            $this->logger->info('✅ Email de invitación aceptada ENVIADO correctamente', [
                'to' => $invitation->getSender()->getEmail()
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('❌ Error enviando email de invitación aceptada', [
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
    
    /**
     * Obtiene el email del remitente configurado
     */
    public function getSenderEmail(): string
    {
        return $this->senderEmail;
    }
    
    /**
     * Obtiene el nombre del remitente configurado
     */
    public function getSenderName(): string
    {
        return $this->senderName;
    }
}