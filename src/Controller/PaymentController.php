<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Entity\Subscription;
use App\Service\StripeService;
use App\Repository\UserRepository;
use App\Repository\SubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Entity\Membresia;


class PaymentController extends AbstractController
{
    private $entityManager;
    private $stripeService;
    private $logger;
    private $userRepository;
    private $subscriptionRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        StripeService $stripeService,
        LoggerInterface $logger,
        UserRepository $userRepository,
        SubscriptionRepository $subscriptionRepository
    ) {
        $this->entityManager = $entityManager;
        $this->stripeService = $stripeService;
        $this->logger = $logger;
        $this->userRepository = $userRepository;
        $this->subscriptionRepository = $subscriptionRepository;
    }

    /**
     * Página de selección de membresías
     * 
     * @Route("/payment/membership-selection", name="payment_membership_selection")
     */
    public function membershipSelection(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $memberships = [
            'ruby' => [
                'name' => 'Ruby Club',
                'price' => 299,
                'currency' => 'usd',
                'features' => [
                    'Acceso completo al Dashboard Ruby',
                    'Contenido exclusivo semanal',
                    'Soporte por email 24/7',
                    'Comunidad privada',
                    'Eventos mensuales'
                ],
                'color' => 'danger',
                'icon' => 'gem'
            ],
            'gold' => [
                'name' => 'Gold Club',
                'price' => 599,
                'currency' => 'usd',
                'features' => [
                    'Todo de Ruby +',
                    'Acceso Dashboard Gold',
                    'Contenido premium diario',
                    'Soporte prioritario 24/7',
                    'Eventos VIP semanales',
                    'Sesiones de coaching'
                ],
                'color' => 'warning',
                'icon' => 'medal'
            ],
            'platinum' => [
                'name' => 'Platinum Club',
                'price' => 999,
                'currency' => 'usd',
                'features' => [
                    'Todo de Gold +',
                    'Acceso Dashboard Platinum',
                    'Contenido VIP exclusivo',
                    'Conserje personal 24/7',
                    'Eventos ilimitados',
                    'Networking elite',
                    'Asesoría personalizada'
                ],
                'color' => 'info',
                'icon' => 'star'
            ]
        ];

        // Verificar si Stripe está configurado
        $stripeConfigured = !$this->stripeService->isMockMode();

        return $this->render('payment/membership_selection.html.twig', [
            'memberships' => $memberships,
            'stripe_configured' => $stripeConfigured,
            'stripe_public_key' => $this->stripeService->getPublicKey()
        ]);
    }

    /**
     * =====================================================
     * MÉTODO PRINCIPAL: Crear sesión de Stripe Checkout
     * =====================================================
     * Este método crea una sesión de Stripe Checkout y 
     * redirige al usuario a la página de pago de Stripe
     * 
     * @Route("/payment/create-checkout-session", name="payment_create_checkout", methods={"POST"})
     */
    public function createCheckoutSession(Request $request): Response
    {
        $em = $this->entityManager;
        $this->denyAccessUnlessGranted('ROLE_USER');

        try {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            
            // Obtener tipo de membresía del formulario
            $membershipType = $request->request->get('membershipType');
            
            $this->logger->info('Iniciando creación de Checkout Session', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'membership_type' => $membershipType
            ]);

            // Validar tipo de membresía
            if (!$membershipType || !in_array($membershipType, [1, 2, 3])) {
                throw new \Exception('Tipo de membresía inválido');
            }

            // Verificar que Stripe esté configurado
            if ($this->stripeService->isMockMode()) {
                $this->logger->warning('🎭 MODO MOCK DETECTADO - Redirigiendo a formulario de checkout simulado');
                
                // En modo MOCK, redirigir al formulario de checkout personalizado
                return $this->redirectToRoute('payment_checkout_form', [
                    'membershipType' => $membershipType
                ]);
            }

            // Obtener Price ID desde variables de entorno
            $priceIds = [
                'ruby' => $_ENV['RUBY_PRICE_ID'] ?? '',
                'gold' => $_ENV['GOLD_PRICE_ID'] ?? '',
                'platinum' => $_ENV['PLATINUM_PRICE_ID'] ?? ''
            ];

            $membership = $em->getRepository(Membresia::class)->find($membershipType);
            
            $priceId = $membership->getPrecio();

            // Validar que el Price ID existe
            if (empty($priceId)) {
                throw new \Exception('Price ID no configurado para ' . $membershipType . '. Verifica tu archivo .env');
            }

            // Preparar metadata
            $metadata = [
                'user_id' => (string) $user->getId(),
                'membership_type' => $membershipType,
                'email' => $user->getEmail(),
                'name' => $user->getFirstName() . ' ' . $user->getLastName()
            ];

            // URLs de éxito y cancelación (absolutas)
            $successUrl = $this->generateUrl(
                'payment_success', 
                ['membership' => $membershipType], 
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            
            $cancelUrl = $this->generateUrl(
                'payment_cancel', 
                [], 
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $this->logger->info('Llamando a Stripe para crear Checkout Session', [
                'price_id' => $priceId,
                'metadata' => $metadata,
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl
            ]);

            // Crear sesión de Stripe Checkout
            $session = $this->stripeService->createCheckoutSessionWithPrice(
                $priceId,
                $metadata,
                $user->getEmail(),
                $successUrl,
                $cancelUrl
            );

            // Verificar que se creó la sesión
            if (!$session) {
                throw new \Exception('No se pudo crear la sesión de pago. Verifica los logs para más detalles.');
            }

            $this->logger->info('Checkout Session creada exitosamente', [
                'session_id' => $session->id,
                'url' => $session->url
            ]);

            // Redirigir a Stripe Checkout
            return $this->redirect($session->url);

        } catch (\Exception $e) {
            $this->logger->error('Error creando Checkout Session', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->addFlash('error', 'Error al procesar el pago: ' . $e->getMessage());
            return $this->redirectToRoute('payment_membership_selection');
        }
    }

    /**
     * Mostrar formulario de checkout personalizado (modo MOCK y producción)
     * 
     * @Route("/payment/checkout/{membershipType}", name="payment_checkout_form")
     */
    
/* removed duplicate function: showCheckoutForm */


    /**
     * Procesar pago en modo MOCK (para pruebas locales)
     */
    private function processMockPayment($user, string $membershipType): Response
    {
        $em = $this->entityManager;
        try {
            $this->logger->info('🎭 PROCESANDO PAGO EN MODO MOCK', [
                'user_id' => $user->getId(),
                'membership_type' => $membershipType
            ]);

            // Configuración de precios
            $prices = [
                'ruby' => 299,
                'gold' => 599,
                'platinum' => 999
            ];

            $membership = $em->getRepository(Membresia::class)->find($membershipType);

            $amount = $membership->getPrecio();

            // Crear registro de pago simulado
            $payment = new Payment();
            $payment->setUser($user);
            $payment->setAmount($amount);
            $payment->setCurrency('usd');
            $payment->setStatus('succeeded');
            $payment->setStripePaymentIntentId('pi_mock_' . uniqid());
            $payment->setPaidAt(new \DateTime());
            $payment->setCreatedAt(new \DateTime());

            $this->entityManager->persist($payment);

            // Crear o actualizar suscripción
            $subscription = $this->subscriptionRepository->findOneBy(['user' => $user]);
            if (!$subscription) {
                $subscription = new Subscription();
                $subscription->setUser($user);
                $subscription->setCreatedAt(new \DateTime());
            }

            $subscription->setMembershipLevel($membershipType);
            $subscription->setStatus('active');
            $subscription->setStartDate(new \DateTime());
            $subscription->setEndDate(new \DateTime('+1 year'));
            $subscription->setCurrentPeriodStart(new \DateTime());
            $subscription->setCurrentPeriodEnd(new \DateTime('+1 month'));
            $subscription->setPriceAmount($amount);
            $subscription->setPriceCurrency('usd');
            $subscription->setStripeSubscriptionId('sub_mock_' . uniqid());
            $subscription->setUpdatedAt(new \DateTime());

            $this->entityManager->persist($subscription);

            // ✅ CRÍTICO: Actualizar membership level Y membership_end_date del usuario
            $user->setMembershipLevel($membershipType);
            $user->setMembershipEndDate(new \DateTime('+1 year'));

            // Actualizar roles del usuario
            $newRole = 'ROLE_' . strtoupper($membershipType);
            $user->setRoles(['ROLE_USER', $newRole]);

            $this->entityManager->flush();

            // 🔄 CRÍTICO: Refrescar el usuario en la sesión para que vea los cambios inmediatamente
            $this->entityManager->refresh($user);

            $this->logger->info('✅ PAGO MOCK PROCESADO EXITOSAMENTE', [
                'user_id' => $user->getId(),
                'payment_id' => $payment->getId(),
                'subscription_id' => $subscription->getId(),
                'membership_type' => $membershipType,
                'membership_level_updated' => $user->getMembershipLevel(),
                'membership_end_date' => $user->getMembershipEndDate()->format('Y-m-d H:i:s')
            ]);

            $this->addFlash('success', '¡Pago procesado exitosamente! Bienvenido a ' . ucfirst($membershipType) . ' Club.');

            return $this->redirectToRoute('dashboard');

        } catch (\Exception $e) {
            $this->logger->error('❌ Error en pago MOCK', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->addFlash('error', 'Error procesando el pago: ' . $e->getMessage());
            return $this->redirectToRoute('dashboard');
        }
    }

    /**
     * =====================================================
     * Página de éxito del pago
     * =====================================================
     * El usuario llega aquí después de completar el pago en Stripe
     * 
     * @Route("/payment/success", name="payment_success")
     */
    public function success(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $membership = $request->query->get('membership', 'ruby');
        $sessionId = $request->query->get('session_id');

        $this->logger->info('Usuario llegó a página de éxito', [
            'user_id' => $user->getId(),
            'membership' => $membership,
            'session_id' => $sessionId
        ]);

        // Si hay session_id, obtener detalles de la sesión
        $sessionDetails = null;
        if ($sessionId && !$this->stripeService->isMockMode()) {
            $sessionDetails = $this->stripeService->getCheckoutSession($sessionId);
            
            if ($sessionDetails) {
                $this->logger->info('Detalles de la sesión de checkout', [
                    'session_id' => $sessionDetails->id,
                    'payment_status' => $sessionDetails->payment_status,
                    'customer_email' => $sessionDetails->customer_email
                ]);

                // Si el pago fue exitoso, actualizar la membresía del usuario
                if ($sessionDetails->payment_status === 'paid') {
                    $this->activateUserMembership($user, $membership, $sessionDetails);
                }
            }
        }

        return $this->render('payment/success.html.twig', [
            'membership' => $membership,
            'session_id' => $sessionId,
            'session_details' => $sessionDetails
        ]);
    }

    /**
     * Activar membresía del usuario después de pago exitoso
     */
    private function activateUserMembership($user, string $membershipType, $sessionDetails): void
    {
        try {
            // Verificar si ya tiene una suscripción activa
            $subscription = $this->subscriptionRepository->findOneBy(['user' => $user]);
            
            if (!$subscription) {
                $subscription = new Subscription();
                $subscription->setUser($user);
                $subscription->setCreatedAt(new \DateTime());
            }

            // Actualizar datos de la suscripción
            $subscription->setMembershipLevel($membershipType);
            $subscription->setStatus('active');
            $subscription->setStartDate(new \DateTime());
            $subscription->setEndDate(new \DateTime('+1 year'));
            $subscription->setCurrentPeriodStart(new \DateTime());
            $subscription->setCurrentPeriodEnd(new \DateTime('+1 month'));
            
            // Establecer precio según tipo de membresía
            $prices = [
                'ruby' => 299,
                'gold' => 599,
                'platinum' => 999
            ];
            $subscription->setPriceAmount($prices[$membershipType] ?? 0);
            $subscription->setPriceCurrency('usd');
            
            if (isset($sessionDetails->subscription)) {
                $subscription->setStripeSubscriptionId($sessionDetails->subscription);
            }
            
            $subscription->setUpdatedAt(new \DateTime());

            // Actualizar rol del usuario
            $newRole = 'ROLE_' . strtoupper($membershipType);
            $user->setRoles(['ROLE_USER', $newRole]);
            $user->setMembershipType($membershipType);

            // Guardar cambios
            $this->entityManager->persist($subscription);
            $this->entityManager->flush();

            $this->logger->info('Membresía activada exitosamente', [
                'user_id' => $user->getId(),
                'membership_type' => $membershipType,
                'subscription_id' => $subscription->getId()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error activando membresía', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId(),
                'membership_type' => $membershipType
            ]);
        }
    }

    /**
     * Mostrar formulario de checkout
     * 
     * @Route("/payment/checkout/{membershipType}", name="payment_checkout_form")
     */
    public function showCheckoutForm(string $membershipType): Response
    {
        $em = $this->entityManager;
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Validar tipo de membresía
        if (!in_array($membershipType, [1,2,3])) {
            $this->addFlash('error', 'Tipo de membresía inválido');
            return $this->redirectToRoute('app_dashboard');
        }

        $membership = $em->getRepository(Membresia::class)->find($membershipType);
            
        $price = $membership->getPrecio();

        // Configuración de membresías
        $memberships = [
            1 => [
                'name' => 'Rubí',
                'price' => $price,
                'benefits' => [
                    'Acceso a eventos mensuales exclusivos',
                    'Compras prioritarias de ediciones limitadas',
                    'Regalo personalizado de cumpleaños',
                    'Acceso anticipado a nuevas vitolas'
                ]
            ],
            2 => [
                'name' => 'Oro',
                'price' => $price,
                'benefits' => [
                    'Todos los beneficios de Rubí',
                    '1 marca personalizada exclusiva',
                    'Hasta 2 líneas por marca',
                    '1 regalo premium adicional por año',
                    'Asesoría personalizada de productos'
                ]
            ],
            3 => [
                'name' => 'Platino',
                'price' => $price,
                'benefits' => [
                    'Todos los beneficios de Oro',
                    'Hasta 3 marcas personalizadas',
                    'Hasta 6 líneas por marca',
                    '2 acompañantes VIP a eventos',
                    'Regalo especial de aniversario',
                    'Concierge personal 24/7'
                ]
            ]
        ];

        $membership = $memberships[$membershipType];

        return $this->render('payment/checkout_form.html.twig', [
            'membership_type' => $membershipType,
            'membership_name' => $membership['name'],
            'membership_price' => $membership['price'],
            'membership_benefits' => $membership['benefits'],
            'mock_mode' => $this->stripeService->isMockMode()
        ]);
    }

    /**
     * Procesar checkout (desde formulario personalizado)
     * 
     * @Route("/payment/process-checkout", name="payment_process_checkout", methods={"POST"})
     */
    public function processCheckout(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        try {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            
            // Obtener datos del formulario
            $membershipType = $request->request->get('membershipType');
            $cardholderName = $request->request->get('cardholderName');
            $cardNumber = $request->request->get('cardNumber');
            $expiryDate = $request->request->get('expiryDate');
            $cvv = $request->request->get('cvv');
            $email = $request->request->get('email');

            $this->logger->info('Procesando checkout', [
                'user_id' => $user->getId(),
                'membership_type' => $membershipType,
                'email' => $email
            ]);

            // Validar datos
            if (!$membershipType || !in_array($membershipType, ['ruby', 'gold', 'platinum'])) {
                throw new \Exception('Tipo de membresía inválido');
            }

            // Configuración de precios
            $prices = [
                'ruby' => 299,
                'gold' => 599,
                'platinum' => 999
            ];

            $amount = $prices[$membershipType];

            // Procesar pago con StripeService (modo MOCK o real)
            $paymentResult = $this->stripeService->processPayment([
                'amount' => $amount,
                'currency' => 'usd',
                'membership_type' => $membershipType,
                'user_id' => $user->getId(),
                'email' => $email,
                'cardholder_name' => $cardholderName,
                'card_number' => $cardNumber,
                'expiry_date' => $expiryDate,
                'cvv' => $cvv
            ]);

            if (!$paymentResult['success']) {
                throw new \Exception($paymentResult['error'] ?? 'Error procesando el pago');
            }

            // Crear registro de pago
            $payment = new Payment();
            $payment->setUser($user);
            $payment->setAmount($amount);
            $payment->setCurrency('usd');
            $payment->setStatus('succeeded');
            $payment->setStripePaymentIntentId($paymentResult['payment_intent_id']);
            $payment->setPaidAt(new \DateTime());
            $payment->setCreatedAt(new \DateTime());

            $this->entityManager->persist($payment);

            // Crear o actualizar suscripción
            $subscription = $this->subscriptionRepository->findOneBy(['user' => $user]);
            if (!$subscription) {
                $subscription = new Subscription();
                $subscription->setUser($user);
                $subscription->setCreatedAt(new \DateTime());
            }

            $subscription->setMembershipLevel($membershipType);
            $subscription->setStatus('active');
            $subscription->setStartDate(new \DateTime());
            $subscription->setEndDate(new \DateTime('+1 year'));
            $subscription->setCurrentPeriodStart(new \DateTime());
            $subscription->setCurrentPeriodEnd(new \DateTime('+1 month'));
            $subscription->setPriceAmount($amount);
            $subscription->setPriceCurrency('usd');
            $subscription->setStripeSubscriptionId($paymentResult['subscription_id'] ?? null);
            $subscription->setUpdatedAt(new \DateTime());

            $this->entityManager->persist($subscription);

            // ✅ CRÍTICO: Actualizar membership level Y membership_end_date del usuario
            $user->setMembershipLevel($membershipType);
            $user->setMembershipEndDate(new \DateTime('+1 year'));

            // Actualizar roles del usuario
            $newRole = 'ROLE_' . strtoupper($membershipType);
            $user->setRoles(['ROLE_USER', $newRole]);
            if (method_exists($user, 'setMembershipType')) {
                $user->setMembershipType($membershipType);
            }

            $this->entityManager->flush();

            // 🔄 CRÍTICO: Refrescar el usuario en la sesión para que vea los cambios inmediatamente
            $this->entityManager->refresh($user);

            $this->logger->info('Pago procesado exitosamente', [
                'user_id' => $user->getId(),
                'payment_id' => $payment->getId(),
                'subscription_id' => $subscription->getId(),
                'membership_type' => $membershipType,
                'membership_level_updated' => $user->getMembershipLevel(),
                'membership_end_date' => ($user->getMembershipEndDate() ? $user->getMembershipEndDate()->format('Y-m-d H:i:s') : null)
            ]);

            $this->addFlash('success', '¡Pago procesado exitosamente! Bienvenido a ' . ucfirst($membershipType) . ' Club.');

            // ✅ CORRECCIÓN: Redirigir al dashboard en lugar de payment_success
            return $this->redirectToRoute('dashboard');

        } catch (\Exception $e) {
            $this->logger->error('Error procesando checkout', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->addFlash('error', 'Error procesando el pago: ' . $e->getMessage());

            return $this->redirectToRoute('payment_cancel');
        }
    }

    /**
     * Página de cancelación del pago
     * 
     * @Route("/payment/cancel", name="payment_cancel")
     */
    public function cancel(): Response
    {
        $this->logger->info('Usuario canceló el pago', [
            'user_id' => $this->getUser() ? $this->getUser()->getId() : null
        ]);

        return $this->render('payment/cancel.html.twig');

    }

    /**
     * =====================================================
     * MÉTODO LEGACY: Procesar pago (mantener por compatibilidad)
     * =====================================================
     * Este método se mantiene para no romper código existente
     * pero ya NO se usa. Usar createCheckoutSession() en su lugar.
     * 
     * @Route("/payment/process", name="payment_process", methods={"POST"})
     */
    public function processPayment(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        try {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            
            // Obtener datos del formulario
            $membershipType = $request->request->get('membershipType');
            $cardholderName = $request->request->get('cardholderName');
            $cardNumber = $request->request->get('cardNumber');
            $expiryDate = $request->request->get('expiryDate');
            $cvv = $request->request->get('cvv');
            $email = $request->request->get('email');

            $this->logger->info('Procesando pago (método legacy)', [
                'user_id' => $user->getId(),
                'membership_type' => $membershipType,
                'email' => $email
            ]);

            // Validar datos
            if (!$membershipType || !in_array($membershipType, ['ruby', 'gold', 'platinum'])) {
                throw new \Exception('Tipo de membresía inválido');
            }

            // Configuración de precios
            $prices = [
                'ruby' => 299,
                'gold' => 599,
                'platinum' => 999
            ];

            $amount = $prices[$membershipType];

            // Procesar pago con StripeService (modo MOCK o real)
            $paymentResult = $this->stripeService->processPayment([
                'amount' => $amount,
                'currency' => 'usd',
                'membership_type' => $membershipType,
                'user_id' => $user->getId(),
                'email' => $email,
                'cardholder_name' => $cardholderName,
                'card_number' => $cardNumber,
                'expiry_date' => $expiryDate,
                'cvv' => $cvv
            ]);

            if (!$paymentResult['success']) {
                throw new \Exception($paymentResult['error'] ?? 'Error procesando el pago');
            }

            // Crear registro de pago
            $payment = new Payment();
            $payment->setUser($user);
            $payment->setAmount($amount);
            $payment->setCurrency('usd');
            $payment->setStatus('succeeded');
            $payment->setStripePaymentIntentId($paymentResult['payment_intent_id']);
            $payment->setPaidAt(new \DateTime());
            $payment->setCreatedAt(new \DateTime());

            $this->entityManager->persist($payment);

            // Crear o actualizar suscripción
            $subscription = $this->subscriptionRepository->findOneBy(['user' => $user]);
            if (!$subscription) {
                $subscription = new Subscription();
                $subscription->setUser($user);
                $subscription->setCreatedAt(new \DateTime());
            }

            $subscription->setMembershipLevel($membershipType);
            $subscription->setStatus('active');
            $subscription->setStartDate(new \DateTime());
            $subscription->setEndDate(new \DateTime('+1 year'));
            $subscription->setCurrentPeriodStart(new \DateTime());
            $subscription->setCurrentPeriodEnd(new \DateTime('+1 month'));
            $subscription->setPriceAmount($amount);
            $subscription->setPriceCurrency('usd');
            $subscription->setStripeSubscriptionId($paymentResult['subscription_id'] ?? null);
            $subscription->setUpdatedAt(new \DateTime());

            $this->entityManager->persist($subscription);

            // ✅ CRÍTICO: Actualizar membership level Y membership_end_date del usuario
            $user->setMembershipLevel($membershipType);
            $user->setMembershipEndDate(new \DateTime('+1 year'));

            // Actualizar roles del usuario
            $newRole = 'ROLE_' . strtoupper($membershipType);
            $user->setRoles(['ROLE_USER', $newRole]);
            if (method_exists($user, 'setMembershipType')) {
                $user->setMembershipType($membershipType);
            }

            $this->entityManager->flush();

            // 🔄 CRÍTICO: Refrescar el usuario en la sesión para que vea los cambios inmediatamente
            $this->entityManager->refresh($user);

            $this->logger->info('Pago procesado exitosamente (método legacy)', [
                'user_id' => $user->getId(),
                'payment_id' => $payment->getId(),
                'subscription_id' => $subscription->getId(),
                'membership_type' => $membershipType,
                'membership_level_updated' => $user->getMembershipLevel(),
                'membership_end_date' => ($user->getMembershipEndDate() ? $user->getMembershipEndDate()->format('Y-m-d H:i:s') : null)
            ]);

            $this->addFlash('success', '¡Pago procesado exitosamente! Bienvenido a ' . ucfirst($membershipType) . ' Club.');

            // ✅ CORRECCIÓN: Redirigir al dashboard en lugar de payment_success
            return $this->redirectToRoute('dashboard');

        } catch (\Exception $e) {
            $this->logger->error('Error procesando pago (método legacy)', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->addFlash('error', 'Error procesando el pago: ' . $e->getMessage());

            return $this->redirectToRoute('payment_cancel');
        }
    }
}