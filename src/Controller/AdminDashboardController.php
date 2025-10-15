<?php
namespace App\Controller;

use App\Entity\User;
use App\Entity\Invitation;
use App\Repository\UserRepository;
use App\Repository\InvitationRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/admin")
 */
class AdminDashboardController extends AbstractController
{
    private $userRepository;
    private $entityManager;
    private $passwordHasher;
    private $validator;
    private $invitationRepository;
    private $emailService;
    private $logger;

    public function __construct(
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
        InvitationRepository $invitationRepository,
        EmailService $emailService,
        LoggerInterface $logger
    ) {
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->validator = $validator;
        $this->invitationRepository = $invitationRepository;
        $this->emailService = $emailService;
        $this->logger = $logger;
    }

    /**
     * @Route("/dashboard", name="admin_dashboard")
     */
    public function index(): Response
    {
        $user = $this->getUser();
        
        if (!$user || !$user->isAdmin()) {
            throw $this->createAccessDeniedException('Acceso denegado. Se requieren privilegios de administrador.');
        }

        $stats = $this->userRepository->getStats();
        
        return $this->render('admin/dashboard/index.html.twig', [
            'user' => $user,
            'stats' => $stats,
            'recent_users' => $this->getRecentUsers(),
            'membership_distribution' => $this->getMembershipDistribution($stats)
        ]);
    }

    /**
     * @Route("/users", name="admin_users")
     */
    public function users(Request $request): Response
    {
        $user = $this->getUser();
        
        if (!$user || !$user->isAdmin()) {
            throw $this->createAccessDeniedException();
        }

        // Obtener todos los usuarios (activos e inactivos)
        $users = $this->userRepository->findBy([], ['createdAt' => 'DESC']);
        
        // Calcular estadísticas para la vista
        $totalUsers = count($users);
        $activeUsers = count(array_filter($users, fn($u) => $u->getIsActive()));
        $inactiveUsers = count(array_filter($users, fn($u) => !$u->getIsActive()));
        $premiumUsers = count(array_filter($users, fn($u) => in_array($u->getMembershipLevel(), ['premium', 'vip'])));
        
        // Usuarios nuevos del último mes
        $lastMonth = new \DateTime('-30 days');
        $newUsersThisMonth = count(array_filter($users, fn($u) => $u->getCreatedAt() >= $lastMonth));
        
        // Si viene ?edit=ID, cargamos el usuario y lo pasamos a la vista
        $editId = $request->query->getInt('edit', 0);
        $userToEdit = null;
        if ($editId > 0) {
            $userToEdit = $this->userRepository->find($editId);
            if (!$userToEdit) {
                $this->addFlash('danger', 'Usuario a editar no encontrado');
            }
        }
        
        return $this->render('admin/users/index.html.twig', [
            'users' => $users,
            'user' => $userToEdit, // Para el formulario integrado
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'inactiveUsers' => $inactiveUsers,
            'premiumUsers' => $premiumUsers,
            'newUsersThisMonth' => $newUsersThisMonth,
            'stats' => $this->userRepository->getStats()
        ]);
    }

    /**
     * @Route("/users/create", name="admin_users_create", methods={"POST"})
     */
    public function createUser(Request $request): JsonResponse
    {
        error_log("🚀 AdminController::createUser() - Inicio de la función");
        
        // Verificar que es admin
        $currentUser = $this->getUser();
        if (!$currentUser || !$currentUser->isAdmin()) {
            error_log("❌ Usuario no es admin");
            return new JsonResponse([
                'success' => false,
                'error' => 'Acceso denegado. Privilegios de administrador requeridos.'
            ], 403);
        }

        try {
            // Log del contenido recibido
            $rawContent = $request->getContent();
            error_log("📦 Contenido raw recibido: " . $rawContent);
            
            // Obtener datos del request
            $data = json_decode($rawContent, true);
            error_log("📄 Datos parseados: " . print_r($data, true));
            
            if (!$data) {
                error_log("❌ Error parseando JSON");
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Datos inválidos. Se requiere JSON válido.'
                ], 400);
            }

