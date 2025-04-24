<?php

declare(strict_types=1);

namespace Admin\Service\ScheduledTask;

use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Admin\Core\Checkout\AbandonedCart\AbandonedCartManager;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: DeleteAbandonedCartTask::class)]
class DeleteAbandonedCartTaskHandler extends ScheduledTaskHandler
{
    private AbandonedCartManager $manager;

    public function __construct(
        EntityRepository $scheduledTaskRepository,
        AbandonedCartManager $manager
    ) {
        parent::__construct($scheduledTaskRepository);
        $this->manager = $manager;
    }

    /**
     * @throws Exception
     */
    public function run(): void
    {
        $this->manager->cleanUp();
    }
}
