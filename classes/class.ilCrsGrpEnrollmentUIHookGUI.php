<?php

declare(strict_types=1);

/* Copyright (c) 1998-2021 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\DI\Container;
use ILIAS\Plugin\CrsGrpEnrollment\Frontend;
use ILIAS\Plugin\CrsGrpEnrollment\Frontend\ViewModifier;
use ILIAS\Plugin\CrsGrpEnrollment\Frontend\ViewModifier\CourseGroupTabs;

/**
 * @author            Timo Müller <timomueller@databay.de>
 * @ilCtrl_isCalledBy ilCrsGrpEnrollmentUIHookGUI: ilUIPluginRouterGUI
 */
class ilCrsGrpEnrollmentUIHookGUI extends ilUIHookPluginGUI
{
    protected Container $dic;
    /** @var ViewModifier[]|null */
    protected static ?array $modifiers = null;

    /**
     * ilCrsGrpEnrollmentUIHookGUI constructor.
     */
    public function __construct()
    {
        global $DIC;

        $this->dic = $DIC;
    }

    /**
     * The main entry point for own plugin controllers
     */
    public function executeCommand(): void
    {
        $this->setPluginObject(ilCrsGrpEnrollmentPlugin::getInstance());

        $nextClass = $this->dic->ctrl()->getNextClass();
        switch (strtolower($nextClass)) {
            default:
                $dispatcher = Frontend\Dispatcher::getInstance($this);
                $dispatcher->setDic($this->dic);

                $response = $dispatcher->dispatch($this->dic->ctrl()->getCmd());
                break;
        }

        $this->dic->ui()->mainTemplate()->setContent($response);
        $this->dic->ui()->mainTemplate()->printToStdOut();
    }

    /**
     *
     */
    private function initModifiers(): void
    {
        if (
            !isset($this->dic['tpl']) ||
            !isset($this->dic['refinery']) ||
            !isset($this->dic['ilToolbar'])
        ) {
            return;
        }

        if (null !== self::$modifiers) {
            return;
        }

        $phpSelf = (string) ($_SERVER['PHP_SELF'] ?? '');
        $urlParts = parse_url($phpSelf);
        $script = basename($phpSelf);

        $isLiveVotinRequest = (
            strlen($phpSelf) > 0 &&
            is_array($urlParts) &&
            isset($urlParts['path']) &&
            strpos($urlParts['path'], '/LiveVoting/') !== false
        );
        if ($isLiveVotinRequest) {
            return;
        }

        $isBootstrappedRequest = in_array($script, ['login.php', 'goto.php', 'ilias.php']);
        if (!$isBootstrappedRequest) {
            return;
        }

        self::$modifiers = [
            new CourseGroupTabs($this, $this->dic),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getHTML($a_comp, $a_part, $a_par = []): array
    {
        $unmodified = ['mode' => ilUIHookPluginGUI::KEEP, 'html' => ''];

        $this->initModifiers();

        if (is_array(self::$modifiers)) {
            foreach (self::$modifiers as $modifier) {
                if ($modifier->shouldModifyHtml($a_comp, $a_part, $a_par)) {
                    return $modifier->modifyHtml($a_comp, $a_part, $a_par);
                }
            }
        }

        return $unmodified;
    }

    /**
     * @inheritDoc
     */
    public function modifyGUI($a_comp, $a_part, $a_par = []): void
    {
        parent::modifyGUI($a_comp, $a_part, $a_par);

        $this->initModifiers();

        if (is_array(self::$modifiers)) {
            foreach (self::$modifiers as $modifier) {
                if ($modifier->shouldModifyGUI($a_comp, $a_part, $a_par)) {
                    $modifier->modifyGUI($a_comp, $a_part, $a_par);
                }
            }
        }
    }
}
