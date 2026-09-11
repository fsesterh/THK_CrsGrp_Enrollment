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

use ilCtrl;
use ILIAS\HTTP\Wrapper\WrapperFactory;
use ILIAS\Refinery\Factory;
use ilObjectDataCache;
use ReflectionClass;

trait HttpContext
{
    protected ilObjectDataCache $object_cache;
    protected WrapperFactory $http_wrapper;
    protected Factory $refinery;
    protected ilCtrl $ctrl;

    final public function isBaseClass(string $class): bool
    {
        $base_class = $this->http_wrapper->query()->retrieve(
            "baseClass",
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->string(),
                $this->refinery->always("")
            ])
        );

        return strtolower($class) === strtolower($base_class);
    }

    final public function hasBaseClass(): bool
    {
        return $this->http_wrapper->query()->has("baseClass");
    }

    final public function isCommandClass(string $class): bool
    {
        $cmd_class = $this->http_wrapper->query()->retrieve(
            "cmdClass",
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->string(),
                $this->refinery->always("")
            ])
        );

        return strtolower($class) === strtolower($cmd_class);
    }

    final public function hasCommandClass(): bool
    {
        return $this->http_wrapper->query()->has("cmdClass");
    }

    /**
     * @param string[] $cmd_classes
     */
    final public function isOneOfCommandClasses(array $cmd_classes): bool
    {
        if (!$this->hasCommandClass()) {
            return false;
        }

        $cmd_class = $this->http_wrapper->query()->retrieve(
            "cmdClass",
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->string(),
                $this->refinery->always("")
            ])
        );

        return in_array(strtolower($cmd_class), array_map(
            strtolower(...),
            $cmd_classes
        ), true);
    }

    /**
     * @param string[] $commands
     */
    final public function isOneOfCommands(array $commands): bool
    {
        return in_array(strtolower((string) $this->ctrl->getCmd()), array_map(
            strtolower(...),
            $commands
        ), true);
    }

    /**
     * @param string[] $commands
     */
    final public function isOneOfPluginCommandsLike(array $commands): bool
    {
        return count(array_filter($commands, function (string $command): bool {
            if (class_exists($command)) {
                $command = (new ReflectionClass($command))->getShortName();
            }

            return str_contains(strtolower((string) $this->ctrl->getCmd()), strtolower($command));
        })) > 0;
    }

    final public function getRefId(): int
    {
        return $this->http_wrapper->query()->retrieve(
            "ref_id",
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->int(),
                $this->refinery->always(0)
            ])
        );
    }

    final public function getTargetRefId(): int
    {
        $target = $this->http_wrapper->query()->retrieve(
            "target",
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->string(),
                $this->refinery->always("")
            ])
        );
        if (preg_match('/^[a-zA-Z0-9]+_(\d+)$/', $target, $matches) && $matches[1] > 0) {
            return (int) $matches[1];
        }

        return 0;
    }

    final public function isObjectOfId(int $obj_id): bool
    {
        $ref_id = $this->getRefId();
        if ($ref_id <= 0) {
            return false;
        }

        return ($this->object_cache->lookupObjId($ref_id) === $obj_id);
    }

    final public function isObjectOfType(string $type): bool
    {
        $ref_id = $this->getRefId();
        if ($ref_id <= 0) {
            return false;
        }

        $obj_id = $this->object_cache->lookupObjId($ref_id);

        return $this->object_cache->lookupType($obj_id) === $type;
    }

    final public function isTargetObjectOfType(string $type): bool
    {
        $ref_id = $this->getTargetRefId();
        if ($ref_id <= 0) {
            return false;
        }

        $obj_id = $this->object_cache->lookupObjId($ref_id);

        return $this->object_cache->lookupType($obj_id) === $type;
    }
}
