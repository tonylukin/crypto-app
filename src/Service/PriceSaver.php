<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Price;
use App\Repository\SymbolRepository;
use Doctrine\ORM\EntityManagerInterface;

class PriceSaver
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SymbolRepository $symbolRepository,
        private ApiInterface $api
    ) {}

    public function savePrices(): void
    {
        $dateTime = new \DateTimeImmutable();
        $symbols = $this->symbolRepository->getActiveList();
        foreach ($symbols as $symbol) {
            $priceValue = $this->api->price($symbol->getName());
            $price = (new Price())
                ->setPrice($priceValue)
                ->setSymbol($symbol->getName())
                ->setDatetime($dateTime)
            ;
            $this->entityManager->persist($price);
        }

        $this->entityManager->flush();
    }
}
