<?php declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollment\Frontend\Controller;

use ilFileInputGUI;
use ILIAS\FileUpload\DTO\ProcessingStatus;
use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\InvalidCsvColumnDefinitionException;
use ilLink;
use ilObjCourseGUI;
use ilObjectFactory;
use ilObjGroupGUI;
use ilPropertyFormGUI;
use ilUIPluginRouterGUI;
use ilUtil;
use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\FileNotReadableException;
use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\CsvEmptyException;
use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\CoulNotFindUploadedFileException;
use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\UploadRejectedException;

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
    public function getConstructorArgs() : array
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
        if (version_compare(ILIAS_VERSION_NUMERIC, '6.0', '>=')) {
            $this->pageTemplate->loadStandardTemplate();
        } else {
            $this->pageTemplate->getStandardTemplate();
        }

        parent::init();

        $this->drawHeader();

        if (0 === $this->getRefId() || !$this->coreAccessHandler->checkAccess('manage_members', '', $this->getRefId())) {
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
        $fileInput->setInfo($this->getCoreController()->getPluginObject()->txt('course_group_import_field_description'));
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
        global $DIC;
        $form = $this->buildForm();

        if ($form->checkInput()) {
            try {
                if (false === $DIC->upload()->hasBeenProcessed()) {
                    $DIC->upload()->process();
                }

                if (false === $DIC->upload()->hasUploads()) {
                    throw new CoulNotFindUploadedFileException($this->lng->txt('upload_error_file_not_found'));
                }

                $uploadResults = $DIC->upload()->getResults();
                $uploadResult = array_values($uploadResults)[0];
                if (!($uploadResult instanceof UploadResult)) {
                    throw new CoulNotFindUploadedFileException('Could not find upload result');
                }

                if ($uploadResult->getStatus()->getCode() === ProcessingStatus::REJECTED) {
                    throw new UploadRejectedException($uploadResult->getStatus()->getMessage());
                }

                $this->userImportValidator->validate($uploadResult->getPath());

                // TODO: Store/Process file
                $dataArray = $this->userImportService->convertCSVToArray($uploadResult->getPath());

                echo "<pre>";
                var_dump("Data Array", $dataArray);
                die();

                $this->ctrl->redirectByClass(
                    [ilUIPluginRouterGUI::class, get_class($this->getCoreController())],
                    $this->getControllerName() . '.showImportForm'
                );

                ilUtil::sendSuccess($this->getCoreController()->getPluginObject()->txt('import_successfully_enqueued'));
            } catch (InvalidCsvColumnDefinitionException $e) {
                $form
                    ->getItemByPostVar('userImportFile')
                    ->setAlert($this->getCoreController()->getPluginObject()->txt('err_csv_file_different_row_width'));
                ilUtil::sendFailure($this->lng->txt('form_input_not_valid'));
            } catch (FileNotReadableException $e) {
                $form
                    ->getItemByPostVar('userImportFile')
                    ->setAlert($this->getCoreController()->getPluginObject()->txt('err_csv_file_cannot_be_read'));
                ilUtil::sendFailure($this->lng->txt('form_input_not_valid'));
            } catch (CsvEmptyException $e) {
                $form
                    ->getItemByPostVar('userImportFile')
                    ->setAlert($this->getCoreController()->getPluginObject()->txt('err_csv_empty'));
                ilUtil::sendFailure($this->lng->txt('form_input_not_valid'));
            } catch (CoulNotFindUploadedFileException $e) {
                $form
                    ->getItemByPostVar('userImportFile')
                    ->setAlert($this->getCoreController()->getPluginObject()->txt('err_csv_empty'));
                ilUtil::sendFailure($this->lng->txt('upload_error_file_not_found'));
            } catch (UploadRejectedException $e) {
                $form
                    ->getItemByPostVar('userImportFile')
                    ->setAlert($e->getMessage());
                ilUtil::sendFailure($this->lng->txt('upload_error_file_not_found'));
            }
        }

        $form->setValuesByPost();

        return $form->getHtml();
    }
}
