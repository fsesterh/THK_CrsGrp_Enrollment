<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Frontend\ViewModifier;

use ILIAS\DI\Container;
use ILIAS\Plugin\Proctorio\Frontend\HttpContext;
use ILIAS\Plugin\Proctorio\Frontend\ViewModifier;
use ILIAS\Plugin\Proctorio\Service\Proctorio\Impl;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ViewModifier
 * @package ILIAS\Plugin\Proctorio\Frontend\ViewModifier
 * @author Michael Jansen <mjansen@databay.de>
 */
abstract class Base implements ViewModifier
{
    use HttpContext;

    /** @var \ilTemplate */
    protected $pageTemplate;
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
    protected $lng;
    /** @var \ilProctorioUIHookGUI */
    public $coreController;
    /** @var \ilTemplate */
    protected $mainTemplate;
    /** @var Impl */
    protected $service;
    /** @var ServerRequestInterface */
    protected $httpRequest;

    /**
     * Base constructor.
     * @param \ilProctorioUIHookGUI $controller
     * @param Container $dic
     */
    final public function __construct(\ilProctorioUIHookGUI $controller, Container $dic)
    {
        $this->coreController = $controller;
        $this->dic = $dic;

        $this->httpRequest = $dic->http()->request();
        $this->objectCache = $dic['ilObjDataCache'];

        $this->mainTemplate = $dic->ui()->mainTemplate();
        $this->ctrl = $dic->ctrl();
        $this->lng = $dic->language();
        $this->pageTemplate = $dic->ui()->mainTemplate();
        $this->user = $dic->user();
        $this->uiRenderer = $dic->ui()->renderer();
        $this->uiFactory = $dic->ui()->factory();
        $this->coreAccessHandler = $dic->access();
        $this->errorHandler = $dic['ilErr'];
        $this->service = $dic['plugin.proctorio.service'];
    }

    /**
     * @return \ilProctorioUIHookGUI
     */
    public function getCoreController() : \ilProctorioUIHookGUI
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
    final public function getClassName() : string
    {
        return (new \ReflectionClass($this))->getShortName();
    }
}