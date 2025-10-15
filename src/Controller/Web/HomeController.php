<?php

namespace App\Controller\Web;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'title' => 'Mokhuba Cigar Club',
            'subtitle' => 'El club de tabacos más exclusivo del mundo'
        ]);
    }

    /**
     * @Route("/about", name="about")
     */
    public function about(): Response
    {
        return $this->render('home/about.html.twig', [
            'title' => 'Acerca de Mokhuba'
        ]);
    }

    /**
     * @Route("/dashboard", name="dashboard")
     */
    public function dashboard(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('login');
        }

        return $this->render('dashboard/index.html.twig', [
            'user' => $user,
            'title' => 'Dashboard'
        ]);
    }

    /**
     * 

    

    /**
     * @Route("/invite/{token}", name="invite_accept")
     */
    public function inviteAccept(string $token): Response
    {
        // TODO: Validar token de invitación
        // Por ahora solo mostramos la página de registro
        
        return $this->render('auth/register.html.twig', [
            'title' => 'Registro - Mokhuba Club',
            'token' => $token
        ]);
    }

    /**
     * @Route("/api/status", name="api_status")
     */
    public function apiStatus(): Response
    {
        return $this->json([
            'status' => 'OK',
            'service' => 'Mokhuba Cigar Club API',
            'version' => '1.0.0',
            'timestamp' => (new \DateTime())->format('c'),
            'environment' => $this->getParameter('kernel.environment')
        ]);
    }
    public function beneficios(): Response
    {
        return $this->render('dashboard/beneficios.html.twig', [
            'currentUser' => $this->getUser(),
            'membershipLevel' => $this->getUser() ? $this->getUser()->getMembershipLevel() : 'guest'
    ]);
    }
}