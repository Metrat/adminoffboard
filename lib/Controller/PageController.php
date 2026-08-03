<?php

declare(strict_types=1);

namespace OCA\AdminOffboard\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IUserSession;
use OCA\AdminOffboard\Configuration\AppConfig;

class PageController extends Controller
{
    public function __construct(
        string $appName,
        IRequest $request,
        private IUserSession $userSession,
        private AppConfig $config
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index(): TemplateResponse
    {
        return new TemplateResponse('adminoffboard', 'index', [
            'appName' => 'adminoffboard',
            'version' => $this->config->getAppVersion()
        ]);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function dashboard(): TemplateResponse
    {
        return new TemplateResponse('adminoffboard', 'dashboard', [
            'appName' => 'adminoffboard',
            'version' => $this->config->getAppVersion()
        ]);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function offboard(): TemplateResponse
    {
        return new TemplateResponse('adminoffboard', 'offboard', [
            'appName' => 'adminoffboard',
            'version' => $this->config->getAppVersion()
        ]);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function settings(): TemplateResponse
    {
        return new TemplateResponse('adminoffboard', 'settings', [
            'appName' => 'adminoffboard',
            'version' => $this->config->getAppVersion()
        ]);
    }
}
