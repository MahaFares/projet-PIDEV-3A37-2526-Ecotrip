<?php

namespace App\Form;

use App\Entity\Activity;
use App\Entity\ActivityCategory;
use App\Entity\Guide;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType; 
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ActivityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'required' => true,
                'help' => 'Titre de l\'activité (3-150 caractères)',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Randonnée en montagne'],
            ])
            ->add('description', TextareaType::class, [
                'required' => true,
                'help' => 'Description détaillée (10-5000 caractères)',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Décrivez l\'activité en détail', 'rows' => 6],
            ])
            ->add('price', MoneyType::class, [
                'required' => true,
                'help' => 'Prix en euros (montant positif)',
                'attr' => ['class' => 'form-control'],
                'currency' => 'EUR',
            ])
            ->add('durationMinutes', IntegerType::class, [
                'required' => true,
                'help' => 'Durée en minutes (5-1440)',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: 120'],
            ])
            ->add('location', TextType::class, [
                'required' => true,
                'help' => 'Lieu de l\'activité (3-150 caractères)',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Alpes, Chamonix'],
            ])
            ->add('maxParticipants', IntegerType::class, [
                'required' => false,
                'help' => 'Nombre maximum de participants',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: 15'],
            ])
            ->add('image', FileType::class, [
                'required' => false,
                'help' => 'Téléchargez une image JPG, PNG ou GIF (max 5 MB)',
                'attr' => ['class' => 'form-control', 'accept' => 'image/jpeg,image/png,image/gif'],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPG, PNG ou GIF)',
                    ])
                ],
                'mapped' => false,
            ])
            

            ->add('isActive', CheckboxType::class, [
                'required' => false,
                'help' => 'Cochez pour rendre l\'activité disponible',
            ])
            ->add('category', EntityType::class, [
                'class' => ActivityCategory::class,
                'choice_label' => 'name',
                'placeholder' => 'Choisir une catégorie',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('guide', EntityType::class, [
                'class' => Guide::class,
                'choice_label' => function (Guide $g) {
                    return $g->getFirstName() . ' ' . $g->getLastName();
                },
                'placeholder' => 'Choisir un guide (optionnel)',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Activity::class,
        ]);
    }
}
