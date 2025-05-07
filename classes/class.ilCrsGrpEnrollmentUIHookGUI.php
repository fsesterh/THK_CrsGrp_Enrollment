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

use ILIAS\DI\Container;
use ILIAS\Plugin\CrsGrpEnrollment\Frontend;
use ILIAS\Plugin\CrsGrpEnrollment\Frontend\ViewModifier;
use ILIAS\Plugin\CrsGrpEnrollment\Frontend\ViewModifier\CourseGroupTabs;

/**
  * @ilCtrl_isCalledBy ilCrsGrpEnrollmentUIHookGUI: ilUIPluginRouterGUI
 */
class ilCrsGrpEnrollmentUIHookGUI extends ilUIHookPluginGUI
{
    protected Container $dic;
    /** @var ViewModifier[]|null */
    protected static ?array $modifiers = null;

    public function __construct()
    {
        global $DIC;

        $this->dic = $DIC;
    }

    public function executeCommand(): void
    {
        $this->setPluginObject(ilCrsGrpEnrollmentPlugin::getInstance());

        $dispatcher = Frontend\Dispatcher::getInstance($this);
        $dispatcher->setDic($this->dic);

        $response = $dispatcher->dispatch($this->dic->ctrl()->getCmd());

        $this->dic->ui()->mainTemplate()->setContent($response);
        $this->dic->ui()->mainTemplate()->printToStdOut();
    }

    private function initModifiers(): void
    {
        if (!isset($this->dic['tpl'], $this->dic['refinery'], $this->dic['ilToolbar'])) {
            return;
        }

        if (null !== self::$modifiers) {
            return;
        }

        $phpSelf = ($_SERVER['PHP_SELF'] ?? '');
        $urlParts = parse_url($phpSelf);
        $script = basename($phpSelf);

        $isLiveVotinRequest = (
            $phpSelf !== '' &&
            is_array($urlParts) &&
            isset($urlParts['path']) &&
            str_contains($urlParts['path'], '/LiveVoting/')
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
