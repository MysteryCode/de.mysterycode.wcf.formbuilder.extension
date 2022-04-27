<?php

namespace wcf\system\form\builder\field\dependency;

class MCItemListFormFieldDependency extends AbstractFormFieldDependency {
	/**
	 * @inheritDoc
	 */
	protected $templateName = '__mcItemListFormFieldDependency';
	
	/**
	 * @var integer
	 */
	const STATE_EMPTY = 1;
	
	/**
	 * @var integer
	 */
	const STATE_NON_EMPTY = 2;
	
	/**
	 * @var integer[]
	 */
	protected static array $supportedStates = [
		self::STATE_EMPTY,
		self::STATE_NON_EMPTY,
	];
	
	/**
	 * @var integer
	 */
	protected int $state;
	
	/**
	 * is `true` if the field value may not have any of the set values and otherwise `false`
	 * @var boolean
	 */
	protected $negate = false;
	
	/**
	 * possible values the field may have for the dependency to be met
	 * @var array|null
	 */
	protected ?array $values = null;
	
	/**
	 * @inheritDoc
	 */
	public function __construct() {
		$this->state(self::STATE_NON_EMPTY);
	}
	
	/**
	 * Set's the required state of the item list field.
	 * 
	 * @param integer $state
	 * @return static
	 */
	public function state(int $state) : MCItemListFormFieldDependency {
		if (!\in_array($state, self::$supportedStates)) {
			throw new \InvalidArgumentException("Value '$state' is not allowed for dependency '{$this->getId()}' on node '{$this->getDependentNode()->getId()}'.");
		}
		
		$this->state = $state;
		
		return $this;
	}
	
	/**
	 * Returns the required state.
	 * 
	 * @return integer|null
	 */
	public function getState() : ?int {
		if (!isset($this->state)) {
			throw new \BadMethodCallException("Required state has not been set for dependency '{$this->getId()}' on node '{$this->getDependentNode()->getId()}'.");
		}
		
		return $this->state ?? null;
	}
	
	/**
	 * Sets the possible values the field may have for the dependency to be met.
	 * 
	 * @param array $values
	 * @return static
	 */
	public function values(array $values) : MCItemListFormFieldDependency {
		$this->values = $values;
		
		return $this;
	}
	
	/**
	 * Returns the possible values the field may have for the dependency to be met.
	 * 
	 * @return array|null
	 */
	public function getValues() : ?array {
		return $this->values;
	}
	
	/**
	 * Returns `true` if the field value may not have any of the set values and
	 * otherwise `false`.
	 *
	 * @return boolean
	 */
	public function isNegated() : bool {
		return $this->negate;
	}
	
	/**
	 * Sets if the field value may not have any of the set values.
	 *
	 * @param boolean $negate
	 * @return static $this this dependency
	 */
	public function negate(bool $negate = true) : MCItemListFormFieldDependency {
		if ($this->getValues() === null) {
			throw new \BadMethodCallException('Cannot negate values before they are set.');
		}
		
		$this->negate = $negate;
		
		return $this;
	}
	
	/**
	 * @inheritDoc
	 */
	public function checkDependency() {
		$check = false;
		
		if ($this->getState() === self::STATE_EMPTY) {
			$check = empty($this->getField()->getValue());
		}
		else if ($this->getState() === self::STATE_NON_EMPTY) {
			$check = !empty($this->getField()->getValue());
			
			$values = $this->getValues();
			if ($values !== null && $check) {
				if (\is_array($this->getField()->getValue())) {
					// do not use `array_diff` because we use weak comparison
					foreach ($this->getValues() as $possibleValue) {
						foreach ($this->getField()->getValue() as $actualValue) {
							if ($possibleValue == $actualValue) {
								$check = true;
								break;
							}
						}
					}
				}
				else {
					$check = \in_array($this->getField()->getValue(), $this->getValues());
				}
				
				if ($this->isNegated()) {
					return !$check;
				}
			}
		}
		
		return $check;
	}
}
