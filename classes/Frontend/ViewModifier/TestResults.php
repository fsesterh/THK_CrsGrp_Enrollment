<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Frontend\ViewModifier;

/**
 * Class TestResults
 * @package ILIAS\Plugin\Proctorio\Frontend\Controller
 * @author Michael Jansen <mjansen@databay.de>
 */
class TestResults extends Base
{
    /** @var \ilObjTest */
    private $test;

    /**
     * @return bool
     */
    private function isResultContext() : bool
    {
        return $this->isCommandClass(\ilParticipantsTestResultsGUI::class);
    }

    /**
     * @inheritDoc
     */
    public function shouldModifyHtml(string $component, string $part, array $parameters) : bool
    {
        if ('template_get' !== $part) {
            return false;
        }

        if ('Services/Table/tpl.table2.html' !== $parameters['tpl_id']) {
            return false;
        }

        if (!$this->isObjectOfType('tst')) {
            return false;
        }

        if (!$this->isResultContext()) {
            return false;
        }

        $this->test = \ilObjectFactory::getInstanceByRefId($this->getRefId());
        if (!$this->service->isTestSupported($this->test)) {
           return false;
        }

        // We do not check any RBAC permissions here, since this is already done by the ILIAS core when rendering this view
        if (!$this->accessHandler->mayReadTestReviews($this->test)) {
            return false;
        }

        if (!$this->service->getConfigurationForTest($this->test)['status']) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function modifyHtml(string $component, string $part, array $parameters) : array
    {
        $unmodified = ['mode' => \ilUIHookPluginGUI::KEEP, 'html' => ''];

        $this->ctrl->setParameterByClass(
            get_class($this->getCoreController()),
            'ref_id',
            $this->getRefId()
        );
        $url = $this->ctrl->getLinkTargetByClass(
            ['ilUIPluginRouterGUI', get_class($this->getCoreController())],
            'TestLaunchAndReview.review',
            '',
            false,
            false
        );
        $btn = \ilLinkButton::getInstance();
        $btn->setUrl($url);
        $btn->setCaption($this->getCoreController()->getPluginObject()->txt('btn_label_proctorio_review'), false);
        $this->toolbar->addButtonInstance($btn);

        return $unmodified;
    }

    /**
     * @inheritDoc
     */
    public function shouldModifyGUI(string $component, string $part, array $parameters) : bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function modifyGUI(string $component, string $part, array $parameters) : void
    {
    }
}