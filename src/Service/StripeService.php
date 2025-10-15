<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Stripe\Customer;

/**
 * Servicio de Stripe para Mokhuba Club
 * 
 * Compatible con PHP 7.4.33
 * Maneja pagos en modo MOCK (desarrollo) y modo REAL (producción)
 */
class StripeService
{
    private $secretKey;
    private $publicKey;
    private $logger;
    private $mockMode;

    public function __construct(
        string $stripeSecretKey,
        string $stripePublicKey,
        LoggerInterface $logger
    ) {
        $this->secretKey = $stripeSecretKey;
        $this->publicKey = $stripePublicKey;
        $this->logger = $logger;
        
        // =====================================================
        // CORRECCIÓN CRÍTICA: Lógica del modo MOCK
        // =====================================================
        // El sistema entra en modo MOCK si:
        // 1. La clave secreta está vacía, O
        // 2. La clave es un placeholder, O  
        // 3. La clave NO empieza con sk_test_ ni sk_live_
        // =====================================================
        
        // PHP 7.4 compatible: usar strpos en lugar de str_starts_with
        $isTestKey = strpos($this->secretKey, 'sk_test_') === 0;
        $isLiveKey = strpos($this->secretKey, 'sk_live_') === 0;
        
        $this->mockMode = empty($this->secretKey) || 
                         $this->secretKey === 'your_stripe_secret_key_here' ||
                         (!$isTestKey && !$isLiveKey);

        // Si NO está en modo MOCK, configurar Stripe API
        if (!$this->mockMode) {
            Stripe::setApiKey($this->secretKey);
        }

        $this->logger->info('StripeService inicializado', [
            'mock_mode' => $this->mockMode,
            'has_secret_key' => !empty($this->secretKey),
            'key_prefix' => $this->mockMode ? 'mock' : substr($this->secretKey, 0, 7) . '...'
        ]);
    }

    /**
     * Verificar si está en modo MOCK
     * 
     * @return bool
     */
    public function isMockMode(): bool
    {
        return $this->mockMode;
    }

