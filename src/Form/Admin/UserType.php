<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Entity\User;
use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserType extends AbstractType
{
    public function __construct(
        private UserPasswordHasherInterface $userPasswordHasher,
        private Encryptor $encryptor,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $user */
        $user = $builder->getData();

        $builder->add('username', TextType::class);
        $builder->add('roles', ChoiceType::class, [
            'choices' => array_combine([
                'admin',
            ], User::ROLES),
            'multiple' => true,
            'required' => false,
        ]);
        $builder->add('binanceApiKey', TextType::class, [
            'required' => false,
        ]);
        $builder->add('binanceApiSecret', TextareaType::class, [
            'required' => false,
        ]);
        $builder->add('plainPassword', RepeatedType::class, [
            'type' => PasswordType::class,
            'first_options'  => ['label' => 'Password', 'hash_property_path' => 'password'],
            'second_options' => ['label' => 'Repeat Password'],
            'mapped' => false,
            'required' => $user->getId() === null,
            'disabled' => true,
        ]);

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'postSetData']);
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
            'error_mapping' => [
                'plainPassword' => 'password',
            ],
        ]);
    }

    public function postSetData(FormEvent $event): void
    {
        /** @var User $user */
        $user = $event->getData();
        if ($user->getBinanceApiSecret() !== null) {
            $event->getForm()->get('binanceApiSecret')->setData($this->encryptor->decrypt($user->getBinanceApiSecret()));
        }
    }

    public function onSubmit(FormEvent $event): void
    {
        /** @var User $user */
        $user = $event->getData();
        $password = $event->getForm()->get('plainPassword')->getData();
        if ($password) {
            $user->setPassword($this->userPasswordHasher->hashPassword($user, $password));
        }
        $binanceApiSecret = $event->getForm()->get('binanceApiSecret')->getData();
        if ($binanceApiSecret) {
            $user->setBinanceApiSecret($this->encryptor->encrypt($binanceApiSecret));
        }
        $user->setRoles($event->getForm()->get('roles')->getData());
    }
}
