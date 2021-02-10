<?php declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollement\Frontend\Controller;

use \ILIAS\DI\Container;
use ILIAS\Plugin\CrsGrpEnrollement\Frontend\HttpContext;
use \ILIAS\UI\Factory;
use \ILIAS\UI\Renderer;
use \Psr\Http\Message\ServerRequestInterface;

/**
 * @author Timo Müller <timomueller@databay.de>
 */
abstract class Base
{
    use HttpContext;

    /** @var \ilTemplate */
    public $pageTemplate;
    /** @var Factory */
    protected $uiFactory;
    /** @var \ilCtrl */
    protected $ctrl;
    /** @var Renderer */
    protected $uiRenderer;
    /** @var Container */
    protected $dic;
    /** @var \ilToolbarGUI */
    protected $toolbar;
    /** @var \ilObjuser */
    protected $user;
    /** @var \ilAccessHandler */
    protected $coreAccessHandler;
    /** @var \ilErrorHandling */
    protected $errorHandler;
    /** @var \ilLanguage */
    public $lng;
    /** @var \ilCrsGrpEnrollementUIHookGUI */
    public $coreController;
    /** @var ServerRequestInterface */
    protected $httpRequest;
    /** @var \ilLogger */
    protected $log;

    /**
     * Base constructor.
     * @param \ilCrsGrpEnrollementUIHookGUI $controller
     * @param Container $dic
     */
    final public function __construct(\ilCrsGrpEnrollementUIHookGUI $controller, Container $dic)
    {
        $this->coreController = $controller;
        $this->dic = $dic;

        $this->httpRequest = $dic->http()->request();
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

        $this->init();
    }

    /**
     *
     */
    protected function init() : void
    {
        if (!$this->getCoreController()->getPluginObject()->isActive()) {
            $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        }
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    final public function __call(string $name, array $arguments)
    {
        return call_user_func_array([$this, $this->getDefaultCommand()], []);
    }

    /**
     * @return string
     */
    abstract public function getDefaultCommand() : string;

    /**
     * @return \ilCrsGrpEnrollementUIHookGUI
     */
    public function getCoreController() : \ilCrsGrpEnrollementUIHookGUI
    {
        return $this->coreController;
    }

    /**
     * @return Container
     */
    public function getDic() : Container
    {
        return $this->dic;
    }

    /**
     * @return string
     * @throws \ReflectionException
     */
    final public function getControllerName() : string
    {
        return (new \ReflectionClass($this))->getShortName();
    }
}
