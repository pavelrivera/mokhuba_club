<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * UserService (compatible con PHP 7.4 / Symfony 5.4)
 * - Sin operador nullsafe (?->)
 * - Métodos frecuentes de dominio para User
 * - Normalizador seguro a array (toArray)
 */
class UserService
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var UserPasswordHasherInterface */
    private $hasher;

    public function __construct(
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ) {
        $this->em     = $em;
        $this->hasher = $hasher;
    }

    /**
     * Crea un usuario a partir de datos de registro.
     * Espera, al menos: email, password (plano) y name (si lo usas).
     *
     * @param array $data
     */
    public function createFromRegistration(array $data): User
    {
        $user = new User();

        // email
        $email = isset($data['email']) ? (string) $data['email'] : '';
        if (method_exists($user, 'setEmail')) {
            $user->setEmail($email);
        }

        // nombre
        $name = isset($data['name']) ? (string) $data['name'] : '';
        if ($name !== '' && method_exists($user, 'setName')) {
            $user->setName($name);
        }

        // password (hash)
        $plain = isset($data['password']) ? (string) $data['password'] : '';
        if ($plain !== '' && method_exists($user, 'setPassword')) {
            $hash = $this->hasher->hashPassword($user, $plain);
            $user->setPassword($hash);
        }

        // unique_code si lo usas y es NOT NULL
        if (method_exists($user, 'setUniqueCode')) {
            $user->setUniqueCode($this->randomCode(10));
        }

        // rol por defecto si tu User tiene role_id o roles
        if (method_exists($user, 'setRoleId') && empty($this->tryCall($user, 'getRoleId'))) {
            // 1 = ROLE_USER u otro valor por defecto en tu proyecto
            $user->setRoleId(1);
        }

        // timestamps
        $now = new DateTimeImmutable('now');
        if (method_exists($user, 'setCreatedAt')) {
            $user->setCreatedAt($now);
        }
        if (method_exists($user, 'setUpdatedAt')) {
            $user->setUpdatedAt($now);
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * Actualiza campos básicos del perfil.
     *
     * @param array $data ['name'?, 'email'?, 'photo_path'?]
     */
    public function updateProfile(User $user, array $data): User
    {
        if (isset($data['name']) && method_exists($user, 'setName')) {
            $user->setName((string) $data['name']);
        }
        if (isset($data['email']) && method_exists($user, 'setEmail')) {
            $user->setEmail((string) $data['email']);
        }
        if (isset($data['photo_path']) && method_exists($user, 'setPhotoPath')) {
            $user->setPhotoPath((string) $data['photo_path']);
        }

        if (method_exists($user, 'setUpdatedAt')) {
            $user->setUpdatedAt(new DateTimeImmutable('now'));
        }

        $this->em->flush();
        return $user;
    }

    /**
     * Cambia contraseña de un usuario.
     */
    public function changePassword(User $user, string $plainPassword): User
    {
        if ($plainPassword !== '' && method_exists($user, 'setPassword')) {
            $hash = $this->hasher->hashPassword($user, $plainPassword);
            $user->setPassword($hash);

            if (method_exists($user, 'setUpdatedAt')) {
                $user->setUpdatedAt(new DateTimeImmutable('now'));
            }

            $this->em->flush();
        }
        return $user;
    }

    /**
     * Marca email como verificado y fecha de verificación.
     */
    public function markEmailVerified(User $user, ?DateTimeInterface $when = null): User
    {
        $when = $when ?: new DateTimeImmutable('now');

        if (method_exists($user, 'setEmailVerifiedAt')) {
            $user->setEmailVerifiedAt($when);
        }
        if (method_exists($user, 'setUpdatedAt')) {
            $user->setUpdatedAt(new DateTimeImmutable('now'));
        }

        $this->em->flush();
        return $user;
    }

    /**
     * Establece preferencias de tabaco (array o string según tu entidad).
     *
     * @param mixed $preferences
     */
    public function setTobaccoPreferences(User $user, $preferences): User
    {
        if (method_exists($user, 'setTobaccoPreferences')) {
            $user->setTobaccoPreferences($preferences);
        }
        if (method_exists($user, 'setUpdatedAt')) {
            $user->setUpdatedAt(new DateTimeImmutable('now'));
        }
        $this->em->flush();
        return $user;
    }

    /**
     * Establece ruta/archivo de foto de perfil.
     */
    public function setPhotoPath(User $user, ?string $path): User
    {
        if (method_exists($user, 'setPhotoPath')) {
            $user->setPhotoPath($path);
        }
        if (method_exists($user, 'setUpdatedAt')) {
            $user->setUpdatedAt(new DateTimeImmutable('now'));
        }
        $this->em->flush();
        return $user;
    }

    /**
     * Helpers de roles — ajústalo a tu modelo (role_id o array de roles).
     */
    public function isAdmin(User $user): bool
    {
        // Si usas role_id:
        $roleId = $this->tryCall($user, 'getRoleId');
        if ($roleId !== null) {
            // ejemplo: 2 == admin (ajusta a tu mapeo real)
            return (int) $roleId === 2;
        }

        // Si usas roles (array):
        $roles = $this->tryCall($user, 'getRoles');
        if (is_array($roles)) {
            return in_array('ROLE_ADMIN', $roles, true);
        }
        return false;
    }

    public function hasRole(User $user, string $role): bool
    {
        // role_id mapeado
        if ($role === 'ROLE_ADMIN' && $this->isAdmin($user)) {
            return true;
        }
        // lista de roles
        $roles = $this->tryCall($user, 'getRoles');
        if (is_array($roles)) {
            return in_array($role, $roles, true);
        }
        return false;
    }

    /**
     * Normaliza un User a array (para API/plantillas).
     * NO usa operador nullsafe (?->) — compatible con PHP 7.4.
     */
    public function toArray(User $user): array
    {
        $id           = $this->tryCall($user, 'getId');
        $name         = $this->resolveName($user);
        $email        = $this->tryCall($user, 'getEmail');
        $uniqueCode   = $this->tryCall($user, 'getUniqueCode');
        $photoPath    = $this->tryCall($user, 'getPhotoPath');
        $tobaccoPrefs = $this->tryCall($user, 'getTobaccoPreferences');

        // verificación de email
        $emailVerified = false;
        $flag = $this->tryCall($user, 'isEmailVerified');
        if (is_bool($flag)) {
            $emailVerified = $flag;
        } else {
            $emailVerifiedAtObj = $this->tryCall($user, 'getEmailVerifiedAt');
            $emailVerified = $emailVerifiedAtObj instanceof DateTimeInterface;
        }

        $emailVerifiedAt = $this->formatIfDateTime($this->tryCall($user, 'getEmailVerifiedAt'));
        $createdAt       = $this->formatIfDateTime($this->tryCall($user, 'getCreatedAt'));
        $updatedAt       = $this->formatIfDateTime($this->tryCall($user, 'getUpdatedAt'));

        $roles = $this->tryCall($user, 'getRoles');
        if (!is_array($roles)) {
            $roles = [];
        }

        // Si usas role_id además de roles:
        $roleId = $this->tryCall($user, 'getRoleId');

        return [
            'id'                  => $id,
            'name'                => $name,
            'email'               => $email,
            'roles'               => $roles,
            'role_id'             => $roleId,
            'unique_code'         => $uniqueCode,
            'photo_path'          => $photoPath,
            'email_verified'      => $emailVerified,
            'email_verified_at'   => $emailVerifiedAt,   // ISO-8601 o null
            'tobacco_preferences' => $tobaccoPrefs,
            'created_at'          => $createdAt,         // ISO-8601 o null
            'updated_at'          => $updatedAt,         // ISO-8601 o null
        ];
    }

    /**
     * -------------------------
     * Helpers internos
     * -------------------------
     */

    /**
     * Intenta obtener nombre con distintos getters comunes.
     */
    private function resolveName(User $user): ?string
    {
        $name = $this->tryCall($user, 'getName');
        if (is_string($name) && $name !== '') {
            return $name;
        }
        $full = $this->tryCall($user, 'getFullName');
        if (is_string($full) && $full !== '') {
            return $full;
        }
        $username = $this->tryCall($user, 'getUsername');
        if (is_string($username) && $username !== '') {
            return $username;
        }
        return null;
    }

    /**
     * Llama un método si existe; si no, devuelve null.
     *
     * @param object $object
     * @param string $method
     * @return mixed|null
     */
    private function tryCall($object, string $method)
    {
        if (is_object($object) && method_exists($object, $method)) {
            return $object->{$method}();
        }
        return null;
    }

    /**
     * Si es DateTimeInterface, lo devuelve en ISO-8601; si no, null.
     */
    private function formatIfDateTime($value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            // 'c' (DATE_ATOM) -> 2025-09-20T19:35:00+00:00
            return $value->format(DATE_ATOM);
        }
        return null;
    }

    /**
     * Genera un código aleatorio alfanumérico.
     */
    private function randomCode(int $len = 10): string
    {
        // seguro y simple para 7.4
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
        $out = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $len; $i++) {
            $out .= $chars[random_int(0, $max)];
        }
        return $out;
    }
}
