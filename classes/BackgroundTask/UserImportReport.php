<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollment\BackgroundTask;

use ILIAS\BackgroundTasks\Bucket;
use ILIAS\BackgroundTasks\Implementation\Tasks\AbstractUserInteraction;
use ILIAS\BackgroundTasks\Implementation\Tasks\UserInteraction\UserInteractionOption;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\BooleanValue;
use ILIAS\BackgroundTasks\Implementation\Values\ScalarValues\StringValue;
use ILIAS\BackgroundTasks\Task\UserInteraction\Option;
use ILIAS\BackgroundTasks\Types\SingleType;
use ILIAS\BackgroundTasks\Value;
use ILIAS\Plugin\CrsGrpEnrollment\Repositories\UserImportRepository;
use ilPHPOutputDelivery;
use ilUtil;

/**
 * Class UserImportReport
 * @author Timo Müller <timomueller@databay.de>
 */
class UserImportReport extends AbstractUserInteraction
{
    const OPTION_DOWNLOAD = 'download';
    const OPTION_REMOVE = 'remove';

    /**
     * @param Value[] $input The input value of this task.
     *
     * @return Option[] Options are buttons the user can press on this interaction.
     */
    public function getOptions(array $input)
    {
        return [
            new UserInteractionOption(self::OPTION_DOWNLOAD, self::OPTION_DOWNLOAD),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getRemoveOption()
    {
        return new UserInteractionOption(self::OPTION_REMOVE, self::OPTION_REMOVE);
    }

    /**
     * @inheritdoc
     */
    public function interaction(array $input, Option $user_selected_option, Bucket $bucket)
    {
        if ($user_selected_option->getValue() === self::OPTION_DOWNLOAD) {
            /** @var StringValue */
            $csvString = $input[0];
            /** @var StringValue */
            $csvName = $input[1];

            $outputter = new ilPHPOutputDelivery();
            $outputter->start($csvName->getValue() . '.csv', 'text/csv');
            ilUtil::deliverData($csvString->getValue(), $csvName->getValue() . '.csv', 'text/csv');
            $outputter->stop();
            return $csvString;
        }

        if ($user_selected_option->getValue() === self::OPTION_REMOVE) {
        }

        return '';
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
