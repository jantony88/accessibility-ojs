<?php

/**
 * @file plugins/generic/zytekAccessibility/ZytekAccessibilitySettingsForm.php
 *
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class ZytekAccessibilitySettingsForm
 *
 * @brief Settings: which reader tools to offer, button position and colour,
 *  and the accessibility statement.
 */

namespace APP\plugins\generic\zytekAccessibility;

use APP\template\TemplateManager;
use PKP\form\Form;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorCustom;
use PKP\form\validation\FormValidatorEmail;
use PKP\form\validation\FormValidatorInSet;
use PKP\form\validation\FormValidatorPost;
use PKP\form\validation\FormValidatorRegExp;

class ZytekAccessibilitySettingsForm extends Form
{
    public function __construct(protected ZytekAccessibilityPlugin $plugin, protected int $contextId)
    {
        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

        // Hex colour dark enough for the white text of the button and panel header
        $this->addCheck(new FormValidatorCustom($this, 'buttonColour', 'required', 'plugins.generic.zytekAccessibility.settings.error.colour', function ($colour) {
            return ZytekAccessibilityPlugin::isUsableColour($colour);
        }));
        $this->addCheck(new FormValidatorInSet($this, 'position', 'required', 'plugins.generic.zytekAccessibility.settings.error.position', ZytekAccessibilityPlugin::POSITIONS));
        $this->addCheck(new FormValidatorRegExp($this, 'statementPath', 'required', 'plugins.generic.zytekAccessibility.settings.error.path', ZytekAccessibilityPlugin::PATH_PATTERN));
        $this->addCheck(new FormValidatorEmail($this, 'contactEmail', 'optional', 'plugins.generic.zytekAccessibility.settings.error.email'));
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    /**
     * @copydoc Form::initData()
     */
    public function initData()
    {
        $display = $this->plugin->getDisplaySettings($this->contextId);
        $enabled = $this->plugin->getEnabledFeatures($this->contextId);
        $data = [
            'position' => $display['position'],
            'buttonColour' => $display['buttonColour'],
            'showStatement' => $display['showStatement'],
            'statementPath' => $display['statementPath'],
            'footerLink' => $display['footerLink'],
            'showBrand' => $display['showBrand'],
            'contactEmail' => $this->plugin->getSetting($this->contextId, 'contactEmail'),
            'reviewDate' => $this->plugin->getSetting($this->contextId, 'reviewDate'),
            'statement' => $this->plugin->getSetting($this->contextId, 'statement'),
        ];
        foreach ($this->plugin->getAllFeatures() as $feature) {
            $data['feature_' . $feature] = in_array($feature, $enabled);
        }
        $this->_data = $data;
    }

    /**
     * @copydoc Form::readInputData()
     */
    public function readInputData()
    {
        $vars = ['position', 'buttonColour', 'showBrand', 'showStatement', 'statementPath', 'footerLink', 'contactEmail', 'reviewDate', 'statement'];
        foreach ($this->plugin->getAllFeatures() as $feature) {
            $vars[] = 'feature_' . $feature;
        }
        $this->readUserVars($vars);
        $this->setData('buttonColour', trim((string) $this->getData('buttonColour')));
        $this->setData('statementPath', strtolower(trim((string) $this->getData('statementPath'))));
        $this->setData('contactEmail', trim((string) $this->getData('contactEmail')));
    }

    /**
     * @copydoc Form::fetch()
     *
     * @param null|mixed $template
     */
    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $featureValues = [];
        foreach ($this->plugin->getAllFeatures() as $feature) {
            $featureValues[$feature] = (bool) $this->getData('feature_' . $feature);
        }
        $templateMgr->assign([
            'pluginName' => $this->plugin->getName(),
            'featureValues' => $featureValues,
            'featureGroups' => ZytekAccessibilityPlugin::FEATURE_GROUPS,
            'positions' => array_combine(
                ZytekAccessibilityPlugin::POSITIONS,
                array_map(
                    fn ($position) => 'plugins.generic.zytekAccessibility.settings.position.' . $position,
                    ZytekAccessibilityPlugin::POSITIONS
                )
            ),
        ]);
        return parent::fetch($request, $template, $display);
    }

    /**
     * @copydoc Form::getLocaleFieldNames()
     */
    public function getLocaleFieldNames(): array
    {
        return ['statement'];
    }

    /**
     * @copydoc Form::execute()
     */
    public function execute(...$functionArgs)
    {
        $plugin = $this->plugin;
        $contextId = $this->contextId;
        foreach ($plugin->getAllFeatures() as $feature) {
            $plugin->updateSetting($contextId, 'feature_' . $feature, (bool) $this->getData('feature_' . $feature), 'bool');
        }
        $plugin->updateSetting($contextId, 'position', $this->getData('position'), 'string');
        $plugin->updateSetting($contextId, 'buttonColour', $this->getData('buttonColour'), 'string');
        $plugin->updateSetting($contextId, 'showBrand', (bool) $this->getData('showBrand'), 'bool');
        $plugin->updateSetting($contextId, 'showStatement', (bool) $this->getData('showStatement'), 'bool');
        $plugin->updateSetting($contextId, 'footerLink', (bool) $this->getData('footerLink'), 'bool');
        $plugin->updateSetting($contextId, 'statementPath', $this->getData('statementPath'), 'string');
        $plugin->updateSetting($contextId, 'contactEmail', $this->getData('contactEmail'), 'string');
        $plugin->updateSetting($contextId, 'reviewDate', trim((string) $this->getData('reviewDate')), 'string');
        $plugin->updateSetting($contextId, 'statement', (array) $this->getData('statement'), 'object');
        parent::execute(...$functionArgs);
    }
}
