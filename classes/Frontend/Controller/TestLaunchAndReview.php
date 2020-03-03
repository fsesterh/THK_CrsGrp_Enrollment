<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Frontend\Controller;

use GuzzleHttp\Exception\GuzzleException;
use ILIAS\Data\URI;
use ILIAS\Plugin\Proctorio\Refinery\Transformation\UriToString;
use ILIAS\Plugin\Proctorio\Webservice\Exception;
use ILIAS\Plugin\Proctorio\Webservice\Exception\QualifiedResponseError;

/**
 * Class TestLaunchAndReview
 * @package ILIAS\Plugin\Proctorio\Frontend\Controller
 * @author Michael Jansen <mjansen@databay.de>
 */
class TestLaunchAndReview extends RepositoryObject
{
    /** @var \ilObjTest */
    protected $test;
    /** @var string|null */
    private $sessionLockString;
    /** @var \ilTestSession */
    private $testSession;
    /** @var string */
    private $testCommand = '';
    /** @var \ilTestQuestionSetConfigFactory */
    private $testQuestionSetConfigFactory;

    /**
     * @inheritdoc
     */
    public function getDefaultCommand() : string
    {
        return 'launchCmd';
    }

    /**
     * @inheritdoc
     */
    public function getObjectGuiClass() : string
    {
        return \ilObjTestGUI::class;
    }

    /**
     * 
     */
    private function ensureInitialisedSessionLockString() : void
    {
        if (!is_string($this->getSessionLockString()) || !strlen($this->getSessionLockString())) {
            $this->setSessionLockString($this->buildSessionLockString());
        }
    }

    /**
     * @return string
     */
    private function buildSessionLockString() : string
    {
        return md5($_COOKIE[session_name()] . time());
    }

    /**
     * @return string
     */
    private function getSessionLockString() : ?string
    {
        return $this->sessionLockString;
    }

    /**
     * @param string $sessionLockString
     */
    private function setSessionLockString(string $sessionLockString) : void
    {
        $this->sessionLockString = $sessionLockString;
    }

    /**
     * @inheritdoc
     */
    protected function init() : void
    {
        parent::init();

        if (0 === $this->getRefId() || !$this->coreAccessHandler->checkAccess('read', '', $this->getRefId())) {
            $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        }

        $this->test = \ilObjectFactory::getInstanceByRefId($this->getRefId());

        if (!$this->service->isTestSupported($this->test)) {
            $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        }

        if (!$this->service->getConfigurationForTest($this->test)['status']) {
            $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        }

        $this->testQuestionSetConfigFactory = new \ilTestQuestionSetConfigFactory(
            $this->dic->repositoryTree(),
            $this->dic->database(),
            $this->dic['ilPluginAdmin'],
            $this->test
        );
        $testSessionFactory = new \ilTestSessionFactory($this->test);
        $this->testSession = $testSessionFactory->getSession();

        $this->ensureInitialisedSessionLockString();

        $this->testCommand = 'startPlayer';
        if ($this->testSession->getActiveId() > 0) {
            $testPassesSelector = new \ilTestPassesSelector($this->dic->database(), $this->test);
            $testPassesSelector->setActiveId($this->testSession->getActiveId());
            $testPassesSelector->setLastFinishedPass($this->testSession->getLastFinishedPass());

            $closedPasses = $testPassesSelector->getClosedPasses();
            $existingPasses = $testPassesSelector->getExistingPasses();

            if ($existingPasses > $closedPasses) {
                $this->testCommand = 'resumePlayer';
            }
        }
    }

    /**
     * @return URI
     */
    private function getLaunchUrl() : URI
    {
        $this->ctrl->setParameterByClass(get_class($this->getCoreController()), 'ref_id', $this->test->getRefId());
        $startAndLauncHUrl = $this->ctrl->getLinkTargetByClass(
            ['ilUIPluginRouterGUI', get_class($this->getCoreController())],
            $this->getControllerName() . '.start',
            '',
            false,
            false
        );

        return new URI(ILIAS_HTTP_PATH . '/' . $startAndLauncHUrl);
    }

    /**
     * @return URI
     */
    private function getTakeUrl() : URI 
    {
        $testPlayerFactory = new \ilTestPlayerFactory($this->test);
        $playerGui = $testPlayerFactory->getPlayerGUI();
        $this->ctrl->setParameterByClass(get_class($playerGui), 'lock', $this->getSessionLockString());
        $this->ctrl->setParameterByClass(get_class($playerGui), 'sequence', $this->testSession->getLastSequence());
        $this->ctrl->setParameterByClass(get_class($playerGui), 'ref_id', $this->test->getRefId());
        return new URI(ILIAS_HTTP_PATH . '/' . $this->ctrl->getLinkTargetByClass(
            ['ilRepositoryGUI', 'ilObjTestGUI', get_class($playerGui)],
            $this->testCommand,
            '',
            false,
            false
        ));
    }

    /**
     * @return URI
     */
    private function getTestUrl() : URI
    {
        return new URI(\ilLink::_getStaticLink($this->test->getRefId(), 'tst'));
    }

