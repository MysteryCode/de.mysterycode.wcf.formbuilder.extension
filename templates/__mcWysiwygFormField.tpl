<textarea {*
	*}id="{@$field->getPrefixedId()}" {*
	*}name="{@$field->getPrefixedId()}" {*
	*}class="wysiwygTextarea" {*
	*}data-disable-attachments="{if $field->supportsAttachments()}false{else}true{/if}" {*
	*}data-support-mention="{if $field->supportsMentions()}true{else}false{/if}"{*
	*}{if $field->getAutosaveId() !== null}{*
		*} data-autosave="{@$field->getAutosaveId()}"{*
		*}{if $field->getLastEditTime() !== 0}{*
			*} data-autosave-last-edit-time="{@$field->getLastEditTime()}"{*
		*}{/if}{*
	*}{/if}{*
	*}{foreach from=$field->getFieldAttributes() key='attributeName' item='attributeValue'} {$attributeName}="{$attributeValue}"{/foreach}{*
*}>{if !$field->isI18n() || !$field->hasI18nValues() || $availableLanguages|count === 1}{$field->getValue()}{/if}</textarea>

{include file='wysiwyg' wysiwygSelector=$field->getPrefixedId()}

{if $field->supportsQuotes()}
	<script data-relocate="true">
		// Bootstrap for window.__wcf_bc_eventHandler
		require(['WoltLabSuite/Core/Bootstrap', 'WoltLabSuite/Core/Ui/Message/Quote'], (Bootstrap, UiMessageQuote) => {
			{include file='__messageQuoteManager' wysiwygSelector=$field->getPrefixedId() supportPaste=true}

			{if $field->getQuoteData() !== null}
				const quoteHandler = new UiMessageQuote.default(
					$quoteManager,
					'{$field->getQuoteData('actionClass')|encodeJS}',
					'{$field->getQuoteData('objectType')}',
					'{$field->getQuoteData('selectors')[container]}',
					'{$field->getQuoteData('selectors')[messageBody]}',
					'{$field->getQuoteData('selectors')[messageContent]}',
					true
				);

				document.getElementById('{@$field->getPrefixedId()}').setAttribute('data-quote-handler', quoteHandler);
			{/if}
		});
	</script>
{/if}

{if $field->isI18n()}
	{include file='multipleLanguageInputJavascript'}
{/if}
