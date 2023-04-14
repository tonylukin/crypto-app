<?php

namespace App\Form\Admin;

use App\Entity\Order;
use App\Entity\UserSetting;
use Nzo\UrlEncryptorBundle\Encryptor\Encryptor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserSettingType extends AbstractType
{
    public function __construct(
        private Encryptor $encryptor,
    ) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('useExchange', ChoiceType::class, [
                'choices' => array_flip(Order::EXCHANGE_LABELS),
                'expanded' => true,
            ])
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
            ->add('fallenPriceIntervalHours', IntegerType::class)
            ->add('daysIntervalMinPriceOnDistance', IntegerType::class)
        ;
        $builder
            ->add('binanceApiKey', TextType::class, [
                'required' => false,
            ])
            ->add('binanceApiSecret', TextType::class, [
                'required' => false,
            ])
        ;
        $builder
            ->add('huobiApiKey', TextType::class, [
                'required' => false,
            ])
            ->add('huobiApiSecret', TextType::class, [
                'required' => false,
            ])
        ;
        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'postSetData']);
        $builder->addEventListener(FormEvents::SUBMIT, [$this, 'onSubmit']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserSetting::class,
        ]);
    }

    public function postSetData(FormEvent $event): void
    {
        /** @var UserSetting $userSetting */
        $userSetting = $event->getData();
        if ($userSetting->getBinanceApiSecret() !== null) {
            $event->getForm()->get('binanceApiSecret')->setData($this->encryptor->decrypt($userSetting->getBinanceApiSecret()));
        }
        if ($userSetting->getHuobiApiSecret() !== null) {
            $event->getForm()->get('huobiApiSecret')->setData($this->encryptor->decrypt($userSetting->getHuobiApiSecret()));
        }
    }

    public function onSubmit(FormEvent $event): void
    {
        /** @var UserSetting $userSetting */
        $userSetting = $event->getData();
        $binanceApiSecret = $event->getForm()->get('binanceApiSecret')->getData();
        if ($binanceApiSecret) {
            $userSetting->setBinanceApiSecret($this->encryptor->encrypt($binanceApiSecret));
        }
        $huobiApiSecret = $event->getForm()->get('huobiApiSecret')->getData();
        if ($huobiApiSecret) {
            $userSetting->setHuobiApiSecret($this->encryptor->encrypt($huobiApiSecret));
        }
    }
}
