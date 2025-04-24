<?php

declare(strict_types=1);

namespace Admin\Service\ScheduledTask;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class UpdateAbandonedCartTask extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'mailcampaigns.abandoned_cart.update';
    }

    public static function getDefaultInterval(): int
    {
        return 420; 
    }
}
