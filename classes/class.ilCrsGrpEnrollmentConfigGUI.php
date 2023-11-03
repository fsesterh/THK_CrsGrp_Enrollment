<?php declare(strict_types=1);

use ILIAS\Plugin\CrsGrpEnrollment\Lock\Locker;
use ILIAS\UI\Factory;
use ILIAS\UI\Renderer;

class ilCrsGrpEnrollmentConfigGUI extends \ilPluginConfigGUI
{
    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var Renderer
     */
    private $renderer;

    /**
     * @var \ilGlobalTemplateInterface
     */
    private $template;

    /**
     * @var \ilCtrl
     */
    private $ctrl;
    /**
     * @var Locker
     */
    private $lock;
    /**
     * @var ilLanguage
     */
    private $lng;

    public function __construct()
    {
        global $DIC;

        $this->factory = $DIC->ui()->factory();
        $this->renderer = $DIC->ui()->renderer();
        $this->template = $DIC->ui()->mainTemplate();
        $this->ctrl = $DIC->ctrl();
        $this->lng = $DIC->language();
        $this->lock = $DIC['plugin.crs_grp_enrol.cronjob.locker'];
    }

    public function performCommand($cmd) : void
    {
        $this->$cmd();
    }

    protected function performReleaseLock() : void
    {
        if ($this->lock->isLocked()) {
            $this->lock->releaseLock();
            ilUtil::sendSuccess($this->getPluginObject()->txt('lock.released'), true);
        }

        $this->ctrl->redirect($this, 'configure');
    }

    public function confirmReleaseLock() : void
    {
        $confirmation = new ilConfirmationGUI();
        $confirmation->setFormAction($this->ctrl->getFormAction($this, 'configure'));
        $confirmation->setConfirm($this->lng->txt('confirm'), 'performReleaseLock');
        $confirmation->setCancel($this->lng->txt('cancel'), 'configure');
        $confirmation->setHeaderText($this->getPluginObject()->txt('lock.release.sure'));

        $this->template->setContent($confirmation->getHTML());
    }




    private function configure(string $add = '') : void
    {
        $content = $this->renderer->render($this->factory->button()->standard($this->txt('course_group_config_clear'), $this->ctrl->getLinkTarget($this, 'clear')));

        if ($this->lock->isLocked()) {
            $releaseLockButton = $this->factory->button()->standard(
                $this->txt('lock.release'),
                $this->ctrl->getLinkTarget($this, 'confirmReleaseLock')
            );
            ilUtil::sendInfo($this->getPluginObject()->txt('lock.locked'));
            $content .= $this->renderer->render($releaseLockButton);
        }


        $this->template->setContent($add . $content);
    }

    private function clear() : void
    {
        $this->ctrl->redirectToURL($this->ctrl->getLinkTarget($this, 'cleared'));
    }

    private function cleared() : void
    {
        $content = $this->renderer->render($this->factory->messageBox()->success($this->txt('course_group_config_cleared')));
        $this->configure($content);
    }

    private function txt(string $txt) : string
    {
        return \ilCrsGrpEnrollmentPlugin::getInstance()->txt($txt);
    }
}
