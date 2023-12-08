<?php

declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollment\Frontend\Controller;

use ilAccessHandler;
use ilCrsGrpEnrollmentUIHookGUI;
use ilCtrl;
use ilErrorHandling;
use ilGlobalPageTemplate;
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

/**
 * @author Timo Müller <timomueller@databay.de>
 */
abstract class Base
{
    use HttpContext;

    public ilGlobalPageTemplate $pageTemplate;
    protected Factory $uiFactory;
    protected ilCtrl $ctrl;
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

    final public function __construct(ilCrsGrpEnrollmentUIHookGUI $controller, Container $dic)
    {
        $this->coreController = $controller;
        $this->dic = $dic;

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
        if (!$this->getCoreController()->getPluginObject()->isActive()) {
            $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        }
    }

    /**
     * @return mixed
     */
    final public function __call(string $name, array $arguments)
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
