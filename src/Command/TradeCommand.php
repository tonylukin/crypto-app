<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Symbol;
use App\Repository\SymbolRepository;
use App\Service\OrderManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:trade')]
class TradeCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private OrderManager $orderManager,
        private SymbolRepository $symbolRepository,
    ) {
        parent::__construct();
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // SymfonyStyle is an optional feature that Symfony provides, so you can
        // apply a consistent look to the commands of your application.
        // See https://symfony.com/doc/current/console/style.html
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function configure()
    {
        $this
            ->setDescription('Main command for buy and sell currencies.')
            ->addOption('symbols', null, InputOption::VALUE_OPTIONAL, 'The specific symbols for buy/sell separated by comma.')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $symbols = $input->getOption('symbols');
        if ($symbols !== null) {
            $symbols = $this->symbolRepository->findByName(explode(',', $symbols));
        } else {
            $symbols = $this->symbolRepository->getActiveList();
        }

        foreach ($symbols as $symbol) {
            foreach ($symbol->getUserSymbols() as $userSymbol) {
                $this->io->write("Start trading for {$symbol->getName()} of user {$userSymbol->getUser()->getUserIdentifier()}\n");
                $this->orderManager->buy($userSymbol->getUser(), $userSymbol, $userSymbol->getTotalPrice() ?? Symbol::DEFAULT_TOTAL_PRICE_USD);
                $this->orderManager->sell($userSymbol->getUser(), $userSymbol);
            }
        }

        return 0;
    }
}
