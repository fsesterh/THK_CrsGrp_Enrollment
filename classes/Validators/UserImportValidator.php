<?php declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollment\Validators;

use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\InvalidCsvColumnDefinitionException;
use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\FileNotReadableException;
use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\CsvEmptyException;

/**
 * Class UserImportValidator
 * @package ILIAS\Plugin\CrsGrpEnrollment\Validators
 * @author Timo Müller <timomueller@databay.de>
 */
class UserImportValidator
{
    public function validate($importFile)
    {
        $tmpFile = fopen($importFile, "r");

        if (!$tmpFile || !is_resource($tmpFile)) {
            throw new FileNotReadableException('CSV not readable');
        }

        $noElementsFlag = true;
        while (($row = fgetcsv($tmpFile, 0, ';')) !== false) {
            $noElementsFlag = false;
            if (count($row) > 1) {
                throw new InvalidCsvColumnDefinitionException("Data format not correct");
            }
        }

        if ($noElementsFlag) {
            throw new CsvEmptyException('CSV empty');
        }


        if (is_resource($tmpFile)) {
            fclose($tmpFile);
        }
    }
}
