<?php
// src/Controller/CajasController.php

namespace App\Controller;

use App\Entity\Cajas;
use App\Repository\CajasRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CajasController extends AbstractController
{
    #[Route('/cajas', name: 'cajas_index')]
    public function index(CajasRepository $repository): Response
    {
        $cajas = $repository->findAllOrderedByCantidadPuros();
        
        return $this->render('cajas/index.html.twig', [
            'cajas' => $cajas,
        ]);
    }

    #[Route('/cajas/new', name: 'cajas_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $caja = new Cajas();
        
        if ($request->isMethod('POST')) {
            $caja->setCantPuros($request->request->get('cant_puros'));
            $caja->setEstilo($request->request->get('estilo'));
            $caja->setDetalleInt($request->request->get('detalle_int'));
            $caja->setDetalleExt($request->request->get('detalle_ext'));
            $caja->setColor($request->request->get('color'));
            $caja->setMadera($request->request->get('madera'));
            $caja->setTexto($request->request->get('texto'));
            
            $entityManager->persist($caja);
            $entityManager->flush();
            
            $this->addFlash('success', 'Caja creada correctamente.');
            return $this->redirectToRoute('cajas_index');
        }
        
        return $this->render('cajas/new.html.twig');
    }

    #[Route('/cajas/{id}/edit', name: 'cajas_edit')]
    public function edit(Cajas $caja, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $caja->setCantPuros($request->request->get('cant_puros'));
            $caja->setEstilo($request->request->get('estilo'));
            $caja->setDetalleInt($request->request->get('detalle_int'));
            $caja->setDetalleExt($request->request->get('detalle_ext'));
            $caja->setColor($request->request->get('color'));
            $caja->setMadera($request->request->get('madera'));
            $caja->setTexto($request->request->get('texto'));
            
            $entityManager->flush();
            
            $this->addFlash('success', 'Caja actualizada correctamente.');
            return $this->redirectToRoute('cajas_index');
        }
        
        return $this->render('cajas/edit.html.twig', [
            'caja' => $caja,
        ]);
    }

    #[Route('/cajas/{id}/delete', name: 'cajas_delete')]
    public function delete(Cajas $caja, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($caja);
        $entityManager->flush();
        
        $this->addFlash('success', 'Caja eliminada correctamente.');
        return $this->redirectToRoute('cajas_index');
    }

    #[Route('/cajas/search', name: 'cajas_search')]
    public function search(Request $request, CajasRepository $repository): Response
    {
        $searchTerm = $request->query->get('q', '');
        $cajas = [];
        
        if (!empty($searchTerm)) {
            $cajas = $repository->searchByText($searchTerm);
        }
        
        return $this->render('cajas/search.html.twig', [
            'cajas' => $cajas,
            'searchTerm' => $searchTerm,
        ]);
    }

    #[Route('/cajas/filter', name: 'cajas_filter')]
    public function filter(Request $request, CajasRepository $repository): Response
    {
        $estilo = $request->query->get('estilo');
        $madera = $request->query->get('madera');
        $color = $request->query->get('color');
        $minPuros = $request->query->get('min_puros');
        $maxPuros = $request->query->get('max_puros');
        
        $cajas = [];
        
        if ($estilo) {
            $cajas = $repository->findByEstilo($estilo);
        } elseif ($madera) {
            $cajas = $repository->findByMadera($madera);
        } elseif ($color) {
            $cajas = $repository->findByColor($color);
        } elseif ($minPuros && $maxPuros) {
            $cajas = $repository->findByCantidadPurosRange($minPuros, $maxPuros);
        }
        
        return $this->render('cajas/filter.html.twig', [
            'cajas' => $cajas,
            'filters' => [
                'estilo' => $estilo,
                'madera' => $madera,
                'color' => $color,
                'min_puros' => $minPuros,
                'max_puros' => $maxPuros,
            ]
        ]);
    }

    #[Route('/cajas/estadisticas', name: 'cajas_estadisticas')]
    public function estadisticas(CajasRepository $repository): Response
    {
        $estadisticasEstilo = $repository->getEstadisticasPorEstilo();
        $estadisticasMadera = $repository->getEstadisticasPorMadera();
        $totalCajas = $repository->countAll();
        
        return $this->render('cajas/estadisticas.html.twig', [
            'estadisticasEstilo' => $estadisticasEstilo,
            'estadisticasMadera' => $estadisticasMadera,
            'totalCajas' => $totalCajas,
        ]);
    }

    #[Route('/cajas/latest', name: 'cajas_latest')]
    public function latest(CajasRepository $repository): Response
    {
        $cajas = $repository->findLatest(10);
        
        return $this->render('cajas/latest.html.twig', [
            'cajas' => $cajas,
        ]);
    }
}