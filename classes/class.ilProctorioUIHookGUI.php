<?php
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\DI\Container;
use ILIAS\Plugin\Proctorio\Frontend;
use ILIAS\Plugin\Proctorio\Frontend\ViewModifier;

/**
 * @author            Michael Jansen <mjansen@databay.de>
 * @ilCtrl_isCalledBy ilProctorioUIHookGUI: ilUIPluginRouterGUI
 */
class ilProctorioUIHookGUI extends ilUIHookPluginGUI
{
    /** @var Container */
    protected $dic;

    /** @var ViewModifier[]|null */
    protected static $modifiers = null;

    /**
     * ilProctorioUIHookGUI constructor.
     */
    public function __construct()
    {
        global $DIC;

        $this->dic = $DIC;
    }

    /**
     * The main entry point for own plugin controllers
     */
    public function executeCommand() : void
    {
        $this->setPluginObject(ilProctorioPlugin::getInstance());

        $this->dic->ui()->mainTemplate()->getStandardTemplate();

        $nextClass = $this->dic->ctrl()->getNextClass();
        switch (strtolower($nextClass)) {
            default:
                $dispatcher = Frontend\Dispatcher::getInstance($this);
                $dispatcher->setDic($this->dic);

                $response = $dispatcher->dispatch($this->dic->ctrl()->getCmd());
                break;
        }

        $this->dic->ui()->mainTemplate()->setContent($response);
        $this->dic->ui()->mainTemplate()->show();
    }

    /**
     * @inheritDoc
     */
    public function getHTML($a_comp, $a_part, $a_par = [])
    {
        $unmodified = ['mode' => ilUIHookPluginGUI::KEEP, 'html' => ''];

        $phpSelf = (string) ($_SERVER['PHP_SELF'] ?? '');
        $urlParts = parse_url($phpSelf);
        $script = basename($phpSelf);

        if (
            null === self::$modifiers &&
            in_array($script, ['login.php', 'goto.php', 'ilias.php']) &&
            (
                0 === strlen($phpSelf) ||
                !is_array($urlParts) ||
                !isset($urlParts['path']) ||
                strpos($urlParts['path'], '/LiveVoting/') === false
            )
        ) {
            self::$modifiers = [
                // TODO: Define them modifiers
            ];
        }

        if (is_array(self::$modifiers)) {
            foreach (self::$modifiers as $modifier) {
                if ($modifier->shouldModifyHtml($a_comp, $a_part, $a_par)) {
                    return $modifier->modifyHtml($a_comp, $a_part, $a_par);
                }
            }
        }

        return $unmodified;
    }
}