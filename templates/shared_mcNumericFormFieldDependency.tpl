require(['MysteryCode/Form/Builder/Field/Dependency/Numeric'], ({ Numeric }) => {
	// dependency '{$dependency->getId()}'
	new Numeric(
		'{unsafe:$dependency->getDependentNode()->getPrefixedId()|encodeJS}Container',
		'{unsafe:$dependency->getField()->getPrefixedId()|encodeJS}'
	).referenceValue({$dependency->getReferenceValue()})
	.operator('{unsafe:$dependency->getOperator()|encodeJS}');
});
