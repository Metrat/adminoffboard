<?php

declare(strict_types=1);

namespace OCA\AdminOffboard\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\IGroupManager;
use OCP\AppFramework\Http\ContentSecurityPolicy;

class PageController extends Controller
{
    public function __construct(
        string $appName,
        IRequest $request,
        private IUserSession $userSession,
        private IGroupManager $groupManager
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): TemplateResponse
    {
        $user = $this->userSession->getUser();
        if (!$user) {
            return new TemplateResponse('core', 'error', [
                'message' => 'You must be logged in to access this page'
            ]);
        }

        $isAdmin = $this->groupManager->isAdmin($user->getUID());
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
                'version' => '0.1.2'
            ],
            'user'
        );

        $csp = new ContentSecurityPolicy();
        $csp->addAllowedScriptDomain('*');
        $csp->addAllowedStyleDomain('*');
        $response->setContentSecurityPolicy($csp);

        return $response;
    }

    // ... остальные методы без изменений
}