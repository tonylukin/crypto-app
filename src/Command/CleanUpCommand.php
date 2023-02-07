<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:clean-up')]
class CleanUpCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private EntityManagerInterface $entityManager,
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

    protected function configure()
    {
        $this
            ->setDescription('Clean up db of old cron report and price records.')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $dateToDeleteBefore = (new \DateTime())->modify('-2 weeks');
        $sql = <<<SQL
            DELETE FROM cron_report WHERE DATE(run_at) <= :dateToDeleteBefore
        SQL;
        $this->entityManager
            ->getConnection()
            ->prepare($sql)
            ->executeQuery(['dateToDeleteBefore' => $dateToDeleteBefore->format('Y-m-d')])
        ;
        $this->io->writeln('Cron report table has been cleaned successfully');

        $dateToDeleteBefore = (new \DateTime())->modify('-3 months');
        $sql = <<<SQL
            DELETE FROM price WHERE DATE(datetime) <= :dateToDeleteBefore
        SQL;
        $this->entityManager
            ->getConnection()
            ->prepare($sql)
            ->executeQuery(['dateToDeleteBefore' => $dateToDeleteBefore->format('Y-m-d')])
        ;
        $this->io->writeln('Prices table has been cleaned successfully');

        $output = (string) exec('rm -rf /var/log/journal/');
        $this->io->writeln("Journal logs purge output: '{$output}'");

        return 0;
    }
}
