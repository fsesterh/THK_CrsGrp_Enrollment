<?php declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollment\Validators;

use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\InvalidCsvColumnDefinitionException;

/**
 * Class UserImportValidator
 * @package ILIAS\Plugin\CrsGrpEnrollment\Validators
 * @author Timo Müller <timomueller@databay.de>
 */
class UserImportValidator{
    public function validate($importFile){
        $tmpFile = fopen($importFile,"r");
        while (($row = fgetcsv($tmpFile, 0, ';')) !== false) {
            if(count($row) > 1){
                throw new InvalidCsvColumnDefinitionException("Data format not correct");
            }
        }
        fclose($tmpFile);
    }
}
