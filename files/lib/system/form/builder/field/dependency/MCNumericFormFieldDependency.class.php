<?php

namespace wcf\system\form\builder\field\dependency;

class MCNumericFormFieldDependency extends AbstractFormFieldDependency {
	/**
	 * @inheritDoc
	 */
	protected $templateName = '__mcNumericFormFieldDependency';
	
	/**
	 * @var string
	 */
	protected string $operator;
	
	/**
	 * @var int|float
	 */
	protected int|float $referenceValue;
	
	/**
	 * @var string
	 */
	const AVAILABLE_OPERATORS = ['<', '<=', '>', '>=', '==', '==='];
	
	/**
	 * @param string $operator
	 * @return MCNumericFormFieldDependency
	 */
	public function operator(string $operator) : MCNumericFormFieldDependency {
		if (!\in_array($operator, self::AVAILABLE_OPERATORS)) {
			throw new \InvalidArgumentException("Unknown operator '" . $operator . "'.");
		}
		
		$this->operator = $operator;
		
		return $this;
	}
	
	public function getOperator() : string {
		return $this->operator;
	}
	
	/**
	 * @param integer|float $value
	 * @return MCNumericFormFieldDependency
	 */
	public function referenceValue(int|float $value) : MCNumericFormFieldDependency {
		$this->referenceValue = $value;
		
		return $this;
	}
	
	public function getReferenceValue() : int|float {
		return $this->referenceValue;
	}
	
	/**
	 * @inheritDoc
	 */
	public function checkDependency() {
		return $this->checkValue();
	}
	
	/**
	 * @return boolean
	 */
	protected function checkValue() : bool {
		$value = $this->getField()->getValue();
		
		if (!\is_numeric($value)) {
			return false;
		}
		
		switch ($this->getOperator()) {
			case '>':
				return $value > $this->getReferenceValue();
			
			case '>=':
				return $value >= $this->getReferenceValue();
			
			case '<':
				return $value < $this->getReferenceValue();
			
			case '<=':
				return $value <= $this->getReferenceValue();
			
			case '==':
				return $value == $this->getReferenceValue();
			
			case '===':
				return $value === $this->getReferenceValue();
			
			default:
				return false;
		}
	}
}
