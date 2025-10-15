<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileEditType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;


use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
/**
 * @Route("/profile")
 */
class ProfileController extends AbstractController
{
    private $entityManager;
    private $slugger;

    public function __construct(EntityManagerInterface $entityManager, SluggerInterface $slugger)
    {
        $this->entityManager = $entityManager;
        $this->slugger = $slugger;
    }

    /**
     * Muestra y procesa el formulario de edición de perfil
     * 
     * @Route("/edit", name="profile_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user) {
            $this->addFlash('error', 'Debes iniciar sesión para editar tu perfil');
            return $this->redirectToRoute('auth_login');
        }

        error_log("📝 ProfileController::edit() - Usuario: " . $user->getEmail());

        // Crear el formulario
        $form = $this->createForm(ProfileEditType::class, $user);
        
        // Pre-llenar el campo de preferencias de tabaco (es JSON en BD, texto en form)
        if ($user->getTobaccoPreferences()) {
            $preferencesText = is_array($user->getTobaccoPreferences()) 
                ? implode("\n", $user->getTobaccoPreferences())
                : $user->getTobaccoPreferences();
            $form->get('tobaccoPreferencesText')->setData($preferencesText);
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Manejar la foto de perfil si se subió una nueva
                $photoFile = $form->get('photoFile')->getData();
                
                if ($photoFile) {
                    $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $this->slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                    try {
                        $photoFile->move(
                            $this->getParameter('kernel.project_dir') . '/public/uploads/profiles',
                            $newFilename
                        );
                        
                        // Eliminar la foto anterior si existe
                        if ($user->getPhotoPath()) {
                            $oldPhotoPath = $this->getParameter('kernel.project_dir') . '/public' . $user->getPhotoPath();
                            if (file_exists($oldPhotoPath) && is_file($oldPhotoPath)) {
                                unlink($oldPhotoPath);
                            }
                        }
                        
                        $user->setPhotoPath('/uploads/profiles/' . $newFilename);
                        error_log("✅ Foto de perfil actualizada: " . $newFilename);
                        
                    } catch (FileException $e) {
                        error_log("❌ Error al subir foto: " . $e->getMessage());
                        $this->addFlash('warning', 'Hubo un problema al subir la foto, pero los demás cambios se guardaron correctamente.');
                    }
                }

                // Procesar preferencias de tabaco (convertir texto a array)
                $preferencesText = $form->get('tobaccoPreferencesText')->getData();
                if ($preferencesText) {
                    $preferencesArray = array_filter(
                        array_map('trim', explode("\n", $preferencesText))
                    );
                    $user->setTobaccoPreferences($preferencesArray);
                } else {
                    $user->setTobaccoPreferences([]);
                }

                // Actualizar la fecha de modificación
                $user->setUpdatedAt(new \DateTime());

                // IMPORTANTE: NO permitir cambiar el uniqueCode
                // Symfony ya maneja esto automáticamente porque no está en el formulario

                // Guardar en base de datos
                $this->entityManager->flush();

                error_log("✅ Perfil actualizado exitosamente para usuario: " . $user->getEmail());
                
                $this->addFlash('success', '¡Tu perfil ha sido actualizado exitosamente!');
                
                // Redirigir de vuelta al formulario de edición
                return $this->redirectToRoute('dashboard');
                
            } catch (\Exception $e) {
                error_log("❌ Error al actualizar perfil: " . $e->getMessage());
                error_log($e->getTraceAsString());
                
                $this->addFlash('error', 'Hubo un error al actualizar tu perfil. Por favor intenta nuevamente.');
            }
        }

        return $this->render('profile/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Ver perfil del usuario actual
     * 
     * @Route("/view", name="profile_view", methods={"GET"})
     */
    public function view(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user) {
            $this->addFlash('error', 'Debes iniciar sesión para ver tu perfil');
            return $this->redirectToRoute('auth_login');
        }

        return $this->render('profile/view.html.twig', [
            'user' => $user,
        ]);
    }
}