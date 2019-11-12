<?php
/* Copyright (c) 1998-2016 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Frontend\Controller;

/**
 * Class Course
 * @package ILIAS\Plugin\Proctorio\Frontend\Controller
 * @author Michael Jansen <mjansen@databay.de>
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
        
        $this->tpl->setLocator();
    }
}