<?php
namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MembershipDashboardController extends AbstractController
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @Route("/dashboard/ruby", name="ruby_dashboard")
     */
    public function rubyDashboard(): Response
    {
        $user = $this->getUser();
        
        error_log("💎 RubyDashboard: Usuario " . ($user ? $user->getEmail() : 'null') . " con membresía " . ($user ? $user->getMembershipLevel() : 'null'));
        
        if (!$user) {
            error_log("❌ Usuario no autenticado en ruby dashboard");
            return $this->redirectToRoute('auth_login');
        }

        // Permitir acceso a usuarios con membresía ruby, basica, o admins
        $allowedMemberships = ['ruby', 'basica', 'admin'];
        if (!in_array($user->getMembershipLevel(), $allowedMemberships)) {
            error_log("🚫 Usuario con membresía '{$user->getMembershipLevel()}' intentando acceder a ruby dashboard");
            return $this->redirectToRoute('dashboard_redirect');
        }

        $stats = $this->getRubyStats($user);
        $benefits = $this->getRubyBenefits();

        error_log("✅ Renderizando ruby dashboard con " . count($benefits) . " beneficios");

        return $this->render('dashboard/ruby/index.html.twig', [
            'user' => $user,
            'membershipLevel' => 'ruby',
            'membershipName' => 'Rubí',
            'benefits' => $benefits,
            'stats' => $stats
        ]);
    }

    /**
     * @Route("/dashboard/gold", name="gold_dashboard")
     */
    public function goldDashboard(): Response
    {
        $user = $this->getUser();
        
        error_log("🥇 GoldDashboard: Usuario " . ($user ? $user->getEmail() : 'null') . " con membresía " . ($user ? $user->getMembershipLevel() : 'null'));
        
        if (!$user) {
            error_log("❌ Usuario no autenticado en gold dashboard");
            return $this->redirectToRoute('auth_login');
        }

        // Permitir acceso a usuarios con membresía gold, premium, o admins
        $allowedMemberships = ['gold', 'premium', 'admin'];
        if (!in_array($user->getMembershipLevel(), $allowedMemberships)) {
            error_log("🚫 Usuario con membresía '{$user->getMembershipLevel()}' intentando acceder a gold dashboard");
            return $this->redirectToRoute('dashboard_redirect');
        }

        $stats = $this->getGoldStats($user);
        $benefits = $this->getGoldBenefits();

        error_log("✅ Renderizando gold dashboard con " . count($benefits) . " beneficios");

        return $this->render('dashboard/gold/index.html.twig', [
            'user' => $user,
            'membershipLevel' => 'gold',
            'membershipName' => 'Oro',
            'benefits' => $benefits,
            'stats' => $stats
        ]);
    }

    /**
     * @Route("/dashboard/platinum", name="platinum_dashboard")
     */
    public function platinumDashboard(): Response
    {
        $user = $this->getUser();
        
        error_log("⭐ PlatinumDashboard: Usuario " . ($user ? $user->getEmail() : 'null') . " con membresía " . ($user ? $user->getMembershipLevel() : 'null'));
        
        if (!$user) {
            error_log("❌ Usuario no autenticado en platinum dashboard");
            return $this->redirectToRoute('auth_login');
        }

        // Permitir acceso a usuarios con membresía platinum, vip, o admins
        $allowedMemberships = ['platinum', 'vip', 'admin'];
        if (!in_array($user->getMembershipLevel(), $allowedMemberships)) {
            error_log("🚫 Usuario con membresía '{$user->getMembershipLevel()}' intentando acceder a platinum dashboard");
            return $this->redirectToRoute('dashboard_redirect');
        }

        $stats = $this->getPlatinumStats($user);
        $benefits = $this->getPlatinumBenefits();

        error_log("✅ Renderizando platinum dashboard con " . count($benefits) . " beneficios");

        return $this->render('dashboard/platinum/index.html.twig', [
            'user' => $user,
            'membershipLevel' => 'platinum',
            'membershipName' => 'Platino',
            'benefits' => $benefits,
            'stats' => $stats
        ]);
    }

    /**
     * @Route("/dashboard/membership-selection", name="membership_selection")
     */
    public function membershipSelection(): Response
    {
        $user = $this->getUser();
        
        error_log("❓ MembershipSelection: Usuario " . ($user ? $user->getEmail() : 'null'));
        
        if (!$user) {
            error_log("❌ Usuario no autenticado en membership selection");
            return $this->redirectToRoute('auth_login');
        }

        // Si el usuario ya tiene una membresía asignada, redirigir a su dashboard
        $membershipLevel = $user->getMembershipLevel();
        if (!empty($membershipLevel) && $membershipLevel !== 'guest') {
            error_log("↩️ Usuario ya tiene membresía '{$membershipLevel}', redirigiendo a su dashboard");
            return $this->redirectToRoute('dashboard_redirect');
        }

        $membershipPlans = $this->getMembershipPlans();

        error_log("✅ Renderizando página de selección de membresía");

        return $this->render('dashboard/membership-selection.html.twig', [
            'user' => $user,
            'membershipPlans' => $membershipPlans
        ]);
    }

    // =====================================================
    // MÉTODOS PRIVADOS PARA BENEFICIOS Y ESTADÍSTICAS
    // =====================================================

    private function getRubyBenefits(): array
    {
        return [
            [
                'icon' => 'fas fa-calendar-alt',
                'title' => 'Acceso a Eventos',
                'description' => 'Invitaciones a eventos mensuales exclusivos con degustaciones premium'
            ],
            [
                'icon' => 'fas fa-shopping-cart',
                'title' => 'Compras Exclusivas',
                'description' => 'Acceso prioritario a productos de edición limitada antes que el público general'
            ],
            [
                'icon' => 'fas fa-gift',
                'title' => 'Regalo de Cumpleaños',
                'description' => 'Selección especial de tabacos premium en tu día especial'
            ],
            [
                'icon' => 'fas fa-leaf',
                'title' => 'Nuevas Vitolas',
                'description' => 'Primero en probar y adquirir nuevos productos antes de su lanzamiento oficial'
            ]
        ];
    }

    private function getGoldBenefits(): array
    {
        return array_merge($this->getRubyBenefits(), [
            [
                'icon' => 'fas fa-palette',
                'title' => 'Marca Personalizada',
                'description' => '1 marca propia totalmente personalizada con tu nombre y diseño exclusivo'
            ],
            [
                'icon' => 'fas fa-layer-group',
                'title' => 'Líneas Exclusivas',
                'description' => 'Hasta 2 líneas diferentes para tu marca personalizada con mezclas únicas'
            ],
            [
                'icon' => 'fas fa-star',
                'title' => 'Regalo Adicional',
                'description' => '1 regalo especial adicional por año con productos de alta gama'
            ]
        ]);
    }

    private function getPlatinumBenefits(): array
    {
        return array_merge($this->getGoldBenefits(), [
            [
                'icon' => 'fas fa-crown',
                'title' => '3 Marcas Premium',
                'description' => 'Hasta 3 marcas diferentes con 6 líneas cada una - tu propio imperio tabacalero'
            ],
            [
                'icon' => 'fas fa-users',
                'title' => '2 Acompañantes VIP',
                'description' => 'Trae hasta 2 invitados especiales a todos los eventos exclusivos del club'
            ],
            [
                'icon' => 'fas fa-heart',
                'title' => 'Regalo de Aniversario',
                'description' => 'Celebración especial de tu aniversario como miembro con productos únicos'
            ],
            [
                'icon' => 'fas fa-concierge-bell',
                'title' => 'Concierge Personal',
                'description' => 'Servicio de concierge 24/7 para cualquier necesidad relacionada con el club'
            ]
        ]);
    }

    private function getRubyStats($user): array
    {
        return [
            'events_attended' => 0, // TODO: Implementar cuando tengamos sistema de eventos
            'purchases_made' => 0,  // TODO: Implementar cuando tengamos sistema de pedidos
            'since_member' => $user->getCreatedAt() ? $user->getCreatedAt()->format('M Y') : 'N/A',
            'next_event' => 'Cata Mensual - 25 Oct',
            'membership_level' => 'ruby'
        ];
    }

    private function getGoldStats($user): array
    {
        $rubyStats = $this->getRubyStats($user);
        return array_merge($rubyStats, [
            'brands_created' => 0, // TODO: Implementar cuando tengamos sistema de marcas
            'lines_available' => 2,
            'brands_limit' => 1,
            'membership_level' => 'gold'
        ]);
    }

    private function getPlatinumStats($user): array
    {
        $goldStats = $this->getGoldStats($user);
        return array_merge($goldStats, [
            'brands_limit' => 3,
            'lines_available' => 6,
            'guests_allowed' => 2,
            'membership_level' => 'platinum'
        ]);
    }

    private function getMembershipPlans(): array
    {
        return [
            [
                'level' => 'ruby',
                'name' => 'Rubí',
                'price' => 299,
                'icon' => 'fas fa-gem',
                'color' => '#e74c3c',
                'features' => [
                    'Acceso a eventos mensuales exclusivos',
                    'Compras prioritarias de ediciones limitadas',
                    'Regalo personalizado de cumpleaños',
                    'Acceso anticipado a nuevas vitolas'
                ]
            ],
            [
                'level' => 'gold',
                'name' => 'Oro',
                'price' => 599,
                'icon' => 'fas fa-medal',
                'color' => '#f39c12',
                'popular' => true,
                'features' => [
                    'Todos los beneficios de Rubí',
                    '1 marca personalizada exclusiva',
                    'Hasta 2 líneas por marca',
                    '1 regalo premium adicional por año',
                    'Asesoría personalizada de productos'
                ]
            ],
            [
                'level' => 'platinum',
                'name' => 'Platino',
                'price' => 999,
                'icon' => 'fas fa-star',
                'color' => '#95a5a6',
                'features' => [
                    'Todos los beneficios de Oro',
                    'Hasta 3 marcas personalizadas',
                    'Hasta 6 líneas por marca',
                    '2 acompañantes VIP a eventos',
                    'Regalo especial de aniversario',
                    'Concierge personal 24/7',
                    'Acceso a eventos ultra-exclusivos'
                ]
            ]
        ];
    }

    /**
     * Método auxiliar para verificar si el usuario puede acceder a un nivel específico
     */
    private function canAccessMembershipLevel($user, $requiredLevel): bool
    {
        if (!$user) {
            return false;
        }

        $userLevel = $user->getMembershipLevel();
        
        // Los admins pueden acceder a cualquier nivel
        if ($userLevel === 'admin') {
            return true;
        }

        // Mapeo de niveles para compatibilidad
        $membershipMap = [
            'ruby' => ['ruby', 'basica'],
            'gold' => ['gold', 'premium'],
            'platinum' => ['platinum', 'vip']
        ];

        return isset($membershipMap[$requiredLevel]) && 
               in_array($userLevel, $membershipMap[$requiredLevel]);
    }

    /**
     * Debug method - puede removerse en producción
     */
    public function debugMembership(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new Response('No user logged in');
        }

        $debug = [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'membership_level' => $user->getMembershipLevel(),
            'is_active' => $user->getIsActive(),
            'roles' => $user->getRoles(),
            'created_at' => $user->getCreatedAt() ? $user->getCreatedAt()->format('Y-m-d H:i:s') : 'null'
        ];

        return new Response('<pre>' . print_r($debug, true) . '</pre>');
    }
}