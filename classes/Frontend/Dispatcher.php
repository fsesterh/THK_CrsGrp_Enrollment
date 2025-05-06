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

namespace ILIAS\Plugin\CrsGrpEnrollment\Frontend;

use ilCrsGrpEnrollmentUIHookGUI;
use ILIAS\DI\Container;
use ILIAS\Plugin\CrsGrpEnrollment\Frontend\Controller\Base;

/**
 * Class Dispatcher
 *
 * @package ILIAS\Plugin\CrsGrpEnrollment\Frontend
 * @author  Timo Müller <timomueller@databay.de>
 */
class Dispatcher
{
    private static ?self $instance = null;
    private ilCrsGrpEnrollmentUIHookGUI $coreController;
    private string $defaultController = '';
    private Container $dic;

    private function __clone()
    {
    }

    private function __construct(ilCrsGrpEnrollmentUIHookGUI $baseController, string $defaultController = '')
    {
        $this->coreController = $baseController;
        $this->defaultController = $defaultController;
    }

    public function setDic(Container $dic): void
    {
        $this->dic = $dic;
    }

    public static function getInstance(ilCrsGrpEnrollmentUIHookGUI $baseController): self
    {
        if (self::$instance === null) {
            self::$instance = new self($baseController);
        }

        return self::$instance;
    }

    public function dispatch(string $cmd): string
    {
        $controller = $this->getController($cmd);
        $command = $this->getCommand($cmd);
        $controller = $this->instantiateController($controller);

        return $controller->$command();
    }

    protected function getController(string $cmd): string
    {
        $parts = explode('.', $cmd);

        if (count($parts) >= 1) {
            return $parts[0];
        }

        return $this->defaultController ? $this->defaultController : 'Error';
    }

    protected function getCommand(string $cmd): string
    {
        $parts = explode('.', $cmd);

        if (count($parts) === 2) {
            $cmd = $parts[1];

            return $cmd . 'Cmd';
        }

        return '';
    }

    protected function instantiateController(string $controller): Base
    {
        $class = "ILIAS\\Plugin\\CrsGrpEnrollment\\Frontend\\Controller\\$controller";

        return new $class($this->getCoreController(), $this->dic);
    }

    protected function getControllerPath(): string
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

    protected function requireController(string $controller): void
    {
        require_once $this->getControllerPath() . $controller . '.php';
    }

    public function getCoreController(): ilCrsGrpEnrollmentUIHookGUI
    {
        return $this->coreController;
    }

    public function setCoreController(ilCrsGrpEnrollmentUIHookGUI $coreController): void
    {
        $this->coreController = $coreController;
    }
}
