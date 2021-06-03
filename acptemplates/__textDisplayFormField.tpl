<div id="{@$field->getPrefixedId()}">
	{if $field->supportsHTML()}
		{@$field->getText()}
	{else}
		{$field->getText()}
	{/if}
</div>
