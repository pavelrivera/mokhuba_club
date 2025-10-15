<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\InvoiceRepository")
 * @ORM\Table(name="invoices")
 */
class Invoice
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
     * @ORM\ManyToOne(targetEntity="App\Entity\Payment")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $payment;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $stripeInvoiceId;

    /**
     * @ORM\Column(type="string", length=50, unique=true)
     */
    private $invoiceNumber;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $status;

    /**
     * @ORM\Column(type="integer")
     */
    private $subtotal;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private $taxAmount = 0;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private $discountAmount = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $totalAmount;

    /**
     * @ORM\Column(type="integer")
     */
    private $amountDue;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private $amountPaid = 0;

    /**
     * @ORM\Column(type="string", length=3, options={"default": "USD"})
     */
    private $currency = 'EUR';

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $customerEmail;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $customerName;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $customerAddress;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $dueDate;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $paidAt;

    /**
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    private $invoiceUrl;

    /**
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    private $invoicePdf;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $attemptedCollection = false;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $collectionMethod;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $lineItems;

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

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function setPayment(?Payment $payment): self
    {
        $this->payment = $payment;
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

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(string $invoiceNumber): self
    {
        $this->invoiceNumber = $invoiceNumber;
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

    public function getSubtotal(): ?int
    {
        return $this->subtotal;
    }

    public function setSubtotal(int $subtotal): self
    {
        $this->subtotal = $subtotal;
        return $this;
    }

    public function getTaxAmount(): ?int
    {
        return $this->taxAmount;
    }

    public function setTaxAmount(int $taxAmount): self
    {
        $this->taxAmount = $taxAmount;
        return $this;
    }

    public function getDiscountAmount(): ?int
    {
        return $this->discountAmount;
    }

    public function setDiscountAmount(int $discountAmount): self
    {
        $this->discountAmount = $discountAmount;
        return $this;
    }

    public function getTotalAmount(): ?int
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(int $totalAmount): self
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }

    public function getAmountDue(): ?int
    {
        return $this->amountDue;
    }

    public function setAmountDue(int $amountDue): self
    {
        $this->amountDue = $amountDue;
        return $this;
    }

    public function getAmountPaid(): ?int
    {
        return $this->amountPaid;
    }

    public function setAmountPaid(int $amountPaid): self
    {
        $this->amountPaid = $amountPaid;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(?string $customerEmail): self
    {
        $this->customerEmail = $customerEmail;
        return $this;
    }

    public function getCustomerName(): ?string
    {
        return $this->customerName;
    }

    public function setCustomerName(?string $customerName): self
    {
        $this->customerName = $customerName;
        return $this;
    }

    public function getCustomerAddress(): ?string
    {
        return $this->customerAddress;
    }

    public function setCustomerAddress(?string $customerAddress): self
    {
        $this->customerAddress = $customerAddress;
        return $this;
    }

    public function getDueDate(): ?\DateTime
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTime $dueDate): self
    {
        $this->dueDate = $dueDate;
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

    public function getInvoiceUrl(): ?string
    {
        return $this->invoiceUrl;
    }

    public function setInvoiceUrl(?string $invoiceUrl): self
    {
        $this->invoiceUrl = $invoiceUrl;
        return $this;
    }

    public function getInvoicePdf(): ?string
    {
        return $this->invoicePdf;
    }

    public function setInvoicePdf(?string $invoicePdf): self
    {
        $this->invoicePdf = $invoicePdf;
        return $this;
    }

    public function getAttemptedCollection(): ?bool
    {
        return $this->attemptedCollection;
    }

    public function setAttemptedCollection(bool $attemptedCollection): self
    {
        $this->attemptedCollection = $attemptedCollection;
        return $this;
    }

    public function getCollectionMethod(): ?string
    {
        return $this->collectionMethod;
    }

    public function setCollectionMethod(?string $collectionMethod): self
    {
        $this->collectionMethod = $collectionMethod;
        return $this;
    }

    public function getLineItems(): ?string
    {
        return $this->lineItems;
    }

    public function setLineItems(?string $lineItems): self
    {
        $this->lineItems = $lineItems;
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

    // Helper methods

    /**
     * Verifica si la factura está pagada
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Verifica si la factura está vencida
     */
    public function isOverdue(): bool
    {
        return $this->status === 'open' && 
               $this->dueDate && 
               $this->dueDate < new \DateTime();
    }

    /**
     * Obtiene el monto total formateado
     */
    public function getFormattedTotal(): string
    {
        return '$' . number_format($this->totalAmount / 100, 2);
    }

    /**
     * Obtiene el monto pendiente formateado
     */
    public function getFormattedAmountDue(): string
    {
        return '$' . number_format($this->amountDue / 100, 2);
    }

    /**
     * Calcula los días hasta el vencimiento
     */
    public function getDaysUntilDue(): ?int
    {
        if (!$this->dueDate) {
            return null;
        }

        $now = new \DateTime();
        $interval = $now->diff($this->dueDate);
        
        return $interval->invert ? -$interval->days : $interval->days;
    }
}