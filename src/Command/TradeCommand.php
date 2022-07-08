<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Symbol;
use App\Repository\SymbolRepository;
use App\Service\OrderManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TradeCommand extends Command
{
    protected static $defaultName = 'app:trade';
    private SymfonyStyle $io;

    public function __construct(
        private OrderManager $orderManager,
        private SymbolRepository $symbolRepository
    ) {
        parent::__construct();
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // SymfonyStyle is an optional feature that Symfony provides so you can
        // apply a consistent look to the commands of your application.
        // See https://symfony.com/doc/current/console/style.html
        $this->io = new SymfonyStyle($input, $output);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $symbols = $this->symbolRepository->getActiveList();foreach ($symbols as $symbol) {
            $this->io->writeln("Start trading for {$symbol->getName()}");
            $this->orderManager->buy($symbol, $symbol->getTotalPrice() ?? Symbol::DEFAULT_TOTAL_PRICE_USD);
            $this->orderManager->sell($symbol);
        }

        return 0;
    }
}
