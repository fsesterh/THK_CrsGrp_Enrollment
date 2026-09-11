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

    protected Factory $ui_factory;
    protected Renderer $ui_renderer;
    protected Container $dic;
    protected ilToolbarGUI $toolbar;
    protected ilObjUser $user;
    protected ilAccessHandler $core_access_handler;
    protected ilErrorHandling $error_handler;
    protected ilLanguage $lng;
    protected ilGlobalTemplateInterface $main_template;

    final public function __construct(public ilCrsGrpEnrollmentUIHookGUI $core_controller, Container $dic)
    {
        $this->dic = $dic;
        $this->ctrl = $this->dic->ctrl();

        $this->http_wrapper = $dic->http()->wrapper();
        $this->refinery = $dic->refinery();
        $this->object_cache = $dic['ilObjDataCache'];

        $this->main_template = $dic->ui()->mainTemplate();
        $this->lng = $dic->language();
        $this->user = $dic->user();
        $this->ui_renderer = $dic->ui()->renderer();
        $this->ui_factory = $dic->ui()->factory();
        $this->error_handler = $dic['ilErr'];
        $this->core_access_handler = $dic->access();
        $this->toolbar = $dic->toolbar();
    }

    public function getCoreController(): ilCrsGrpEnrollmentUIHookGUI
    {
        return $this->core_controller;
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
