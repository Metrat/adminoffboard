<?php

declare(strict_types=1);

namespace OCA\AdminOffboard\Logger;

class AppLogger
{
    public function __construct(
        private string $appId
    ) {
    }

    public function debug(string $message, array $context = []): void
    {
        error_log("[AdminOffboard][DEBUG] $message");
    }

    public function info(string $message, array $context = []): void
    {
        error_log("[AdminOffboard][INFO] $message");
    }

    public function warning(string $message, array $context = []): void
    {
        error_log("[AdminOffboard][WARNING] $message");
    }

    public function error(string $message, array $context = []): void
    {
        error_log("[AdminOffboard][ERROR] $message");
    }

    public function fatal(string $message, array $context = []): void
    {
        error_log("[AdminOffboard][FATAL] $message");
    }

    public function withContext(array $context): self
    {
        return $this;
    }
}