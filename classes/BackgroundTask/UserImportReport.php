<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollment\BackgroundTask;

use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\BooleanValue;
use ILIAS\BackgroundTasks\Types\SingleType;
use ILIAS\BackgroundTasks\Implementation\Tasks\AbstractUserInteraction;
use ILIAS\BackgroundTasks\Task\UserInteraction\Option;
use ILIAS\BackgroundTasks\Value;
use ILIAS\BackgroundTasks\Bucket;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\StringValue;
use ILIAS\BackgroundTasks\Implementation\Tasks\UserInteraction\UserInteractionOption;
use ilUtil;
use ilPHPOutputDelivery;

/**
 * Class UserImportReport
 * @author Timo Müller <timomueller@databay.de>
 */
class UserImportReport extends AbstractUserInteraction
{

    /**
     * @param Value[] $input The input value of this task.
     *
     * @return Option[] Options are buttons the user can press on this interaction.
     */
    public function getOptions(array $input)
    {
        return [
            new UserInteractionOption('download', 'download'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function interaction(array $input, Option $user_selected_option, Bucket $bucket)
    {
        /** @var StringValue */
        $csvString = $input[0];

        /** @var StringValue */
        $csvName = $input[1];

        if ($user_selected_option->getValue() == 'download') {
            $outputter = new ilPHPOutputDelivery();
            $outputter->start('User Data String');
            ilUtil::deliverData($csvString->getValue(), $csvName->getValue() . '.csv', 'text/csv');
            $outputter->stop();
        }

        return $csvString;
    }

    /**
     * @inheritdoc
     */
    public function getInputTypes()
    {
        return [
            new SingleType(StringValue::class), // 0. Data String
            new SingleType(StringValue::class), // 1. CSV Name
        ];
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

    /**
     * @inheritdoc
     */
    public function getOutputType()
    {
        return new SingleType(BooleanValue::class);
    }
}
