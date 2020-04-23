<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Frontend;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Trait HttpContext
 * @package ILIAS\Plugin\Proctorio\Frontend
 * @author Michael Jansen <mjansen@databay.de>
 */
trait HttpContext
{
    /** @var \ilObjectDataCache */
    protected $objectCache;
    /** @var ServerRequestInterface */
    protected $httpRequest;

    /**
     * @param string $class
     * @return bool
     */
    final public function isBaseClass(string $class) : bool
    {
        $baseClass = (string) ($this->httpRequest->getQueryParams()['baseClass'] ?? '');

        return strtolower($class) === strtolower($baseClass);
    }

    /**
     * @param string $class
     * @return bool
     */
    final public function isCommandClass(string $class) : bool
    {
        $cmdClass = (string) ($this->httpRequest->getQueryParams()['cmdClass'] ?? '');

        return strtolower($class) === strtolower($cmdClass);
    }

    /**
     * @return int
     */
    final public function getRefId() : int
    {
        $refId = (int) ($this->httpRequest->getQueryParams()['ref_id'] ?? 0);

        return $refId;
    }

    /**
     * @return int
     */
    final public function getPreviewRefId() : int
    {
        $refId = (int) ($this->httpRequest->getQueryParams()['intro_item_ref_id'] ?? 0);

        return $refId;
    }

    /**
     * @return int
     */
    final public function getTargetRefId() : int
    {
        $matches = null;
        if (preg_match('/^[a-zA-Z0-9]+_(\d+)$/', ((string) $this->httpRequest->getQueryParams()['target'] ?? ''), $matches)) {
            if (is_array($matches) && isset($matches[1]) && is_numeric($matches[1]) && $matches[1] > 0) {
                return (int) $matches[1];
            }
        }

        return 0;
    }

    /**
     * @param int $objId
     * @return bool
     */
    final public function isObjectOfId(int $objId) : bool
    {
        $refId = $this->getRefId();
        if ($refId <= 0) {
            return false;
        }

        return ((int) $this->objectCache->lookupObjId($refId) === $objId);
    }

    /**
     * @param string $type
     * @return bool
     */
    final public function isObjectOfType(string $type) : bool
    {
        $refId = $this->getRefId();
        if ($refId <= 0) {
            return false;
        }

        $objId = (int) $this->objectCache->lookupObjId($refId);

        return $this->objectCache->lookupType($objId) === $type;
    }

    /**
     * @param string $type
     * @return bool
     */
    final public function isPreviewObjectOfType(string $type) : bool
    {
        $refId = $this->getPreviewRefId();
        if ($refId <= 0) {
            return false;
        }

        $objId = (int) $this->objectCache->lookupObjId($refId);

        return $this->objectCache->lookupType($objId) === $type;
    }


    /**
     * @param string $type
     * @return bool
     */
    final public function isTargetObjectOfType(string $type) : bool
    {
        $refId = $this->getTargetRefId();
        if ($refId <= 0) {
            return false;
        }

        $objId = (int) $this->objectCache->lookupObjId($refId);

        return $this->objectCache->lookupType($objId) === $type;
    }
}
