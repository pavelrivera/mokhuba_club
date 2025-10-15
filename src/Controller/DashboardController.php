<?php

namespace App\Controller;

use App\Entity\Subscription;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/dashboard")
 */
class DashboardController extends AbstractController
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Dashboard principal - redirige según membresía
     * 
     * @Route("", name="dashboard")
     */
    public function index(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Verificar si el usuario tiene membresía activa
        $hasMembership = $this->hasActiveMembership($user);

        if (!$hasMembership) {
            // Sin membresía: Mostrar selección (Imagen 1 - Nueva Vista)
            return $this->render('dashboard/membership-selection-new.html.twig', [
                'user' => $user,
                'stripe_key' => $_ENV['STRIPE_PUBLIC_KEY'] ?? ''
            ]);
        }

        // Con membresía: Mostrar dashboard completo del club (Imagen 2 - Nueva Vista)
        return $this->render('dashboard/home-club.html.twig', [
            'user' => $user,
            'membership' => $user->getMembershipLevel()
        ]);
    }

    /**
     * Dashboard Ruby
     * 
     * @Route("/ruby", name="dashboard_ruby")
     */
    public function ruby(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Verificar suscripción activa
        if (!$this->hasActiveSubscription($user, 'ruby')) {
            $this->addFlash('warning', 'Necesitas una membresía Ruby activa para acceder a este dashboard.');
            return $this->redirectToRoute('dashboard');
        }

        return $this->render('dashboard/ruby/index.html.twig', [
            'subscription' => $this->getSubscription($user, 'ruby')
        ]);
    }

    /**
     * Dashboard Gold
     * 
     * @Route("/gold", name="dashboard_gold")
     */
    public function gold(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Verificar suscripción activa
        if (!$this->hasActiveSubscription($user, 'gold')) {
            $this->addFlash('warning', 'Necesitas una membresía Gold activa para acceder a este dashboard.');
            return $this->redirectToRoute('dashboard');
        }

        return $this->render('dashboard/gold/index.html.twig', [
            'subscription' => $this->getSubscription($user, 'gold')
        ]);
    }

    /**
     * Dashboard Platinum
     * 
     * @Route("/platinum", name="dashboard_platinum")
     */
    public function platinum(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Verificar suscripción activa
        if (!$this->hasActiveSubscription($user, 'platinum')) {
            $this->addFlash('warning', 'Necesitas una membresía Platinum activa para acceder a este dashboard.');
            return $this->redirectToRoute('dashboard');
        }

        return $this->render('dashboard/platinum/index.html.twig', [
            'subscription' => $this->getSubscription($user, 'platinum')
        ]);
    }

    /**
     * Verificar si el usuario tiene CUALQUIER membresía activa
     */
    private function hasActiveMembership($user): bool
    {
        // Verificar por membership level del usuario
        $membershipLevel = $user->getMembershipLevel();
        
        if ($membershipLevel && in_array($membershipLevel, ['ruby', 'gold', 'platinum'])) {
            $endDate = $user->getMembershipEndDate();
            if ($endDate && $endDate > new \DateTime()) {
                return true;
            }
        }

        // Verificar por suscripción en BD
        $subscription = $this->entityManager->getRepository(Subscription::class)
            ->findOneBy([
                'user' => $user,
                'status' => 'active'
            ]);

        if ($subscription && $subscription->getEndDate() > new \DateTime()) {
            return true;
        }

        return false;
    }

    /**
     * Verificar si el usuario tiene una suscripción activa de un nivel específico
     */
    private function hasActiveSubscription($user, string $level): bool
    {
        // Verificar por membership level del usuario
        if ($user->getMembershipLevel() === $level) {
            $endDate = $user->getMembershipEndDate();
            if ($endDate && $endDate > new \DateTime()) {
                return true;
            }
        }

        // Verificar por suscripción en BD
        $subscription = $this->entityManager->getRepository(Subscription::class)
            ->findOneBy([
                'user' => $user,
                'membershipLevel' => $level,
                'status' => 'active'
            ]);

        if ($subscription && $subscription->getEndDate() > new \DateTime()) {
            return true;
        }

        return false;
    }

    /**
     * Obtener la suscripción activa del usuario
     */
    private function getSubscription($user, string $level): ?Subscription
    {
        return $this->entityManager->getRepository(Subscription::class)
            ->findOneBy([
                'user' => $user,
                'membershipLevel' => $level,
                'status' => 'active'
            ]);
    }
}