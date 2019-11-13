<?php
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Frontend\ViewModifier;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;

/**
 * Class ExamLaunch
 * @package ILIAS\Plugin\Proctorio\Frontend\Controller
 * @author Michael Jansen <mjansen@databay.de>
 */
class ExamLaunch extends Base
{
    /**
     * @return bool
     */
    private function isPreviewContext() : bool
    {
        return (
            $this->isCommandClass('ilobjcoursegui') &&
            strtolower($this->ctrl->getCmd()) === strtolower('showItemIntro')
        );
    }

    /**
     * @return bool
     */
    private function isInfoScreenContext() : bool
    {
        $isBaseClassInfoScreenRequest = (
            $this->isBaseClass('ilObjTestGUI') &&
            in_array(
                strtolower($this->ctrl->getCmd()),
                [
                    strtolower('infoScreen'),
                    strtolower('showSummary'),
                ]
            )
        );

        $isCmdClassInfoScreenRequest = (
            $this->isCommandClass('ilInfoScreenGUI') &&
            in_array(
                strtolower($this->ctrl->getCmd()),
                [
                    strtolower('infoScreen'),
                ]
            )
        );

        $isGotoRequest = (
            preg_match('/^tst_\d+$/', (string) $this->httpRequest->getQueryParams()['target'] ?? '')
        );

        return $isBaseClassInfoScreenRequest || $isCmdClassInfoScreenRequest || $isGotoRequest;
    }

    /**
     * @return int
     */
    private function getTestRefId() : int
    {
        $refId = $this->getRefId();
        if ($refId <= 0) {
            $refId = $this->getPreviewRefId();
        }

        if ($refId <= 0) {
            $refId = $this->getTargetRefId();
        }
        
        return $refId;
    }
    
