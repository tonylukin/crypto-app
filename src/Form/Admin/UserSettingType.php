<?php

namespace App\Form\Admin;

use App\Entity\UserSetting;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserSettingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('disableTrading', CheckboxType::class, [
                'required' => false,
            ])
            ->add('minFallenPricePercent', NumberType::class)
            ->add('minProfitPercent', NumberType::class)
            ->add('maxDaysWaitingForProfit', IntegerType::class)
            ->add('minPricesCountMustHaveBeforeOrder', IntegerType::class)
            ->add('maxPercentDiffOnMoving', NumberType::class)
            ->add('legalMovingStepPercent', NumberType::class)
            ->add('hoursExtremelyShortIntervalForPrices', IntegerType::class)
            ->add('minPriceDiffPercentAfterLastSell', NumberType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserSetting::class,
        ]);
    }
}
