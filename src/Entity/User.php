<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table(name="users")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $phone;

    /**
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    private $photoPath;

    /**
     * @ORM\Column(type="json")
     */
    private $tobaccoPreferences = [];

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $emailVerifiedAt;

    /**
     * @ORM\Column(type="string", length=20, unique=true)
     */
    private $uniqueCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $stripeCustomerId;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $membershipLevel;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $membershipEndDate;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isActive = true;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getUsername(): string
    {
        return (string) $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials()
    {
        // Si almacenas algún dato temporal sensible del usuario, límpialo aquí
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getPhotoPath(): ?string
    {
        return $this->photoPath;
    }

    public function setPhotoPath(?string $photoPath): self
    {
        $this->photoPath = $photoPath;
        return $this;
    }

    public function getTobaccoPreferences(): array
    {
        return $this->tobaccoPreferences;
    }

    public function setTobaccoPreferences(array $tobaccoPreferences): self
    {
        $this->tobaccoPreferences = $tobaccoPreferences;
        return $this;
    }

    public function getEmailVerifiedAt(): ?\DateTime
    {
        return $this->emailVerifiedAt;
    }

    public function setEmailVerifiedAt(?\DateTime $emailVerifiedAt): self
    {
        $this->emailVerifiedAt = $emailVerifiedAt;
        return $this;
    }

    public function getUniqueCode(): ?string
    {
        return $this->uniqueCode;
    }

    public function setUniqueCode(string $uniqueCode): self
    {
        $this->uniqueCode = $uniqueCode;
        return $this;
    }

    public function getStripeCustomerId(): ?string
    {
        return $this->stripeCustomerId;
    }

    public function setStripeCustomerId(?string $stripeCustomerId): self
    {
        $this->stripeCustomerId = $stripeCustomerId;
        return $this;
    }

    public function getMembershipLevel(): ?string
    {
        return $this->membershipLevel;
    }

    public function setMembershipLevel(?string $membershipLevel): self
    {
        $this->membershipLevel = $membershipLevel;
        return $this;
    }

    public function getMembershipEndDate(): ?\DateTime
    {
        return $this->membershipEndDate;
    }

    public function setMembershipEndDate(?\DateTime $membershipEndDate): self
    {
        $this->membershipEndDate = $membershipEndDate;
        return $this;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    // Métodos de ayuda para verificar roles y membresías
    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->getRoles()) || $this->membershipLevel === 'admin';
    }

    public function getMembershipDisplayName(): string
    {
        switch ($this->membershipLevel) {
            case 'admin':
                return 'Administrador';
            case 'ruby':
                return 'Rubí';
            case 'gold':
                return 'Oro';
            case 'platinum':
                return 'Platino';
            default:
                return 'Sin membresía';
        }
    }

    public function getMembershipIcon(): string
    {
        switch ($this->membershipLevel) {
            case 'admin':
                return 'fas fa-crown';
            case 'ruby':
                return 'fas fa-gem';
            case 'gold':
                return 'fas fa-medal';
            case 'platinum':
                return 'fas fa-star';
            default:
                return 'fas fa-user';
        }
    }

    public function getMembershipColor(): string
    {
        switch ($this->membershipLevel) {
            case 'admin':
                return '#6f42c1'; // púrpura
            case 'ruby':
                return '#e74c3c'; // rojo
            case 'gold':
                return '#f39c12'; // dorado
            case 'platinum':
                return '#95a5a6'; // plateado
            default:
                return '#6c757d'; // gris
        }
    }

    /**
     * Verifica si el usuario tiene una membresía activa
     */
    public function isMembershipActive(): bool
    {
        if (!$this->membershipLevel || $this->membershipLevel === 'guest') {
            return false;
        }

        if ($this->membershipLevel === 'admin') {
            return true; // Los admins siempre tienen membresía activa
        }

        if (!$this->membershipEndDate) {
            return false;
        }

        return $this->membershipEndDate > new \DateTime();
    }
    
}