    /**
     * @inheritDoc
     */
    public function shouldModifyHtml(string $component, string $part, array $parameters) : bool
    {
        if (!$this->isInfoScreenContext() && !$this->isPreviewContext()) {
            return false;
        }

        if ('template_get' !== $part) {
            return false;
        }
        
        if ($this->isPreviewContext()) {
            if ('Modules/Course/Intro/tpl.intro_layout.html' !== $parameters['tpl_id']) {
                return false;
            }

            if (!$this->isPreviewObjectOfType('tst')) {
                return false;
            }
        } else {
            if ('Services/UIComponent/Toolbar/tpl.toolbar.html' !== $parameters['tpl_id']) {
                return false;
            }

            if (!$this->isObjectOfType('tst') && !$this->isTargetObjectOfType('tst')) {
                return false;
            }
        }

        if (!$this->coreAccessHandler->checkAccess('read', '', $this->getTestRefId())) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function modifyHtml(string $component, string $part, array $parameters) : array
    {
        $html = $parameters['html'];

        if ($this->isPreviewContext()) {
            // TODO: Parse/Manipulate buttons
        } else {
            // TODO: Parse/Manipulate buttons
        }

        return ['mode' => \ilUIHookPluginGUI::REPLACE, 'html' => $html];
    }
    /*
    private function callApi()
    {
        global $DIC;

        $testUrl =  ilLink::_getStaticLink($this->testOBJ->getRefId(), 'tst');

        $urlParts = parse_url($testUrl);
        $baseUrlWithScript = $urlParts['scheme'] . '://' . $urlParts['host'];
        $regexQuotedBaseUrlWithScript = preg_quote($baseUrlWithScript, '/');

        $refId = $this->testOBJ->getRefId();

        $startRegex = sprintf(
            '(.*?)(([\?&]target=tst_%s)|(([\?&]cmd=infoScreen(.*?)&ref_id=%s)|([\?&]ref_id=%s(.*?)&cmd=infoScreen)))',
            $refId, $refId, $refId
        );

        $parameterValues = [
            ilTestPlayerCommands::START_TEST,
            ilTestPlayerCommands::INIT_TEST,
            ilTestPlayerCommands::START_PLAYER,
            ilTestPlayerCommands::RESUME_PLAYER,
            //ilTestPlayerCommands::DISPLAY_ACCESS_CODE,
            //ilTestPlayerCommands::ACCESS_CODE_CONFIRMED,
            ilTestPlayerCommands::SHOW_QUESTION,
            ilTestPlayerCommands::PREVIOUS_QUESTION,
            ilTestPlayerCommands::NEXT_QUESTION,
            ilTestPlayerCommands::EDIT_SOLUTION,
            //ilTestPlayerCommands::MARK_QUESTION,
            //ilTestPlayerCommands::MARK_QUESTION_SAVE,
            //ilTestPlayerCommands::UNMARK_QUESTION,
            //ilTestPlayerCommands::UNMARK_QUESTION_SAVE,
            ilTestPlayerCommands::SUBMIT_INTERMEDIATE_SOLUTION,
            ilTestPlayerCommands::SUBMIT_SOLUTION,
            ilTestPlayerCommands::SUBMIT_SOLUTION_AND_NEXT,
            ilTestPlayerCommands::REVERT_CHANGES,
            //ilTestPlayerCommands::DETECT_CHANGES,
            //ilTestPlayerCommands::DISCARD_SOLUTION,
            //ilTestPlayerCommands::SKIP_QUESTION,
            //ilTestPlayerCommands::SHOW_INSTANT_RESPONSE,
            //ilTestPlayerCommands::CONFIRM_HINT_REQUEST,
            //ilTestPlayerCommands::SHOW_REQUESTED_HINTS_LIST,
            ilTestPlayerCommands::QUESTION_SUMMARY,
            //ilTestPlayerCommands::QUESTION_SUMMARY_INC_OBLIGATIONS,
            //ilTestPlayerCommands::QUESTION_SUMMARY_OBLIGATIONS_ONLY,
            //ilTestPlayerCommands::TOGGLE_SIDE_LIST,
            ilTestPlayerCommands::SHOW_QUESTION_SELECTION,
            //ilTestPlayerCommands::UNFREEZE_ANSWERS,
            //ilTestPlayerCommands::AUTO_SAVE,
            //ilTestPlayerCommands::REDIRECT_ON_TIME_LIMIT,
            ilTestPlayerCommands::SUSPEND_TEST,
            ilTestPlayerCommands::FINISH_TEST,
            ilTestPlayerCommands::AFTER_TEST_PASS_FINISHED,
            ilTestPlayerCommands::SHOW_FINAL_STATMENT,
            ilTestPlayerCommands::BACK_TO_INFO_SCREEN,
            ilTestPlayerCommands::BACK_FROM_FINISHING,
            'show',
            $this->testOBJ->getRefId(),
        ];

        $parameterValues[] = 'iltestsubmissionreviewgui';
        if ($this->testOBJ->isRandomTest()) {
            $parameterValues[] = 'iltestplayerrandomquestionsetgui';
        } elseif ($this->testOBJ->isFixedTest()) {
            $parameterValues[] = 'iltestplayerfixedquestionsetgui';
        } else {
            throw new \ilException("Proctorio: Not implemented");
        }

        $parameterNames = [
            'cmd',
            'fallbackCmd',
            'ref_id',
            'cmdClass',
        ];

        $this->setParameter($this->getTestPlayerGUI(), 'lock', $this->getSessionLockString());
        $this->setParameter($this->getTestPlayerGUI(), 'sequence', $this->getTestSession()->getLastSequence());
        $this->setParameter('ilObjTestGUI', 'ref_id', $this->getTestOBJ()->getRefId());
        $launchUrl = $this->buildLinkTarget($this->testPlayerGUI, $launchCommand, false);

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
            "launch_url" => $finalLaunchUrl,
            "user_id" => $DIC->user()->getId(),
            "oauth_consumer_key" => "8fa3fe65889b46e5bc2e9f8702b44f7f",
            "exam_start" => $regexQuotedBaseUrlWithScript . $startRegex,
            "exam_take" => $regexQuotedBaseUrlWithScript . $takeRegex,
            "exam_end" => $regexQuotedBaseUrlWithScript . $endRegex,
            "exam_settings" => implode(',', [
                "recordaudio", // TODO: Konfig. in ILIAS mit Bild (checkbox)
                "recordvideo",
            ]),
            "fullname" => $DIC->user()->getFullname(),
            "exam_tag" => $this->testOBJ->getId(),
            "oauth_signature_method" => "HMAC-SHA1",
            "oauth_version" => "1.0",
            "oauth_timestamp" => time(),
            "oauth_nonce" => md5(uniqid(rand(), true)),
        ];
        $consumerSecret = "9aebc1ae3b4a411ca26f2c397ca33359";

        $region = 'us1';
        $urlPath = '6521ca945bd84cfc85d2767da06aa7c8';
        $url = 'https://' . $region . '5499ws.proctor.io';

        $DIC->logger()->root()->info(print_r($parameters,1));

        $signature_data = '';
        // https://csharp.hotexamples.com/de/site/file?hash=0xee75c4aef1fe45609c1c1cfc2677509faae0583c603d230fce0c1559b16dddb8&fullName=LtiLibrary.Core/OAuthUtility.cs&project=andyfmiller/LtiLibrary
        // https://github.com/andyfmiller/LtiLibrary/blob/master/src/LtiLibrary.NetCore/Extensions/NameValueCollectionExtensions.cs#L20
        // https://github.com/andyfmiller/LtiLibrary/blob/master/src/LtiLibrary.NetCore/Extensions/StringExtensions.cs
        foreach ($parameters as $key => $value) {
            $signature_data .= '&' . rawurlencode($key) . '=' . rawurlencode($value);
        }
        $signature_data = ltrim($signature_data, '&');

        $signature_base_string = 'POST&' . rawurlencode($url . '/' . $urlPath) . '&' . rawurlencode($signature_data); // rawurlencode() = RFC3986

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

        // Iterate over the requests and responses
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
            $btn = ilLinkButton::getInstance();
            $btn->setCaption('Proctorio: ' . $launchLabel, false);
            $btn->setUrl($responseArray[0]);
            $btn->setPrimary(true);
            $this->addButtonInstance($btn);
        }

        $isEvalApiSuccess = is_array($responseArray) && isset($responseArray[1]) && is_string($responseArray[1]) && strlen($responseArray[1]) > 0;
        if ($isEvalApiSuccess) {
            $btn = ilLinkButton::getInstance();
            $btn->setCaption('Proctorio Evaluation', false);
            $btn->setUrl($responseArray[1]);
            $btn->setPrimary(true);
            $this->addButtonInstance($btn);
        }

        $messages = [];
        $messages[] = ('Launch URL: ' . $finalLaunchUrl);
        $messages[] = ('Start Regex: ' . $regexQuotedBaseUrlWithScript . $startRegex);
        $messages[] = ('Take Regex: ' . $regexQuotedBaseUrlWithScript . $takeRegex);
        $messages[] = ('End Regex: ' . $regexQuotedBaseUrlWithScript . $endRegex);
        $messages[] = ('API Launch Request Success: ' . ($isLaunchApiSuccess ? 'Yes' : 'No'));
        $messages[] = ('API Evaluation Request Success: ' . ($isEvalApiSuccess ? 'Yes' : 'No'));

        $debugMessage = implode('<br><br>', $messages);
        $debugMessage = str_replace(["{", "}"], ["&#123;", "&#125;"], $debugMessage);
    }*/

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