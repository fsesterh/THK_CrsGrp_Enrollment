<?php

declare(strict_types=1);

use ILIAS\Plugin\CrsGrpEnrollment\Lock\Locker;
use ILIAS\Plugin\CrsGrpEnrollment\Utils\UiUtil;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;

/**
 * @ilCtrl_Calls      ilCrsGrpEnrollmentConfigGUI: ilPropertyFormGUI
 * @ilCtrl_Calls      ilCrsGrpEnrollmentConfigGUI: ilExplorerSelectInputGUI
 * @ilCtrl_Calls      ilCrsGrpEnrollmentConfigGUI: ilFileSystemGUI
 * @ilCtrl_Calls      ilCrsGrpEnrollmentConfigGUI: ilAdministrationGUI
 * @ilCtrl_IsCalledBy ilCrsGrpEnrollmentConfigGUI: ilObjComponentSettingsGUI
 */
class ilCrsGrpEnrollmentConfigGUI extends ilPluginConfigGUI
{
    private readonly Factory $factory;
    private readonly Renderer $renderer;
    private readonly ilGlobalTemplateInterface $template;
    private readonly ilCtrl $ctrl;
    private readonly Locker $lock;
    private readonly ilLanguage $lng;
    private readonly UiUtil $uiUtil;
    private readonly ilCrsGrpEnrollmentPlugin $plugin;

    public function __construct()
    {
        global $DIC;

        $this->factory = $DIC->ui()->factory();
        $this->renderer = $DIC->ui()->renderer();
        $this->template = $DIC->ui()->mainTemplate();
        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->lock = $DIC['plugin.' . ilCrsGrpEnrollmentPlugin::ID . '.cronjob.locker'];
        $this->uiUtil = new UiUtil();
        $this->plugin = ilCrsGrpEnrollmentPlugin::getInstance();
    }

    public function performCommand($cmd): void
    {
        $this->$cmd();
    }

    protected function performReleaseLock(): void
    {
        if ($this->lock->isLocked()) {
            $this->lock->releaseLock();
            $this->uiUtil->sendSuccess($this->plugin->txt('lock.released'), true);
        }

        $this->ctrl->redirect($this, 'configure');
    }

    public function confirmReleaseLock(): void
    {
        $confirmation = new ilConfirmationGUI();
        $confirmation->setFormAction($this->ctrl->getFormAction($this, 'configure'));
        $confirmation->setConfirm($this->lng->txt('confirm'), 'performReleaseLock');
        $confirmation->setCancel($this->lng->txt('cancel'), 'configure');
        $confirmation->setHeaderText($this->plugin->txt('lock.release.sure'));

        $this->template->setContent($confirmation->getHTML());
    }

    private function configure(string $add = ''): void
    {
        $content = "";
        if ($this->lock->isLocked()) {
            $releaseLockButton = $this->factory->button()->standard(
                $this->txt('lock.release'),
                $this->ctrl->getLinkTarget($this, 'confirmReleaseLock')
            );
            $this->uiUtil->sendInfo($this->plugin->txt('lock.locked'));
            $content .= $this->renderer->render($releaseLockButton);
        }

        $this->template->setContent($add . $content);
    }

    private function txt(string $txt): string
    {
        return ilCrsGrpEnrollmentPlugin::getInstance()->txt($txt);
    }
}
