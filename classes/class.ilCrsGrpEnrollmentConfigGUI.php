<?php

include_once("./Services/Component/classes/class.ilPluginConfigGUI.php");

class ilCrsGrpEnrollmentConfigGUI extends \ilPluginConfigGUI
{
    public function performCommand(string $cmd) : void
    {
        $this->$cmd();
    }

    private function configure(string $add = '') : void
    {
        global $tpl, $DIC;

        $factory = $DIC->ui()->factory();
        $renderer = $DIC->ui()->renderer();

        $content = $renderer->render($factory->button()->standard($this->txt('course_group_config_clear'), $DIC->ctrl()->getLinkTarget($this, 'clear')));

        $tpl->setContent($add . $content);
    }

    private function clear() : void
    {
        global $DIC;

        \ilCrsGrpEnrollmentPlugin::getInstance()->clearDatabaseRows();
        $DIC->ctrl()->redirectToURL($DIC->ctrl()->getLinkTarget($this, 'cleared'));
    }

    private function cleared() : void
    {
        global $DIC;

        $factory = $DIC->ui()->factory();
        $renderer = $DIC->ui()->renderer();

        $content = $renderer->render($factory->messageBox()->success($this->txt('course_group_config_cleared')));
        $this->configure($content);
    }

    private function txt(string $txt) : string
    {
        return \ilCrsGrpEnrollmentPlugin::getInstance()->txt($txt);
    }
}
