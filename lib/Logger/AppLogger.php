<?php

declare(strict_types=1);

namespace OCA\AdminOffboard\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\AbstractLogger;
use Stringable;

class AppLogger extends AbstractLogger implements LoggerInterface
{
    public function __construct(
        private string $appId
    ) {
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        $level = strtoupper((string)$level);
        error_log("[AdminOffboard][$level] $message");
    }

    public function info(Stringable|string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function warning(Stringable|string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function error(Stringable|string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function debug(Stringable|string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function fatal(Stringable|string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    public function withContext(array $context): self
    {
        return $this;
    }
}
