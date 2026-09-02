<?php

declare(strict_types=1);

namespace OCA\AdminOffboard\Logger;

class LoggerFactory
{
    public function __construct(
        private string $appId
    ) {
    }

    public function getLogger(): AppLogger
    {
        return new AppLogger($this->appId);
    }
}