            // Validar campos requeridos
            $requiredFields = ['firstName', 'lastName', 'email', 'membershipLevel', 'uniqueCode', 'password'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                error_log("❌ Campos faltantes: " . implode(', ', $missingFields));
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Campos requeridos faltantes: ' . implode(', ', $missingFields),
                    'missing_fields' => $missingFields
                ], 400);
            }

            // Verificar que el email no exista
            $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
            if ($existingUser) {
                error_log("❌ Email ya existe: " . $data['email']);
                return new JsonResponse([
                    'success' => false,
                    'error' => 'El email ya está registrado en el sistema.',
                    'field' => 'email'
                ], 409);
            }

            // Verificar que el código único no exista
            $existingCode = $this->userRepository->findOneBy(['uniqueCode' => $data['uniqueCode']]);
            if ($existingCode) {
                error_log("❌ Código único ya existe: " . $data['uniqueCode']);
                return new JsonResponse([
                    'success' => false,
                    'error' => 'El código único ya existe. Genere uno nuevo.',
                    'field' => 'uniqueCode'
                ], 409);
            }

            // Validar formato de email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                error_log("❌ Email inválido: " . $data['email']);
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Formato de email inválido.',
                    'field' => 'email'
                ], 400);
            }

            // Validar membresía válida
            $validMemberships = ['basica', 'premium', 'vip', 'admin'];
            if (!in_array($data['membershipLevel'], $validMemberships)) {
                error_log("❌ Membresía inválida: " . $data['membershipLevel']);
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Nivel de membresía inválido.',
                    'field' => 'membershipLevel'
                ], 400);
            }

            error_log("✅ Validaciones pasadas, creando usuario...");

            // Crear nueva instancia de User
            $newUser = new User();
            
            // Establecer fechas de creación y actualización
            $now = new \DateTime();
            $newUser->setCreatedAt($now);
            $newUser->setUpdatedAt($now);
            
            // Establecer datos básicos
            $newUser->setFirstName(trim($data['firstName']));
            $newUser->setLastName(trim($data['lastName']));
            $newUser->setEmail(strtolower(trim($data['email'])));
            $newUser->setUniqueCode($data['uniqueCode']);
            $newUser->setMembershipLevel($data['membershipLevel']);
            
            // Teléfono opcional
            if (!empty($data['phone'])) {
                $newUser->setPhone(trim($data['phone']));
            }

            // Hashear contraseña
            error_log("🔐 Hasheando contraseña...");
            $hashedPassword = $this->passwordHasher->hashPassword($newUser, $data['password']);
            $newUser->setPassword($hashedPassword);

            // Establecer roles según membresía
            $roles = ['ROLE_USER'];
            if ($data['membershipLevel'] === 'admin') {
                $roles[] = 'ROLE_ADMIN';
            }
            $newUser->setRoles($roles);

            // Usuario activo por defecto
            $newUser->setIsActive(true);

            // Validar la entidad
            $errors = $this->validator->validate($newUser);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                
                error_log("❌ Errores de validación: " . implode(', ', $errorMessages));
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Errores de validación: ' . implode(', ', $errorMessages),
                    'validation_errors' => $errorMessages
                ], 400);
            }

            error_log("💾 Guardando en base de datos...");
            
            // Persistir en base de datos
            $this->entityManager->persist($newUser);
            $this->entityManager->flush();

            error_log("✅ Usuario creado exitosamente con ID: " . $newUser->getId());

            // Log de auditoría
            $this->logUserCreation($currentUser, $newUser);

            // Nombre del método getMembershipDisplayName()
            $membershipDisplay = $this->getMembershipDisplayName($newUser->getMembershipLevel());

            // Respuesta exitosa
            return new JsonResponse([
                'success' => true,
                'message' => 'Usuario creado exitosamente.',
                'user' => [
                    'id' => $newUser->getId(),
                    'firstName' => $newUser->getFirstName(),
                    'lastName' => $newUser->getLastName(),
                    'fullName' => $newUser->getFullName(),
                    'email' => $newUser->getEmail(),
                    'uniqueCode' => $newUser->getUniqueCode(),
                    'membershipLevel' => $newUser->getMembershipLevel(),
                    'membershipDisplay' => $membershipDisplay,
                    'createdAt' => $newUser->getCreatedAt()->format('Y-m-d H:i:s')
                ],
                'credentials' => [
                    'email' => $newUser->getEmail(),
                    'password' => $data['password'], // Solo para mostrar una vez
                    'uniqueCode' => $newUser->getUniqueCode()
                ]
            ], 201);

        } catch (\Exception $e) {
            // Log del error
            error_log("💥 Error creating user: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            return new JsonResponse([
                'success' => false,
                'error' => 'Error interno del servidor. Intente nuevamente.',
                'debug' => $this->getParameter('kernel.environment') === 'dev' ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @Route("/invitations/send", name="admin_send_invitation", methods={"POST"})
     */
    public function sendInvitation(Request $request): JsonResponse
    {
        error_log("📧 AdminController::sendInvitation() - Inicio");
        
        // Verificar que es admin
        $currentUser = $this->getUser();
        if (!$currentUser || !$currentUser->isAdmin()) {
            error_log("❌ Usuario no es admin");
            return new JsonResponse([
                'success' => false,
                'error' => 'Acceso denegado. Privilegios de administrador requeridos.'
            ], 403);
        }

        try {
            // Obtener datos del request
            $data = json_decode($request->getContent(), true);
            error_log("📄 Datos recibidos: " . print_r($data, true));
            
            if (!$data) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Datos inválidos. Se requiere JSON válido.'
                ], 400);
            }

            $name = $data['name'] ?? '';
            $email = $data['email'] ?? '';
            $phone = $data['phone'] ?? '';
            $message = $data['message'] ?? '';

            // Validaciones
            if (empty($name) || empty($email)) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'El nombre y el email son obligatorios'
                ], 400);
            }

            // Validar formato de email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'El formato del email no es válido'
                ], 400);
            }

            // Verificar si el email ya está registrado
            $existingUser = $this->userRepository->findOneBy(['email' => $email]);
            if ($existingUser) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Este email ya está registrado en el sistema'
                ], 400);
            }

            // Verificar si ya existe una invitación pendiente para este email
            if ($this->invitationRepository->hasPendingInvitation($email)) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Ya existe una invitación pendiente para este email'
                ], 400);
            }

            // Crear invitación
            $invitation = new Invitation();
            $invitation->setSender($currentUser);
            $invitation->setInviteeName($name);
            $invitation->setInviteeEmail($email);
            $invitation->setInviteePhone($phone);
            $invitation->setMessage($message);

            $this->entityManager->persist($invitation);
            $this->entityManager->flush();

            // Enviar email
            $emailSent = $this->emailService->sendInvitationEmail($invitation);

            $this->logger->info('✅ [ADMIN] Invitación creada', [
                'invitation_id' => $invitation->getId(),
                'admin' => $currentUser->getEmail(),
                'invitee' => $email,
                'token' => $invitation->getToken(),
                'email_sent' => $emailSent,
                'expires_at' => $invitation->getExpiresAt()->format('Y-m-d H:i:s')
            ]);

            return new JsonResponse([
                'success' => true,
                'message' => 'Invitación enviada exitosamente. Válida por 3 días.',
                'invitation' => [
                    'id' => $invitation->getId(),
                    'token' => $invitation->getToken(),
                    'invitee_name' => $invitation->getInviteeName(),
                    'invitee_email' => $invitation->getInviteeEmail(),
                    'sent_at' => $invitation->getSentAt()->format('Y-m-d H:i:s'),
                    'expires_at' => $invitation->getExpiresAt()->format('Y-m-d H:i:s'),
                    'status' => $invitation->getStatus()
                ],
                'mock_mode' => $this->emailService->isMockMode()
            ], 201);

        } catch (\Exception $e) {
            $this->logger->error('❌ [ADMIN] Error enviando invitación', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => 'Error al enviar la invitación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @Route("/users/{id}/get", name="admin_users_get", methods={"GET"})
     */
    public function fetchUserById(int $id): JsonResponse
    {
        error_log("📥 AdminController::fetchUserById() - Usuario ID: " . $id);
        
        $currentUser = $this->getUser();
        if (!$currentUser || !$currentUser->isAdmin()) {
            error_log("❌ Usuario no es admin");
            return new JsonResponse([
                'success' => false,
                'error' => 'Acceso denegado.'
            ], 403);
        }

        try {
            $user = $this->userRepository->find($id);
            if (!$user) {
                error_log("❌ Usuario no encontrado: " . $id);
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Usuario no encontrado.'
                ], 404);
            }

            error_log("✅ Usuario encontrado: " . $user->getEmail());
            
            return new JsonResponse([
                'success' => true,
                'user' => [
                    'id' => $user->getId(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'email' => $user->getEmail(),
                    'phone' => $user->getPhone(),
                    'membershipLevel' => $user->getMembershipLevel(),
                    'uniqueCode' => $user->getUniqueCode(),
                    'isActive' => $user->getIsActive(),
                    'roles' => $user->getRoles()
                ]
            ]);
            
        } catch (\Exception $e) {
            error_log("💥 Error fetching user: " . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'error' => 'Error interno del servidor.',
                'debug' => $this->getParameter('kernel.environment') === 'dev' ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @Route("/users/{id}/edit", name="admin_users_edit", methods={"POST"})
     */
    public function editUser(int $id, Request $request): JsonResponse
    {
        error_log("✏️ AdminController::editUser() - Usuario ID: " . $id);
        
        $currentUser = $this->getUser();
        if (!$currentUser || !$currentUser->isAdmin()) {
            error_log("❌ Usuario no es admin");
            return new JsonResponse([
                'success' => false,
                'error' => 'Acceso denegado.'
            ], 403);
        }

        try {
            // Obtener datos del request
            $rawContent = $request->getContent();
            error_log("📦 Contenido de edición recibido: " . $rawContent);
            
            $data = json_decode($rawContent, true);
            
            if (!$data) {
                error_log("❌ Error parseando JSON de edición");
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Datos inválidos. Se requiere JSON válido.'
                ], 400);
            }
            
            error_log("📄 Datos de edición parseados: " . print_r($data, true));
            
            // Obtener el usuario a editar
            $user = $this->userRepository->find($id);
            if (!$user) {
                error_log("❌ Usuario a editar no encontrado: " . $id);
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Usuario no encontrado.'
                ], 404);
            }

            // Validar campos requeridos
            $requiredFields = ['firstName', 'lastName', 'email', 'membershipLevel'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || trim($data[$field]) === '') {
                    error_log("❌ Campo requerido faltante en edición: " . $field);
                    return new JsonResponse([
                        'success' => false,
                        'error' => "El campo {$field} es obligatorio."
                    ], 400);
                }
            }

            // Validar email único (excluyendo el usuario actual)
            if (trim($data['email']) !== $user->getEmail()) {
                $existingUser = $this->userRepository->findOneBy(['email' => trim($data['email'])]);
                if ($existingUser) {
                    error_log("❌ Email ya existe en edición: " . $data['email']);
                    return new JsonResponse([
                        'success' => false,
                        'error' => 'El email ya está en uso por otro usuario.'
                    ], 400);
                }
            }

            // Validar formato de email
            if (!filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL)) {
                error_log("❌ Email inválido en edición: " . $data['email']);
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Formato de email inválido.'
                ], 400);
            }

            // Validar membresía
            $validMemberships = ['basica', 'premium', 'vip', 'admin'];
            if (!in_array($data['membershipLevel'], $validMemberships)) {
                error_log("❌ Membresía inválida en edición: " . $data['membershipLevel']);
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Nivel de membresía inválido.'
                ], 400);
            }

            error_log("✅ Validaciones de edición pasadas, actualizando usuario...");

            // Actualizar datos
            $user->setFirstName(trim($data['firstName']));
            $user->setLastName(trim($data['lastName']));
            $user->setEmail(strtolower(trim($data['email'])));
            $user->setPhone(isset($data['phone']) ? trim($data['phone']) : $user->getPhone());
            $user->setMembershipLevel($data['membershipLevel']);
            $user->setUpdatedAt(new \DateTime());

            // Actualizar roles si cambió la membresía
            $roles = ['ROLE_USER'];
            if ($data['membershipLevel'] === 'admin') {
                $roles[] = 'ROLE_ADMIN';
            }
            $user->setRoles($roles);

            // Validar la entidad actualizada
            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                
                error_log("❌ Errores de validación en edición: " . implode(', ', $errorMessages));
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Errores de validación: ' . implode(', ', $errorMessages),
                    'validation_errors' => $errorMessages
                ], 400);
            }

            // Guardar cambios
            $this->entityManager->flush();

            error_log("✅ Usuario actualizado exitosamente: " . $user->getId());

            // Log de auditoría
            error_log(sprintf(
                "AUDIT: Admin %s (%s) updated user %s (%s)",
                $currentUser->getFullName(),
                $currentUser->getEmail(),
                $user->getFullName(),
                $user->getEmail()
            ));

            return new JsonResponse([
                'success' => true,
                'message' => 'Usuario actualizado exitosamente.',
                'user' => [
                    'id' => $user->getId(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'fullName' => $user->getFullName(),
                    'email' => $user->getEmail(),
                    'phone' => $user->getPhone(),
                    'membershipLevel' => $user->getMembershipLevel(),
                    'membershipDisplay' => $this->getMembershipDisplayName($user->getMembershipLevel()),
                    'uniqueCode' => $user->getUniqueCode(),
                    'updatedAt' => $user->getUpdatedAt()->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            error_log("💥 Error updating user: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            return new JsonResponse([
                'success' => false,
                'error' => 'Error al actualizar usuario.',
                'debug' => $this->getParameter('kernel.environment') === 'dev' ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @Route("/users/{id}/toggle-status", name="admin_users_toggle_status", methods={"POST"})
     */
    public function toggleUserStatus(int $id, Request $request): JsonResponse
    {
        error_log("🔄 AdminController::toggleUserStatus() - Usuario ID: " . $id);
        
        $currentUser = $this->getUser();
        if (!$currentUser || !$currentUser->isAdmin()) {
            error_log("❌ Usuario no es admin");
            return new JsonResponse([
                'success' => false,
                'error' => 'Acceso denegado.'
            ], 403);
        }

        try {
            $user = $this->userRepository->find($id);
            if (!$user) {
                error_log("❌ Usuario no encontrado para cambio de estado: " . $id);
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Usuario no encontrado.'
                ], 404);
            }

            // Evitar que se desactive a sí mismo
            if ($user->getId() === $currentUser->getId()) {
                error_log("❌ Admin intenta cambiar su propio estado");
                return new JsonResponse([
                    'success' => false,
                    'error' => 'No puede cambiar su propio estado.'
                ], 400);
            }

            $originalStatus = $user->getIsActive();
            $newStatus = !$originalStatus;
            
            error_log("🔄 Cambiando estado de usuario " . $user->getEmail() . " de " . ($originalStatus ? 'activo' : 'inactivo') . " a " . ($newStatus ? 'activo' : 'inactivo'));
            
            $user->setIsActive($newStatus);
            $user->setUpdatedAt(new \DateTime());

            $this->entityManager->flush();

            error_log("✅ Estado cambiado exitosamente");

            // Log de auditoría
            error_log(sprintf(
                "AUDIT: Admin %s (%s) changed status of user %s (%s) from %s to %s",
                $currentUser->getFullName(),
                $currentUser->getEmail(),
                $user->getFullName(),
                $user->getEmail(),
                $originalStatus ? 'active' : 'inactive',
                $newStatus ? 'active' : 'inactive'
            ));

            return new JsonResponse([
                'success' => true,
                'message' => $newStatus ? 'Usuario activado exitosamente.' : 'Usuario desactivado exitosamente.',
                'user' => [
                    'id' => $user->getId(),
                    'fullName' => $user->getFullName(),
                    'email' => $user->getEmail(),
                    'isActive' => $user->getIsActive(),
                    'status' => $newStatus ? 'active' : 'inactive'
                ]
            ]);

        } catch (\Exception $e) {
            error_log("💥 Error toggling user status: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            return new JsonResponse([
                'success' => false,
                'error' => 'Error al cambiar estado del usuario.',
                'debug' => $this->getParameter('kernel.environment') === 'dev' ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @Route("/users/{id}/reset-password", name="admin_users_reset_password", methods={"POST"})
     */
    public function resetUserPassword(int $id, Request $request): JsonResponse
    {
        error_log("🔐 AdminController::resetUserPassword() - Usuario ID: " . $id);
        
        $currentUser = $this->getUser();
        if (!$currentUser || !$currentUser->isAdmin()) {
            error_log("❌ Usuario no es admin");
            return new JsonResponse([
                'success' => false,
                'error' => 'Acceso denegado.'
            ], 403);
        }

        try {
            $user = $this->userRepository->find($id);
            if (!$user) {
                error_log("❌ Usuario no encontrado para reset de contraseña: " . $id);
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Usuario no encontrado.'
                ], 404);
            }

            // Generar nueva contraseña temporal
            $newPassword = $this->generateTemporaryPassword();
            error_log("🔑 Contraseña temporal generada: " . $newPassword);
            
            // Hashear y establecer la nueva contraseña
            $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            $user->setUpdatedAt(new \DateTime());

            $this->entityManager->flush();

            error_log("✅ Contraseña restablecida exitosamente");

            // Log de auditoría
            error_log(sprintf(
                "AUDIT: Admin %s (%s) reset password for user %s (%s)",
                $currentUser->getFullName(),
                $currentUser->getEmail(),
                $user->getFullName(),
                $user->getEmail()
            ));

            return new JsonResponse([
                'success' => true,
                'message' => 'Contraseña restablecida exitosamente.',
                'user' => [
                    'id' => $user->getId(),
                    'fullName' => $user->getFullName(),
                    'email' => $user->getEmail()
                ],
                'newPassword' => $newPassword // Solo se muestra una vez
            ]);

        } catch (\Exception $e) {
            error_log("💥 Error resetting password: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            return new JsonResponse([
                'success' => false,
                'error' => 'Error al restablecer contraseña.',
                'debug' => $this->getParameter('kernel.environment') === 'dev' ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * @Route("/users/{id}/update", name="admin_users_update", methods={"POST"})
     */
    public function updateUserForm(int $id, Request $request): Response
    {
        error_log("📝 AdminController::updateUserForm() - Usuario ID: " . $id);
        
        $currentUser = $this->getUser();
        if (!$currentUser || !$currentUser->isAdmin()) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->userRepository->find($id);
        if (!$user) {
            $this->addFlash('danger', 'Usuario no encontrado');
            return $this->redirectToRoute('admin_users');
        }

        try {
            // Obtener datos del formulario
            $name = trim($request->get('name', ''));
            $email = trim($request->get('email', ''));
            $roles = $request->get('roles', []);
            $isActive = $request->get('isActive') === 'on';
            $plainPassword = $request->get('plainPassword');

            // Validar campos requeridos
            if (empty($email)) {
                $this->addFlash('danger', 'El email es obligatorio');
                return $this->redirectToRoute('admin_users', ['edit' => $id]);
            }

            // Validar email único (excluyendo el usuario actual)
            if ($email !== $user->getEmail()) {
                $existingUser = $this->userRepository->findOneBy(['email' => $email]);
                if ($existingUser) {
                    $this->addFlash('danger', 'El email ya está en uso por otro usuario');
                    return $this->redirectToRoute('admin_users', ['edit' => $id]);
                }
            }

            // Extraer firstName y lastName del campo name
            if (!empty($name)) {
                $nameParts = explode(' ', $name, 2);
                $user->setFirstName($nameParts[0]);
                $user->setLastName(isset($nameParts[1]) ? $nameParts[1] : '');
            }

            // Actualizar datos
            $user->setEmail(strtolower($email));
            $user->setRoles($roles ?: ['ROLE_USER']);
            $user->setIsActive($isActive);
            $user->setUpdatedAt(new \DateTime());

            // Cambiar contraseña si se proporcionó
            if (!empty($plainPassword)) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
                error_log("🔐 Contraseña actualizada para usuario: " . $user->getEmail());
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Usuario actualizado exitosamente');
            error_log("✅ Usuario actualizado via formulario: " . $user->getEmail());

            return $this->redirectToRoute('admin_users');

        } catch (\Exception $e) {
            error_log("💥 Error updating user via form: " . $e->getMessage());
            $this->addFlash('danger', 'Error al actualizar usuario: ' . $e->getMessage());
            return $this->redirectToRoute('admin_users', ['edit' => $id]);
        }
    }

    /**
     * @Route("/memberships", name="admin_memberships")
     */
    public function memberships(): Response
    {
        $user = $this->getUser();
        
        if (!$user || !$user->isAdmin()) {
            throw $this->createAccessDeniedException();
        }

        $membershipStats = $this->userRepository->countByMembershipLevel();
        
        return $this->render('admin/memberships/index.html.twig', [
            'membership_stats' => $membershipStats,
            'basica_users' => $this->userRepository->findActiveByMembershipLevel('basica'),
            'premium_users' => $this->userRepository->findActiveByMembershipLevel('premium'),
            'vip_users' => $this->userRepository->findActiveByMembershipLevel('vip')
        ]);
    }

    /**
     * @Route("/settings", name="admin_settings")
     */
    public function settings(): Response
    {
        $user = $this->getUser();
        
        if (!$user || !$user->isAdmin()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('admin/settings/index.html.twig', [
            'user' => $user
        ]);
    }

    // =====================================================
    // MÉTODOS PRIVADOS
    // =====================================================

    private function getRecentUsers(): array
    {
        return $this->userRepository->createQueryBuilder('u')
            ->where('u.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    private function getMembershipDistribution(array $stats): array
    {
        $total = $stats['total_users'] ?? 0;
        
        if ($total === 0) {
            return [
                'basica' => 0,
                'premium' => 0,
                'vip' => 0,
                'admin' => 0
            ];
        }

        $membershipCounts = $stats['membership_counts'] ?? [];

        return [
            'basica' => round((($membershipCounts['basica'] ?? 0) / $total) * 100, 1),
            'premium' => round((($membershipCounts['premium'] ?? 0) / $total) * 100, 1),
            'vip' => round((($membershipCounts['vip'] ?? 0) / $total) * 100, 1),
            'admin' => round((($membershipCounts['admin'] ?? 0) / $total) * 100, 1)
        ];
    }

    private function generateTemporaryPassword(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%';
        $password = '';
        
        for ($i = 0; $i < 12; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        error_log("🔑 Contraseña temporal generada: " . $password);
        return $password;
    }

    private function logUserCreation(User $admin, User $newUser): void
    {
        // Log para auditoría
        error_log(sprintf(
            "AUDIT: Admin %s (%s) created user %s (%s) with membership %s",
            $admin->getFullName(),
            $admin->getEmail(),
            $newUser->getFullName(),
            $newUser->getEmail(),
            $newUser->getMembershipLevel()
        ));
    }

    /**
     * Obtener nombre de display para membresía
     */
    private function getMembershipDisplayName(string $membership): string
    {
        $displays = [
            'basica' => 'Básica',
            'premium' => 'Premium', 
            'vip' => 'VIP',
            'admin' => 'Administrador'
        ];

        return $displays[$membership] ?? ucfirst($membership);
    }
}