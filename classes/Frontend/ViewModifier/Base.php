<?php

declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollment\Frontend\ViewModifier;

use ilAccessHandler;
use ilCrsGrpEnrollmentUIHookGUI;
use ilCtrl;
use ilErrorHandling;
use ilGlobalPageTemplate;
use ILIAS\DI\Container;
use ILIAS\Plugin\CrsGrpEnrollment\AccessControl\AccessHandler;
use ILIAS\Plugin\CrsGrpEnrollment\Frontend\HttpContext;
use ILIAS\Plugin\CrsGrpEnrollment\Frontend\ViewModifier;
use ILIAS\Plugin\CrsGrpEnrollment\Service\CrsGrpEnrollment\Impl;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use ilLanguage;
use ilObjUser;
use ilToolbarGUI;
use ReflectionClass;

/**
 * Class ViewModifier
 *
 * @package ILIAS\Plugin\CrsGrpEnrollment\Frontend\ViewModifier
 * @author  Timo Müller <timomueller@databay.de>
 */
abstract class Base implements ViewModifier
{
    use HttpContext;

    protected ilGlobalPageTemplate $pageTemplate;
    protected Factory $uiFactory;
    protected ilCtrl $ctrl;
    protected Renderer $uiRenderer;
    protected Container $dic;
    protected ilToolbarGUI $toolbar;
    protected ilObjuser $user;
    protected ilAccessHandler $coreAccessHandler;
    protected ilErrorHandling $errorHandler;
    protected ilLanguage $lng;
    public ilCrsGrpEnrollmentUIHookGUI $coreController;
    protected ilGlobalPageTemplate $mainTemplate;
    protected Impl $service;

    final public function __construct(ilCrsGrpEnrollmentUIHookGUI $controller, Container $dic)
    {
        $this->coreController = $controller;
        $this->dic = $dic;

        $this->httpWrapper = $dic->http()->wrapper();
        $this->refinery = $dic->refinery();
        $this->objectCache = $dic['ilObjDataCache'];

        $this->mainTemplate = $dic->ui()->mainTemplate();
        $this->ctrl = $dic->ctrl();
        $this->lng = $dic->language();
        $this->pageTemplate = $dic->ui()->mainTemplate();
        $this->user = $dic->user();
        $this->uiRenderer = $dic->ui()->renderer();
        $this->uiFactory = $dic->ui()->factory();
        $this->errorHandler = $dic['ilErr'];
        $this->coreAccessHandler = $dic->access();
        $this->toolbar = $dic->toolbar();
    }

    public function getCoreController(): ilCrsGrpEnrollmentUIHookGUI
    {
        return $this->coreController;
    }

    public function getDic(): Container
    {
        return $this->dic;
    }

    final public function getClassName(): string
    {
        return (new ReflectionClass($this))->getShortName();
    }

    final protected function cleanHtmlString(string $html): string
    {
        return str_replace(['<body>', '</body>'], '', $html);
    }
}
