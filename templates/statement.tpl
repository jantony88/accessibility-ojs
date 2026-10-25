{**
 * plugins/generic/zytekAccessibility/templates/statement.tpl
 *
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @brief Accessibility statement page.
 *
 * @uses $zytekA11yStatement string Statement written by the press (HTML), or ''
 * @uses $zytekA11yContextName string Journal name
 * @uses $zytekA11yContactEmail string
 * @uses $zytekA11yReviewDate string
 * @uses $zytekA11yGroupsForStatement array group => enabled feature ids
 *}
{include file="frontend/components/header.tpl" pageTitle="plugins.generic.zytekAccessibility.statement.title"}

<div class="page zytek-a11y-statement">
	<h1>{translate key="plugins.generic.zytekAccessibility.statement.title"}</h1>

	{if $zytekA11yStatement}
		{$zytekA11yStatement|strip_unsafe_html}
	{else}
		{translate key="plugins.generic.zytekAccessibility.statement.default" contextName=$zytekA11yContextName|escape}
	{/if}

	<h2>{translate key="plugins.generic.zytekAccessibility.statement.tools"}</h2>
	<p>{translate key="plugins.generic.zytekAccessibility.statement.toolsIntro"}</p>
	{foreach from=$zytekA11yGroupsForStatement key=group item=features}
		{if $features}
			<h3>{translate key="plugins.generic.zytekAccessibility.group.$group"}</h3>
			<ul>
				{foreach from=$features item=feature}
					<li><strong>{translate key="plugins.generic.zytekAccessibility.feature.$feature.label"}</strong>: {translate key="plugins.generic.zytekAccessibility.feature.$feature.description"}</li>
				{/foreach}
			</ul>
		{/if}
	{/foreach}
	<p>{translate key="plugins.generic.zytekAccessibility.statement.privacy"}</p>

	<h2>{translate key="plugins.generic.zytekAccessibility.statement.feedback"}</h2>
	<p>{translate key="plugins.generic.zytekAccessibility.statement.feedbackText"}</p>
	{if $zytekA11yContactEmail}
		<p>
			<a href="mailto:{$zytekA11yContactEmail|escape}?subject={translate|escape:'url' key="plugins.generic.zytekAccessibility.statement.mailSubject"}">{$zytekA11yContactEmail|escape}</a>
		</p>
	{/if}

	{if $zytekA11yReviewDate}
		<p class="zytek-a11y-statement__date">{translate key="plugins.generic.zytekAccessibility.statement.reviewed" date=$zytekA11yReviewDate|escape}</p>
	{/if}
</div>

{include file="frontend/components/footer.tpl"}
