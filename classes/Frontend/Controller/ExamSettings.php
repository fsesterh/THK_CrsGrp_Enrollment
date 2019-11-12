<?php
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Frontend\Controller;

/**
 * Class ExamSettings
 * @package ILIAS\Plugin\Proctorio\Frontend\Controller
 * @author Michael Jansen <mjansen@databay.de>
 */
class ExamSettings extends Base
{
    /**
     * @inheritdoc
     */
    public function getDefaultCommand() : string
    {
        return 'showForm';
    }

    /**
     * 
     */
    public function showForm() : string
    {
        return '';
    }
}