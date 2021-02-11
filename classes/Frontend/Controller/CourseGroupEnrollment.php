<?php declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollment\Frontend\Controller;

use ilFileInputGUI;
use ilLink;
use ilObjCourseGUI;
use ilObjectFactory;
use ilObjGroupGUI;
use ilPropertyFormGUI;
use ilUIPluginRouterGUI;
use ilUtil;

/**
 * Class CourseGroupEnrollment
 * @package ILIAS\Plugin\CrsGrpEnrollment\Frontend\Controller
 * @author Timo Müller <timomueller@databay.de>
 */
class CourseGroupEnrollment extends RepositoryObject
{
    /**
     * @var \ilObject $object
     */
    private $object;

    /**
     * @inheritdoc
     */
    public function getDefaultCommand() : string
    {
        return 'showSettingsCmd';
    }

    /**
     * @inheritdoc
     */
    public function getObjectGuiClass() : string
    {
        if ($this->isObjectOfType('crs')) {
            return ilObjCourseGUI::class;
        }

        return ilObjGroupGUI::class;
    }

    /**
     * @inheritDoc
     */
    public function getConstructorArgs(): array
    {
        if ($this->isObjectOfType('crs')) {
            return [];
        }

        return [
            null,
            $this->getRefId(),
            true
        ];
    }


    /**
     * @inheritdoc
     */
    protected function init() : void
    {
        global $DIC;
        $ctrl = $DIC->ctrl();
        if (version_compare(ILIAS_VERSION_NUMERIC, '6.0', '>=')) {
            $this->pageTemplate->loadStandardTemplate();
        } else {
            $this->pageTemplate->getStandardTemplate();
        }

        parent::init();

        $this->drawHeader();

        if (0 === $this->getRefId() || !$this->coreAccessHandler->checkAccess('write', '', $this->getRefId())) {
            $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        }
        $this->object = ilObjectFactory::getInstanceByRefId($this->getRefId());

        $tabs = $DIC->tabs();
        $tabs->setBackTarget(
            $this->lng->txt('back'),
            ilLink::_getLink(
                $this->getRefId(),
                $this->object->getType()
            )
        );
    }

    /**
     * @return ilPropertyFormGUI
     */
    private function buildForm() : ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $this->ctrl->setParameterByClass(get_class($this->getCoreController()), 'ref_id', $this->object->getRefId());
        $form->setFormAction($this->ctrl->getFormActionByClass([ilUIPluginRouterGUI::class, get_class($this->getCoreController())], $this->getControllerName() . ".submitImportForm"));
        $form->setTitle($this->getCoreController()->getPluginObject()->txt("course_group_import_field"));
        $fileInput = new ilFileInputGUI($this->getCoreController()->getPluginObject()->txt('course_group_import_field'), 'userImportFile');
        $fileInput->setRequired(true);
        $fileInput->setSuffixes(["csv"]);
        $form->addItem($fileInput);
        $form->addCommandButton('CourseGroupEnrollment.submitImportForm', $this->lng->txt("save"));

        return $form;
    }

    /**
     * @return string
     */
    public function showImportFormCmd() : string
    {
        $form = $this->buildForm();

        return $form->getHTML();
    }

    /**
     * @return string
     */
    public function submitImportFormCmd() : string
    {
        $form = $this->buildForm();
        if ($form->checkInput()) {
            try {
                // TODO: Replace translation with a more meaningful description (your import is enqueued and processed asynchronously etc.) from the plugin txt
                ilUtil::sendSucces($this->getCoreController()->getPluginObject()->txt('import_successfully_enqueued'));

                // TODO Plausibilität grundsätlzich prüfen und Exception werfen, wenn ein Problem aufetreten ist
                // TODO: Store/Process file

                $this->ctrl->redirectByClass(
                    [ilUIPluginRouterGUI::class, get_class($this->getCoreController())],
                    $this->getControllerName() . '.showImportForm'
                );
            } catch (InvalidCsvColumnDefinition $e) {
                $form
                    ->getItemByPostVar('userImportFile')
                    ->setAlert($this->getCoreController()->getPluginObject()->txt('err_csv_file_different_row_width'));
                ilUtil::sendFailure($this->lng->txt('form_input_not_valid'));
            }
        }

        $form->setValuesByPost();

        return $form->getHtml();
    }
}
