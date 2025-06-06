<?php

declare(strict_types=1);

namespace Admin\Command;

use Doctrine\DBAL\Exception;
use Admin\Core\Checkout\AbandonedCart\AbandonedCartManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


#[AsCommand(
    name: 'mailcampaigns:abandoned-cart:mark',
    description: 'Marks shopping carts older than the configured time as "abandoned".'
)]
final class MarkAbandonedCartCommand extends Command
{
    public function __construct(private AbandonedCartManager $manager, string $name = null)
    {
        parent::__construct($name);
    }

    /**
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $cnt = $this->manager->generate();

        $output->writeln("Marked $cnt shopping carts as \"abandoned\".");

        return Command::SUCCESS;
    }
}
