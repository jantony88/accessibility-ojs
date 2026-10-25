<?php

/**
 * @file plugins/generic/zytekAccessibility/ZytekAccessibilityHandler.php
 *
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class ZytekAccessibilityHandler
 *
 * @brief Public accessibility statement page.
 */

namespace APP\plugins\generic\zytekAccessibility;

use APP\handler\Handler;
use APP\template\TemplateManager;
use PKP\core\PKPRequest;

class ZytekAccessibilityHandler extends Handler
{
    public function __construct(protected ZytekAccessibilityPlugin $plugin)
    {
        parent::__construct();
    }

    /**
     * Display the statement. The journal can write its own text; otherwise a
     * default statement is shown. The tools and the contact details are
     * always generated from the current settings, so they stay accurate.
     *
     * @param array $args
     */
    public function statement($args, PKPRequest $request): void
    {
        $context = $request->getContext();
        $contextId = $context->getId();
        $plugin = $this->plugin;

        $this->setupTemplate($request);
        $templateMgr = TemplateManager::getManager($request);

        $statement = $plugin->getSetting($contextId, 'statement');
        $statement = is_array($statement) ? $statement : [];
        $localized = '';
        foreach ($context->getLocalePrecedence() as $locale) {
            if (!empty($statement[$locale])) {
                $localized = $statement[$locale];
                break;
            }
        }

        $enabled = $plugin->getEnabledFeatures($contextId);
        $templateMgr->assign([
            'pageTitle' => 'plugins.generic.zytekAccessibility.statement.title',
            'zytekA11yStatement' => $localized,
            'zytekA11yContextName' => $context->getLocalizedName(),
            'zytekA11yContactEmail' => $plugin->getContactEmail($context),
            'zytekA11yReviewDate' => (string) $plugin->getSetting($contextId, 'reviewDate'),
            'zytekA11yGroupsForStatement' => array_map(
                fn ($groupFeatures) => array_values(array_intersect($groupFeatures, $enabled)),
                ZytekAccessibilityPlugin::FEATURE_GROUPS
            ),
        ]);
        $templateMgr->display($plugin->getTemplateResource('statement.tpl'));
    }
}
