<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\PriceSaver;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:import-prices')]
class ImportPricesCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private PriceSaver $priceSaver
    )
    {
        parent::__construct();
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // SymfonyStyle is an optional feature that Symfony provides, so you can
        // apply a consistent look to the commands of your application.
        // See https://symfony.com/doc/current/console/style.html
        $this->io = new SymfonyStyle($input, $output);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->writeln('Start prices import');
        $logs = $this->priceSaver->savePrices();
        $this->io->writeln('Finish prices import. ' . implode('. ', $logs));
        if (!empty($this->priceSaver->getErrors())) {
            $this->io->writeln('Errors: ' . implode(', ', $this->priceSaver->getErrors()));
        }

        return 0;
    }
}
