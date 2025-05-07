<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\Plugin\CrsGrpEnrollment\Frontend\Controller;

use ilAccessHandler;
use ilCrsGrpEnrollmentPlugin;
use ilCrsGrpEnrollmentUIHookGUI;
use ilCtrl;
use ilErrorHandling;
use ilGlobalPageTemplate;
use ilGlobalTemplateInterface;
use ILIAS\DI\Container;
use ILIAS\Plugin\CrsGrpEnrollment\Frontend\HttpContext;
use ILIAS\Plugin\CrsGrpEnrollment\Services\UserImportService;
use ILIAS\Plugin\CrsGrpEnrollment\Validators\UserImportValidator;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use ilLanguage;
use ilLogger;
use ilObjuser;
use ilToolbarGUI;
use ReflectionClass;

abstract class Base
{
    use HttpContext;

    public ilGlobalTemplateInterface $pageTemplate;
    protected Factory $uiFactory;
    protected Renderer $uiRenderer;
    protected Container $dic;
    protected ilToolbarGUI $toolbar;
    protected ilObjuser $user;
    protected ilAccessHandler $coreAccessHandler;
    protected ilErrorHandling $errorHandler;
    public ilLanguage $lng;
    public ilCrsGrpEnrollmentUIHookGUI $coreController;
    protected ilLogger $log;
    protected UserImportValidator $userImportValidator;
    protected UserImportService $userImportService;
    protected ilCrsGrpEnrollmentPlugin $plugin;

    final public function __construct(ilCrsGrpEnrollmentUIHookGUI $controller, Container $dic)
    {
        $this->coreController = $controller;
        $this->dic = $dic;
        $this->ctrl = $this->dic->ctrl();

        $this->httpWrapper = $dic->http()->wrapper();
        $this->refinery = $dic->refinery();
        $this->objectCache = $dic['ilObjDataCache'];

        $this->ctrl = $dic->ctrl();
        $this->lng = $dic->language();
        $this->pageTemplate = $dic->ui()->mainTemplate();
        $this->user = $dic->user();
        $this->uiRenderer = $dic->ui()->renderer();
        $this->uiFactory = $dic->ui()->factory();
        $this->coreAccessHandler = $dic->access();
        $this->errorHandler = $dic['ilErr'];
        $this->toolbar = $dic->toolbar();
        $this->log = $dic->logger()->root();

        $this->userImportValidator = new UserImportValidator();
        $this->userImportService = new UserImportService($this->getCoreController()->getPluginObject());

        $this->init();
    }

    protected function init(): void
    {
        $this->plugin = ilCrsGrpEnrollmentPlugin::getInstance();
        if (!$this->plugin->isActive()) {
            $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        }
    }

    final public function __call(string $name, array $arguments): mixed
    {
        return call_user_func_array([$this, $this->getDefaultCommand()], []);
    }

    abstract public function getDefaultCommand(): string;

    public function getCoreController(): ilCrsGrpEnrollmentUIHookGUI
    {
        return $this->coreController;
    }

    public function getDic(): Container
    {
        return $this->dic;
    }

    final public function getControllerName(): string
    {
        return (new ReflectionClass($this))->getShortName();
    }
}
