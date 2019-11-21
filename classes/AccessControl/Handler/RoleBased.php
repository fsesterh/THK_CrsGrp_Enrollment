<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\AccessControl\Handler;

use ILIAS\Plugin\Proctorio\AccessControl\AccessHandler;
use ILIAS\Plugin\Proctorio\AccessControl\Acl;
use ILIAS\Plugin\Proctorio\Administration\GeneralSettings\Settings;

/**
 * Class RoleBased
 * @package ILIAS\Plugin\Proctorio\AccessControl\Handler
 * @author Michael Jansen <mjansen@databay.de>
 */
class RoleBased implements AccessHandler
{
    /** @var \ilObjUser */
    private $actor;
    /** @var \ilRbacReview */
    private $rbacReview;
    /** @var Acl */
    private $acl;
    /** @var Settings */
    private $settings;
    /** @var int[] */
    protected $assignedGlobalRoles = [];

    /**
     * RoleBasedAccessHandler constructor.
     * @param \ilObjUser    $actor
     * @param Settings      $settings
     * @param \ilRbacReview $rbacReview
     * @param Acl           $acl
     */
    public function __construct(
        \ilObjUser $actor,
        Settings $settings,
        \ilRbacReview $rbacReview,
        Acl $acl
    ) {
        $this->actor = $actor;
        $this->settings = $settings;
        $this->rbacReview = $rbacReview;
        $this->acl = $acl;

        $this->assignedGlobalRoles = $this->rbacReview->assignedGlobalRoles($this->actor->getId());
    }

    /**
     * @param \ilObjUser $actor
     * @return self
     */
    public function withActor(\ilObjUser $actor) : AccessHandler
    {
        $clone = clone $this;
        $clone->actor = $actor;
        $clone->assignedGlobalRoles = $this->rbacReview->assignedGlobalRoles($actor->getId());

        return $clone;
    }

    /**
     * @return bool
     */
    private function isActorAnonymous() : bool
    {
        return $this->actor->isAnonymous() || (int) $this->actor->getId() === 0;
    }

    /**
     * @param string $resource
     * @param string $privilege
     * @return bool
     */
    private function hasAccess(string $resource, string $privilege) : bool
    {
        $hasAccess = false;

        foreach ($this->settings->getAclRoleToGlobalRoleMappings() as $aclRole => $globalRoleIds) {
            $roles = array_intersect($globalRoleIds, $this->assignedGlobalRoles);
            if (count($roles) > 0) {
                $hasAccess = $this->acl->isAllowed($aclRole, $resource, $privilege);
                if ($hasAccess) {
                    return $hasAccess;
                }
            }
        }

        return $hasAccess;
    }

    /**
     * @inheritDoc
     */
    public function mayTakeTests(\ilObjTest $test) : bool
    {
        return !$this->isActorAnonymous();
    }

    /**
     * @inheritDoc
     */
    public function mayReadTestReviews(\ilObjTest $test) : bool
    {
        return !$this->isActorAnonymous() && $this->hasAccess('exam_review', 'read');
    }

    /**
     * @inheritDoc
     */
    public function mayReadTestSettings(\ilObjTest $test) : bool
    {
        return !$this->isActorAnonymous() && $this->hasAccess('exam_settings', 'read');
    }

    /**
     * @inheritDoc
     */
    public function mayWriteTestSettings(\ilObjTest $test) : bool
    {
        return !$this->isActorAnonymous() && $this->hasAccess('exam_settings', 'write');
    }
}