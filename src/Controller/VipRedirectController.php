<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class VipRedirectController extends AbstractController
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @Route("/dashboard/vip", name="vip_dashboard")
     */
    public function vip(): Response
    {
        $routes = $this->router->getRouteCollection();

        if ($routes->get('dashboard_platinum')) {
            return $this->redirectToRoute('dashboard_platinum');
        }
        if ($routes->get('dashboard')) {
            return $this->redirectToRoute('dashboard');
        }
        return $this->redirect('/');
    }
}