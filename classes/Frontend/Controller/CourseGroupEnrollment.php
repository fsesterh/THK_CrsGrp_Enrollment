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

use ilFileInputGUI;
use ILIAS\FileUpload\DTO\ProcessingStatus;
use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\Plugin\CrsGrpEnrollment\Enum\ImportStatus;
use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\CouldNotFindUploadedFileException;
use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\CsvEmptyException;
use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\FileNotReadableException;
use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\InvalidCsvColumnDefinitionException;
use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\UploadRejectedException;
use ILIAS\Plugin\CrsGrpEnrollment\Models\UserImport;
use ILIAS\Plugin\CrsGrpEnrollment\Repositories\UserImportRepository;
use ILIAS\Plugin\CrsGrpEnrollment\Utils\UiUtil;
use ilLink;
use ilObjCourse;
use ilObjCourseGUI;
use ilObject;
use ilObjectFactory;
use ilObjGroup;
use ilObjGroupGUI;
use ilPropertyFormGUI;
use ilUIPluginRouterGUI;
use JsonException;

class CourseGroupEnrollment extends RepositoryObject
{
    private ilObject $object;
    private UiUtil $ui_util;

    public function getDefaultCommand(): string
    {
        return 'showSettingsCmd';
    }

    public function getObjectGuiClass(): string
    {
        if ($this->isObjectOfType('crs')) {
            return ilObjCourseGUI::class;
        }

        return ilObjGroupGUI::class;
    }

    public function getConstructorArgs(): array
    {
        return [
            null,
            $this->getRefId(),
            true
        ];
    }

    protected function init(): void
    {
        global $DIC;

        $this->ui_util = new UiUtil();

        $this->page_template->loadStandardTemplate();

        parent::init();

        $this->drawHeader();

        if (0 === $this->getRefId() || !$this->core_access_handler->checkAccess(
            'manage_members',
            '',
            $this->getRefId()
        )) {
            $this->error_handler->raiseError($this->lng->txt('permission_denied'), $this->error_handler->MESSAGE);
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

    private function buildForm(): ilPropertyFormGUI
    {
        $form = new ilPropertyFormGUI();
        $this->ctrl->setParameterByClass($this->getCoreController()::class, 'ref_id', $this->object->getRefId());
        $form->setFormAction($this->ctrl->getFormActionByClass(
            [
                ilUIPluginRouterGUI::class,
                $this->getCoreController()::class,
            ],
            $this->getControllerName() . '.submitImportForm'
        ));
        $form->setTitle($this->plugin->txt('course_group_import'));
        $file_input = new ilFileInputGUI(
            $this->plugin->txt('course_group_import_field'),
            'userImportFile'
        );
        $file_input->setRequired(true);
        $file_input->setSuffixes(['csv']);
        $file_input->setInfo($this->plugin->txt('course_group_import_field_description'));
        $form->addItem($file_input);
        $form->addCommandButton('CourseGroupEnrollment.submitImportForm', $this->lng->txt('import'));

        return $form;
    }

    public function showImportFormCmd(): string
    {
        return $this->buildForm()->getHTML();
    }

    public function submitImportFormCmd(): string
    {
        global $DIC;

        $user_import_repository = new UserImportRepository();
        $form = $this->buildForm();

        if ($form->checkInput()) {
            try {
                if (false === $DIC->upload()->hasBeenProcessed()) {
                    $DIC->upload()->process();
                }

                if (false === $DIC->upload()->hasUploads()) {
                    throw new CouldNotFindUploadedFileException($this->lng->txt('upload_error_file_not_found'));
                }

                $upload_results = $DIC->upload()->getResults();
                $upload_result = array_values($upload_results)[0];
                if (!($upload_result instanceof UploadResult)) {
                    throw new CouldNotFindUploadedFileException('Could not find upload result');
                }

                if ($upload_result->getStatus()->getCode() === ProcessingStatus::REJECTED) {
                    throw new UploadRejectedException($upload_result->getStatus()->getMessage());
                }

                $this->user_import_validator->validate($upload_result->getPath());

                $data_array = $this->user_import_service->convertCSVToArray($upload_result->getPath());

                $user_import = new UserImport();
                $user_import->setStatus(ImportStatus::PENDING);
                $user_import->setUser($DIC->user()->getId());
                $user_import->setCreatedTimestamp(time());
                $user_import->setData(json_encode($data_array, JSON_THROW_ON_ERROR));
                $user_import->setObjId($this->object->getId());

                $user_import = $user_import_repository->save($user_import);

                $object = ilObjectFactory::getInstanceByObjId($user_import->getObjId());
                if (($object instanceof ilObjCourse || $object instanceof ilObjGroup) === false) {
                    $csv_export_name = $this->plugin->txt('err_csv_empty') . '_' . date('dmY_H_i');
                }

                $this->ui_util->sendSuccess(
                    $this->plugin->txt('import_successfully_enqueued'),
                    true
                );

                $this->ctrl->redirectByClass(
                    [ilUIPluginRouterGUI::class, $this->getCoreController()::class],
                    $this->getControllerName() . '.showImportForm'
                );
            } catch (InvalidCsvColumnDefinitionException) {
                $form->getItemByPostVar('userImportFile')?->setAlert($this->plugin->txt('err_csv_file_different_row_width'));
                $this->ui_util->sendFailure($this->lng->txt('form_input_not_valid'));
            } catch (FileNotReadableException) {
                $form->getItemByPostVar('userImportFile')?->setAlert($this->plugin->txt('err_csv_file_cannot_be_read'));
                $this->ui_util->sendFailure($this->lng->txt('form_input_not_valid'));
            } catch (CsvEmptyException) {
                $form->getItemByPostVar('userImportFile')?->setAlert($this->plugin->txt('err_csv_empty'));
                $this->ui_util->sendFailure($this->lng->txt('form_input_not_valid'));
            } catch (CouldNotFindUploadedFileException) {
                $form->getItemByPostVar('userImportFile')?->setAlert($this->plugin->txt('err_csv_empty'));
                $this->ui_util->sendFailure($this->lng->txt('upload_error_file_not_found'));
            } catch (UploadRejectedException $e) {
                $form->getItemByPostVar('userImportFile')?->setAlert($e->getMessage());
                $this->ui_util->sendFailure($this->lng->txt('upload_error_file_not_found'));
            } catch (JsonException $e) {
                $form->getItemByPostVar('userImportFile')?->setAlert($e->getMessage());
                $this->ui_util->sendFailure($this->lng->txt('form_input_not_valid'));
            }
        }

        $form->setValuesByPost();

        return $form->getHtml();
    }
}
