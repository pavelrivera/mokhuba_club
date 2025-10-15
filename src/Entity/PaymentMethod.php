<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PaymentMethodRepository")
 * @ORM\Table(name="payment_methods")
 */
class PaymentMethod
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
    private $stripePaymentMethodId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $stripeCustomerId;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $type;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $isDefault = false;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $cardBrand;

    /**
     * @ORM\Column(type="string", length=4, nullable=true)
     */
    private $cardLast4;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $cardExpMonth;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $cardExpYear;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $cardFunding;

    /**
     * @ORM\Column(type="string", length=2, nullable=true)
     */
    private $cardCountry;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $cardFingerprint;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $billingName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $billingEmail;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $billingPhone;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $billingAddress;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $billingCity;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $billingState;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $billingPostalCode;

    /**
     * @ORM\Column(type="string", length=2, nullable=true)
     */
    private $billingCountry;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $bankAccountLast4;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $bankName;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $bankAccountType;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $bankRoutingNumber;

    /**
     * @ORM\Column(type="boolean", options={"default": true})
     */
    private $isActive = true;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $isVerified = false;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastUsedAt;

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

    public function getStripePaymentMethodId(): ?string
    {
        return $this->stripePaymentMethodId;
    }

    public function setStripePaymentMethodId(?string $stripePaymentMethodId): self
    {
        $this->stripePaymentMethodId = $stripePaymentMethodId;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getIsDefault(): ?bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): self
    {
        $this->isDefault = $isDefault;
        return $this;
    }

    public function getCardBrand(): ?string
    {
        return $this->cardBrand;
    }

    public function setCardBrand(?string $cardBrand): self
    {
        $this->cardBrand = $cardBrand;
        return $this;
    }

    public function getCardLast4(): ?string
    {
        return $this->cardLast4;
    }

    public function setCardLast4(?string $cardLast4): self
    {
        $this->cardLast4 = $cardLast4;
        return $this;
    }

    public function getCardExpMonth(): ?int
    {
        return $this->cardExpMonth;
    }

    public function setCardExpMonth(?int $cardExpMonth): self
    {
        $this->cardExpMonth = $cardExpMonth;
        return $this;
    }

    public function getCardExpYear(): ?int
    {
        return $this->cardExpYear;
    }

    public function setCardExpYear(?int $cardExpYear): self
    {
        $this->cardExpYear = $cardExpYear;
        return $this;
    }

    public function getCardFunding(): ?string
    {
        return $this->cardFunding;
    }

    public function setCardFunding(?string $cardFunding): self
    {
        $this->cardFunding = $cardFunding;
        return $this;
    }

    public function getCardCountry(): ?string
    {
        return $this->cardCountry;
    }

    public function setCardCountry(?string $cardCountry): self
    {
        $this->cardCountry = $cardCountry;
        return $this;
    }

    public function getCardFingerprint(): ?string
    {
        return $this->cardFingerprint;
    }

    public function setCardFingerprint(?string $cardFingerprint): self
    {
        $this->cardFingerprint = $cardFingerprint;
        return $this;
    }

    public function getBillingName(): ?string
    {
        return $this->billingName;
    }

    public function setBillingName(?string $billingName): self
    {
        $this->billingName = $billingName;
        return $this;
    }

    public function getBillingEmail(): ?string
    {
        return $this->billingEmail;
    }

    public function setBillingEmail(?string $billingEmail): self
    {
        $this->billingEmail = $billingEmail;
        return $this;
    }

    public function getBillingPhone(): ?string
    {
        return $this->billingPhone;
    }

    public function setBillingPhone(?string $billingPhone): self
    {
        $this->billingPhone = $billingPhone;
        return $this;
    }

    public function getBillingAddress(): ?string
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?string $billingAddress): self
    {
        $this->billingAddress = $billingAddress;
        return $this;
    }

    public function getBillingCity(): ?string
    {
        return $this->billingCity;
    }

    public function setBillingCity(?string $billingCity): self
    {
        $this->billingCity = $billingCity;
        return $this;
    }

    public function getBillingState(): ?string
    {
        return $this->billingState;
    }

    public function setBillingState(?string $billingState): self
    {
        $this->billingState = $billingState;
        return $this;
    }

    public function getBillingPostalCode(): ?string
    {
        return $this->billingPostalCode;
    }

    public function setBillingPostalCode(?string $billingPostalCode): self
    {
        $this->billingPostalCode = $billingPostalCode;
        return $this;
    }

    public function getBillingCountry(): ?string
    {
        return $this->billingCountry;
    }

    public function setBillingCountry(?string $billingCountry): self
    {
        $this->billingCountry = $billingCountry;
        return $this;
    }

    public function getBankAccountLast4(): ?string
    {
        return $this->bankAccountLast4;
    }

    public function setBankAccountLast4(?string $bankAccountLast4): self
    {
        $this->bankAccountLast4 = $bankAccountLast4;
        return $this;
    }

    public function getBankName(): ?string
    {
        return $this->bankName;
    }

    public function setBankName(?string $bankName): self
    {
        $this->bankName = $bankName;
        return $this;
    }

    public function getBankAccountType(): ?string
    {
        return $this->bankAccountType;
    }

    public function setBankAccountType(?string $bankAccountType): self
    {
        $this->bankAccountType = $bankAccountType;
        return $this;
    }

    public function getBankRoutingNumber(): ?string
    {
        return $this->bankRoutingNumber;
    }

    public function setBankRoutingNumber(?string $bankRoutingNumber): self
    {
        $this->bankRoutingNumber = $bankRoutingNumber;
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

    public function getIsVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getLastUsedAt(): ?\DateTime
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?\DateTime $lastUsedAt): self
    {
        $this->lastUsedAt = $lastUsedAt;
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
     * Obtiene la descripción del método de pago
     */
    public function getDisplayName(): string
    {
        if ($this->type === 'card') {
            $brand = ucfirst($this->cardBrand ?? 'Card');
            return "{$brand} ****{$this->cardLast4}";
        }

        if ($this->type === 'bank_account') {
            $bank = $this->bankName ? $this->bankName : 'Bank';
            return "{$bank} ****{$this->bankAccountLast4}";
        }

        return ucfirst($this->type);
    }

    /**
     * Verifica si la tarjeta está expirada
     */
    public function isExpired(): bool
    {
        if ($this->type !== 'card' || !$this->cardExpYear || !$this->cardExpMonth) {
            return false;
        }

        $now = new \DateTime();
        $expiry = \DateTime::createFromFormat('Y-m-d', $this->cardExpYear . '-' . $this->cardExpMonth . '-01');
        $expiry->modify('last day of this month');

        return $now > $expiry;
    }

    /**
     * Verifica si la tarjeta expira pronto
     */
    public function isExpiringSoon(int $months = 2): bool
    {
        if ($this->type !== 'card' || !$this->cardExpYear || !$this->cardExpMonth) {
            return false;
        }

        $now = new \DateTime();
        $expiry = \DateTime::createFromFormat('Y-m-d', $this->cardExpYear . '-' . $this->cardExpMonth . '-01');
        $expiry->modify('last day of this month');
        
        $thresholdDate = (clone $now)->modify("+{$months} months");

        return $expiry <= $thresholdDate && $expiry > $now;
    }

    /**
     * Obtiene la fecha de expiración formateada
     */
    public function getFormattedExpiry(): ?string
    {
        if ($this->type !== 'card' || !$this->cardExpYear || !$this->cardExpMonth) {
            return null;
        }

        return sprintf('%02d/%04d', $this->cardExpMonth, $this->cardExpYear);
    }

    /**
     * Obtiene la dirección de facturación completa
     */
    public function getFullBillingAddress(): string
    {
        $parts = array_filter([
            $this->billingAddress,
            $this->billingCity,
            $this->billingState,
            $this->billingPostalCode,
            $this->billingCountry
        ]);

        return implode(', ', $parts);
    }

    /**
     * Verifica si el método de pago es una tarjeta
     */
    public function isCard(): bool
    {
        return $this->type === 'card';
    }

    /**
     * Verifica si el método de pago es una cuenta bancaria
     */
    public function isBankAccount(): bool
    {
        return $this->type === 'bank_account';
    }

    /**
     * Obtiene el icono CSS basado en el tipo y marca
     */
    public function getIcon(): string
    {
        if ($this->type === 'card') {
            switch (strtolower($this->cardBrand ?? '')) {
                case 'visa':
                    return 'fab fa-cc-visa';
                case 'mastercard':
                    return 'fab fa-cc-mastercard';
                case 'american_express':
                case 'amex':
                    return 'fab fa-cc-amex';
                case 'discover':
                    return 'fab fa-cc-discover';
                case 'diners_club':
                case 'diners':
                    return 'fab fa-cc-diners-club';
                case 'jcb':
                    return 'fab fa-cc-jcb';
                case 'unionpay':
                    return 'fas fa-credit-card';
                default:
                    return 'fas fa-credit-card';
            }
        }

        if ($this->type === 'bank_account') {
            return 'fas fa-university';
        }

        return 'fas fa-payment';
    }
}