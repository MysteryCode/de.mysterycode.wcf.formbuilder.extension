<?php

namespace wcf\system\form\builder\data\processor;

use wcf\data\IStorableObject;
use wcf\system\form\builder\IFormDocument;

class RecurringElementsFormDataProcessor extends AbstractFormDataProcessor {
	/**
	 * processor id primarily used for error messages
	 * @var	string
	 */
	protected $id;
	
	/**
	 * @var string
	 */
	protected $wrapperProperty;
	
	/**
	 * Initializes a new RecurringElementsFormDataProcessor object.
	 *
	 * @param	string		$id			processor id primarily used for error messages, does not have to be unique
	 * @param	string		$wrapperProperty
	 *
	 * @throws	\InvalidArgumentException		if either id or processor callable are invalid
	 */
	public function __construct($id, $wrapperProperty) {
		if (preg_match('~^[a-z][A-z0-9-]*$~', $id) !== 1) {
			throw new \InvalidArgumentException("Invalid id '{$id}' given.");
		}
		
		$this->id = $id;
		$this->wrapperProperty = $wrapperProperty;
	}
	
	/**
	 * @inheritDoc
	 */
	public function processFormData(IFormDocument $document, array $parameters) {
		wcfDebug($document, $parameters);
		
		return $parameters;
	}
	
	/**
	 * @inheritDoc
	 */
	public function processObjectData(IFormDocument $document, array $data, IStorableObject $object) {
		wcfDebug($document, $data, $object);
		
		return $data;
	}
}
