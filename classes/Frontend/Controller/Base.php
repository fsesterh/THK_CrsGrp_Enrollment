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
use ilErrorHandling;
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

    public ilGlobalTemplateInterface $page_template;
    protected Factory $ui_factory;
    protected Renderer $ui_renderer;
    protected Container $dic;
    protected ilToolbarGUI $toolbar;
    protected ilObjuser $user;
    protected ilAccessHandler $core_access_handler;
    protected ilErrorHandling $error_handler;
    public ilLanguage $lng;
    protected ilLogger $log;
    protected UserImportValidator $user_import_validator;
    protected UserImportService $user_import_service;
    protected ilCrsGrpEnrollmentPlugin $plugin;

    final public function __construct(public ilCrsGrpEnrollmentUIHookGUI $core_controller, Container $dic)
    {
        $this->dic = $dic;
        $this->ctrl = $this->dic->ctrl();

        $this->http_wrapper = $dic->http()->wrapper();
        $this->refinery = $dic->refinery();
        $this->object_cache = $dic['ilObjDataCache'];

        $this->ctrl = $dic->ctrl();
        $this->lng = $dic->language();
        $this->page_template = $dic->ui()->mainTemplate();
        $this->user = $dic->user();
        $this->ui_renderer = $dic->ui()->renderer();
        $this->ui_factory = $dic->ui()->factory();
        $this->core_access_handler = $dic->access();
        $this->error_handler = $dic['ilErr'];
        $this->toolbar = $dic->toolbar();
        $this->log = $dic->logger()->root();

        $this->user_import_validator = new UserImportValidator();
        $this->user_import_service = new UserImportService($this->getCoreController()->getPluginObject());

        $this->init();
    }

    protected function init(): void
    {
        $this->plugin = ilCrsGrpEnrollmentPlugin::getInstance();
        if (!$this->plugin->isActive()) {
            $this->error_handler->raiseError($this->lng->txt('permission_denied'), $this->error_handler->MESSAGE);
        }
    }

    final public function __call(string $name, array $arguments): mixed
    {
        return call_user_func_array([$this, $this->getDefaultCommand()], []);
    }

    abstract public function getDefaultCommand(): string;

    public function getCoreController(): ilCrsGrpEnrollmentUIHookGUI
    {
        return $this->core_controller;
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
