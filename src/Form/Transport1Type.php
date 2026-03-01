<?php

namespace App\Form;

use App\Entity\Chauffeur;
use App\Entity\Transport;
use App\Entity\TransportCategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class Transport1Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = isset($options['data']) && $options['data']->getId();

        $builder
            ->add('category', EntityType::class, [
                'class' => TransportCategory::class,
                'choice_label' => 'name',
                'label' => 'Catégorie',
                'placeholder' => 'Choisir une catégorie',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('chauffeur', EntityType::class, [
                'class' => Chauffeur::class,
                'choice_label' => function (Chauffeur $c) {
                    return $c->getFullName() . ' (' . $c->getLicenseNumber() . ')';
                },
                'label' => 'Chauffeur',
                'placeholder' => 'Choisir un chauffeur',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('type', TextType::class, [
                'label' => 'Type de Transport',
                'attr' => ['placeholder' => 'Ex: Bus, Voiture, Train'],
            ])
            ->add('capacite', IntegerType::class, [
                'label' => 'Capacité (passagers)',
                'attr' => ['placeholder' => 'Nombre de passagers', 'min' => 1],
            ])
            ->add('emissionco2', NumberType::class, [
                'label' => 'Émission CO2 (kg)',
                'attr' => ['placeholder' => '50.5', 'step' => '0.01'],
                'scale' => 2,
            ])
            ->add('prixparpersonne', NumberType::class, [
                'label' => 'Prix par Personne (DT)',
                'attr' => ['placeholder' => '25.50 DT', 'step' => '0.01'],
                'scale' => 2,
            ])
            ->add( 'image', FileType::class, [
                'label' => $isEdit ? 'Nouvelle Image' : 'Image du Transport',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPEG, PNG, GIF, WebP)',
                    ])
                ],
            ])
            ->add('disponible', CheckboxType::class, [
                'label' => 'Disponible',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transport::class,
        ]);
    }
}

