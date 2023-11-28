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

namespace ILIAS\Plugin\CrsGrpEnrollment\Utils;

use ILIAS\DI\Container;

class UiUtil
{
    public Container $dic;

    public function __construct(Container $dic = null)
    {
        if (!$dic) {
            global $DIC;
            $dic = $DIC;
        }
        $this->dic = $dic;
    }

    public function sendQuestion(string $message, bool $keep = false): void
    {
        $this->dic->ui()->mainTemplate()->setOnScreenMessage('question', $message, $keep);
    }

    public function sendInfo(string $message, bool $keep = false): void
    {
        $this->dic->ui()->mainTemplate()->setOnScreenMessage('info', $message, $keep);
    }

    public function sendFailure(string $message, bool $keep = false): void
    {
        $this->dic->ui()->mainTemplate()->setOnScreenMessage('failure', $message, $keep);
    }

    public function sendSuccess(string $message, bool $keep = false): void
    {
        $this->dic->ui()->mainTemplate()->setOnScreenMessage('success', $message, $keep);
    }
}
