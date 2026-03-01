<?php

namespace App\Form;

use App\Entity\Chauffeur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChauffeurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Prénom'],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Nom'],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone',
                'attr' => ['class' => 'form-control', 'placeholder' => '+33 6 12 34 56 78'],
            ])
            ->add('licenseNumber', TextType::class, [
                'label' => 'Numéro de permis',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Numéro de permis'],
            ])
            ->add('experience', IntegerType::class, [
                'label' => 'Années d\'expérience',
                'attr' => ['class' => 'form-control', 'min' => 0, 'max' => 50],
            ])
            ->add('rating', NumberType::class, [
                'label' => 'Note (0-5)',
                'attr' => ['class' => 'form-control', 'step' => '0.1', 'min' => 0, 'max' => 5],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Chauffeur::class,
        ]);
    }
}
