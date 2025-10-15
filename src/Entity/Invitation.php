<?php

namespace App\Entity;

use App\Repository\InvitationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InvitationRepository::class)]
#[ORM\Table(name: 'invitations')]
class Invitation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180)]
    private string $email;

    #[ORM\Column(type: 'string', length: 64, unique: true)]
    private string $token;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'pending'; // pending, accepted, expired, cancelled

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $acceptedAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $invitedByUserId = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function getToken(): string { return $this->token; }
    public function setToken(string $token): self { $this->token = $token; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $dt): self { $this->createdAt = $dt; return $this; }

    public function getExpiresAt(): ?\DateTimeImmutable { return $this->expiresAt; }
    public function setExpiresAt(?\DateTimeImmutable $dt): self { $this->expiresAt = $dt; return $this; }

    public function getAcceptedAt(): ?\DateTimeImmutable { return $this->acceptedAt; }
    public function setAcceptedAt(?\DateTimeImmutable $dt): self { $this->acceptedAt = $dt; return $this; }

    public function getInvitedByUserId(): ?int { return $this->invitedByUserId; }
    public function setInvitedByUserId(?int $id): self { $this->invitedByUserId = $id; return $this; }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt < new \DateTimeImmutable();
    }
}
