<?php

namespace App\Form;

use App\Entity\Reservation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Positive;

class ReservationEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateFrom', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('dateTo', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
            ])
            ->add('numberOfPersons', IntegerType::class, [
                'label' => 'Nombre de personnes',
                'constraints' => [new Positive(['message' => 'Le nombre doit être au moins 1.'])],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
        ]);
    }
}
