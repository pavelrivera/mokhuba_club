<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SubscriptionRepository")
 * @ORM\Table(name="subscriptions")
 */
class Subscription
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $stripeSubscriptionId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $stripeCustomerId;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $membershipLevel;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $status;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $startDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $endDate;

    /**
     * @ORM\Column(type="datetime")
     */
    private $currentPeriodStart;

    /**
     * @ORM\Column(type="datetime")
     */
    private $currentPeriodEnd;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $cancelAtPeriodEnd = false;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $canceledAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $cancelledAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $trialStart;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $trialEnd;

    /**
     * @ORM\Column(type="integer")
     */
    private $priceAmount;

    /**
     * @ORM\Column(type="string", length=3, options={"default": "EUR"})
     */
    private $priceCurrency = 'EUR';

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $metadata;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastStripeSync;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getStripeSubscriptionId(): ?string
    {
        return $this->stripeSubscriptionId;
    }

    public function setStripeSubscriptionId(?string $stripeSubscriptionId): self
    {
        $this->stripeSubscriptionId = $stripeSubscriptionId;
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

    public function setMembershipLevel(string $membershipLevel): self
    {
        $this->membershipLevel = $membershipLevel;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTime $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTime $endDate): self
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getCurrentPeriodStart(): ?\DateTime
    {
        return $this->currentPeriodStart;
    }

    public function setCurrentPeriodStart(\DateTime $currentPeriodStart): self
    {
        $this->currentPeriodStart = $currentPeriodStart;
        return $this;
    }

    public function getCurrentPeriodEnd(): ?\DateTime
    {
        return $this->currentPeriodEnd;
    }

    public function setCurrentPeriodEnd(\DateTime $currentPeriodEnd): self
    {
        $this->currentPeriodEnd = $currentPeriodEnd;
        return $this;
    }

    public function getCancelAtPeriodEnd(): ?bool
    {
        return $this->cancelAtPeriodEnd;
    }

    public function setCancelAtPeriodEnd(bool $cancelAtPeriodEnd): self
    {
        $this->cancelAtPeriodEnd = $cancelAtPeriodEnd;
        return $this;
    }

    public function getCanceledAt(): ?\DateTime
    {
        return $this->canceledAt;
    }

    public function setCanceledAt(?\DateTime $canceledAt): self
    {
        $this->canceledAt = $canceledAt;
        // Mantener sincronizados ambos campos
        $this->cancelledAt = $canceledAt;
        return $this;
    }

    public function getCancelledAt(): ?\DateTime
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?\DateTime $cancelledAt): self
    {
        $this->cancelledAt = $cancelledAt;
        // Mantener sincronizados ambos campos
        $this->canceledAt = $cancelledAt;
        return $this;
    }

    public function getTrialStart(): ?\DateTime
    {
        return $this->trialStart;
    }

    public function setTrialStart(?\DateTime $trialStart): self
    {
        $this->trialStart = $trialStart;
        return $this;
    }

    public function getTrialEnd(): ?\DateTime
    {
        return $this->trialEnd;
    }

    public function setTrialEnd(?\DateTime $trialEnd): self
    {
        $this->trialEnd = $trialEnd;
        return $this;
    }

    public function getPriceAmount(): ?int
    {
        return $this->priceAmount;
    }

    public function setPriceAmount(int $priceAmount): self
    {
        $this->priceAmount = $priceAmount;
        return $this;
    }

    public function getPriceCurrency(): ?string
    {
        return $this->priceCurrency;
    }

    public function setPriceCurrency(string $priceCurrency): self
    {
        $this->priceCurrency = $priceCurrency;
        return $this;
    }

    public function getMetadata(): ?string
    {
        return $this->metadata;
    }

    public function setMetadata(?string $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getLastStripeSync(): ?\DateTime
    {
        return $this->lastStripeSync;
    }

    public function setLastStripeSync(?\DateTime $lastStripeSync): self
    {
        $this->lastStripeSync = $lastStripeSync;
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
}