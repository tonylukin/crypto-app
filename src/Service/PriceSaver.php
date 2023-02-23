<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Price;
use App\Repository\SymbolRepository;
use Doctrine\ORM\EntityManagerInterface;

class PriceSaver
{
    private array $errors = [];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SymbolRepository $symbolRepository,
        private ApiInterface $api
    ) {}

    public function savePrices(): array
    {
        $dateTime = new \DateTimeImmutable();
        $symbols = $this->symbolRepository->getActiveList(includeNonActive: true);
        $logs = [];
        $this->errors = [];
        foreach ($symbols as $symbol) {
            try {
                $priceValue = $this->api->price($symbol->getName());
            } catch (\Throwable $e) {
                $this->errors[] = "'{$symbol->getName()}': {$e->getMessage()}";
                continue;
            }
            $price = (new Price())
                ->setPrice($priceValue)
                ->setSymbol($symbol)
                ->setDatetime($dateTime)
            ;
            $this->entityManager->persist($price);
            $logs[] = "Symbol '{$symbol->getName()}' price saved";
        }

        $this->entityManager->flush();
        return $logs;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
