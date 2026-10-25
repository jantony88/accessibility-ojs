{**
 * plugins/generic/zytekAccessibility/templates/widget.tpl
 *
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @brief Accessibility panel: a grid of tiles grouped by need. Rendered
 *  hidden; js/zytekAccessibility.js shows the button and wires the tiles
 *  (progressive enhancement).
 *
 * @uses $zytekA11yGroups array group => feature ids
 * @uses $zytekA11yLevels array feature => steps, for tools with several steps
 * @uses $zytekA11yIcons array name => inner SVG markup
 * @uses $zytekA11yFeaturesJson string enabled feature ids (JSON)
 * @uses $zytekA11yLevelsJson string feature => steps (JSON)
 * @uses $zytekA11yScalesJson string text size step => scale (JSON)
 * @uses $zytekA11yStringsJson string texts used by the script (JSON)
 * @uses $zytekA11yDisplay array position, buttonColour, footerLink, showBrand...
 * @uses $zytekA11yLocales array locale => name, code, lang, url ([] with one language)
 * @uses $zytekA11yCurrentLocale string
 * @uses $zytekA11yReportEmail string
 * @uses $zytekA11yStatementUrl string
 *}
{capture assign="zytekA11ySvg"}class="zytek-a11y__svg" aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"{/capture}
<div id="zytekA11y" class="zytek-a11y zytek-a11y--{$zytekA11yDisplay.position|escape}" hidden
	style="--zytek-a11y-accent: {$zytekA11yDisplay.buttonColour|escape};"
	data-features="{$zytekA11yFeaturesJson|escape}"
	data-levels="{$zytekA11yLevelsJson|escape}"
	data-scales="{$zytekA11yScalesJson|escape}"
	data-statement-url="{$zytekA11yStatementUrl|escape}"
	data-footer-link="{if $zytekA11yDisplay.footerLink && $zytekA11yStatementUrl}1{else}0{/if}"
	data-strings="{$zytekA11yStringsJson|escape}">

	<button type="button" class="zytek-a11y__toggle" aria-expanded="false" aria-controls="zytekA11yPanel" data-a11y-open>
		<svg {$zytekA11ySvg} width="30" height="30">{$zytekA11yIcons.accessibility}</svg>
		<span class="zytek-a11y__sr">{translate key="plugins.generic.zytekAccessibility.open"}</span>
	</button>

	<div id="zytekA11yPanel" class="zytek-a11y__panel" role="dialog" aria-modal="false" aria-labelledby="zytekA11yTitle" hidden>
		<div class="zytek-a11y__head">
			<h2 id="zytekA11yTitle" class="zytek-a11y__title">{translate key="plugins.generic.zytekAccessibility.title"}</h2>
			<div class="zytek-a11y__head-actions">
				{if $zytekA11yLocales}
					<div class="zytek-a11y__lang">
						<button type="button" class="zytek-a11y__head-button zytek-a11y__head-button--lang" aria-expanded="false" aria-controls="zytekA11yLangMenu" data-a11y-lang>
							<svg {$zytekA11ySvg} width="22" height="22">{$zytekA11yIcons.language}</svg>
							<span aria-hidden="true">{$zytekA11yLocales.$zytekA11yCurrentLocale.code|escape}</span>
							<span class="zytek-a11y__sr">{translate key="plugins.generic.zytekAccessibility.language" language=$zytekA11yLocales.$zytekA11yCurrentLocale.name}</span>
						</button>
						<ul id="zytekA11yLangMenu" class="zytek-a11y__lang-menu" hidden>
							{foreach from=$zytekA11yLocales key=locale item=link}
								<li>
									<a href="{$link.url|escape}" lang="{$link.lang|escape}" hreflang="{$link.lang|escape}"{if $locale == $zytekA11yCurrentLocale} aria-current="true"{/if}>{$link.name|escape}</a>
								</li>
							{/foreach}
						</ul>
					</div>
				{/if}
				<button type="button" class="zytek-a11y__head-button" data-a11y-hide>
					<svg {$zytekA11ySvg} width="22" height="22">{$zytekA11yIcons.hide}</svg>
					<span class="zytek-a11y__sr">{translate key="plugins.generic.zytekAccessibility.hide"}</span>
				</button>
				<button type="button" class="zytek-a11y__head-button" data-a11y-close>
					<svg {$zytekA11ySvg} width="22" height="22">{$zytekA11yIcons.close}</svg>
					<span class="zytek-a11y__sr">{translate key="plugins.generic.zytekAccessibility.close"}</span>
				</button>
			</div>
		</div>

		<div class="zytek-a11y__body">
			<p class="zytek-a11y__hint">{translate key="plugins.generic.zytekAccessibility.shortcut"}</p>

			{foreach from=$zytekA11yGroups key=group item=features}
				<section class="zytek-a11y__group" aria-labelledby="zytekA11yGroup-{$group|escape}">
					<h3 class="zytek-a11y__group-title" id="zytekA11yGroup-{$group|escape}">{translate key="plugins.generic.zytekAccessibility.group.$group"}</h3>
					<div class="zytek-a11y__grid">
						{foreach from=$features item=feature}
							<button type="button" class="zytek-a11y__tile" data-a11y-feature="{$feature|escape}"
								{if $feature == 'pageStructure'}aria-expanded="false" aria-controls="zytekA11yStructure"{else}aria-pressed="false"{/if}
								{if $zytekA11yLevels.$feature}data-a11y-steps="{$zytekA11yLevels.$feature|@count}"{/if}>
								<svg {$zytekA11ySvg} width="26" height="26">{$zytekA11yIcons.$feature}</svg>
								<span class="zytek-a11y__tile-label">{translate key="plugins.generic.zytekAccessibility.feature.$feature.label"}</span>
								<span class="zytek-a11y__tile-value" data-a11y-value></span>
								{if $zytekA11yLevels.$feature}
									<span class="zytek-a11y__steps" aria-hidden="true">
										{foreach from=$zytekA11yLevels.$feature item=step}<span class="zytek-a11y__step"></span>{/foreach}
									</span>
								{/if}
							</button>
						{/foreach}
					</div>
				</section>
			{/foreach}

			<div id="zytekA11yStructure" class="zytek-a11y__structure" hidden></div>

			<button type="button" class="zytek-a11y__reset" data-a11y-reset>
				<svg {$zytekA11ySvg} width="20" height="20">{$zytekA11yIcons.reset}</svg>
				{translate key="plugins.generic.zytekAccessibility.reset"}
			</button>
		</div>

		<div class="zytek-a11y__foot">
			<div class="zytek-a11y__foot-links">
				{if $zytekA11yStatementUrl}
					<a href="{$zytekA11yStatementUrl|escape}">{translate key="plugins.generic.zytekAccessibility.statement.title"}</a>
				{/if}
				{if $zytekA11yReportEmail}
					<a href="mailto:{$zytekA11yReportEmail|escape}" data-a11y-report>{translate key="plugins.generic.zytekAccessibility.report"}</a>
				{/if}
			</div>
			{if $zytekA11yDisplay.showBrand}
				<span class="zytek-a11y__brand">
					<svg {$zytekA11ySvg} width="26" height="26">{$zytekA11yIcons.accessibility}</svg>
					Zytek
				</span>
			{/if}
		</div>
	</div>
	<p class="zytek-a11y__sr" role="status" data-a11y-announce></p>

	{if $zytekA11yLevels.readAloud}
		<div class="zytek-a11y__player" role="group" aria-labelledby="zytekA11yPlayerLabel" data-a11y-player hidden>
			<span class="zytek-a11y__player-label" id="zytekA11yPlayerLabel">{translate key="plugins.generic.zytekAccessibility.feature.readAloud.label"}</span>
			<button type="button" class="zytek-a11y__player-button" data-a11y-read-play>{translate key="plugins.generic.zytekAccessibility.readAloud.readPage"}</button>
			<button type="button" class="zytek-a11y__player-button" data-a11y-read-stop disabled>{translate key="plugins.generic.zytekAccessibility.readAloud.stop"}</button>
			<span class="zytek-a11y__player-status" role="status" data-a11y-read-status></span>
		</div>
	{/if}

	<div class="zytek-a11y__tooltip" data-a11y-tooltip aria-hidden="true" hidden></div>
	<div class="zytek-a11y__guide" data-a11y-guide aria-hidden="true"></div>
</div>
