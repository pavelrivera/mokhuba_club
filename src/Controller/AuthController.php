<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthController extends AbstractController
{
    /**
     * @Route("/login", name="auth_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si ya está logueado, redirigir según su rol
        if ($this->getUser()) {
            // AQUÍ ESTABA EL ERROR - VERIFICAR ROL ANTES DE REDIRIGIR
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin_dashboard');
            } else {
                return $this->redirectToRoute('dashboard'); // Dashboard normal de usuario
            }
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('auth/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    /**
     * @Route("/logout", name="auth_logout")
     */
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}