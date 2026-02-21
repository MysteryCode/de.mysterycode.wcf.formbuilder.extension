require(['MysteryCode/Form/Builder/Field/Dependency/ItemList'], ({ ItemList }) => {
	// dependency '{$dependency->getId()}'
	new ItemList(
		'{unsafe:$dependency->getDependentNode()->getPrefixedId()|encodeJS}Container',
		'{unsafe:$dependency->getField()->getPrefixedId()|encodeJS}'
	).state({$dependency->getState()})
	.values({if $dependency->getValues() === null}null{else}[ {implode from=$dependency->getValues() item=dependencyValue}'{unsafe:$dependencyValue|encodeJS}'{/implode} ]{/if})
	.negate({if $dependency->isNegated()}true{else}false{/if});
});
