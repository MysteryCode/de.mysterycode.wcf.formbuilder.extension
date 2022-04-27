require(['MysteryCode/Form/Builder/Field/Dependency/Numeric'], ({ Numeric }) => {
	// dependency '{@$dependency->getId()}'
	new Numeric(
		'{@$dependency->getDependentNode()->getPrefixedId()}Container',
		'{@$dependency->getField()->getPrefixedId()}'
	).referenceValue({@$dependency->getReferenceValue()})
	.operator('{@$dependency->getOperator()}');
});
