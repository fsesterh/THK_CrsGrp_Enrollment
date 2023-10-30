<?php declare(strict_types=1);

use ILIAS\UI\Renderer;
use ILIAS\UI\Factory;

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

    public function __construct()
    {
        global $DIC;

        $this->factory = $DIC->ui()->factory();
        $this->renderer = $DIC->ui()->renderer();
        $this->template = $DIC->ui()->mainTemplate();
        $this->ctrl = $DIC->ctrl();
    }

    public function performCommand($cmd) : void
    {
        $this->$cmd();
    }

    private function configure(string $add = '') : void
    {
        $content = $this->renderer->render($this->factory->button()->standard($this->txt('course_group_config_clear'), $this->ctrl->getLinkTarget($this, 'clear')));

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
