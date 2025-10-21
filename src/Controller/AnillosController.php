<?php
// src/Controller/AnillosController.php

namespace App\Controller;

use App\Entity\Anillos;
use App\Entity\Documento;
use App\Repository\AnillosRepository;
use App\Repository\DocumentoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AnillosController extends AbstractController
{
    #[Route('/anillos', name: 'anillos_index')]
    public function index(AnillosRepository $repository): Response
    {
        $anillos = $repository->findAllOrderedByCantidad();
        
        return $this->render('anillos/index.html.twig', [
            'anillos' => $anillos,
        ]);
    }

    #[Route('/anillos/new', name: 'anillos_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, DocumentoRepository $documentoRepository): Response
    {
        $anillo = new Anillos();
        
        if ($request->isMethod('POST')) {
            $anillo->setCantidad($request->request->get('cantidad'));
            $anillo->setForma($request->request->get('forma'));
            $anillo->setTexto($request->request->get('texto'));
            $anillo->setColor($request->request->get('color'));
            $anillo->setColorBordes($request->request->get('color_bordes'));
            
            // Manejar la imagen si se proporciona
            $imagenId = $request->request->get('imagen');
            if ($imagenId) {
                $imagen = $documentoRepository->find($imagenId);
                $anillo->setImagen($imagen);
            }
            
            $entityManager->persist($anillo);
            $entityManager->flush();
            
            $this->addFlash('success', 'Anillo creado correctamente.');
            return $this->redirectToRoute('anillos_index');
        }
        
        $imagenes = $documentoRepository->findAll();
        
        return $this->render('anillos/new.html.twig', [
            'imagenes' => $imagenes,
        ]);
    }

    #[Route('/anillos/{id}/edit', name: 'anillos_edit')]
    public function edit(Anillos $anillo, Request $request, EntityManagerInterface $entityManager, DocumentoRepository $documentoRepository): Response
    {
        if ($request->isMethod('POST')) {
            $anillo->setCantidad($request->request->get('cantidad'));
            $anillo->setForma($request->request->get('forma'));
            $anillo->setTexto($request->request->get('texto'));
            $anillo->setColor($request->request->get('color'));
            $anillo->setColorBordes($request->request->get('color_bordes'));
            
            // Manejar la imagen si se proporciona
            $imagenId = $request->request->get('imagen');
            if ($imagenId) {
                $imagen = $documentoRepository->find($imagenId);
                $anillo->setImagen($imagen);
            } else {
                $anillo->setImagen(null);
            }
            
            $entityManager->flush();
            
            $this->addFlash('success', 'Anillo actualizado correctamente.');
            return $this->redirectToRoute('anillos_index');
        }
        
        $imagenes = $documentoRepository->findAll();
        
        return $this->render('anillos/edit.html.twig', [
            'anillo' => $anillo,
            'imagenes' => $imagenes,
        ]);
    }

    #[Route('/anillos/{id}/delete', name: 'anillos_delete')]
    public function delete(Anillos $anillo, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($anillo);
        $entityManager->flush();
        
        $this->addFlash('success', 'Anillo eliminado correctamente.');
        return $this->redirectToRoute('anillos_index');
    }

    #[Route('/anillos/search', name: 'anillos_search')]
    public function search(Request $request, AnillosRepository $repository): Response
    {
        $searchTerm = $request->query->get('q', '');
        $anillos = [];
        
        if (!empty($searchTerm)) {
            $anillos = $repository->searchByText($searchTerm);
        }
        
        return $this->render('anillos/search.html.twig', [
            'anillos' => $anillos,
            'searchTerm' => $searchTerm,
        ]);
    }

    #[Route('/anillos/filter', name: 'anillos_filter')]
    public function filter(Request $request, AnillosRepository $repository): Response
    {
        $forma = $request->query->get('forma');
        $color = $request->query->get('color');
        $colorBordes = $request->query->get('color_bordes');
        $minCantidad = $request->query->get('min_cantidad');
        $maxCantidad = $request->query->get('max_cantidad');
        $conImagen = $request->query->get('con_imagen');
        
        $anillos = [];
        
        if ($forma) {
            $anillos = $repository->findByForma($forma);
        } elseif ($color) {
            $anillos = $repository->findByColor($color);
        } elseif ($colorBordes) {
            $anillos = $repository->findByColorBordes($colorBordes);
        } elseif ($minCantidad && $maxCantidad) {
            $anillos = $repository->findByCantidadRange($minCantidad, $maxCantidad);
        } elseif ($conImagen === '1') {
            $anillos = $repository->findWithImages();
        } elseif ($conImagen === '0') {
            $anillos = $repository->findWithoutImages();
        }
        
        return $this->render('anillos/filter.html.twig', [
            'anillos' => $anillos,
            'filters' => [
                'forma' => $forma,
                'color' => $color,
                'color_bordes' => $colorBordes,
                'min_cantidad' => $minCantidad,
                'max_cantidad' => $maxCantidad,
                'con_imagen' => $conImagen,
            ]
        ]);
    }

    #[Route('/anillos/estadisticas', name: 'anillos_estadisticas')]
    public function estadisticas(AnillosRepository $repository): Response
    {
        $estadisticasForma = $repository->getEstadisticasPorForma();
        $estadisticasColor = $repository->getEstadisticasPorColor();
        $totalAnillos = $repository->countAll();
        $conColoresCoincidentes = $repository->findWithMatchingColors();
        
        return $this->render('anillos/estadisticas.html.twig', [
            'estadisticasForma' => $estadisticasForma,
            'estadisticasColor' => $estadisticasColor,
            'totalAnillos' => $totalAnillos,
            'conColoresCoincidentes' => count($conColoresCoincidentes),
        ]);
    }

    #[Route('/anillos/latest', name: 'anillos_latest')]
    public function latest(AnillosRepository $repository): Response
    {
        $anillos = $repository->findLatest(10);
        
        return $this->render('anillos/latest.html.twig', [
            'anillos' => $anillos,
        ]);
    }
}