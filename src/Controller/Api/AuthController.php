<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @Route("/api/auth", name="api_auth_")
 */
class AuthController extends BaseApiController
{
    private UserRepository $userRepository;
    private UserPasswordEncoderInterface $passwordEncoder;
    private ValidatorInterface $validator;
    private EntityManagerInterface $entityManager;

    public function __construct(
        UserRepository $userRepository,
        UserPasswordEncoderInterface $passwordEncoder,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager
    ) {
        $this->userRepository = $userRepository;
        $this->passwordEncoder = $passwordEncoder;
        $this->validator = $validator;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/login", name="login", methods={"POST"})
     * 
     * Este endpoint será manejado por el sistema de seguridad de Symfony
     * Ver configuración en config/packages/security.yaml
     */
    public function login(): JsonResponse
    {
        // Este método no se ejecutará en producción
        // El login será manejado por el guard authenticator
        return $this->success([
            'message' => 'Login endpoint - handled by security system'
        ]);
    }

    /**
     * @Route("/register", name="register", methods={"POST"})
     * 
     * Registro paso 1: Datos personales básicos
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $data = $this->getJsonData($request);
            
            // Validar campos requeridos
            $this->validateRequired($data, [
                'registration_token',
                'first_name', 
                'last_name', 
                'password',
                'password_confirmation',
                'terms_accepted'
            ]);

            // TODO: Validar registration_token de invitación
            // Por ahora simulamos que es válido
            if (!isset($data['registration_token']) || empty($data['registration_token'])) {
                return $this->error('Token de registro inválido', null, 400, 'INVALID_TOKEN');
            }

            // Validar confirmación de contraseña
            if ($data['password'] !== $data['password_confirmation']) {
                return $this->error('Las contraseñas no coinciden', null, 400, 'PASSWORD_MISMATCH');
            }

            // Validar términos aceptados
            if (!$data['terms_accepted']) {
                return $this->error('Debe aceptar los términos y condiciones', null, 400, 'TERMS_NOT_ACCEPTED');
            }

            // TODO: Extraer email del token de invitación
            // Por ahora usamos un email dummy
            $email = $data['email'] ?? 'temp_' . uniqid() . '@mokhubaclub.com';

            // Verificar si el email ya existe
            if ($this->userRepository->findByEmail($email)) {
                return $this->error('Este email ya está registrado', null, 409, 'EMAIL_EXISTS');
            }

            // Crear usuario
            $user = new User();
            $user->setEmail($email);
            $user->setFirstName($data['first_name']);
            $user->setLastName($data['last_name']);
            
            if (isset($data['phone'])) {
                $user->setPhone($data['phone']);
            }

            // Encriptar contraseña
            $encodedPassword = $this->passwordEncoder->encodePassword($user, $data['password']);
            $user->setPassword($encodedPassword);

            // Generar código único
            $user->setUniqueCode($this->userRepository->generateUniqueCode());

            // TODO: Manejar upload de foto
            if (isset($data['photo'])) {
                // Por ahora solo guardamos un placeholder
                $user->setPhotoPath('placeholder.jpg');
            }

            // Validar entidad
            $violations = $this->validator->validate($user);
            if (count($violations) > 0) {
                return $this->validationError($violations);
            }

            // Guardar usuario
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // TODO: Enviar código de verificación por email
            
            return $this->success([
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'verification_sent' => true,
                'next_step' => 'email_verification'
            ], 'Usuario registrado exitosamente. Verifica tu email para continuar.', 201);

        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), null, 400, 'INVALID_INPUT');
        } catch (\Exception $e) {
            // Log del error real para debugging
            error_log('Registration error: ' . $e->getMessage());
            return $this->serverError('Error interno del servidor');
        }
    }

    /**
     * @Route("/email/send-code", name="send_verification", methods={"POST"})
     */
    public function sendVerificationCode(Request $request): JsonResponse
    {
        try {
            $data = $this->getJsonData($request);
            $this->validateRequired($data, ['email']);

            $email = $this->sanitizeEmail($data['email']);
            $user = $this->userRepository->findByEmail($email);

            if (!$user) {
                return $this->notFound('Usuario no encontrado');
            }

            if ($user->isEmailVerified()) {
                return $this->error('El email ya está verificado', null, 400, 'ALREADY_VERIFIED');
            }

            // TODO: Generar y enviar código de verificación
            // Por ahora simulamos el envío
            
            return $this->success([
                'code_expires_at' => (new \DateTime('+15 minutes'))->format('c')
            ], 'Código de verificación enviado');

        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), null, 400, 'INVALID_INPUT');
        } catch (\Exception $e) {
            error_log('Send verification error: ' . $e->getMessage());
            return $this->serverError('Error interno del servidor');
        }
    }

    /**
     * @Route("/email/verify", name="verify_email", methods={"POST"})
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        try {
            $data = $this->getJsonData($request);
            $this->validateRequired($data, ['email', 'code']);

            $email = $this->sanitizeEmail($data['email']);
            $code = $data['code'];

            $user = $this->userRepository->findByEmail($email);

            if (!$user) {
                return $this->notFound('Usuario no encontrado');
            }

            if ($user->isEmailVerified()) {
                return $this->error('El email ya está verificado', null, 400, 'ALREADY_VERIFIED');
            }

            // TODO: Validar código de verificación
            // Por ahora simulamos que el código '123456' es válido
            if ($code !== '123456') {
                return $this->error('Código de verificación incorrecto', null, 400, 'INVALID_CODE');
            }

            // Marcar email como verificado
            $user->markEmailAsVerified();
            $this->entityManager->flush();

            return $this->success([
                'verified' => true,
                'next_step' => 'preferences'
            ], 'Email verificado exitosamente');

        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), null, 400, 'INVALID_INPUT');
        } catch (\Exception $e) {
            error_log('Email verification error: ' . $e->getMessage());
            return $this->serverError('Error interno del servidor');
        }
    }

    /**
     * @Route("/preferences", name="preferences", methods={"POST"})
     */
    public function setPreferences(Request $request): JsonResponse
    {
        try {
            $data = $this->getJsonData($request);
            $this->validateRequired($data, ['email', 'strength_preference']);

            $email = $this->sanitizeEmail($data['email']);
            $user = $this->userRepository->findByEmail($email);

            if (!$user) {
                return $this->notFound('Usuario no encontrado');
            }

            if (!$user->isEmailVerified()) {
                return $this->error('Debe verificar su email primero', null, 400, 'EMAIL_NOT_VERIFIED');
            }

            // Validar fortaleza
            $validStrengths = ['suave', 'medio-fuerte', 'fuerte'];
            if (!in_array($data['strength_preference'], $validStrengths)) {
                return $this->error('Fortaleza inválida', null, 400, 'INVALID_STRENGTH');
            }

            // Guardar preferencias
            $preferences = [
                'types' => $data['tobacco_types'] ?? [],
                'strength' => $data['strength_preference'],
                'additional' => $data['additional_preferences'] ?? ''
            ];

            $user->setTobaccoPreferences($preferences);
            $this->entityManager->flush();

            return $this->success([
                'user' => $this->formatUser($user),
                'next_step' => 'membership_selection'
            ], 'Preferencias guardadas exitosamente');

        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), null, 400, 'INVALID_INPUT');
        } catch (\Exception $e) {
            error_log('Preferences error: ' . $e->getMessage());
            return $this->serverError('Error interno del servidor');
        }
    }

    /**
     * @Route("/me", name="me", methods={"GET"})
     */
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->unauthorized('No autenticado');
        }

        return $this->success([
            'user' => $this->formatUser($user),
            'permissions' => $this->getUserPermissions($user)
        ]);
    }

    /**
     * @Route("/logout", name="logout", methods={"POST"})
     */
    public function logout(): JsonResponse
    {
        // El logout será manejado por el sistema de seguridad
        return $this->success(null, 'Sesión cerrada exitosamente');
    }

    /**
     * Obtener permisos del usuario
     */
    private function getUserPermissions($user): array
    {
        $permissions = [];

        // Permisos básicos para todos los usuarios verificados
        if ($user->isEmailVerified()) {
            $permissions[] = 'access_dashboard';
            $permissions[] = 'view_events';
        }

        // Permisos de admin
        if ($user->hasRole('ROLE_ADMIN')) {
            $permissions[] = 'admin_access';
            $permissions[] = 'create_invitations';
            $permissions[] = 'manage_users';
            $permissions[] = 'view_reports';
        }

        // TODO: Agregar permisos basados en nivel de membresía
        // $permissions[] = 'create_brands';
        // $permissions[] = 'access_premium_events';

        return $permissions;
    }
}