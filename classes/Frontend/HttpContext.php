<?php

declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollment\Frontend;

use ilCtrl;
use ILIAS\HTTP\Wrapper\WrapperFactory;
use ILIAS\Refinery\Factory;
use ilObjectDataCache;
use ReflectionClass;

/**
 * Trait HttpContext
 *
 * @package ILIAS\Plugin\CrsGrpEnrollment\Frontend
 * @author  Timo Müller <timomueller@databay.de>
 */
trait HttpContext
{
    protected ilObjectDataCache $objectCache;
    protected WrapperFactory $httpWrapper;
    protected Factory $refinery;
    protected ilCtrl $ctrl;

    final public function isBaseClass(string $class): bool
    {
        $baseClass = $this->httpWrapper->query()->retrieve(
            "baseClass",
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->string(),
                $this->refinery->always("")
            ])
        );

        return strtolower($class) === strtolower($baseClass);
    }

    final public function hasBaseClass(): bool
    {
        return $this->httpWrapper->query()->has("baseClass");
    }

    final public function isCommandClass(string $class): bool
    {
        $cmdClass = $this->httpWrapper->query()->retrieve(
            "cmdClass",
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->string(),
                $this->refinery->always("")
            ])
        );

        return strtolower($class) === strtolower($cmdClass);
    }

    final public function hasCommandClass(): bool
    {
        return $this->httpWrapper->query()->has("cmdClass");
    }

    /**
     * @param string[] $cmdClasses
     */
    final public function isOneOfCommandClasses(array $cmdClasses): bool
    {
        if (!$this->hasCommandClass()) {
            return false;
        }

        $cmdClass = $this->httpWrapper->query()->retrieve(
            "cmdClass",
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->string(),
                $this->refinery->always("")
            ])
        );

        return in_array(strtolower($cmdClass), array_map(
            'strtolower',
            $cmdClasses
        ), true);
    }

    /**
     * @param string[] $commands
     */
    final public function isOneOfCommands(array $commands): bool
    {
        return in_array(strtolower((string) $this->ctrl->getCmd()), array_map(
            'strtolower',
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

            return strpos(strtolower((string) $this->ctrl->getCmd()), strtolower($command)) !== false;
        })) > 0;
    }

    final public function getRefId(): int
    {
        return $this->httpWrapper->query()->retrieve(
            "ref_id",
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->int(),
                $this->refinery->always(0)
            ])
        );
    }

    final public function getTargetRefId(): int
    {
        $target = $this->httpWrapper->query()->retrieve(
            "target",
            $this->refinery->byTrying([
                $this->refinery->kindlyTo()->string(),
                $this->refinery->always("")
            ])
        );
        if (preg_match('/^[a-zA-Z0-9]+_(\d+)$/', $target, $matches)) {
            if (isset($matches[1]) && is_numeric($matches[1]) && $matches[1] > 0) {
                return (int) $matches[1];
            }
        }

        return 0;
    }

    final public function isObjectOfId(int $objId): bool
    {
        $refId = $this->getRefId();
        if ($refId <= 0) {
            return false;
        }

        return ((int) $this->objectCache->lookupObjId($refId) === $objId);
    }

    final public function isObjectOfType(string $type): bool
    {
        $refId = $this->getRefId();
        if ($refId <= 0) {
            return false;
        }

        $objId = (int) $this->objectCache->lookupObjId($refId);

        return $this->objectCache->lookupType($objId) === $type;
    }

    final public function isTargetObjectOfType(string $type): bool
    {
        $refId = $this->getTargetRefId();
        if ($refId <= 0) {
            return false;
        }

        $objId = (int) $this->objectCache->lookupObjId($refId);

        return $this->objectCache->lookupType($objId) === $type;
    }
}
