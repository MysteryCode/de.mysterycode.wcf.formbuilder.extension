<div id="{$field->getPrefixedId()}">
	{if $field->supportsHTML()}
		{unsafe:$field->getText()}
	{else}
		{$field->getText()}
	{/if}
</div>
