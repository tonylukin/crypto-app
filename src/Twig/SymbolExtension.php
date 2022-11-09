<?php

declare(strict_types=1);

namespace App\Twig;

use App\Repository\SymbolRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SymbolExtension extends AbstractExtension
{
    public function __construct(private SymbolRepository $symbolRepository)
    {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getSymbols', [$this, 'getSymbols']),
        ];
    }

    public function getSymbols(): array
    {
        return $this->symbolRepository->getActiveList();
    }
}
