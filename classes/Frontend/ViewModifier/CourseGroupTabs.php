<?php

declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollment\Frontend\ViewModifier;

use ilCalendarCategoryGUI;
use ilCalendarPresentationGUI;
use ilContainerStartObjectsGUI;
use ilCourseMembershipGUI;
use ilCourseParticipantsGroupsGUI;
use ilGroupMembershipGUI;
use ilMailMemberSearchGUI;
use ilMemberExportGUI;
use ilObjectCustomUserFieldsGUI;
use ilPublicUserProfileGUI;
use ilRepositoryGUI;
use ilTabsGUI;
use ilUIPluginRouterGUI;
use ilUsersGalleryGUI;

/**
 * Class CourseGroupTabs
 *
 * @package ILIAS\Plugin\CrsGrpEnrollment\Frontend\Controller
 * @author  Timo Müller <timomueller@databay.de>
 */
class CourseGroupTabs extends Base
{
    private function getContainerRefId(): int
    {
        $refId = $this->getRefId();

        if ($refId <= 0) {
            $refId = $this->getTargetRefId();
        }

        return $refId;
    }

    private function shouldRenderCustomCourseOrGroupTabs(): bool
    {
        $isBlackListedCommandClass = (
            (
                $this->isCommandClass(ilObjectCustomUserFieldsGUI::class) &&
                $this->isOneOfCommands(['editMember', 'saveMember', 'cancelEditMember',])
            ) || (
                $this->isCommandClass(ilCourseMembershipGUI::class) &&
                $this->isOneOfCommands(['printMembers', 'printMembersOutput'])
            ) || (
                $this->isCommandClass(ilGroupMembershipGUI::class) &&
                $this->isOneOfCommands(['printMembers', 'printMembersOutput'])
            ) ||
            $this->isCommandClass(ilContainerStartObjectsGUI::class) ||
            $this->isCommandClass(ilCalendarPresentationGUI::class) ||
            $this->isCommandClass(ilCalendarCategoryGUI::class) ||
            $this->isCommandClass(ilPublicUserProfileGUI::class) ||
            $this->isCommandClass(ilMailMemberSearchGUI::class) || (
                $this->isOneOfCommands(['create',]) &&
                $this->isBaseClass(ilRepositoryGUI::class)
            )
        );

        return !$isBlackListedCommandClass;
    }

    private function shouldRenderCourseOrGroupTabs(): bool
    {
        $shouldRenderCustomCourseTabs = $this->shouldRenderCustomCourseOrGroupTabs();
        if (!$shouldRenderCustomCourseTabs) {
            return false;
        }

        $isCourseMembershipSubTabContext = (
            $this->isCommandClass(ilCourseMembershipGUI::class) ||
            $this->isCommandClass(ilGroupMembershipGUI::class) ||
            $this->isCommandClass(ilCourseParticipantsGroupsGUI::class) ||
            $this->isCommandClass(ilUsersGalleryGUI::class) ||
            $this->isCommandClass(ilMemberExportGUI::class)
        );

        return $isCourseMembershipSubTabContext;
    }

    /**
     * @inheritDoc
     */
    public function shouldModifyHtml(string $component, string $part, array $parameters): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function modifyHtml(string $component, string $part, array $parameters): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function shouldModifyGUI(string $component, string $part, array $parameters): bool
    {
        if ('tabs' !== $part && 'sub_tabs' !== $part) {
            return false;
        }

        if (
            !$this->isObjectOfType('crs') &&
            !$this->isTargetObjectOfType('crs') &&
            !$this->isObjectOfType('grp') &&
            !$this->isTargetObjectOfType('grp')
        ) {
            return false;
        }

        if (!$this->shouldRenderCourseOrGroupTabs()) {
            return false;
        }

        if (!$this->coreAccessHandler->checkAccess('manage_members', '', $this->getContainerRefId())) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function modifyGUI(string $component, string $part, array $parameters): void
    {
        /** @var ilTabsGUI $tabs */
        $tabs = $parameters['tabs'];

        $this->ctrl->setParameterByClass(get_class($this->getCoreController()), 'ref_id', $this->getContainerRefId());
        $tabs->addSubTab(
            'course_group_import',
            $this->getCoreController()->getPluginObject()->txt("course_group_import"),
            $this->ctrl->getLinkTargetByClass(
                [ilUIPluginRouterGUI::class, get_class($this->getCoreController())],
                'CourseGroupEnrollment.showImportForm'
            ),
        );
    }
}
