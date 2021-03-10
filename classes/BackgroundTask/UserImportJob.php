<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollment\BackgroundTask;

use ILIAS\BackgroundTasks\Implementation\Tasks\AbstractJob;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\IntegerValue;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\StringValue;
use ILIAS\BackgroundTasks\Observer;
use ILIAS\BackgroundTasks\Types\SingleType;
use ILIAS\Plugin\CrsGrpEnrollment\Repositories\UserImportRepository;
use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\Repository\DataNotFoundException;
use ILIAS\Plugin\CrsGrpEnrollment\Models\UserImport;
use ilObjUser;
use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\UserNotFoundException;
use ilObjCourse;
use ilObjectFactory;
use ilObjGroup;
use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\AssociatedObjectNotFoundException;
use ILIAS\Plugin\CrsGrpEnrollment\Services\UserImportService;
use ilCSVWriter;

/**
 * Class UserImportJob
 * @author Timo Müller <timomueller@databay.de>
 */
class UserImportJob extends AbstractJob
{
    /**
     * @var ilCSVWriter
     */
    protected $csv = null;

    /**
     * @inheritdoc
     */
    public function run(array $input, Observer $observer)
    {
        global $DIC;

        $plugin = null;
        $this->csv = new ilCSVWriter();

        if (
            $DIC['ilPluginAdmin']->exists('Services', 'UIComponent', 'uihk', 'CrsGrpEnrollment') &&
            $DIC['ilPluginAdmin']->isActive('Services', 'UIComponent', 'uihk', 'CrsGrpEnrollment')
        ) {
            $plugin = call_user_func_array(
                array(get_class($DIC['ilPluginAdmin']), 'getPluginObject'),
                ['Services', 'UIComponent', 'uihk', 'CrsGrpEnrollment']
            );
        }

        if (!$plugin) {
            $this->csv->addColumn('Fatal Error! Plugin not installed!');
            return $this->csv;
        }

        $userImportRepository = new UserImportRepository();
        $userImportService = new UserImportService($plugin);

        $userImport = null;
        try {
            $userImport = $userImportRepository->findOneById((int) $input[0]->getValue());

            $DIC->logger()->root()->info(sprintf(
                'Start User Import with this users: %s',
                json_encode($userImport->getData(), JSON_PRETTY_PRINT)
            ));

            if (ilObjUser::_lookupLogin($userImport->getUser()) === false) {
                throw new UserNotFoundException('Executive User not found');
            }

            $object = ilObjectFactory::getInstanceByObjId($userImport->getObjId());
            if ($object === false || ($object instanceof ilObjCourse || $object instanceof ilObjGroup) === false) {
                throw new AssociatedObjectNotFoundException('Associated object not found');
            }

            if ($object instanceof ilObjCourse) {
                $this->csv = $userImportService->importUserToCourse($object, $userImport);
            }

            if ($object instanceof ilObjGroup) {
                $this->csv = $userImportService->importUserToGroup($object, $userImport);
            }
        } catch (DataNotFoundException $e) {
            $this->csv->addColumn(sprintf($plugin->txt('report_csv_no_user_import_found'), $input[0]->getValue()));
        } catch (UserNotFoundException $e) {
            $this->csv->addColumn(sprintf($plugin->txt('report_csv_no_executive_user_found'), $userImport->getUser()));
        } catch (AssociatedObjectNotFoundException $e) {
            $this->csv->addColumn(sprintf($plugin->txt('report_csv_no_associated_object_found'), $userImport->getObjId()));
        }

        $output = new StringValue();
        $output->setValue($this->csv->getCSVString());

        if (null !== $userImport) {
            $userImportRepository->delete($userImport);
        }

        return $output;
    }

    /**
     * @inheritdoc
     */
    public function getInputTypes()
    {
        return [
            new SingleType(IntegerValue::class), // 0. User Import Id
        ];
    }

    /**
     * @inheritdoc
     */
    public function getOutputType()
    {
        return new SingleType(StringValue::class);
    }

    /**
     * @inheritdoc
     */
    public function isStateless()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getExpectedTimeOfTaskInSeconds()
    {
        return 30;
    }
}
