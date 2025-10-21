<?php
// src/Controller/VitolariosController.php

namespace App\Controller;

use App\Entity\Vitolarios;
use App\Repository\VitolariosRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VitolariosController extends AbstractController
{
    #[Route('/vitolarios', name: 'vitolarios_index')]
    public function index(VitolariosRepository $repository): Response
    {
        $vitolarios = $repository->findAllOrderedByNombre();
        
        return $this->render('vitolarios/index.html.twig', [
            'vitolarios' => $vitolarios,
        ]);
    }

    #[Route('/vitolarios/new', name: 'vitolarios_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $vitolario = new Vitolarios();
        
        if ($request->isMethod('POST')) {
            $vitolario->setNombre($request->request->get('nombre'));
            $vitolario->setCepo($request->request->get('cepo'));
            $vitolario->setDiametro($request->request->get('diametro'));
            $vitolario->setLargo($request->request->get('largo'));
            $vitolario->setFortaleza($request->request->get('fortaleza'));
            
            $entityManager->persist($vitolario);
            $entityManager->flush();
            
            return $this->redirectToRoute('vitolarios_index');
        }
        
        return $this->render('vitolarios/new.html.twig');
    }

    #[Route('/vitolarios/{id}/edit', name: 'vitolarios_edit')]
    public function edit(Vitolarios $vitolario, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $vitolario->setNombre($request->request->get('nombre'));
            $vitolario->setCepo($request->request->get('cepo'));
            $vitolario->setDiametro($request->request->get('diametro'));
            $vitolario->setLargo($request->request->get('largo'));
            $vitolario->setFortaleza($request->request->get('fortaleza'));
            
            $entityManager->flush();
            
            return $this->redirectToRoute('vitolarios_index');
        }
        
        return $this->render('vitolarios/edit.html.twig', [
            'vitolario' => $vitolario,
        ]);
    }

    #[Route('/vitolarios/{id}/delete', name: 'vitolarios_delete')]
    public function delete(Vitolarios $vitolario, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($vitolario);
        $entityManager->flush();
        
        $this->addFlash('success', 'Vitolario eliminado correctamente.');
        
        return $this->redirectToRoute('vitolarios_index');
    }
}