    /**
     * Obtener clave pública (para usar en frontend)
     * 
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * =====================================================
     * MÉTODO PRINCIPAL: Crear Checkout Session
     * =====================================================
     * Este método crea una sesión de Stripe Checkout que
     * redirige al usuario a la página de pago de Stripe
     * 
     * @param string $priceId - ID del precio de Stripe (price_...)
     * @param array $metadata - Datos adicionales (user_id, membership_type, etc)
     * @param string $customerEmail - Email del cliente
     * @param string $successUrl - URL de éxito
     * @param string $cancelUrl - URL de cancelación
     * @return Session|null - Sesión de checkout o null si falla
     */
    public function createCheckoutSessionWithPrice(
        string $priceId,
        array $metadata,
        string $customerEmail,
        string $successUrl,
        string $cancelUrl
    ): ?Session {
        
        // Si está en modo MOCK, retornar null
        if ($this->mockMode) {
            $this->logger->warning('🎭 MODO MOCK: No se puede crear checkout session real. Configura las claves de Stripe.');
            return null;
        }

        try {
            $this->logger->info('Creando Checkout Session', [
                'price_id' => $priceId,
                'customer_email' => $customerEmail,
                'metadata' => $metadata
            ]);

            // Crear la sesión de Checkout
            $session = Session::create([
                'mode' => 'subscription', // Modo suscripción
                'payment_method_types' => ['card'], // Solo tarjetas
                'line_items' => [[
                    'price' => $priceId, // Price ID de Stripe
                    'quantity' => 1,
                ]],
                'success_url' => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $cancelUrl,
                'customer_email' => $customerEmail,
                'metadata' => $metadata,
                'allow_promotion_codes' => true, // Permitir códigos promocionales
                'billing_address_collection' => 'required', // Requerir dirección
            ]);

            $this->logger->info('✅ Checkout Session creada exitosamente', [
                'session_id' => $session->id,
                'url' => $session->url
            ]);

            return $session;

        } catch (\Exception $e) {
            $this->logger->error('❌ Error creando Checkout Session', [
                'error' => $e->getMessage(),
                'price_id' => $priceId,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * =====================================================
     * MÉTODO ALTERNATIVO: Crear Payment Intent
     * =====================================================
     * Para implementaciones personalizadas con Stripe Elements
     * 
     * @param array $data
     * @return array
     */
    public function createPaymentIntent(array $data): array
    {
        try {
            // Modo MOCK para desarrollo
            if ($this->mockMode) {
                return $this->createMockPaymentIntent($data);
            }

            // Modo REAL con Stripe
            return $this->createRealPaymentIntent($data);

        } catch (\Exception $e) {
            $this->logger->error('Error creando Payment Intent', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Crear Payment Intent simulado (MOCK)
     */
    private function createMockPaymentIntent(array $data): array
    {
        $this->logger->info('🎭 MODO MOCK: Creando Payment Intent simulado', $data);

        // Generar IDs simulados
        $mockPaymentIntentId = 'pi_mock_' . bin2hex(random_bytes(12));

        $result = [
            'success' => true,
            'payment_intent_id' => $mockPaymentIntentId,
            'client_secret' => $mockPaymentIntentId . '_secret_' . bin2hex(random_bytes(16)),
            'amount' => $data['amount'] ?? 0,
            'currency' => $data['currency'] ?? 'usd',
            'status' => 'requires_payment_method',
            'mock' => true,
            'message' => 'Payment Intent simulado creado. Configura claves de Stripe para uso real.'
        ];

        $this->logger->info('✅ MOCK: Payment Intent simulado creado', $result);

        return $result;
    }

    /**
     * Crear Payment Intent real con Stripe
     */
    private function createRealPaymentIntent(array $data): array
    {
        $this->logger->info('💳 Creando Payment Intent REAL con Stripe', [
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'usd',
            'user_id' => $data['user_id'] ?? null
        ]);

        // Crear o recuperar cliente de Stripe
        $customer = $this->getOrCreateCustomer($data['email'], $data['user_id'] ?? null);

        // Crear PaymentIntent
        $paymentIntent = PaymentIntent::create([
            'amount' => $data['amount'] * 100, // Convertir a centavos
            'currency' => $data['currency'] ?? 'usd',
            'customer' => $customer->id,
            'description' => $data['description'] ?? 'Membresía Mokhuba Club',
            'metadata' => [
                'user_id' => $data['user_id'] ?? null,
                'membership_type' => $data['membership_type'] ?? null
            ],
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);

        $this->logger->info('✅ Payment Intent creado', [
            'payment_intent_id' => $paymentIntent->id,
            'status' => $paymentIntent->status
        ]);

        return [
            'success' => true,
            'payment_intent_id' => $paymentIntent->id,
            'client_secret' => $paymentIntent->client_secret,
            'status' => $paymentIntent->status,
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'usd',
            'mock' => false
        ];
    }

    /**
     * Obtener o crear cliente de Stripe
     * 
     * @param string $email
     * @param int|null $userId
     * @return Customer
     */
    private function getOrCreateCustomer(string $email, ?int $userId = null): Customer
    {
        // Buscar cliente existente
        $customers = Customer::all(['email' => $email, 'limit' => 1]);

        if (count($customers->data) > 0) {
            $this->logger->info('Cliente existente encontrado', [
                'customer_id' => $customers->data[0]->id,
                'email' => $email
            ]);
            return $customers->data[0];
        }

        // Crear nuevo cliente
        $customer = Customer::create([
            'email' => $email,
            'metadata' => [
                'user_id' => $userId
            ]
        ]);

        $this->logger->info('✅ Nuevo cliente creado en Stripe', [
            'customer_id' => $customer->id,
            'email' => $email
        ]);

        return $customer;
    }

    /**
     * Obtener detalles de un PaymentIntent
     * 
     * @param string $paymentIntentId
     * @return PaymentIntent|null
     */
    public function getPaymentIntent(string $paymentIntentId): ?PaymentIntent
    {
        if ($this->mockMode) {
            $this->logger->info('🎭 MOCK: Simulando recuperación de PaymentIntent');
            return null;
        }

        try {
            return PaymentIntent::retrieve($paymentIntentId);
        } catch (\Exception $e) {
            $this->logger->error('Error obteniendo PaymentIntent', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Obtener detalles de una Checkout Session
     * 
     * @param string $sessionId
     * @return Session|null
     */
    public function getCheckoutSession(string $sessionId): ?Session
    {
        if ($this->mockMode) {
            $this->logger->info('🎭 MOCK: Simulando recuperación de Checkout Session');
            return null;
        }

        try {
            return Session::retrieve($sessionId);
        } catch (\Exception $e) {
            $this->logger->error('Error obteniendo Checkout Session', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * =====================================================
     * MÉTODO LEGACY: Procesar pago (mantener por compatibilidad)
     * =====================================================
     * Este método se mantiene para no romper código existente
     * pero se recomienda usar createCheckoutSessionWithPrice()
     * 
     * @param array $data
     * @return array
     */
    public function processPayment(array $data): array
    {
        try {
            // Modo MOCK para desarrollo
            if ($this->mockMode) {
                return $this->processMockPayment($data);
            }

            // Modo REAL con Stripe
            return $this->processRealPayment($data);

        } catch (\Exception $e) {
            $this->logger->error('Error procesando pago', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Procesar pago simulado (MOCK)
     */
    private function processMockPayment(array $data): array
    {
        $this->logger->info('🎭 MODO MOCK: Procesando pago simulado', $data);

        // Validar datos básicos
        if (empty($data['amount'])) {
            return [
                'success' => false,
                'error' => 'Monto no especificado'
            ];
        }

        // Simular delay de procesamiento
        usleep(500000); // 0.5 segundos

        // Generar IDs simulados
        $mockPaymentIntentId = 'pi_mock_' . bin2hex(random_bytes(12));
        $mockSubscriptionId = 'sub_mock_' . bin2hex(random_bytes(12));

        $result = [
            'success' => true,
            'payment_intent_id' => $mockPaymentIntentId,
            'subscription_id' => $mockSubscriptionId,
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'usd',
            'status' => 'succeeded',
            'mock' => true,
            'message' => 'Pago simulado procesado exitosamente. Configura claves de Stripe para pagos reales.'
        ];

        $this->logger->info('✅ MOCK: Pago simulado exitoso', $result);

        return $result;
    }

    /**
     * Procesar pago real con Stripe
     */
    private function processRealPayment(array $data): array
    {
        $this->logger->info('💳 Procesando pago REAL con Stripe', [
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'usd',
            'user_id' => $data['user_id'] ?? null
        ]);

        // Crear o recuperar cliente de Stripe
        $customer = $this->getOrCreateCustomer($data['email'], $data['user_id'] ?? null);

        // Crear PaymentIntent
        $paymentIntent = PaymentIntent::create([
            'amount' => $data['amount'] * 100, // Convertir a centavos
            'currency' => $data['currency'] ?? 'usd',
            'customer' => $customer->id,
            'description' => 'Membresía ' . ucfirst($data['membership_type'] ?? 'club'),
            'metadata' => [
                'user_id' => $data['user_id'] ?? null,
                'membership_type' => $data['membership_type'] ?? null
            ]
        ]);

        $this->logger->info('✅ PaymentIntent creado', [
            'payment_intent_id' => $paymentIntent->id,
            'status' => $paymentIntent->status
        ]);

        return [
            'success' => true,
            'payment_intent_id' => $paymentIntent->id,
            'client_secret' => $paymentIntent->client_secret,
            'status' => $paymentIntent->status,
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'usd',
            'mock' => false
        ];
    }
}
