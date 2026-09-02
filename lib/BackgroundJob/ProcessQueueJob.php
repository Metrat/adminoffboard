<?php

declare(strict_types=1);

namespace OCA\AdminOffboard\BackgroundJob;

use OCA\AdminOffboard\Queue\QueueManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

class ProcessQueueJob extends TimedJob
{
    public function __construct(
        ITimeFactory $time,
        private QueueManager $queueManager,
        private LoggerInterface $logger
    ) {
        parent::__construct($time);
        $this->setInterval(300);
    }

    protected function run($argument): void
    {
        $this->logger->info('AdminOffboard: Processing queue via background job');

        try {
            $processed = $this->queueManager->processJobs(10);

            if ($processed > 0) {
                $this->logger->info('AdminOffboard: Queue processing completed', [
                    'processed' => $processed
                ]);
            }

            // Получаем статистику очереди отдельно
            $stats = $this->queueManager->getStats();
            if (isset($stats['pending']) && $stats['pending'] > 0) {
                $this->logger->info('AdminOffboard: Jobs remaining in queue', [
                    'pending' => $stats['pending']
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('AdminOffboard: Queue processing failed: ' . $e->getMessage());
        }
    }
}
