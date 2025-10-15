<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PaymentRepository")
 * @ORM\Table(name="payments")
 */
class Payment
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
     * @ORM\ManyToOne(targetEntity="App\Entity\Subscription")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $subscription;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $stripePaymentIntentId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $stripeInvoiceId;

    /**
     * @ORM\Column(type="integer")
     */
    private $amount;

    /**
     * @ORM\Column(type="string", length=3, options={"default": "EUR"})
     */
    private $currency = 'EUR';

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $paymentMethodType;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $failureReason;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $receiptEmail;

    /**
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    private $receiptUrl;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private $refundedAmount = 0;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $refunded = false;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $disputed = false;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $reconciled = false;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $metadata;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $stripeCreatedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $paidAt;

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

    public function getSubscription(): ?Subscription
    {
        return $this->subscription;
    }

    public function setSubscription(?Subscription $subscription): self
    {
        $this->subscription = $subscription;
        return $this;
    }

    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function setStripePaymentIntentId(?string $stripePaymentIntentId): self
    {
        $this->stripePaymentIntentId = $stripePaymentIntentId;
        return $this;
    }

    public function getStripeInvoiceId(): ?string
    {
        return $this->stripeInvoiceId;
    }

    public function setStripeInvoiceId(?string $stripeInvoiceId): self
    {
        $this->stripeInvoiceId = $stripeInvoiceId;
        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
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

    public function getPaymentMethodType(): ?string
    {
        return $this->paymentMethodType;
    }

    public function setPaymentMethodType(?string $paymentMethodType): self
    {
        $this->paymentMethodType = $paymentMethodType;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getFailureReason(): ?string
    {
        return $this->failureReason;
    }

    public function setFailureReason(?string $failureReason): self
    {
        $this->failureReason = $failureReason;
        return $this;
    }

    public function getReceiptEmail(): ?string
    {
        return $this->receiptEmail;
    }

    public function setReceiptEmail(?string $receiptEmail): self
    {
        $this->receiptEmail = $receiptEmail;
        return $this;
    }

    public function getReceiptUrl(): ?string
    {
        return $this->receiptUrl;
    }

    public function setReceiptUrl(?string $receiptUrl): self
    {
        $this->receiptUrl = $receiptUrl;
        return $this;
    }

    public function getRefundedAmount(): ?int
    {
        return $this->refundedAmount;
    }

    public function setRefundedAmount(int $refundedAmount): self
    {
        $this->refundedAmount = $refundedAmount;
        return $this;
    }

    public function getRefunded(): ?bool
    {
        return $this->refunded;
    }

    public function setRefunded(bool $refunded): self
    {
        $this->refunded = $refunded;
        return $this;
    }

    public function getDisputed(): ?bool
    {
        return $this->disputed;
    }

    public function setDisputed(bool $disputed): self
    {
        $this->disputed = $disputed;
        return $this;
    }

    public function getReconciled(): ?bool
    {
        return $this->reconciled;
    }

    public function setReconciled(bool $reconciled): self
    {
        $this->reconciled = $reconciled;
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

    public function getStripeCreatedAt(): ?\DateTime
    {
        return $this->stripeCreatedAt;
    }

    public function setStripeCreatedAt(?\DateTime $stripeCreatedAt): self
    {
        $this->stripeCreatedAt = $stripeCreatedAt;
        return $this;
    }

    public function getPaidAt(): ?\DateTime
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTime $paidAt): self
    {
        $this->paidAt = $paidAt;
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