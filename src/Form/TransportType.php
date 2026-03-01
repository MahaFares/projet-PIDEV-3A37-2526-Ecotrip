<?php

namespace App\Form;

use App\Entity\Transport;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', TextType::class, [
                'label' => 'Type de transport',
                'attr' => ['class' => 'form-input', 'placeholder' => 'Ex: Bus, Minivan, Voiture'],
            ])
            ->add('capacite', IntegerType::class, [
                'label' => 'Capacité (passagers)',
                'attr' => ['class' => 'form-input', 'placeholder' => 'Ex: 8', 'min' => 1],
            ])
            ->add('emissionco2', NumberType::class, [
                'label' => 'Émission CO2 (kg)',
                'attr' => ['class' => 'form-input', 'placeholder' => 'Ex: 50.5', 'step' => '0.01'],
                'scale' => 2,
            ])
            ->add('prixparpersonne', NumberType::class, [
                'label' => 'Prix par personne (DT)',
                'attr' => ['class' => 'form-input', 'placeholder' => 'Ex: 25.50', 'step' => '0.01'],
                'scale' => 2,
            ])
            ->add('disponible', CheckboxType::class, [
                'label' => 'Disponible',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
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
