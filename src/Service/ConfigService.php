<?php

namespace Admin\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;

class ConfigService
{
    private SystemConfigService $configService;

    public function __construct(SystemConfigService $configService)
    {
        $this->configService = $configService;
    }

    public function getAbandonedCartTime(): int
    {
        return $this->configService->get('AbandonedCartAdmin.config.markAbandonedAfter') ?? 300;
    }
    
    public function setAbandonedCartTime(int $seconds): void
    {
        $this->configService->set('AbandonedCartAdmin.config.markAbandonedAfter', $seconds);
    }
    
}
