<?php

namespace wcf\system\form\builder\field;

use wcf\system\WCF;

class MCEmptySingleSelectionFormField extends SingleSelectionFormField {
	/**
	 * @var boolean
	 */
	protected bool $allowEmptySelection = false;
	
	/**
	 * @var string
	 */
	protected string $emptyOptionLanguageItem = 'wcf.global.noSelection';
	
	/**
	 * @var mixed
	 */
	protected mixed $emptyOptionValue = 0;
	
	/**
	 * @param       boolean         $allowEmptySelection
	 * @param       string          $languageItem
	 * @return      static
	 */
	public function allowEmptySelection(bool $allowEmptySelection = true, string $languageItem = 'wcf.global.noSelection') : MCEmptySingleSelectionFormField {
		$this->allowEmptySelection = $allowEmptySelection;
		$this->emptyOptionLanguageItem = $languageItem;
		
		return $this;
	}
	
	/**
	 * @param       mixed   $value
	 * @return      static
	 */
	public function emptyOptionValue(mixed $value) : MCEmptySingleSelectionFormField {
		$this->emptyOptionValue = $value;
		
		return $this;
	}
	
	/**
	 * @return      boolean
	 */
	public function allowsEmptySelection() : bool {
		return $this->allowEmptySelection;
	}
	
	/**
	 * @return string
	 */
	public function getEmptyOptionLabel() : string {
		return WCF::getLanguage()->get($this->emptyOptionLanguageItem);
	}
	
	/**
	 * @return mixed
	 */
	public function getEmptyOptionValue() : mixed {
		return $this->emptyOptionValue;
	}
	
	/**
	 * @inheritDoc
	 */
	public function getOptions() {
		$options = parent::getOptions();
		
		if ($this->allowsEmptySelection()) {
			$options[$this->getEmptyOptionValue()] = $this->getEmptyOptionLabel();
		}
		
		return $options;
	}
	
	/**
	 * @inheriDoc
	 */
	public function validate() {
		if ($this->allowsEmptySelection() && $this->getValue() === $this->getEmptyOptionValue()) {
			AbstractFormField::validate();
		}
		else {
			parent::validate();
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function getNestedOptions() {
		$options = parent::getNestedOptions();
		
		if ($this->allowsEmptySelection()) {
			$options = \array_merge([[
				'depth' => 0,
				'isSelectable' => true,
				'label' => $this->getEmptyOptionLabel(),
				'value' => $this->getEmptyOptionValue(),
			]], $options);
		}
		
		return $options;
	}
	
	/**
	 * @inheritDoc
	 */
	public function getSaveValue() {
		if (
			$this->allowsEmptySelection()
			&& $this->getValue() === $this->getEmptyOptionValue()
			&& $this instanceof INullableFormField
			&& $this->isNullable()
		) {
			return null;
		}
		
		return parent::getSaveValue();
	}
}
