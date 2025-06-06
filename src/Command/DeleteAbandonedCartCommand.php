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
    name: 'mailcampaigns:abandoned-cart:delete',
    description: 'Deletes "abandoned" carts without an existing reference.'
)]
final class DeleteAbandonedCartCommand extends Command
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
        $cnt = $this->manager->cleanUp();

        $output->writeln("Deleted $cnt \"abandoned\" shopping carts.");

        return Command::SUCCESS;
    }
}
