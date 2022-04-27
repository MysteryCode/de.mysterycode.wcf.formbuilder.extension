{assign var='__badgeColorFormFieldCustomValue' value=$field->hasCustomValue()}

<ul class="inlineList badgeColorFormFieldList">
	{foreach from=$field->getAvailableClasses() item=__className}
		<li>
			<label>
				<input {*
				*}type="radio" {*
				*}name="{@$field->getPrefixedId()}" {*
				*}{if $field->getValue() !== null && $field->getValue() === $__className}checked {/if}{*
				*}value="{$__className}" {*
				*}>
				<span class="badge label jsLabelPreview{if $__className !== 'none'} {$__className}{/if}">{$field->getReferenceText()}</span>
			</label>
		</li>
	{/foreach}
	{if $field->supportsCustom()}
		<li class="labelCustomClass">
			<input {*
				*}type="radio" {*
				*}name="{@$field->getPrefixedId()}" {*
				*}class="jsCustomCssClassName"{*
				*}{if $__badgeColorFormFieldCustomValue} checked{/if} {*
				*}value="custom" {*
				*}>
			<span>
				<input type="text" {*
					*}id="{@$field->getPrefixedId()}_className" {*
					*}name="{@$field->getPrefixedId()}_className" {*
					*}value="{if $__badgeColorFormFieldCustomValue}{$field->getValue()}{/if}" {*
					*}class="long"{*
					*}{if $field->isAutofocused()} autofocus{/if}{*
					*}{if $field->isImmutable()} disabled{/if}{*
				*}>
			</span>
		</li>
	{/if}
</ul>

<script data-relocate="true">
	require(['MysteryCode/Form/Builder/LabelPreviewHandler'], ({ LabelPreviewHandler }) => {
		const dlSelector = '#{@$field->getPrefixedId()}Container';
		
		{if $field->getReferenceFieldId()}
			new LabelPreviewHandler(
				'{@$field->getPrefixedId()}Container',
				'{@$field->getReferencedNode()->getPrefixedId()}',
				'{@$field->getReferenceText()}'
			);
		{/if}
		{if $field->supportsCustom()}
			document.getElementById('{@$field->getPrefixedId()}_className').addEventListener('focus', (event) => {
				document.querySelector(dlSelector + ' .jsCustomCssClassName').checked = true;
			});
		{/if}
	});
</script>
