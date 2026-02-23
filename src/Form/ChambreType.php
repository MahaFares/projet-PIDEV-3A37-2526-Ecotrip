<?php

namespace App\Form;

use App\Entity\Chambre;
use App\Entity\Hebergement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChambreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('numero')
            ->add('type')
            ->add('capacite')
            ->add('prixParNuit')
            ->add('description')
            ->add('disponible')
            ->add('hebergement', EntityType::class , [
            'class' => Hebergement::class ,
            'choice_label' => 'nom',
        ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Chambre::class ,
        ]);
    }
}
