<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationType extends AbstractType
{
    public function __construct(
        private UserPasswordHasherInterface $userPasswordHasher,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $user */
        $user = $builder->getData();

        $builder->add('username', TextType::class);
        $builder->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'first_options'  => ['label' => 'Password'],
            'second_options' => ['label' => 'Repeat Password'],
            'mapped' => false,
            'required' => $user->getId() === null,
        ]);

        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'onSubmit']);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }

    public function configureOptions(OptionsResolver $resolver): OptionsResolver
    {
        return $resolver->setDefaults([
            'data_class' => User::class,
            'translation_domain' => false,
            'csrf_protection' => false,
        ]);
    }

    public function onSubmit(FormEvent $event): void
    {
        /** @var User $user */
        $user = $event->getData();
        $password = $event->getForm()->get('plainPassword')->getData();
        if ($password) {
            $user->setPassword($this->userPasswordHasher->hashPassword($user, $password));
        }
    }
}
