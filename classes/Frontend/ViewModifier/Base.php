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

namespace ILIAS\Plugin\CrsGrpEnrollment\Frontend\ViewModifier;

use ilAccessHandler;
use ilCrsGrpEnrollmentUIHookGUI;
use ilErrorHandling;
use ilGlobalTemplateInterface;
use ILIAS\DI\Container;
use ILIAS\Plugin\CrsGrpEnrollment\Frontend\HttpContext;
use ILIAS\Plugin\CrsGrpEnrollment\Frontend\ViewModifier;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use ilLanguage;
use ilObjUser;
use ilToolbarGUI;
use ReflectionClass;

abstract class Base implements ViewModifier
{
    use HttpContext;

    protected Factory $uiFactory;
    protected Renderer $uiRenderer;
    protected Container $dic;
    protected ilToolbarGUI $toolbar;
    protected ilObjuser $user;
    protected ilAccessHandler $coreAccessHandler;
    protected ilErrorHandling $errorHandler;
    protected ilLanguage $lng;
    public ilCrsGrpEnrollmentUIHookGUI $coreController;
    protected ilGlobalTemplateInterface $mainTemplate;

    final public function __construct(ilCrsGrpEnrollmentUIHookGUI $controller, Container $dic)
    {
        $this->coreController = $controller;
        $this->dic = $dic;
        $this->ctrl = $this->dic->ctrl();

        $this->httpWrapper = $dic->http()->wrapper();
        $this->refinery = $dic->refinery();
        $this->objectCache = $dic['ilObjDataCache'];

        $this->mainTemplate = $dic->ui()->mainTemplate();
        $this->lng = $dic->language();
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
