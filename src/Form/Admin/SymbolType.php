<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Entity\Symbol;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SymbolType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Symbol $symbol */
        $symbol = $builder->getData();

        if ($symbol->getId() === null) {
            $builder->add('name', TextType::class);
        }

        $builder->add('active', CheckboxType::class, ['required' => false]);
        $builder->add('riskable', CheckboxType::class, ['required' => false]);
        $builder->add('totalPrice', TextType::class, ['required' => false]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }

    public function configureOptions(OptionsResolver $resolver): OptionsResolver
    {
        return $resolver->setDefaults([
            'data_class' => Symbol::class,
            'translation_domain' => false,
            'csrf_protection' => false,
        ]);
    }
}
