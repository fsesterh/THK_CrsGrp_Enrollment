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

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

abstract class RepositoryObject extends Base
{
    abstract public function getObjectGuiClass(): string;

    abstract public function getConstructorArgs(): array;

    /**
     * @throws ReflectionException
     */
    protected function drawHeader(): void
    {
        $class = $this->getObjectGuiClass();
        $refl = new ReflectionClass($class);
        $object = $refl->newInstanceArgs($this->getConstructorArgs());

        $reflection_method = new ReflectionMethod($class, 'setTitleAndDescription');
        $reflection_method->invoke($object);

        $this->dic['ilLocator']->addRepositoryItems($this->getRefId());
        $this->page_template->setLocator();
    }
}
