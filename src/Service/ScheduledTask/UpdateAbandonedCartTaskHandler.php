<?php

declare(strict_types=1);

namespace Admin\Service\ScheduledTask;

use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Admin\Core\Checkout\AbandonedCart\AbandonedCartManager;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

#[AsMessageHandler(handles: UpdateAbandonedCartTask::class)]
class UpdateAbandonedCartTaskHandler extends ScheduledTaskHandler
{
    protected EntityRepository $scheduledTaskRepository;
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
        $this->manager->updateAbandonedCarts();
    }
}
