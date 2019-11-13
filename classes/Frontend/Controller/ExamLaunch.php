<?php
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Frontend\Controller;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

/**
 * Class ExamLaunch
 * @package ILIAS\Plugin\Proctorio\Frontend\Controller
 * @author Michael Jansen <mjansen@databay.de>
 */
class ExamLaunch extends RepositoryObject
{
    /** @var \ilObjTest */
    protected $test;
    /** @var string */
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



    private function ensureInitialisedSessionLockString()
    {
        if (!strlen($this->getSessionLockString())) {
            $this->setSessionLockString($this->buildSessionLockString());
        }
    }

    private function buildSessionLockString()
    {
        return md5($_COOKIE[session_name()] . time());
    }

    /**
     * @return string
     */
    private function getSessionLockString()
    {
        return $this->sessionLockString;
    }

    /**
     * @param string $sessionLockString
     */
    private function setSessionLockString($sessionLockString)
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
        global $DIC;

        $testPlayerFactory = new \ilTestPlayerFactory($this->test);
        $playerGui = $testPlayerFactory->getPlayerGUI();

        $testUrl =  \ilLink::_getStaticLink($this->test->getRefId(), 'tst');

        $urlParts = parse_url($testUrl);
        $baseUrlWithScript = $urlParts['scheme'] . '://' . $urlParts['host'];
        $regexQuotedBaseUrlWithScript = preg_quote($baseUrlWithScript, '/');

        $refId = $this->test->getRefId();

        $startRegex = sprintf(
            '(.*?)(([\?&]target=tst_%s)|(([\?&]cmd=infoScreen(.*?)&ref_id=%s)|([\?&]ref_id=%s(.*?)&cmd=infoScreen)))',
            $refId, $refId, $refId
        );

        $parameterValues = [
            \ilTestPlayerCommands::START_TEST,
            \ilTestPlayerCommands::INIT_TEST,
            \ilTestPlayerCommands::START_PLAYER,
            \ilTestPlayerCommands::RESUME_PLAYER,
            //\ilTestPlayerCommands::DISPLAY_ACCESS_CODE,
            //\ilTestPlayerCommands::ACCESS_CODE_CONFIRMED,
            \ilTestPlayerCommands::SHOW_QUESTION,
            \ilTestPlayerCommands::PREVIOUS_QUESTION,
            \ilTestPlayerCommands::NEXT_QUESTION,
            \ilTestPlayerCommands::EDIT_SOLUTION,
            //ilTestPlayerCommands::MARK_QUESTION,
            //ilTestPlayerCommands::MARK_QUESTION_SAVE,
            //ilTestPlayerCommands::UNMARK_QUESTION,
            //ilTestPlayerCommands::UNMARK_QUESTION_SAVE,
            \ilTestPlayerCommands::SUBMIT_INTERMEDIATE_SOLUTION,
            \ilTestPlayerCommands::SUBMIT_SOLUTION,
            \ilTestPlayerCommands::SUBMIT_SOLUTION_AND_NEXT,
            \ilTestPlayerCommands::REVERT_CHANGES,
            //\ilTestPlayerCommands::DETECT_CHANGES,
            //\ilTestPlayerCommands::DISCARD_SOLUTION,
            //\ilTestPlayerCommands::SKIP_QUESTION,
            //\ilTestPlayerCommands::SHOW_INSTANT_RESPONSE,
            //\ilTestPlayerCommands::CONFIRM_HINT_REQUEST,
            //\ilTestPlayerCommands::SHOW_REQUESTED_HINTS_LIST,
            \ilTestPlayerCommands::QUESTION_SUMMARY,
            //\ilTestPlayerCommands::QUESTION_SUMMARY_INC_OBLIGATIONS,
            //\ilTestPlayerCommands::QUESTION_SUMMARY_OBLIGATIONS_ONLY,
            //\ilTestPlayerCommands::TOGGLE_SIDE_LIST,
            \ilTestPlayerCommands::SHOW_QUESTION_SELECTION,
            //\ilTestPlayerCommands::UNFREEZE_ANSWERS,
            //\ilTestPlayerCommands::AUTO_SAVE,
            //\ilTestPlayerCommands::REDIRECT_ON_TIME_LIMIT,
            \ilTestPlayerCommands::SUSPEND_TEST,
            \ilTestPlayerCommands::FINISH_TEST,
            \ilTestPlayerCommands::AFTER_TEST_PASS_FINISHED,
            \ilTestPlayerCommands::SHOW_FINAL_STATMENT,
            \ilTestPlayerCommands::BACK_TO_INFO_SCREEN,
            \ilTestPlayerCommands::BACK_FROM_FINISHING,
            'show',
            $this->test->getRefId(),
        ];

