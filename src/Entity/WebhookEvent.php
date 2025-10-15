<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\WebhookEventRepository")
 * @ORM\Table(name="webhook_events")
 */
class WebhookEvent
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $stripeEventId;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $eventType;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private $apiVersion;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $objectId;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $livemode = false;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $processed = false;

    /**
     * @ORM\Column(type="integer", options={"default": 0})
     */
    private $processingAttempts = 0;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastProcessingAttempt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $processedAt;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $processingError;

    /**
     * @ORM\Column(type="text")
     */
    private $rawData;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStripeEventId(): ?string
    {
        return $this->stripeEventId;
    }

    public function setStripeEventId(string $stripeEventId): self
    {
        $this->stripeEventId = $stripeEventId;
        return $this;
    }

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): self
    {
        $this->eventType = $eventType;
        return $this;
    }

    public function getApiVersion(): ?string
    {
        return $this->apiVersion;
    }

    public function setApiVersion(?string $apiVersion): self
    {
        $this->apiVersion = $apiVersion;
        return $this;
    }

    public function getObjectId(): ?string
    {
        return $this->objectId;
    }

    public function setObjectId(?string $objectId): self
    {
        $this->objectId = $objectId;
        return $this;
    }

    public function getLivemode(): ?bool
    {
        return $this->livemode;
    }

    public function setLivemode(bool $livemode): self
    {
        $this->livemode = $livemode;
        return $this;
    }

    public function getProcessed(): ?bool
    {
        return $this->processed;
    }

    public function setProcessed(bool $processed): self
    {
        $this->processed = $processed;
        return $this;
    }

    public function getProcessingAttempts(): ?int
    {
        return $this->processingAttempts;
    }

    public function setProcessingAttempts(int $processingAttempts): self
    {
        $this->processingAttempts = $processingAttempts;
        return $this;
    }

    public function getLastProcessingAttempt(): ?\DateTime
    {
        return $this->lastProcessingAttempt;
    }

    public function setLastProcessingAttempt(?\DateTime $lastProcessingAttempt): self
    {
        $this->lastProcessingAttempt = $lastProcessingAttempt;
        return $this;
    }

    public function getProcessedAt(): ?\DateTime
    {
        return $this->processedAt;
    }

    public function setProcessedAt(?\DateTime $processedAt): self
    {
        $this->processedAt = $processedAt;
        return $this;
    }

    public function getProcessingError(): ?string
    {
        return $this->processingError;
    }

    public function setProcessingError(?string $processingError): self
    {
        $this->processingError = $processingError;
        return $this;
    }

    public function getRawData(): ?string
    {
        return $this->rawData;
    }

    public function setRawData(string $rawData): self
    {
        $this->rawData = $rawData;
        return $this;
    }

    // Alias para compatibilidad con WebhookController
    public function getEventData(): ?string
    {
        return $this->getRawData();
    }

    // Alias para compatibilidad con WebhookController
    public function setEventData(string $eventData): self
    {
        return $this->setRawData($eventData);
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
}