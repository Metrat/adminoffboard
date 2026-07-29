<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Metrat <disparam@gmail.com>
 *
 * @author Metrat <disparam@gmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\AdminOffboard\Logger;

use OCP\ILogger;
use OCP\Log\ILogFactory;
use OCP\AppFramework\Services\Logger;

/**
 * Logger factory for the application
 */
class LoggerFactory
{
    private ?AppLogger $logger = null;

    public function __construct(
        private string $appId,
        private ILogFactory $logFactory,
        private ILogger $loggerService
    ) {
    }

    /**
     * Get the application logger
     */
    public function getLogger(): AppLogger
    {
        if ($this->logger === null) {
            $this->logger = new AppLogger(
                $this->appId,
                $this->logFactory,
                $this->loggerService
            );
        }

        return $this->logger;
    }

    /**
     * Create a new logger instance with a specific context
     */
    public function createContextLogger(array $context): AppLogger
    {
        $logger = $this->getLogger();
        return $logger->withContext($context);
    }

    /**
     * Create a component logger
     */
    public function getComponentLogger(string $component): AppLogger
    {
        $logger = $this->getLogger();
        return $logger->withContext(['component' => $component]);
    }
}