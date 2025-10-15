<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\File;

class ProfileEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Nombre',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ingresa tu nombre'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'El nombre es obligatorio'
                    ]),
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'El nombre no puede tener más de {{ limit }} caracteres'
                    ])
                ]
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Apellido',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ingresa tu apellido'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'El apellido es obligatorio'
                    ]),
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'El apellido no puede tener más de {{ limit }} caracteres'
                    ])
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Correo Electrónico',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'tu@email.com'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'El correo electrónico es obligatorio'
                    ]),
                    new Assert\Email([
                        'message' => 'Por favor ingresa un correo electrónico válido'
                    ])
                ]
            ])
            ->add('phone', TelType::class, [
                'label' => 'Teléfono',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '+1234567890'
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 20,
                        'maxMessage' => 'El teléfono no puede tener más de {{ limit }} caracteres'
                    ])
                ]
            ])
            ->add('photoFile', FileType::class, [
                'label' => 'Foto de Perfil',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/*'
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Por favor sube una imagen válida (JPG, PNG, GIF o WebP)',
                        'maxSizeMessage' => 'La imagen no puede pesar más de 5MB'
                    ])
                ],
                'help' => 'Formatos permitidos: JPG, PNG, GIF, WebP. Tamaño máximo: 5MB'
            ])
            ->add('tobaccoPreferencesText', TextareaType::class, [
                'label' => 'Preferencias de Tabaco',
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Describe tus preferencias de tabaco (tipo, sabor, marca, etc.)'
                ],
                'help' => 'Cuéntanos sobre tus gustos y preferencias en tabacos'
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Guardar Cambios',
                'attr' => [
                    'class' => 'btn btn-primary btn-lg w-100 premium-button'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}