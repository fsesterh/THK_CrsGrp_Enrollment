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

namespace ILIAS\Plugin\CrsGrpEnrollment\Validators;

use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\CsvEmptyException;
use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\FileNotReadableException;
use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\InvalidCsvColumnDefinitionException;

/**
 * Class UserImportValidator
 *
 * @package ILIAS\Plugin\CrsGrpEnrollment\Validators
 * @author  Timo Müller <timomueller@databay.de>
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
