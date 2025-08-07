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

namespace ILIAS\Plugin\CrsGrpEnrollment\Report;

class UserImportReport
{
    private bool $hasErrors = false;
    private bool $hasMessages = false;

    public function __construct(
        private readonly \ilCSVWriter $csv,
        private readonly string $identification_element_txt,
        private readonly string $message_element_txt,
    ) {
        $csv->addColumn($this->identification_element_txt);
        $csv->addColumn($this->message_element_txt);
        $csv->addRow();
    }

    public function addMessage(string $identification, string $txt): void
    {
        $this->csv->addColumn($identification);
        $this->csv->addColumn($txt);
        $this->csv->addRow();
        $this->hasMessages = true;
    }

    public function addError(string $identification, string $txt): void
    {
        $this->csv->addColumn($identification);
        $this->csv->addColumn($txt);
        $this->csv->addRow();
        $this->hasErrors = true;
    }

    public function asText(): string
    {
        return $this->csv->getCSVString();
    }

    public function hasErrors(): bool
    {
        return $this->hasErrors;
    }

    public function hasMessages(): bool
    {
        return $this->hasMessages;
    }

    public function isEmpty(): bool
    {
        return !$this->hasErrors && !$this->hasMessages;
    }
}