        $parameterValues[] = 'iltestsubmissionreviewgui';
        if ($this->test->isRandomTest()) {
            $parameterValues[] = 'iltestplayerrandomquestionsetgui';
        } elseif ($this->test->isFixedTest()) {
            $parameterValues[] = 'iltestplayerfixedquestionsetgui';
        }

        $parameterNames = [
            'cmd',
            'fallbackCmd',
            'ref_id',
            'cmdClass',
        ];

        $this->ctrl->setParameterByClass(get_class($playerGui), 'lock', $this->getSessionLockString());
        $this->ctrl->setParameterByClass(get_class($playerGui), 'sequence', $testSession->getLastSequence());
        $this->ctrl->setParameterByClass(get_class($playerGui), 'ref_id', $this->test->getRefId());
        $launchUrl = $this->ctrl->getLinkTargetByClass(
            ['ilRepositoryGUI', 'ilObjTestGUI', get_class($playerGui)],
            $command,
            '',
            false,
            false
        );

        $takeRegex = '(.*?([\?&]';
        $takeRegex .= '(' . implode('|', $parameterNames) .')=('  . implode('|', $parameterValues) . ')';
        $takeRegex .= ')){3}((.*?)#)+';

        $endRegex = sprintf(
            '(.*?)(([\?&]cmdClass=iltestevaluationgui(.*?)&ref_id=%s)|([\?&]ref_id=%s(.*?)&cmdClass=iltestevaluationgui))',
            $refId, $refId
        );

        $DIC->logger()->root()->info(sprintf(
            "Parameter lengths: Start Exam: %s / Take Exam: %s / End Exam: %s",
            strlen($regexQuotedBaseUrlWithScript . $startRegex),
            strlen($regexQuotedBaseUrlWithScript . $takeRegex),
            strlen($regexQuotedBaseUrlWithScript . $endRegex)
        ));

        $finalLaunchUrl = ILIAS_HTTP_PATH . '/' . ltrim($launchUrl, '/');

        $DIC->logger()->root()->info(sprintf("Launch URL: %s", $finalLaunchUrl));

