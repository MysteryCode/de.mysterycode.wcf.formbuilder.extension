require(['MysteryCode/Form/Builder/Field/Dependency/ItemList'], ({ ItemList }) => {
	// dependency '{@$dependency->getId()}'
	new ItemList(
		'{@$dependency->getDependentNode()->getPrefixedId()}Container',
		'{@$dependency->getField()->getPrefixedId()}'
	).state({@$dependency->getState()})
	.values({if $dependency->getValues() === null}null{else}[ {implode from=$dependency->getValues() item=dependencyValue}'{$dependencyValue|encodeJS}'{/implode} ]{/if})
	.negate({if $dependency->isNegated()}true{else}false{/if});
});
