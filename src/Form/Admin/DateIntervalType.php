<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Model\Admin\DateIntervalModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateIntervalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('dateStart', TextType::class, ['required' => false]);
        $builder->add('dateEnd', TextType::class, ['required' => false]);
        $builder->add('daysAgo', NumberType::class, ['required' => false]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }

    public function configureOptions(OptionsResolver $resolver): OptionsResolver
    {
        return $resolver->setDefaults([
            'data_class' => DateIntervalModel::class,
            'csrf_protection' => false,
            'translation_domain' => false,
            'allow_extra_fields' => true,
        ]);
    }
}
