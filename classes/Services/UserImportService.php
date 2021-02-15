<?php declare(strict_types=1);
/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\CrsGrpEnrollment\Services;

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
        $i = 0;
        $dataArray = [];
        while ($row = fgetcsv($tmpFile, 0, ';')) {
            if ($i == 0) {
                if (substr($row[0], 0, 3) == chr(hexdec('EF')) . chr(hexdec('BB')) . chr(hexdec('BF'))) {
                    $row[0] = substr($row[0], 3);
                }
            }
            $dataArray[] = $row[0];
            $i++;
        }

        fclose($tmpFile);

        return $dataArray;
    }
}