        $parameters = [
            'launch_url' => $finalLaunchUrl,
            'user_id' => $DIC->user()->getId(),
            'oauth_consumer_key' => $this->globalProctorioSettings->getApiKey(),
            'exam_start' => $regexQuotedBaseUrlWithScript . $startRegex,
            'exam_take' => $regexQuotedBaseUrlWithScript . $takeRegex,
            'exam_end' => $regexQuotedBaseUrlWithScript . $endRegex,
            'exam_settings' => implode(',', [
                'recordaudio', // TODO: Read from config
                'recordvideo',
            ]),
            'fullname' => $DIC->user()->getFullname(),
            'exam_tag' => $this->test->getId(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_version' => '1.0',
            'oauth_timestamp' => time(),
            'oauth_nonce' => md5(uniqid(rand(), true)),
        ];
        $consumerSecret = $this->globalProctorioSettings->getApiSecret();

        $region = $this->globalProctorioSettings->getApiRegion();
        $url = str_replace('[ACCOUNT_REGION]', $region, $this->globalProctorioSettings->getApiBaseUrl());
        $urlPath = ltrim($this->globalProctorioSettings->getApiLaunchAndReviewEndpoint(), '/');

        $DIC->logger()->root()->info(print_r($parameters,1));

        // https://csharp.hotexamples.com/de/site/file?hash=0xee75c4aef1fe45609c1c1cfc2677509faae0583c603d230fce0c1559b16dddb8&fullName=LtiLibrary.Core/OAuthUtility.cs&project=andyfmiller/LtiLibrary
        // https://github.com/andyfmiller/LtiLibrary/blob/master/src/LtiLibrary.NetCore/Extensions/NameValueCollectionExtensions.cs#L20
        // https://github.com/andyfmiller/LtiLibrary/blob/master/src/LtiLibrary.NetCore/Extensions/StringExtensions.cs
        $signature_data = '';
        foreach ($parameters as $key => $value) {
            $signature_data .= '&' . rawurlencode($key) . '=' . rawurlencode($value);
        }
        $signature_data = ltrim($signature_data, '&');

        $signature_base_string = 'POST&' . rawurlencode($url . '/' . $urlPath) . '&' . rawurlencode($signature_data);

        $DIC->logger()->root()->info("ignature_base_string: " . $signature_base_string);
        $signature = hash_hmac('sha1', $signature_base_string, $consumerSecret, true);
        $DIC->logger()->root()->info("signature: " . $signature);
        $base64_encoded_data = base64_encode($signature);
        $DIC->logger()->root()->info("oauth_signature: " . $base64_encoded_data);

        $parameters['oauth_signature'] = $base64_encoded_data;

        $container = [];
        $history = Middleware::history($container);
        $stack = HandlerStack::create();
        $stack->push($history);

        $client = new Client([
            'handler' => $stack,
            'base_uri' => $url
        ]);

        $formParams = [];
        foreach ($parameters as $key => $value) {
            $formParams[$key] = $value;
        }

        $response = $client->request('POST', $urlPath, [
            'form_params' => $formParams
        ]);

        foreach ($container as $transaction) {
            $httpHeaderArray = $transaction['request']->getHeaders();
            $requestBody = (string) $transaction['request']->getBody();
        }

        $body = $response->getBody();
        $DIC->logger()->root()->info("Response Status: " . $response->getStatusCode() . ' ' . $response->getReasonPhrase());
        $DIC->logger()->root()->info("Response Body: " . $body);

        $responseArray = json_decode($body, true);

        $isLaunchApiSuccess = is_array($responseArray) && isset($responseArray[0]) && is_string($responseArray[0]) && strlen($responseArray[0]) > 0;
        if ($isLaunchApiSuccess) {
            $responseArray[0];
        }

        /*$isEvalApiSuccess = is_array($responseArray) && isset($responseArray[1]) && is_string($responseArray[1]) && strlen($responseArray[1]) > 0;
        if ($isEvalApiSuccess) {
            $btn = ilLinkButton::getInstance();
            $btn->setCaption('Proctorio Evaluation', false);
            $btn->setUrl($responseArray[1]);
            $btn->setPrimary(true);
            $this->addButtonInstance($btn);
        }*/

        $messages = [];
        $messages[] = ('Launch URL: ' . $finalLaunchUrl);
        $messages[] = ('Start Regex: ' . $regexQuotedBaseUrlWithScript . $startRegex);
        $messages[] = ('Take Regex: ' . $regexQuotedBaseUrlWithScript . $takeRegex);
        $messages[] = ('End Regex: ' . $regexQuotedBaseUrlWithScript . $endRegex);
        $messages[] = ('API Launch Request Success: ' . ($isLaunchApiSuccess ? 'Yes' : 'No'));
        //$messages[] = ('API Evaluation Request Success: ' . ($isEvalApiSuccess ? 'Yes' : 'No'));

        $debugMessage = implode('<br><br>', $messages);
        $debugMessage = str_replace(["{", "}"], ["&#123;", "&#125;"], $debugMessage);
        
        $this->ctrl->redirectToURL($responseArray[0]);
    }
}