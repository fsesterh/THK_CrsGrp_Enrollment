<?php
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollment\BackgroundTask;

use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\BooleanValue;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\IntegerValue;
use ILIAS\BackgroundTasks\Types\SingleType;
use ILIAS\BackgroundTasks\Implementation\Tasks\AbstractUserInteraction;
use ILIAS\BackgroundTasks\Task\UserInteraction\Option;
use ILIAS\BackgroundTasks\Value;
use ILIAS\BackgroundTasks\Bucket;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\StringValue;
use ILIAS\BackgroundTasks\Implementation\Tasks\UserInteraction\UserInteractionOption;

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
            new UserInteractionOption("download", "download"),
        ];
    }

    /**
     * @inheritdoc
     */
    public function interaction(array $input, Option $user_selected_option, Bucket $bucket)
    {
        /** @var StringValue */
        $integerValue = $input[0];
        global $DIC;

        if ($user_selected_option->getValue() == "download") {
            $outputter = new \ilPHPOutputDelivery();
            $outputter->start("User Data String");
            echo $integerValue->getValue();
            $outputter->stop();
        }

        return $integerValue;
    }

    /**
     * @inheritdoc
     */
    public function getInputTypes()
    {
        return [
//            new SingleType(IntegerValue::class), // 0. User Import Id
            new SingleType(StringValue::class), // 0. Data String
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
