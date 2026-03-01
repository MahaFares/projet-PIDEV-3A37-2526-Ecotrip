<?php

namespace App\Form;

use App\Entity\User;
use App\Enum\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $requirePassword = $options['require_password'] ?? false;
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['class' => 'form-control', 'placeholder' => 'email@example.com'],
            ])
            ->add('username', TextType::class, [
                'label' => 'Nom d\'utilisateur',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Nom d\'utilisateur'],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Adresse'],
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => '+33 6 12 34 56 78'],
            ])
            ->add('password', PasswordType::class, [
                'label' => $requirePassword ? 'Mot de passe (obligatoire)' : 'Mot de passe (laisser vide pour ne pas changer)',
                'required' => $requirePassword,
                'mapped' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => $requirePassword ? '••••••••' : 'Laisser vide = inchangé'],
                'constraints' => $requirePassword ? [
                    new NotBlank(['message' => 'Le mot de passe est requis.']),
                    new Length(['min' => 6, 'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères.']),
                ] : [],
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Rôle',
                'choices' => [
                    'Utilisateur' => Role::USER,
                    'Administrateur' => Role::ADMIN,
                ],
                'expanded' => false,
                'multiple' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('isVerified', null, [
                'label' => 'Email vérifié',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'require_password' => false,
        ]);
    }
}
