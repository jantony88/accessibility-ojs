<?php

/**
 * @file plugins/generic/zytekAccessibility/ZytekAccessibilityPlugin.php
 *
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class ZytekAccessibilityPlugin
 *
 * @brief Accessibility tools for readers (text size, contrast, colour
 *  filters, spacing, readable font, reading aids, read aloud, motion
 *  control...) and an accessibility statement page with a channel to report
 *  barriers.
 *
 *  Works with any theme built on PKP's frontend structure. Preferences are
 *  stored only in the reader's browser; nothing is loaded from third parties.
 *  No OJS/PKP core file is modified.
 */

namespace APP\plugins\generic\zytekAccessibility;

use APP\core\Application;
use APP\plugins\generic\zytekAccessibility\classes\ZytekAccessibilityIcons;
use APP\template\TemplateManager;
use PKP\config\Config;
use PKP\context\Context;
use PKP\core\JSONMessage;
use PKP\core\PKPPageRouter;
use PKP\core\PKPRequest;
use PKP\facades\Locale;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class ZytekAccessibilityPlugin extends GenericPlugin
{
    /**
     * Reader tools, grouped as in the panel. The key is the feature id used in
     * the settings ("feature_<id>"), the CSS class on <html> and the JS.
     */
    public const FEATURE_GROUPS = [
        'content' => ['textSize', 'textSpacing', 'readableFont', 'bigCursor', 'tooltips', 'hideImages', 'readAloud'],
        'colors' => ['contrast', 'invertColors', 'brightness', 'grayscale', 'saturation', 'highlightLinks'],
        'navigation' => ['readingGuide', 'readingMode', 'focusHighlight', 'stopAnimations', 'pageStructure'],
    ];

    /**
     * Tools with several steps. Each press of the tile moves to the next step
     * and, after the last one, back to off. Others are simple on/off.
     */
    public const FEATURE_LEVELS = [
        'textSize' => ['1', '2', '3', '4'],
        'contrast' => ['dark', 'light'],
        'brightness' => ['low', 'high'],
        'saturation' => ['low', 'high'],
        'readAloud' => ['normal', 'slow', 'fast'],
    ];

    /** Tiles that open something in the panel instead of storing a preference */
    public const ACTION_FEATURES = ['pageStructure'];

    /** Text size per step; must match the levels of "textSize" */
    public const TEXT_SCALES = ['1' => 1.25, '2' => 1.5, '3' => 1.75, '4' => 2];

    public const POSITIONS = ['bottomRight', 'bottomLeft'];
    public const DEFAULT_POSITION = 'bottomRight';
    public const DEFAULT_BUTTON_COLOUR = '#0B4F9C';
    public const DEFAULT_STATEMENT_PATH = 'accesibilidad';
    public const COLOUR_PATTERN = '/^#[0-9a-fA-F]{6}$/';
    public const PATH_PATTERN = '/^[a-z0-9][a-z0-9-]{1,40}$/';

    /** White text on the button and panel header needs WCAG AA contrast */
    public const MIN_CONTRAST_WITH_WHITE = 4.5;

    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) {
            return true;
        }
        if ($success && $this->getEnabled($mainContextId)) {
            Hook::add('TemplateManager::display', [$this, 'addAssets']);
            Hook::add('Templates::Common::Footer::PageFooter', [$this, 'addWidget']);
            Hook::add('LoadHandler', [$this, 'loadStatementHandler']);
        }
        return $success;
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.generic.zytekAccessibility.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription()
    {
        return __('plugins.generic.zytekAccessibility.description');
    }

    /**
     * @return string[] Every feature id, in display order
     */
    public function getAllFeatures(): array
    {
        return array_merge(...array_values(self::FEATURE_GROUPS));
    }

    /**
     * @return string[] Feature ids enabled by the journal (all of them by default)
     */
    public function getEnabledFeatures(int $contextId): array
    {
        $enabled = [];
        foreach ($this->getAllFeatures() as $feature) {
            if ($this->getSetting($contextId, 'feature_' . $feature) !== false) {
                $enabled[] = $feature;
            }
        }
        return $enabled;
    }

    /**
     * @return array Display settings with defaults applied
     */
    public function getDisplaySettings(int $contextId): array
    {
        $position = $this->getSetting($contextId, 'position');
        $colour = $this->getSetting($contextId, 'buttonColour');
        $path = $this->getSetting($contextId, 'statementPath');
        return [
            'position' => in_array($position, self::POSITIONS, true) ? $position : self::DEFAULT_POSITION,
            'buttonColour' => self::isUsableColour($colour) ? $colour : self::DEFAULT_BUTTON_COLOUR,
            'showStatement' => $this->getSetting($contextId, 'showStatement') !== false,
            'statementPath' => is_string($path) && preg_match(self::PATH_PATTERN, $path) ? $path : self::DEFAULT_STATEMENT_PATH,
            'footerLink' => $this->getSetting($contextId, 'footerLink') !== false,
            'showBrand' => $this->getSetting($contextId, 'showBrand') !== false,
        ];
    }

    /**
     * @return bool A six-digit hex colour on which white text is readable
     */
    public static function isUsableColour(mixed $colour): bool
    {
        return is_string($colour) && preg_match(self::COLOUR_PATTERN, $colour)
            && self::getContrastWithWhite($colour) >= self::MIN_CONTRAST_WITH_WHITE;
    }

    /**
     * WCAG contrast ratio between a colour and white.
     *
     * @param string $hex "#RRGGBB"
     *
     * @return float 1 to 21
     */
    public static function getContrastWithWhite(string $hex): float
    {
        $luminance = 0;
        foreach ([0.2126, 0.7152, 0.0722] as $i => $weight) {
            $channel = hexdec(substr($hex, 1 + $i * 2, 2)) / 255;
            $channel = $channel <= 0.03928 ? $channel / 12.92 : pow(($channel + 0.055) / 1.055, 2.4);
            $luminance += $weight * $channel;
        }
        return 1.05 / ($luminance + 0.05);
    }

    /**
     * The contact for barrier reports: the plugin's own address or, if empty,
     * the journal contact.
     */
    public function getContactEmail(Context $context): string
    {
        $email = trim((string) $this->getSetting($context->getId(), 'contactEmail'));
        return $email !== '' ? $email : (string) $context->getData('contactEmail');
    }

    /**
     * Stylesheet, script and a tiny inline script that applies saved
     * preferences before the page is painted (no flash). They are registered
     * for the "frontend" context only, so they reach every reader page
     * (including pages rendered by plugins, such as static pages) and never
     * the editorial back office.
     *
     * @param string $hookName
     * @param array $args [TemplateManager, string template]
     */
    public function addAssets($hookName, $args): bool
    {
        $templateMgr = $args[0];
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        if (!$context || !($request->getRouter() instanceof PKPPageRouter)) {
            return Hook::CONTINUE;
        }
        $features = $this->getEnabledFeatures($context->getId());
        if (!$features) {
            return Hook::CONTINUE;
        }

        $base = $request->getBaseUrl() . '/' . $this->getPluginPath();
        $version = $this->getCurrentVersion() ? $this->getCurrentVersion()->getVersionString() : '1';
        $templateMgr->addStyleSheet('zytekAccessibility', $base . '/css/zytekAccessibility.css?v=' . urlencode($version), [
            'contexts' => 'frontend',
            'priority' => TemplateManager::STYLE_SEQUENCE_LAST,
        ]);
        $templateMgr->addJavaScript('zytekAccessibility', $base . '/js/zytekAccessibility.js?v=' . urlencode($version), [
            'contexts' => 'frontend',
            'priority' => TemplateManager::STYLE_SEQUENCE_LAST,
        ]);
        $templateMgr->addHeader('zytekAccessibilityEarly', '<script>' . $this->getEarlyScript($features) . '</script>', [
            'contexts' => 'frontend',
        ]);
        return Hook::CONTINUE;
    }

    /**
     * Applies stored preferences to <html> before first paint. Only features
     * the journal enabled, and only known steps, are honoured.
     *
     * @param string[] $features
     */
    protected function getEarlyScript(array $features): string
    {
        $allowed = [];
        foreach (array_diff($features, ['textSize'], self::ACTION_FEATURES) as $feature) {
            $allowed[$feature] = self::FEATURE_LEVELS[$feature] ?? true;
        }
        $allowed = json_encode($allowed);
        $scales = json_encode(self::TEXT_SCALES);
        return "(function(){try{var p=JSON.parse(localStorage.getItem('zytekA11y')||'{}'),a={$allowed},s={$scales},h=document.documentElement;"
            . "Object.keys(a).forEach(function(f){var v=p[f];if(!v)return;if(a[f]===true){h.classList.add('zytek-a11y-'+f);}else if(a[f].indexOf(v)!==-1){h.classList.add('zytek-a11y-'+f,'zytek-a11y-'+f+'-'+v);}});"
            . (in_array('textSize', $features) ? "if(s[p.textSize]&&p.base){h.style.setProperty('--zytek-a11y-base',p.base+'px');h.style.setProperty('--zytek-a11y-scale',s[p.textSize]);h.setAttribute('data-zytek-a11y-text',p.textSize);}" : '')
            . "if(sessionStorage.getItem('zytekA11yHidden')){h.classList.add('zytek-a11y-buttonHidden');}"
            . '}catch(e){}})();';
    }

    /**
     * Adds the panel at the end of every reader page.
     *
     * @param string $hookName
     * @param array $args [params, Smarty, &output]
     */
    public function addWidget($hookName, $args): bool
    {
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        if (!$context) {
            return Hook::CONTINUE;
        }
        $features = $this->getEnabledFeatures($context->getId());
        if (!$features) {
            return Hook::CONTINUE;
        }
        $output = & $args[2];
        $templateMgr = TemplateManager::getManager($request);
        $display = $this->getDisplaySettings($context->getId());
        $groups = [];
        foreach (self::FEATURE_GROUPS as $group => $groupFeatures) {
            $visible = array_values(array_intersect($groupFeatures, $features));
            if ($visible) {
                $groups[$group] = $visible;
            }
        }
        $levels = array_intersect_key(self::FEATURE_LEVELS, array_flip($features));
        $templateMgr->assign([
            'zytekA11yGroups' => $groups,
            'zytekA11yLevels' => $levels,
            'zytekA11yIcons' => ZytekAccessibilityIcons::getAll(),
            'zytekA11yFeaturesJson' => json_encode(array_values($features)),
            'zytekA11yLevelsJson' => json_encode($levels),
            'zytekA11yScalesJson' => json_encode(self::TEXT_SCALES),
            'zytekA11yStringsJson' => json_encode($this->getScriptStrings(), JSON_UNESCAPED_UNICODE),
            'zytekA11yDisplay' => $display,
            'zytekA11yLocales' => $this->getLocaleLinks($request, $context),
            'zytekA11yCurrentLocale' => Locale::getLocale(),
            'zytekA11yReportEmail' => $this->getContactEmail($context),
            'zytekA11yStatementUrl' => $display['showStatement']
                ? $request->getDispatcher()->url($request, Application::ROUTE_PAGE, $context->getPath(), $display['statementPath'])
                : '',
        ]);
        $output .= $templateMgr->fetch($this->getTemplateResource('widget.tpl'));
        return Hook::CONTINUE;
    }

    /**
     * Links that switch the site language and come back to the same page,
     * as the language block of OJS does. Empty when there is one language.
     *
     * @return array [locale => ['name' => ..., 'code' => 'ES', 'url' => ...]]
     */
    protected function getLocaleLinks(PKPRequest $request, Context $context): array
    {
        $names = (array) $context->getSupportedLocaleNames();
        if (count($names) < 2) {
            return [];
        }
        $links = [];
        foreach ($names as $locale => $name) {
            $links[$locale] = [
                'name' => $name,
                'code' => strtoupper(substr($locale, 0, 2)),
                'lang' => str_replace('_', '-', $locale),
                'url' => $request->getDispatcher()->url($request, Application::ROUTE_PAGE, null, 'user', 'setLocale', $locale, [
                    'source' => $_SERVER['REQUEST_URI'] ?? '',
                ]),
            ];
        }
        return $links;
    }

    /**
     * Texts the script needs (announcements, levels, read aloud, landmarks).
     *
     * @return array key => translated text
     */
    protected function getScriptStrings(): array
    {
        $keys = [
            'textSize' => 'textSize.value',
            'on' => 'state.on',
            'off' => 'state.off',
            'readHint' => 'readAloud.hint',
            'readPage' => 'readAloud.readPage',
            'pause' => 'readAloud.pause',
            'resume' => 'readAloud.resume',
            'stop' => 'readAloud.stop',
            'unsupported' => 'readAloud.unsupported',
            'structureEmpty' => 'pageStructure.empty',
            'landmarks' => 'pageStructure.landmarks',
            'headings' => 'pageStructure.headings',
            'banner' => 'landmark.banner',
            'navigation' => 'landmark.navigation',
            'main' => 'landmark.main',
            'complementary' => 'landmark.complementary',
            'contentinfo' => 'landmark.contentinfo',
            'search' => 'landmark.search',
            'footerLink' => 'statement.title',
            'reset' => 'resetDone',
            'hidden' => 'hide.done',
            'reportSubject' => 'report.subject',
            'reportBody' => 'report.body',
        ];
        $strings = [];
        foreach ($keys as $name => $key) {
            $strings[$name] = __('plugins.generic.zytekAccessibility.' . $key);
        }
        // Name of each step, e.g. levels.contrast.dark ("Dark high contrast")
        foreach (self::FEATURE_LEVELS as $feature => $steps) {
            if ($feature === 'textSize') {
                continue;
            }
            foreach ($steps as $step) {
                $strings['levels'][$feature][$step] = __('plugins.generic.zytekAccessibility.' . $feature . '.' . $step);
            }
        }
        return $strings;
    }

    /**
     * Serves the accessibility statement at /<journal>/<statementPath>.
     *
     * @param string $hookName
     * @param array $args [&page, &op, &sourceFile, &handler]
     */
    public function loadStatementHandler($hookName, $args): bool
    {
        $page = & $args[0];
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        if (!$context) {
            return Hook::CONTINUE;
        }
        $display = $this->getDisplaySettings($context->getId());
        if (!$display['showStatement'] || $page !== $display['statementPath']) {
            return Hook::CONTINUE;
        }
        $op = & $args[1];
        $handler = & $args[3];
        $page = 'zytekAccessibility';
        $op = 'statement';
        $handler = new ZytekAccessibilityHandler($this);
        return Hook::ABORT;
    }

    /**
     * @copydoc Plugin::getActions()
     */
    public function getActions($request, $actionArgs)
    {
        $actions = parent::getActions($request, $actionArgs);
        if (!$this->getEnabled()) {
            return $actions;
        }
        $router = $request->getRouter();
        array_unshift($actions, new LinkAction(
            'settings',
            new AjaxModal(
                $router->url($request, null, null, 'manage', null, ['verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic']),
                $this->getDisplayName()
            ),
            __('manager.plugins.settings'),
            null
        ));
        return $actions;
    }

    /**
     * @copydoc Plugin::manage()
     */
    public function manage($args, $request)
    {
        if ($request->getUserVar('verb') !== 'settings') {
            return parent::manage($args, $request);
        }
        $form = new ZytekAccessibilitySettingsForm($this, $request->getContext()->getId());
        if ($request->getUserVar('save')) {
            $form->readInputData();
            if ($form->validate()) {
                $form->execute();
                return new JSONMessage(true);
            }
        } else {
            $form->initData();
        }
        return new JSONMessage(true, $form->fetch($request));
    }
}
