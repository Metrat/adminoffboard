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
            $stats = $this->queueManager->processJobs(10);
            
            if ($stats['processed'] > 0 || $stats['failed'] > 0) {
                $this->logger->info('AdminOffboard: Queue processing completed', [
                    'processed' => $stats['processed'],
                    'failed' => $stats['failed'],
                    'remaining' => $stats['remaining']
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('AdminOffboard: Queue processing failed: ' . $e->getMessage());
        }
    }
}
