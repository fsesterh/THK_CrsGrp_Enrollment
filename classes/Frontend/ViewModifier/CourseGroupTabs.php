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

namespace ILIAS\Plugin\CrsGrpEnrollment\Frontend\ViewModifier;

use ilCalendarCategoryGUI;
use ilCalendarPresentationGUI;
use ilContainerStartObjectsGUI;
use ilCourseMembershipGUI;
use ilCourseParticipantsGroupsGUI;
use ilGroupMembershipGUI;
use ilPublicUserProfileGUI;
use ilMailMemberSearchGUI;
use ilMemberExportGUI;
use ilObjectCustomUserFieldsGUI;
use ilRepositoryGUI;
use ilTabsGUI;
use ilUIPluginRouterGUI;
use ilUsersGalleryGUI;

class CourseGroupTabs extends Base
{
    private function getContainerRefId(): int
    {
        $ref_id = $this->getRefId();

        if ($ref_id <= 0) {
            $ref_id = $this->getTargetRefId();
        }

        return $ref_id;
    }

    private function shouldRenderCustomCourseOrGroupTabs(): bool
    {
        $is_black_listed_command_class = (
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

        return !$is_black_listed_command_class;
    }

    private function shouldRenderCourseOrGroupTabs(): bool
    {
        $should_render_custom_course_tabs = $this->shouldRenderCustomCourseOrGroupTabs();
        if (!$should_render_custom_course_tabs) {
            return false;
        }

        return (
            $this->isCommandClass(ilCourseMembershipGUI::class) ||
            $this->isCommandClass(ilGroupMembershipGUI::class) ||
            $this->isCommandClass(ilCourseParticipantsGroupsGUI::class) ||
            $this->isCommandClass(ilUsersGalleryGUI::class) ||
            $this->isCommandClass(ilMemberExportGUI::class)
        );
    }

    public function shouldModifyHtml(string $component, string $part, array $parameters): bool
    {
        return false;
    }

    public function modifyHtml(string $component, string $part, array $parameters): array
    {
        return [];
    }

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

        if (!$this->core_access_handler->checkAccess('manage_members', '', $this->getContainerRefId())) {
            return false;
        }

        return true;
    }

    public function modifyGUI(string $component, string $part, array $parameters): void
    {
        /** @var ilTabsGUI $tabs */
        $tabs = $parameters['tabs'];

        $this->ctrl->setParameterByClass($this->getCoreController()::class, 'ref_id', $this->getContainerRefId());
        $tabs->addSubTab(
            'course_group_import',
            $this->getCoreController()->getPluginObject()->txt("course_group_import"),
            $this->ctrl->getLinkTargetByClass(
                [ilUIPluginRouterGUI::class, $this->getCoreController()::class],
                'CourseGroupEnrollment.showImportForm'
            ),
        );
    }
}
