<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Frontend\Controller;

use GuzzleHttp\Exception\GuzzleException;
use ILIAS\Data\URI;
use ILIAS\Plugin\Proctorio\Refinery\Transformation\UriToString;
use ILIAS\Plugin\Proctorio\Webservice\Exception;
use ILIAS\Plugin\Proctorio\Webservice\Exception\QualifiedResponseError;

/**
 * Class ExamLaunchAndReview
 * @package ILIAS\Plugin\Proctorio\Frontend\Controller
 * @author Michael Jansen <mjansen@databay.de>
 */
class ExamLaunchAndReview extends RepositoryObject
{
    /** @var \ilObjTest */
    protected $test;
    /** @var string|null */
    private $sessionLockString;

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
     * @inheritdoc
     */
    protected function init() : void
    {
        parent::init();

        if (0 === $this->getRefId() || !$this->coreAccessHandler->checkAccess('read', '', $this->getRefId())) {
            $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        }

        $this->test = \ilObjectFactory::getInstanceByRefId($this->getRefId());

        $this->drawHeader();
    }

    /**
     * @return string
     */
    public function launchCmd() : string 
    {
        if (!$this->test->isRandomTest() && !$this->test->isFixedTest()) {
            $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        }
        
        $testQuestionSetConfigFactory = new \ilTestQuestionSetConfigFactory(
            $this->dic->repositoryTree(),
            $this->dic->database(),
            $this->dic['ilPluginAdmin'],
            $this->test
        );
        $testSessionFactory = new \ilTestSessionFactory($this->test);
        $testQuestionSetConfig = $testQuestionSetConfigFactory->getQuestionSetConfig();
        $testSession = $testSessionFactory->getSession();

        $this->ensureInitialisedSessionLockString();

        $onlineAccess = false;
        if ($this->test->getFixedParticipants()) {
            $onlineAccessResult = \ilObjTestAccess::_lookupOnlineTestAccess(
                $this->test->getId(),
                $testSession->getUserId()
            );
            if (true === $onlineAccessResult) {
                $onlineAccess = true;
            }
        }

        if ($this->test->getOfflineStatus()) {
            $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        }

        if (!$this->test->isComplete($testQuestionSetConfig)) {
            $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        }

        $executable = $this->test->isExecutable(
            $testSession, $testSession->getUserId(), true
        );
        
        if ($this->test->getFixedParticipants() && !$onlineAccess) {
            $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        }

        if (!$executable['executable']) {
            $this->errorHandler->raiseError($this->lng->txt('permission_denied'), $this->errorHandler->MESSAGE);
        }

        $command = 'startPlayer';
        if ($testSession->getActiveId() > 0) {
            $testPassesSelector = new \ilTestPassesSelector($this->dic->database(), $this->test);
            $testPassesSelector->setActiveId($testSession->getActiveId());
            $testPassesSelector->setLastFinishedPass($testSession->getLastFinishedPass());

            $closedPasses = $testPassesSelector->getClosedPasses();
            $existingPasses = $testPassesSelector->getExistingPasses();

            if ($existingPasses > $closedPasses) {
                $command = 'resumePlayer';
            }
        }

        return $this->launchApi($testSession, $command);
    }

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
     * @param \ilTestSession $testSession
     * @param string $command
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function launchApi(\ilTestSession $testSession, string $command) : string
    {
        $testPlayerFactory = new \ilTestPlayerFactory($this->test);
        $playerGui = $testPlayerFactory->getPlayerGUI();

        $testUrl =  new URI(\ilLink::_getStaticLink($this->test->getRefId(), 'tst'));

        $this->ctrl->setParameterByClass(get_class($playerGui), 'lock', $this->getSessionLockString());
        $this->ctrl->setParameterByClass(get_class($playerGui), 'sequence', $testSession->getLastSequence());
        $this->ctrl->setParameterByClass(get_class($playerGui), 'ref_id', $this->test->getRefId());
        $testLaunchUrl = new URI(ILIAS_HTTP_PATH . '/' . $this->ctrl->getLinkTargetByClass(
            ['ilRepositoryGUI', 'ilObjTestGUI', get_class($playerGui)],
            $command,
            '',
            false,
            false
        ));

        try {
            $this->ctrl->redirectToURL((new UriToString())->transform($this->proctorioApi->getLaunchUrl(
                $this->test,
                $testLaunchUrl,
                $testUrl)
            ));
        } catch (GuzzleException | Exception $e) {
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