    /**
     * @return string
     */
    public function launchCmd() : string
    {
        $this->pageTemplate->getStandardTemplate();

        $this->drawHeader();

        if (!$this->accessHandler->mayTakeTests($this->test)) {
            $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        }

        if ($this->test->getOfflineStatus()) {
            $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        }

        $testQuestionSetConfig = $this->testQuestionSetConfigFactory->getQuestionSetConfig();
        $onlineAccess = false;
        if ($this->test->getFixedParticipants()) {
            $onlineAccessResult = \ilObjTestAccess::_lookupOnlineTestAccess(
                $this->test->getId(),
                $this->testSession->getUserId()
            );
            if (true === $onlineAccessResult) {
                $onlineAccess = true;
            }
        }

        if (!$this->test->isComplete($testQuestionSetConfig)) {
            $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        }

        if ($this->test->getFixedParticipants() && !$onlineAccess) {
            $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        }

        $executable = $this->test->isExecutable(
            $this->testSession, $this->testSession->getUserId(), true
        );
        if (!$executable['executable']) {
            $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        }

        try {
            $this->ctrl->redirectToURL((new UriToString())->transform($this->proctorioApi->getLaunchUrl(
                $this->test,
                $this->getLaunchUrl(),
                $this->getTestUrl()
            )));
        } catch (GuzzleException | Exception $e) {
            $this->log->error($e->getMessage());

            return $this->uiRenderer->render([
                $this->uiFactory->messageBox()->failure(
                    $this->getCoreController()->getPluginObject()->txt('api_call_generic')
                )
            ]);
        } catch (QualifiedResponseError $e) {
            return $this->uiRenderer->render([
                $this->uiFactory->messageBox()->failure(sprintf(
                    $this->getCoreController()->getPluginObject()->txt('api_call_unexcpected_response_with_code'),
                    $e->getCode()
                ))
            ]);
        }
    }

    /**
     * @return string
     */
    public function startExamCmd() : string
    {
        if (!$this->accessHandler->mayTakeTests($this->test)) {
            $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        }

        if ($this->test->getOfflineStatus()) {
            $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        }

        $testQuestionSetConfig = $this->testQuestionSetConfigFactory->getQuestionSetConfig();
        $onlineAccess = false;
        if ($this->test->getFixedParticipants()) {
            $onlineAccessResult = \ilObjTestAccess::_lookupOnlineTestAccess(
                $this->test->getId(),
                $this->testSession->getUserId()
            );
            if (true === $onlineAccessResult) {
                $onlineAccess = true;
            }
        }

        if (!$this->test->isComplete($testQuestionSetConfig)) {
            $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        }

        if ($this->test->getFixedParticipants() && !$onlineAccess) {
            $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        }

        $executable = $this->test->isExecutable(
            $this->testSession, $this->testSession->getUserId(), true
        );
        if (!$executable['executable']) {
            $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        }

        $this->pageTemplate->addCss('Modules/Test/templates/default/ta.css');
        $this->pageTemplate->addCss(
            $this->getCoreController()->getPluginObject()->getDirectory() . '/assets/css/styles.css'
        );
        $this->pageTemplate->setBodyClass('kiosk');
        $this->pageTemplate->setAddFooter(FALSE);

        $btn = \ilLinkButton::getInstance();
        $btn->setUrl((new UriToString())->transform($this->getTakeUrl()));
        $btn->setCaption($this->getCoreController()->getPluginObject()->txt('btn_label_continue_proctorio_exam'), false);

        $this->pageTemplate->addBlockfile(
            'CONTENT',
            'content',
            $this->getCoreController()->getPluginObject()->getDirectory() . '/templates/tpl.tst_start_container.html');

        $template = $this->getCoreController()->getPluginObject()->getTemplate('tpl.tst_start.html', true, true);

        $template->setVariable('TEST_TAKE_BUTTON', $btn->render());
        $template->setVariable('INTRODUCTION_TXT', $this->getCoreController()->getPluginObject()->txt('proctorio_start_screen_info'));

        return $template->get();
    }

    /**
     * @return string
     */
    public function reviewCmd() : string 
    {
        $this->pageTemplate->getStandardTemplate();

        $this->drawHeader();

        if (
            !$this->coreAccessHandler->checkAccess('write', '', $this->getRefId()) &&
            !$this->coreAccessHandler->checkAccess('tst_results', '', $this->getRefId()) &&
            !$this->coreAccessHandler->checkPositionAccess(\ilOrgUnitOperation::OP_MANAGE_PARTICIPANTS, $this->getRefId()) &&
            !$this->coreAccessHandler->checkPositionAccess(\ilOrgUnitOperation::OP_ACCESS_RESULTS, $this->getRefId())
        ) {
            $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        }

        if (!$this->accessHandler->mayReadTestReviews($this->test)) {
            $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        }

        try {
            $this->ctrl->redirectToURL((new UriToString())->transform($this->proctorioApi->getReviewUrl(
                $this->test,
                $this->getLaunchUrl(),
                $this->getTestUrl()
            )));
        } catch (GuzzleException | Exception $e) {
            $this->log->error($e->getMessage());

            return $this->uiRenderer->render([
                $this->uiFactory->messageBox()->failure(
                    $this->getCoreController()->getPluginObject()->txt('api_call_generic')
                )
            ]);
        } catch (QualifiedResponseError $e) {
            return $this->uiRenderer->render([
                $this->uiFactory->messageBox()->failure(sprintf(
                    $this->getCoreController()->getPluginObject()->txt('api_call_unexcpected_response_with_code'),
                    $e->getCode()
                ))
            ]);
        }
    }
}