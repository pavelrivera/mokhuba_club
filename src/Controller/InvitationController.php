<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email as MailEmail;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class InvitationController extends AbstractController
{
    private Connection $db;
    private MailerInterface $mailer;
    private ValidatorInterface $validator;
    private UrlGeneratorInterface $urls;
    private Security $security;
    private UserPasswordHasherInterface $passwordHasher;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private string $from;

    public function __construct(
        Connection $db,
        MailerInterface $mailer,
        ValidatorInterface $validator,
        UrlGeneratorInterface $urls,
        Security $security,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        string $from = null
    ) {
        $this->db = $db;
        $this->mailer = $mailer;
        $this->validator = $validator;
        $this->urls = $urls;
        $this->security = $security;
        $this->passwordHasher = $passwordHasher;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->from = $from ?: 'no-reply@mokhubaclub.com';
    }

    /**
     * @Route("/admin/invitations/send", name="invitation_send", methods={"POST"})
     */
    public function send(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return $this->json([
                'success' => false,
                'message' => 'Datos inválidos',
            ], 400);
        }

        $email = $data['email'] ?? '';
        $name = $data['name'] ?? '';
        $phone = $data['phone'] ?? null;
        $message = $data['message'] ?? null;

        // Validar email
        $violations = $this->validator->validate($email, [
            new Assert\NotBlank(['message' => 'El email es obligatorio.']),
            new Assert\Email(['message' => 'Email no válido.']),
            new Assert\Length(['max' => 180, 'maxMessage' => 'Email demasiado largo.']),
        ]);

        if (count($violations) > 0) {
            return $this->json([
                'success' => false,
                'message' => $violations[0]->getMessage(),
            ], 422);
        }

        // Validar nombre
        if (empty($name)) {
            return $this->json([
                'success' => false,
                'message' => 'El nombre es obligatorio.',
            ], 422);
        }

        // Comprobar si ya existe un usuario con ese email
        $exists = (int) $this->db->fetchOne(
            'SELECT COUNT(1) FROM users WHERE email = :email',
            ['email' => $email]
        );
        
        if ($exists > 0) {
            return $this->json([
                'success' => false,
                'message' => 'Este correo ya pertenece a un usuario registrado.',
            ], 409);
        }

        // Buscar invitación previa
        $row = $this->db->fetchAssociative(
            'SELECT id, token, status, expires_at
             FROM invitations
             WHERE email = :email
             ORDER BY id DESC
             LIMIT 1',
            ['email' => $email]
        );

        $now = new \DateTimeImmutable();
        $expires = $now->modify('+72 hours');

        if ($row && $row['status'] === 'pending' && (empty($row['expires_at']) || new \DateTimeImmutable($row['expires_at']) > $now)) {
            // Reutilizar token existente
            $token = (string) $row['token'];
        } else {
            // Crear nueva invitación
            $token = bin2hex(random_bytes(32));
            $invitedBy = null;
            $user = $this->security->getUser();
            
            if ($user && method_exists($user, 'getId')) {
                $invitedBy = $user->getId();
            }

            $this->db->insert('invitations', [
                'email' => $email,
                'token' => $token,
                'status' => 'pending',
                'created_at' => $now->format('Y-m-d H:i:s'),
                'expires_at' => $expires->format('Y-m-d H:i:s'),
                'accepted_at' => null,
                'invited_by_user_id' => $invitedBy,
                'invitee_name' => $name,
                'invitee_phone' => $phone,
                'message' => $message,
            ]);
        }

        // Generar URL de aceptación
        $acceptUrl = $this->urls->generate(
            'invitation_accept',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // Obtener nombre del remitente
        $senderName = 'Mokhuba Club';
        $currentUser = $this->security->getUser();
        if ($currentUser && method_exists($currentUser, 'getFullName')) {
            $senderName = $currentUser->getFullName();
        }

        // Enviar email
        try {
            $emailMessage = (new MailEmail())
                ->from($this->from)
                ->to($email)
                ->subject('✨ Tu invitación a Mokhuba Club')
                ->html($this->renderView('emails/invitation.html.twig', [
                    'acceptUrl' => $acceptUrl,
                    'expires_at' => $expires,
                    'invitee_name' => $name,
                    'sender_name' => $senderName,
                    'message' => $message,
                ]));

            $this->mailer->send($emailMessage);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Error al enviar el email: ' . $e->getMessage(),
            ], 500);
        }

        return $this->json([
            'success' => true,
            'message' => 'Invitación enviada exitosamente a ' . $email,
            'token' => $token,
            'acceptUrl' => $acceptUrl,
        ]);
    }

    /**
     * @Route("/invitations/accept/{token}", name="invitation_accept", methods={"GET"})
     */
    public function accept(string $token): Response
    {
        if (empty($token)) {
            throw $this->createNotFoundException('Token inválido');
        }

        // Buscar invitación
        $row = $this->db->fetchAssociative(
            'SELECT id, email, status, expires_at, invitee_name, invitee_phone, message, invited_by_user_id 
             FROM invitations 
             WHERE token = :token',
            ['token' => $token]
        );

        if (!$row) {
            return $this->render('invitation/invalid.html.twig', [
                'reason' => 'not_found',
                'message' => 'Invitación no encontrada',
            ]);
        }

        $now = new \DateTimeImmutable();

        // Verificar estado
        if ($row['status'] !== 'pending') {
            return $this->render('invitation/invalid.html.twig', [
                'reason' => 'used',
                'message' => 'Esta invitación ya fue utilizada',
            ]);
        }

        // Verificar expiración
        if (!empty($row['expires_at']) && $now > new \DateTimeImmutable($row['expires_at'])) {
            return $this->render('invitation/invalid.html.twig', [
                'reason' => 'expired',
                'message' => 'Esta invitación ha expirado',
            ]);
        }

        // Verificar si el email ya tiene usuario
        $existingUser = $this->userRepository->findOneBy(['email' => $row['email']]);
        if ($existingUser) {
            return $this->render('invitation/invalid.html.twig', [
                'reason' => 'already_registered',
                'message' => 'Este correo ya está registrado. Por favor inicia sesión.',
                'login_url' => $this->generateUrl('auth_login'),
            ]);
        }

        // Obtener información del invitador
        $senderName = 'Mokhuba Club';
        if ($row['invited_by_user_id']) {
            $sender = $this->userRepository->find($row['invited_by_user_id']);
            if ($sender) {
                $senderName = $sender->getFullName();
            }
        }

        // Mostrar formulario de registro
        return $this->render('invitation/register.html.twig', [
            'token' => $token,
            'email' => $row['email'],
            'name' => $row['invitee_name'],
            'phone' => $row['invitee_phone'],
            'message' => $row['message'],
            'sender_name' => $senderName,
        ]);
    }

    /**
     * @Route("/invitations/process/{token}", name="invitation_process", methods={"POST"})
     */
    public function process(Request $request, string $token): Response
    {
        $this->logger->info('🔵 Iniciando proceso de registro con token', ['token' => $token]);

        if (empty($token)) {
            throw $this->createNotFoundException('Token inválido');
        }

        // Buscar invitación
        $row = $this->db->fetchAssociative(
            'SELECT id, email, status, expires_at 
             FROM invitations 
             WHERE token = :token',
            ['token' => $token]
        );

        if (!$row) {
            $this->logger->error('❌ Invitación no encontrada', ['token' => $token]);
            $this->addFlash('error', 'Invitación no encontrada');
            return $this->redirectToRoute('auth_register');
        }

        $this->logger->info('✅ Invitación encontrada', ['email' => $row['email'], 'status' => $row['status']]);

        $now = new \DateTimeImmutable();

        // Validar estado y expiración
        if ($row['status'] !== 'pending') {
            $this->logger->warning('⚠️ Invitación ya utilizada', ['status' => $row['status']]);
            $this->addFlash('error', 'Esta invitación ya fue utilizada');
            return $this->redirectToRoute('auth_login');
        }

        if (!empty($row['expires_at']) && $now > new \DateTimeImmutable($row['expires_at'])) {
            $this->logger->warning('⚠️ Invitación expirada', ['expires_at' => $row['expires_at']]);
            $this->addFlash('error', 'Esta invitación ha expirado');
            return $this->redirectToRoute('auth_register');
        }

        // Verificar si el email ya tiene usuario
        $existingUser = $this->userRepository->findOneBy(['email' => $row['email']]);
        if ($existingUser) {
            $this->logger->warning('⚠️ Email ya registrado', ['email' => $row['email']]);
            $this->addFlash('error', 'Este correo ya está registrado. Por favor inicia sesión.');
            return $this->redirectToRoute('auth_login');
        }

        // Obtener datos del formulario
        $firstName = trim($request->request->get('firstName', ''));
        $lastName = trim($request->request->get('lastName', ''));
        $phone = trim($request->request->get('phone', ''));
        $password = $request->request->get('password', '');
        $confirmPassword = $request->request->get('confirmPassword', '');

        $this->logger->info('📝 Datos recibidos del formulario', [
            'firstName' => $firstName,
            'lastName' => $lastName,
            'phone' => $phone,
            'hasPassword' => !empty($password)
        ]);

        // Validaciones
        $errors = [];

        if (empty($firstName)) {
            $errors[] = 'El nombre es obligatorio';
        }

        if (empty($lastName)) {
            $errors[] = 'El apellido es obligatorio';
        }

        if (empty($password)) {
            $errors[] = 'La contraseña es obligatoria';
        } elseif (strlen($password) < 6) {
            $errors[] = 'La contraseña debe tener al menos 6 caracteres';
        } elseif ($password !== $confirmPassword) {
            $errors[] = 'Las contraseñas no coinciden';
        }

        if (!empty($errors)) {
            $this->logger->error('❌ Errores de validación', ['errors' => $errors]);
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
            return $this->redirectToRoute('invitation_accept', ['token' => $token]);
        }

        $this->logger->info('✅ Validaciones pasadas, creando usuario...');

        try {
            // Crear usuario
            $user = new User();
            $user->setEmail($row['email']);
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            
            if (!empty($phone)) {
                $user->setPhone($phone);
            }

            // Hashear contraseña
            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            // Generar código único
            $uniqueCode = $this->userRepository->generateUniqueCode();
            $user->setUniqueCode($uniqueCode);
            $this->logger->info('🔑 Código único generado', ['uniqueCode' => $uniqueCode]);

            // Establecer rol por defecto
            $user->setRoles(['ROLE_USER']);

            // Marcar email como verificado
            $user->setEmailVerifiedAt(new \DateTime());

            // Establecer estado activo
            $user->setIsActive(true);

            // Establecer fechas
            $user->setCreatedAt(new \DateTime());
            $user->setUpdatedAt(new \DateTime());

            $this->logger->info('💾 Guardando usuario en la base de datos...');

            // Guardar usuario
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->logger->info('✅ Usuario creado exitosamente', ['userId' => $user->getId()]);

            // Actualizar invitación
            $this->db->update('invitations', [
                'status' => 'accepted',
                'accepted_at' => $now->format('Y-m-d H:i:s'),
            ], ['id' => $row['id']]);

            $this->logger->info('✅ Invitación actualizada a "accepted"');

            // Mensaje de éxito
            $this->addFlash('success', '¡Registro completado! Por favor inicia sesión con tus credenciales.');

            // Redirigir al login
            return $this->redirectToRoute('auth_login');

        } catch (\Exception $e) {
            $this->logger->error('💥 Error al crear la cuenta', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->addFlash('error', 'Error al crear la cuenta: ' . $e->getMessage());
            return $this->redirectToRoute('invitation_accept', ['token' => $token]);
        }
    }
}