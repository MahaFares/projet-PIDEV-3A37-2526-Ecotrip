<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Range;

class TransportRecommendationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('origin', TextType::class, [
                'label' => 'Origin',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('destination', TextType::class, [
                'label' => 'Destination',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('passengers', IntegerType::class, [
                'label' => 'Number of passengers',
                'constraints' => [
                    new NotBlank(),
                    new Range(min: 1, max: 500),
                ],
            ])
            ->add('budgetMin', NumberType::class, [
                'label' => 'Budget Min (per person)',
                'required' => false,
                'scale' => 2,
                'constraints' => [
                    new PositiveOrZero(),
                ],
            ])
            ->add('budgetMax', NumberType::class, [
                'label' => 'Budget Max (per person)',
                'required' => false,
                'scale' => 2,
                'constraints' => [
                    new PositiveOrZero(),
                ],
            ])
            ->add('preference', ChoiceType::class, [
                'label' => 'Preference',
                'choices' => [
                    'Eco-friendly' => 'eco-friendly',
                    'Fastest' => 'fastest',
                    'Cheapest' => 'cheapest',
                ],
                'constraints' => [
                    new NotBlank(),
                    new Choice(['eco-friendly', 'fastest', 'cheapest']),
                ],
            ])
            ->add('comfortLevel', ChoiceType::class, [
                'label' => 'Travel comfort level',
                'choices' => [
                    'Basic' => 'basic',
                    'Standard' => 'standard',
                    'Premium' => 'premium',
                ],
                'constraints' => [
                    new NotBlank(),
                    new Choice(['basic', 'standard', 'premium']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'data_class' => null,
        ]);
    }
}
