{**
 * plugins/generic/zytekAccessibility/templates/settingsForm.tpl
 *
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @brief Plugin settings: reader tools, button and accessibility statement.
 *}
<script>
	$(function() {ldelim}
		$('#zytekA11ySettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
	{rdelim});
</script>

<form class="pkp_form" id="zytekA11ySettingsForm" method="post" action="{url router=\PKP\core\PKPApplication::ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}">
	{csrf}
	{include file="controllers/notification/inPlaceNotification.tpl" notificationId="zytekA11ySettingsFormNotification"}

	<p>{translate key="plugins.generic.zytekAccessibility.settings.intro"}</p>

	{foreach from=$featureGroups key=group item=features}
		{fbvFormArea id="zytekA11yGroup-$group" title="plugins.generic.zytekAccessibility.group.$group"}
			{fbvFormSection list=true}
				{foreach from=$features item=feature}
					{fbvElement type="checkbox" id="feature_$feature" value="1" checked=$featureValues.$feature label="plugins.generic.zytekAccessibility.feature.$feature.label"}
				{/foreach}
			{/fbvFormSection}
		{/fbvFormArea}
	{/foreach}

	{fbvFormArea id="zytekA11yButton" title="plugins.generic.zytekAccessibility.settings.button"}
		{fbvFormSection title="plugins.generic.zytekAccessibility.settings.position"}
			{fbvElement type="select" id="position" from=$positions selected=$position translate=true size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
		{fbvFormSection title="plugins.generic.zytekAccessibility.settings.buttonColour" description="plugins.generic.zytekAccessibility.settings.buttonColour.description"}
			{fbvElement type="text" id="buttonColour" value=$buttonColour maxlength="7" size=$fbvStyles.size.SMALL}
		{/fbvFormSection}
		{fbvFormSection list=true}
			{fbvElement type="checkbox" id="showBrand" value="1" checked=$showBrand label="plugins.generic.zytekAccessibility.settings.showBrand"}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormArea id="zytekA11yStatement" title="plugins.generic.zytekAccessibility.settings.statement"}
		{fbvFormSection list=true}
			{fbvElement type="checkbox" id="showStatement" value="1" checked=$showStatement label="plugins.generic.zytekAccessibility.settings.showStatement"}
			{fbvElement type="checkbox" id="footerLink" value="1" checked=$footerLink label="plugins.generic.zytekAccessibility.settings.footerLink"}
		{/fbvFormSection}
		{fbvFormSection title="plugins.generic.zytekAccessibility.settings.statementPath" description="plugins.generic.zytekAccessibility.settings.statementPath.description"}
			{fbvElement type="text" id="statementPath" value=$statementPath maxlength="41" size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
		{fbvFormSection title="plugins.generic.zytekAccessibility.settings.contactEmail" description="plugins.generic.zytekAccessibility.settings.contactEmail.description"}
			{fbvElement type="email" id="contactEmail" value=$contactEmail size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
		{fbvFormSection title="plugins.generic.zytekAccessibility.settings.reviewDate" description="plugins.generic.zytekAccessibility.settings.reviewDate.description"}
			{fbvElement type="text" id="reviewDate" value=$reviewDate maxlength="40" size=$fbvStyles.size.MEDIUM}
		{/fbvFormSection}
		{fbvFormSection title="plugins.generic.zytekAccessibility.settings.statementText" description="plugins.generic.zytekAccessibility.settings.statementText.description"}
			{fbvElement type="textarea" multilingual=true id="statement" value=$statement rich=true height=$fbvStyles.height.TALL}
		{/fbvFormSection}
	{/fbvFormArea}

	{fbvFormButtons}
</form>
