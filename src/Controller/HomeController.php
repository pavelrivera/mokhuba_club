<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

class HomeController extends AbstractController
{
    /**
     * Página principal (/)
     * @Route("/", name="home")
     */
    public function index(SessionInterface $session, EntityManagerInterface $em): Response
    {
        $memberCount = $this->getMemberCountSafe($em);

        return $this->render('home/home.html.twig', [
            'user'         => $session->get('user'),
            'memberCount'  => $memberCount,   // <- evita el error en Twig
        ]);
    }

    /**
     * Dashboard (/dashboard)
     * @Route("/dashboard", name="dashboard")
     */
    public function dashboard(SessionInterface $session, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('auth_login');
        }

        // Verificar si el usuario tiene membresía activa
        $hasMembership = $this->hasActiveMembership($user);

        if (!$hasMembership) {
            // Sin membresía: Mostrar selección
            return $this->render('dashboard/membership-selection-new.html.twig', [
                'user' => $user,
                'stripe_key' => $_ENV['STRIPE_PUBLIC_KEY'] ?? ''
            ]);
        }

        // Con membresía: Mostrar dashboard del club
        return $this->render('dashboard/home-club.html.twig', [
            'user' => $user,
            'membership' => $user->getMembershipLevel()
        ]);
    }

    /**
     * Verificar si el usuario tiene membresía activa
     */
    private function hasActiveMembership($user): bool
    {
        $membershipLevel = $user->getMembershipLevel();
        
        if ($membershipLevel && in_array($membershipLevel, ['ruby', 'gold', 'platinum'])) {
            $endDate = $user->getMembershipEndDate();
            if ($endDate && $endDate > new \DateTime()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Página de beneficios (/beneficios) 
     * @Route("/beneficios", name="beneficios")
     */
    public function beneficios(): Response
    {
        return $this->render('dashboard/beneficios.html.twig', [
            'currentUser' => null, 
            'membershipLevel' => 'guest'
        ]);
    }

    /**
     * Obtiene el número de miembros de forma segura.
     * - Si existe la entidad App\Entity\User, intenta contarla.
     * - Si no existe o hay cualquier error, devuelve 0 para no romper producción.
     */
    private function getMemberCountSafe(EntityManagerInterface $em): int
    {
        try {
            // Ajusta el FQCN de tu entidad si no es App\Entity\User
            if (class_exists(\App\Entity\User::class)) {
                $dql = 'SELECT COUNT(u.id) FROM App\Entity\User u';
                $count = (int) $em->createQuery($dql)->getSingleScalarResult();
                return $count;
            }
        } catch (\Throwable $e) {
            // Log opcional: $this->get('logger')->error($e->getMessage());
        }

        return 0;
    }
}