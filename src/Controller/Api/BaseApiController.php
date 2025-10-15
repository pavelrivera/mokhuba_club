<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolationListInterface;

abstract class BaseApiController extends AbstractController
{
    /**
     * Respuesta exitosa estándar
     */
    protected function success($data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return new JsonResponse($response, $status);
    }

    /**
     * Respuesta de error estándar
     */
    protected function error(string $message = 'Error', $errors = null, int $status = 400, string $errorCode = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        if ($errorCode !== null) {
            $response['error_code'] = $errorCode;
        }

        return new JsonResponse($response, $status);
    }

    /**
     * Respuesta con paginación
     */
    protected function paginate($data, int $page, int $perPage, int $total): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'data' => $data,
            'meta' => [
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage),
                    'from' => (($page - 1) * $perPage) + 1,
                    'to' => min($page * $perPage, $total)
                ]
            ]
        ]);
    }

    /**
     * Convierte errores de validación a formato API
     */
    protected function validationError(ConstraintViolationListInterface $violations): JsonResponse
    {
        $errors = [];
        
        foreach ($violations as $violation) {
            $field = $violation->getPropertyPath();
            if (!isset($errors[$field])) {
                $errors[$field] = [];
            }
            $errors[$field][] = $violation->getMessage();
        }

        return $this->error('Validation failed', $errors, 422, 'VALIDATION_ERROR');
    }

    /**
     * Obtiene datos JSON del request
     */
    protected function getJsonData(Request $request): array
    {
        $data = json_decode($request->getContent(), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON provided');
        }

        return $data ?? [];
    }

    /**
     * Verifica que el usuario actual esté autenticado
     */
    protected function requireAuth(): void
    {
        if (!$this->getUser()) {
            throw $this->createAccessDeniedException('Authentication required');
        }
    }

    /**
     * Verifica que el usuario tenga un rol específico
     */
    protected function requireRole(string $role): void
    {
        $this->requireAuth();
        
        if (!$this->isGranted($role)) {
            throw $this->createAccessDeniedException('Insufficient permissions');
        }
    }

    /**
     * Respuesta 401 - No autenticado
     */
    protected function unauthorized(string $message = 'Authentication required'): JsonResponse
    {
        return $this->error($message, null, 401, 'UNAUTHORIZED');
    }

    /**
     * Respuesta 403 - Sin permisos
     */
    protected function forbidden(string $message = 'Access denied'): JsonResponse
    {
        return $this->error($message, null, 403, 'FORBIDDEN');
    }

    /**
     * Respuesta 404 - No encontrado
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, null, 404, 'NOT_FOUND');
    }

    /**
     * Respuesta 409 - Conflicto
     */
    protected function conflict(string $message = 'Conflict', $errors = null): JsonResponse
    {
        return $this->error($message, $errors, 409, 'CONFLICT');
    }

    /**
     * Respuesta 500 - Error interno
     */
    protected function serverError(string $message = 'Internal server error'): JsonResponse
    {
        return $this->error($message, null, 500, 'INTERNAL_ERROR');
    }

    /**
     * Formatear usuario para respuesta API
     */
    protected function formatUser($user): array
    {
        if (!$user) {
            return [];
        }

        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'first_name' => $user->getFirstName(),
            'last_name' => $user->getLastName(),
            'full_name' => $user->getFullName(),
            'phone' => $user->getPhone(),
            'photo_url' => $user->getPhotoUrl(),
            'unique_code' => $user->getUniqueCode(),
            'is_active' => $user->getIsActive(),
            'email_verified' => $user->isEmailVerified(),
            'roles' => $user->getRoles(),
            'created_at' => $user->getCreatedAt()->format('c')
        ];
    }

    /**
     * Validar parámetros requeridos
     */
    protected function validateRequired(array $data, array $required): void
    {
        $missing = [];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                'Missing required fields: ' . implode(', ', $missing)
            );
        }
    }

    /**
     * Limpiar y validar email
     */
    protected function sanitizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * Obtener parámetros de paginación del request
     */
    protected function getPaginationParams(Request $request): array
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = min(100, max(1, (int) $request->query->get('per_page', 15)));

        return [$page, $perPage];
    }
}