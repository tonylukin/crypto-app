<?php

declare(strict_types=1);

namespace App\Form\Admin;

use App\Entity\Symbol;
use App\Entity\UserSymbol;
use App\Repository\SymbolRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SymbolType extends AbstractType
{
    public function __construct(
        private SymbolRepository $symbolRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var UserSymbol $userSymbol */
        $userSymbol = $builder->getData();

        if ($userSymbol->getSymbol() === null) {
            $builder->add('name', TextType::class, [
                'mapped' => false,
            ]);
            $builder->addEventListener(FormEvents::SUBMIT, [$this, 'onSubmit']);
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
            'data_class' => UserSymbol::class,
            'translation_domain' => false,
            'csrf_protection' => false,
        ]);
    }

    public function onSubmit(FormEvent $event): void
    {
        /** @var UserSymbol $userSymbol */
        $userSymbol = $event->getData();
        $symbolName = $event->getForm()->get('name')->getData();

        $symbol = $this->symbolRepository->findOneBy(['name' => $symbolName]);
        if ($symbol === null) {
            $symbol = new Symbol();
            $symbol->setName($symbolName);
        }
        $userSymbol->setSymbol($symbol);
    }
}
