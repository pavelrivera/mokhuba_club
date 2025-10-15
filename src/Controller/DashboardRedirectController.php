<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardRedirectController extends AbstractController
{
    /**
     * @Route("/dashboard", name="dashboard_redirect")
     */
    public function redirectToDashboard(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

        $membershipLevel = $user->getMembershipLevel();
        
        // Log para debugging
        error_log("🔀 DashboardRedirect: Usuario {$user->getEmail()} con membresía: " . ($membershipLevel ?? 'null'));
        
        // Redirigir según el tipo de membresía
        switch ($membershipLevel) {
            case 'admin':
                error_log("👑 Redirigiendo a admin dashboard");
                return $this->redirectToRoute('admin_dashboard');
            
            case 'ruby':
            case 'basica': // Por compatibilidad con datos existentes
                error_log("💎 Redirigiendo a ruby dashboard");
                return $this->redirectToRoute('ruby_dashboard');
            
            case 'gold':
            case 'premium': // Por compatibilidad con datos existentes
                error_log("🥇 Redirigiendo a gold dashboard");
                return $this->redirectToRoute('gold_dashboard');
            
            case 'platinum':
            case 'vip': // Por compatibilidad con datos existentes
                error_log("⭐ Redirigiendo a platinum dashboard");
                return $this->redirectToRoute('platinum_dashboard');
            
            case null:
            case '':
            case 'guest':
            default:
                error_log("❓ Membresía no asignada, redirigiendo a selección");
                return $this->redirectToRoute('membership_selection');
        }
    }

    /**
     * Ruta directa para home que también maneja la redirección
     * @Route("/home", name="home_dashboard")
     */
    public function homeDashboard(): Response
    {
        return $this->redirectToDashboard();
    }
}