<?php declare(strict_types=1);
/* Copyright (c) 1998-2019 ILIAS open source, Extended GPL, see docs/LICENSE */

namespace ILIAS\Plugin\Proctorio\Administration\GeneralSettings;

use ILIAS\Plugin\Proctorio\AccessControl\Acl;
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
    /** @var Acl */
    private $acl;
    /** @var array */
    protected $aclRoleToGlobalRoleMappings = [];

    /**
     * Settings constructor.
     * @param \ilSetting $settings
     * @param Acl $settings
     */
    public function __construct(\ilSetting $settings, Acl $acl)
    {
        $this->settings = $settings;
        $this->acl = $acl;

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
     * @return string
     */
    public function getApiSecret() : string
    {
        return $this->apiSecret;
    }

    /**
     * @return string
     */
    public function getApiRegion() : string
    {
        return $this->apiRegion;
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
     * @return array
     */
    public function getAclRoleToGlobalRoleMappings() : array
    {
        return $this->aclRoleToGlobalRoleMappings;
    }

    protected function read()
    {
        $this->apiKey = (string) $this->settings->get('api_key', '');
        $this->apiSecret = (string) $this->settings->get('api_secret', '');
        $this->apiRegion = (string) $this->settings->get('api_region', '');
        $this->apiBaseUrl = (string) $this->settings->get('api_base_url', '');
        $this->apiLaunchAndReviewEndpoint = (string) $this->settings->get('api_launch_review_endpoint', '');
        $this->aclRoleToGlobalRoleMappings = unserialize(
            $this->settings->get('aclr_to_role_mapping', serialize([])),
            [
                'allowed_classes' => false
            ]
        );
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
        $mappingByRole = [];
        foreach ($this->acl->getRoles() as $role) {
            $mapping = array_filter(array_map('intval',
                (array) $form->getInput('role_mapping_' . $role->getRoleId())
            ));
            $mappingByRole[$role->getRoleId()] = $mapping;
        }
        $this->aclRoleToGlobalRoleMappings = $mappingByRole;
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
        $this->settings->set('aclr_to_role_mapping', serialize($this->aclRoleToGlobalRoleMappings));
    }

    /**
     * @inheritdoc
     */
    public function toArray() : array
    {
        $data = [
            'api_key' => $this->getApiKey(),
            'api_secret' => $this->getApiSecret(),
            'api_region' => $this->getApiRegion(),
            'api_base_url' => $this->getApiBaseUrl(),
            'api_launch_review_endpoint' => $this->getApiLaunchAndReviewEndpoint(),
        ];

        foreach ($this->acl->getRoles() as $role) {
            $data['role_mapping_' . $role->getRoleId()] = [];
            if (isset($this->aclRoleToGlobalRoleMappings[$role->getRoleId()])) {
                $data['role_mapping_' . $role->getRoleId()] = $this->aclRoleToGlobalRoleMappings[$role->getRoleId()];
            }
        }
        
        return $data;
    }
}