<?php

namespace wcf\system\form\builder\field;

use wcf\system\form\builder\IFormNode;
use wcf\util\StringUtil;

class MCBadgeColorFormField extends AbstractFormField implements IAutoFocusFormField, IImmutableFormField, INullableFormField, IPlaceholderFormField, IPatternFormField {
	use TAutoFocusFormField;
	use TImmutableFormField;
	use TNullableFormField;
	use TPlaceholderFormField;
	use TPatternFormField;
	
	/**
	 * @inheritDoc
	 */
	protected $templateName = '__mcBadgeColorFormField';
	
	/**
	 * @var boolean
	 */
	protected bool $supportCustom = false;
	
	/**
	 * @var string[]
	 */
	protected array $availableClasses = [];
	
	/**
	 * @var string|null
	 */
	protected ?string $referenceFieldId;
	
	/**
	 * @var string
	 */
	protected string $referenceText = '';
	
	/**
	 * @var IFormNode|null
	 */
	protected ?IFormNode $referencedNode = null;
	
	public function __construct() {
		$this->availableClasses([
			'yellow',
			'orange',
			'brown',
			'red',
			'pink',
			'purple',
			'blue',
			'green',
			'black',
			
			'none', /* not a real value */
		]);
		$this->referenceText('Label');
		$this->pattern('^-?[_a-zA-Z]+[_a-zA-Z0-9-]+$');
	}
	
	/**
	 * @inheritDoc
	 * @return MCBadgeColorFormField
	 */
	public function readValue() {
		if ($this->getDocument()->hasRequestData($this->getPrefixedId())) {
			$this->value = StringUtil::trim($this->getDocument()->getRequestData($this->getPrefixedId()));
			if ($this->supportsCustom() && $this->value == 'custom' && $this->getDocument()->hasRequestData($this->getPrefixedId() . '_className')) {
				$this->value = StringUtil::trim($this->getDocument()->getRequestData($this->getPrefixedId() . '_className'));
			}
		}
		
		return $this;
	}
	
	/**
	 * @inheritDoc
	 * @return MCBadgeColorFormField
	 */
	public function populate() {
		parent::populate();
		
		if ($this->referenceFieldId) {
			$this->referencedNode = $this->getDocument()->getNodeById($this->getReferenceFieldId());
			
			if ($this->referencedNode === null) {
				throw new \BadMethodCallException('Dependent node has not been set.');
			}
		}
		
		return $this;
	}
	
	/**
	 * @return string[]
	 */
	public function getAvailableClasses() : array {
		return $this->availableClasses;
	}
	
	/**
	 * @param    string[]    $availableClasses
	 * @return   self
	 */
	public function availableClasses(array $availableClasses) : MCBadgeColorFormField {
		$this->availableClasses = $availableClasses;
		
		return $this;
	}
	
	/**
	 * @return boolean
	 */
	public function supportsCustom() : bool {
		return $this->supportCustom;
	}
	
	/**
	 * @param    boolean    $supportCustom
	 * @return   self
	 */
	public function supportCustom(bool $supportCustom = true) : MCBadgeColorFormField {
		$this->supportCustom = $supportCustom;
		
		return $this;
	}
	
	/**
	 * @return string|null
	 */
	public function getReferenceFieldId() : ?string {
		return $this->referenceFieldId;
	}
	
	/**
	 * @param    string|null    $fieldId
	 * @return   self
	 */
	public function referenceFieldId(?string $fieldId) : MCBadgeColorFormField {
		$this->referenceFieldId = $fieldId;
		
		return $this;
	}
	
	public function getReferencedNode() : IFormNode {
		if ($this->referencedNode === null) {
			throw new \BadMethodCallException('Dependent node has not been set.');
		}
		
		return $this->referencedNode;
	}
	
	/**
	 * @return string
	 */
	public function getReferenceText() : string {
		return $this->referenceText;
	}
	
	/**
	 * @param    string    $text
	 * @return   self
	 */
	public function referenceText(string $text) : MCBadgeColorFormField {
		$this->referenceText = $text;
		
		return $this;
	}
	
	/**
	 * @return boolean
	 */
	public function hasCustomValue() : bool {
		if ($this->getValue() === null || empty($this->getValue())) {
			return false;
		}
		
		foreach ($this->getAvailableClasses() as $class) {
			if (\mb_strtolower($class) === \mb_strtolower($this->getValue())) {
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * @inheritDoc
	 */
	public function getSaveValue() {
		if ($this->getValue() === null && !$this->isNullable()) {
			return '';
		}
		
		return parent::getSaveValue();
	}
}
