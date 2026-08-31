<?php

declare(strict_types=1);

namespace OCA\AdminOffboard\Configuration;

use OCP\IConfig;

class AppConfig
{
    private array $defaults = [
        'queue_batch_size' => 10,
        'queue_max_attempts' => 3,
        'audit_retention_days' => 30,
        'dry_run_default' => false,
    ];

    public function __construct(
        private IConfig $config,
        private string $appId
    ) {
    }

    public function init(): void
    {
        foreach ($this->defaults as $key => $defaultValue) {
            if ($this->config->getAppValue($this->appId, $key, '') === '') {
                $this->config->setAppValue($this->appId, $key, (string)$defaultValue);
            }
        }
    }

    public function getAppId(): string
    {
        return $this->appId;
    }

    public function getAppVersion(): string
    {
        return $this->config->getSystemValue('version', '0.0.0');
    }

    public function get(string $key, ?string $default = null): string
    {
        return $this->config->getAppValue($this->appId, $key, $default ?? '');
    }

    public function set(string $key, string $value): void
    {
        $this->config->setAppValue($this->appId, $key, $value);
    }

    public function getInt(string $key, ?int $default = null): int
    {
        return (int)$this->get($key, (string)($default ?? 0));
    }

    public function getBool(string $key, ?bool $default = null): bool
    {
        return $this->get($key, $default ? '1' : '0') === '1';
    }

    public function getQueueBatchSize(): int
    {
        return $this->getInt('queue_batch_size', 10);
    }

    public function getQueueMaxAttempts(): int
    {
        return $this->getInt('queue_max_attempts', 3);
    }

    public function getAuditLogRetentionDays(): int
    {
        return $this->getInt('audit_retention_days', 30);
    }

    public function isDryRunDefault(): bool
    {
        return $this->getBool('dry_run_default', false);
    }
}
