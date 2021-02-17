<?php declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollment\Services;

use ILIAS\Plugin\CrsGrpEnrollment\Exceptions\FileNotReadableException;

/**
 * Class UserImportService
 * @package ILIAS\Plugin\CrsGrpEnrollment\Services
 * @author Timo Müller <timomueller@databay.de>
 */
class UserImportService
{
    public function convertCSVToArray($importFile)
    {
        $tmpFile = fopen($importFile, "r");

        if (!$tmpFile || !is_resource($tmpFile)) {
            throw new FileNotReadableException('CSV not readable');
        }

        $i = 0;
        $dataArray = [];
        while (($row = fgetcsv($tmpFile, 0, ';')) !== false) {
            if ($i == 0) {
                if (substr($row[0], 0, 3) == chr(hexdec('EF')) . chr(hexdec('BB')) . chr(hexdec('BF'))) {
                    $row[0] = substr($row[0], 3);
                }
            }
            $dataArray[] = $row[0];
            $i++;
        }

        if (is_resource($tmpFile)) {
            fclose($tmpFile);
        }

        return $dataArray;
    }
}
