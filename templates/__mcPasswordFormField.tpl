<input type="password" {*
	*}id="{@$field->getPrefixedId()}" {*
	*}name="{@$field->getPrefixedId()}" {*
	*}value="{$field->getValue()}" {*
	*}class="long"{*
	*}{if $field->isAutofocused()} autofocus{/if}{*
	*}{if $field->isRequired()} required{/if}{*
	*}{if $field->isImmutable()} disabled{/if}{*
	*}{if $field->getMinimumLength() !== null} minlength="{$field->getMinimumLength()}"{/if}{*
	*}{if $field->getMaximumLength() !== null} maxlength="{$field->getMaximumLength()}"{/if}{*
	*}{if $field->getPlaceholder() !== null} placeholder="{$field->getPlaceholder()}"{/if}{*
	*}{if $field->getDocument()->isAjax()} data-dialog-submit-on-enter="true"{/if}{*
*}>

{if $field->getMinimumPasswordStrength() !== null}
	<script data-relocate="true">
		require(['WoltLabSuite/Core/Ui/User/PasswordStrength', 'Language'], function (PasswordStrength, Language) {
			{if !$passwordStrengthLanguageSet|isset}
				{include file='passwordStrengthLanguage'}
				{assign var=passwordStrengthLanguageSet value=true}
			{/if}

			new PasswordStrength(elById('{@$field->getPrefixedId()}'), {
				staticDictionary: [
					// TODO
				]
			});
		})
	</script>
{/if}