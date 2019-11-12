<?php
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Administration\GeneralSettings;

use ILIAS\Plugin\Proctorio\UI\Form\Bindable;

/**
 * Class Settings
 * @package ILIAS\Plugin\Administration\GeneralSettings
 * @author  Michael Jansen <mjansen@databay.de>
 */
class Settings implements Bindable
{
    /** @var \ilSetting */
    private $settings;

    /** @var string */
    private $apiKey = '';

    /** @var string */
    private $apiSecret = '';

    /** @var string */
    private $apiRegion = '';

    /** @var string */
    private $apiBaseUrl = '';

    /** @var string */
    private $apiLaunchAndReviewEndpoint = '';

    /**
     * Settings constructor.
     * @param \ilSetting $settings
     */
    public function __construct(\ilSetting $settings)
    {
        $this->settings = $settings;

        $this->read();
    }

    /**
     * @return \ilSetting
     */
    public function getSettings() : \ilSetting
    {
        return $this->settings;
    }

    /**
     * @param \ilSetting $settings
     */
    public function setSettings(\ilSetting $settings) : void
    {
        $this->settings = $settings;
    }

    /**
     * @return string
     */
    public function getApiKey() : string
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey(string $apiKey) : void
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return string
     */
    public function getApiSecret() : string
    {
        return $this->apiSecret;
    }

    /**
     * @param string $apiSecret
     */
    public function setApiSecret(string $apiSecret) : void
    {
        $this->apiSecret = $apiSecret;
    }

    /**
     * @return string
     */
    public function getApiRegion() : string
    {
        return $this->apiRegion;
    }

    /**
     * @param string $apiRegion
     */
    public function setApiRegion(string $apiRegion) : void
    {
        $this->apiRegion = $apiRegion;
    }

    /**
     * @return string
     */
    public function getApiBaseUrl() : string
    {
        return $this->apiBaseUrl;
    }

    /**
     * @return string
     */
    public function getApiLaunchAndReviewEndpoint() : string
    {
        return $this->apiLaunchAndReviewEndpoint;
    }

    /**
     * @param string $apiLaunchAndReviewEndpoint
     */
    public function setApiLaunchAndReviewEndpoint(string $apiLaunchAndReviewEndpoint) : void
    {
        $this->apiLaunchAndReviewEndpoint = $apiLaunchAndReviewEndpoint;
    }

    /**
     * @param string $apiBaseUrl
     */
    public function setApiBaseUrl(string $apiBaseUrl) : void
    {
        $this->apiBaseUrl = $apiBaseUrl;
    }

    protected function read()
    {
        $this->apiKey = (string) $this->settings->get('api_key', '');
        $this->apiSecret = (string) $this->settings->get('api_secret', '');
        $this->apiRegion = (string) $this->settings->get('api_region', '');
        $this->apiBaseUrl = (string) $this->settings->get('api_base_url', '');
        $this->apiLaunchAndReviewEndpoint = (string) $this->settings->get('api_launch_review_endpoint', '');
    }

    /**
     * @inheritdoc
     */
    public function bindForm(\ilPropertyFormGUI $form)
    {
        $this->apiKey = (string) $form->getInput('api_key');
        $this->apiSecret = (string) $form->getInput('api_secret');
        $this->apiRegion = (string) $form->getInput('api_region');
        $this->apiBaseUrl = (string) $form->getInput('api_base_url');
        $this->apiLaunchAndReviewEndpoint = (string) $form->getInput('api_launch_review_endpoint');
    }

    /**
     * @inheritdoc
     */
    public function onFormSaved()
    {
        $this->settings->set('api_key', $this->getApiKey());
        $this->settings->set('api_secret', $this->getApiSecret());
        $this->settings->set('api_region', $this->getApiRegion());
        $this->settings->set('api_base_url', $this->getApiBaseUrl());
        $this->settings->set('api_launch_review_endpoint', $this->getApiLaunchAndReviewEndpoint());
    }

    /**
     * @inheritdoc
     */
    public function toArray() : array
    {
        return [
            'api_key' => $this->getApiKey(),
            'api_secret' => $this->getApiSecret(),
            'api_region' => $this->getApiRegion(),
            'api_base_url' => $this->getApiBaseUrl(),
            'api_launch_review_endpoint' => $this->getApiLaunchAndReviewEndpoint(),
        ];
    }
}