<?php declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollment\Frontend;

use \ILIAS\DI\Container;
use \ILIAS\Plugin\CrsGrpEnrollment\Frontend\Controller\Base;

/**
 * Class Dispatcher
 * @package ILIAS\Plugin\CrsGrpEnrollment\Frontend
 * @author  Timo Müller <timomueller@databay.de>
 */
class Dispatcher
{
    /** @var self */
    private static $instance = null;
    /** @var \ilCrsGrpEnrollmentUIHookGUI */
    private $coreController;
    /** @var string */
    private $defaultController = '';
    /** @var Container */
    private $dic;

    /**
     *
     */
    private function __clone()
    {
    }

    /**
     * Dispatcher constructor.
     * @param \ilCrsGrpEnrollmentUIHookGUI $baseController
     * @param string $defaultController
     */
    private function __construct(\ilCrsGrpEnrollmentUIHookGUI $baseController, string $defaultController = '')
    {
        $this->coreController = $baseController;
        $this->defaultController = $defaultController;
    }

    /**
     * @param Container $dic
     */
    public function setDic(Container $dic) : void
    {
        $this->dic = $dic;
    }

    /**
     * @param \ilCrsGrpEnrollmentUIHookGUI $baseController
     * @return self
     */
    public static function getInstance(\ilCrsGrpEnrollmentUIHookGUI $baseController) : self
    {
        if (self::$instance === null) {
            self::$instance = new self($baseController);
        }

        return self::$instance;
    }

    /**
     * @param string $cmd
     * @return string
     */
    public function dispatch(string $cmd) : string
    {
        $controller = $this->getController($cmd);
        $command = $this->getCommand($cmd);
        $controller = $this->instantiateController($controller);

        return $controller->$command();
    }

    /**
     * @param string $cmd
     * @return string
     */
    protected function getController(string $cmd) : string
    {
        $parts = explode('.', $cmd);

        if (count($parts) >= 1) {
            return $parts[0];
        }

        return $this->defaultController ? $this->defaultController : 'Error';
    }

    /**
     * @param string $cmd
     * @return string
     */
    protected function getCommand(string $cmd) : string
    {
        $parts = explode('.', $cmd);

        if (count($parts) === 2) {
            $cmd = $parts[1];

            return $cmd . 'Cmd';
        }

        return '';
    }

    /**
     * @param string $controller
     * @return Base
     */
    protected function instantiateController(string $controller) : Base
    {
        $class = "ILIAS\\Plugin\\CrsGrpEnrollment\\Frontend\\Controller\\$controller";

        return new $class($this->getCoreController(), $this->dic);
    }

    /**
     * @return string
     */
    protected function getControllerPath() : string
    {
        $path = $this->getCoreController()->getPluginObject()->getDirectory() .
            DIRECTORY_SEPARATOR .
            'classes' .
            DIRECTORY_SEPARATOR .
            'Frontend' .
            DIRECTORY_SEPARATOR .
            'Controller' .
            DIRECTORY_SEPARATOR;

        return $path;
    }

    /**
     * @param string $controller
     */
    protected function requireController(string $controller) : void
    {
        require_once $this->getControllerPath() . $controller . '.php';
    }

    /**
     * @return \ilCrsGrpEnrollmentUIHookGUI
     */
    public function getCoreController() : \ilCrsGrpEnrollmentUIHookGUI
    {
        return $this->coreController;
    }

    /**
     * @param \ilCrsGrpEnrollmentUIHookGUI $coreController
     */
    public function setCoreController(\ilCrsGrpEnrollmentUIHookGUI $coreController) : void
    {
        $this->coreController = $coreController;
    }
}
