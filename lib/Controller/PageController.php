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

namespace OCA\AdminOffboard\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\AppFramework\Http\ContentSecurityPolicy;

/**
 * Page controller for web UI
 */
class PageController extends Controller
{
    public function __construct(
        string $appName,
        IRequest $request,
        private IUserSession $userSession
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * 
     * Main dashboard page
     */
    public function index(): TemplateResponse
    {
        $user = $this->userSession->getUser();
        if (!$user) {
            return new TemplateResponse('core', 'error', [
                'message' => 'You must be logged in to access this page'
            ]);
        }

        $isAdmin = \OC::$server->getGroupManager()->isAdmin($user->getUID());
        if (!$isAdmin) {
            return new TemplateResponse('core', 'error', [
                'message' => 'You do not have permission to access this page'
            ]);
        }

        $response = new TemplateResponse(
            $this->appName,
            'index',
            [
                'user' => $user->getUID(),
                'version' => '0.1.0'
            ],
            'user'
        );

        $csp = new ContentSecurityPolicy();
        $csp->addAllowedScriptDomain('*');
        $csp->addAllowedStyleDomain('*');
        $response->setContentSecurityPolicy($csp);

        return $response;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * 
     * Dashboard page
     */
    public function dashboard(): TemplateResponse
    {
        return $this->index();
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * 
     * Offboard page
     */
    public function offboard(): TemplateResponse
    {
        return $this->index();
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * 
     * Settings page
     */
    public function settings(): TemplateResponse
    {
        return $this->index();
    }
}