<?php

namespace wcf\system\form\builder\data\processor;

use wcf\data\IStorableObject;
use wcf\system\form\builder\container\MCDummyFormContainer;
use wcf\system\form\builder\container\IFormContainer;
use wcf\system\form\builder\field\IFormField;
use wcf\system\form\builder\IFormDocument;
use wcf\system\form\builder\IFormNode;

class MCPrefixedFormDataProcessor extends AbstractFormDataProcessor {
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
	 * @var string
	 */
	protected $targetProperty;
	
	/**
	 * Initializes a new PrefixedFormDataProcessor object.
	 *
	 * @param	string		$id			processor id primarily used for error messages, does not have to be unique
	 * @param	string		$wrapperProperty
	 * @param	string		$targetProperty
	 *
	 * @throws	\InvalidArgumentException		if either id or processor callable are invalid
	 */
	public function __construct($id, $wrapperProperty, $targetProperty = '') {
		if (preg_match('~^[a-z][A-z0-9-]*$~', $id) !== 1) {
			throw new \InvalidArgumentException("Invalid id '{$id}' given.");
		}
		
		$this->id = $id;
		$this->wrapperProperty = $wrapperProperty;
		$this->targetProperty = $targetProperty;
	}
	
	/**
	 * @inheritDoc
	 */
	public function processFormData(IFormDocument $document, array $parameters) {
		/** @var MCDummyFormContainer $container */
		$container = $document->getNodeById($this->id);
		if ($container === null || !$container->hasChildren()) {
			return $parameters;
		}
		
		$this->processNode($container, $parameters);
		
		return $parameters;
	}
	
	/**
	 * @param    IFormNode    $node
	 * @param    mixed[]      $parameters
	 */
	protected function processNode(IFormNode $node, &$parameters) {
		if ($node instanceof IFormContainer) {
			foreach ($node->children() as $childNode) {
				$this->processNode($childNode, $parameters);
			}
		}
		else if ($node instanceof IFormField) {
			$id = $node->getPrefixedId();
			
			if (preg_match('/^([^\[\]]+)((?:\[[^\]]+\]){1,})$/', $id, $matches)) {
				unset($parameters['data'][$id]);
				
				$property = '$parameters[\'data\'][\'' . $matches[1] . '\']' . str_replace(['[', ']'], ['[\'', '\']'], $matches[2]);
				$value = $node->getSaveValue();
				$test = $property . '=$value;';
				eval($test);
			}
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function processObjectData(IFormDocument $document, array $data, IStorableObject $object) {
		/** @var MCDummyFormContainer $container */
		$container = $document->getNodeById($this->id);
		
		$container = $document->getNodeById($this->id);
		if ($container === null || !$container->hasChildren()) {
			return $data;
		}
		
		$this->processNodeObject($container, $data);
		unset($data[$container->getPrefixedId()]);
		
		return $data;
	}
	
	/**
	 * @param    IFormNode    $node
	 * @param    mixed[]      $data
	 */
	protected function processNodeObject(IFormNode $node, &$data) {
		if ($node instanceof IFormContainer) {
			foreach ($node->children() as $childNode) {
				$this->processNodeObject($childNode, $data);
			}
		}
		else if ($node instanceof IFormField) {
			$id = $node->getPrefixedId();
			
			if (preg_match('/^([^\[\]]+)((?:\[[^\]]+\]){1,})$/', $id, $matches)) {
				$data[$id] = $this->getDataFromArray($data, $id);
			}
		}
	}
	
	/**
	 * @param    mixed[]    $data
	 * @param    string     $index
	 * @return mixed|null
	 */
	protected function getDataFromArray($data, $index) {
		preg_match('/^([^\[]+)(\[.*\])$/', $index, $matches);
		if (!empty($matches[2])) {
			unset($matches[0]);
			
			preg_match_all('/(?:\[([^\]]+)\])/', $matches[2], $parts);
			if (!empty($parts[1])) {
				$i = 0;
				$source = $data[$matches[1]];
				if (!is_array($source)) $source = unserialize($source);
				while (!empty($parts[1][$i]) && is_array($source)) {
					$source = $source[$parts[1][$i]] ?? null;
					$i++;
				}
				
				return $source;
			}
		}
		
		return null;
	}
}
