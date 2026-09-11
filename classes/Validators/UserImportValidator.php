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

class UserImportValidator
{
    /**
     * @throws FileNotReadableException
     * @throws CsvEmptyException
     * @throws InvalidCsvColumnDefinitionException
     */
    public function validate($import_file): void
    {
        $tmp_file = fopen($import_file, 'rb');

        if (!$tmp_file || !is_resource($tmp_file)) {
            throw new FileNotReadableException('CSV not readable');
        }

        $no_elements_flag = true;
        while (($row = fgetcsv($tmp_file, 0, ';')) !== false) {
            $no_elements_flag = false;
            if (count($row) > 1) {
                throw new InvalidCsvColumnDefinitionException("Data format not correct");
            }
        }

        if ($no_elements_flag) {
            throw new CsvEmptyException('CSV empty');
        }

        fclose($tmp_file);
    }
}
