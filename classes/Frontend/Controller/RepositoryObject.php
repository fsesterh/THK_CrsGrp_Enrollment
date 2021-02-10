<?php declare(strict_types=1);
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollment\Frontend\Controller;

/**
 * Class Course
 * @package ILIAS\Plugin\CrsGrpEnrollment\Frontend\Controller
 * @author Timo Müller <timomueller@databay.de>
 */
abstract class RepositoryObject extends Base
{
    /**
     * @return string
     */
    abstract public function getObjectGuiClass() : string;

    /**
     * @throws \ReflectionException
     */
    protected function drawHeader() : void
    {
        $class = $this->getObjectGuiClass();
        $object = new $class();

        $reflectionMethod = new \ReflectionMethod($class, 'setTitleAndDescription');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($object);

        $this->dic['ilLocator']->addRepositoryItems($this->getRefId());
        $this->pageTemplate->setLocator();
    }
}
