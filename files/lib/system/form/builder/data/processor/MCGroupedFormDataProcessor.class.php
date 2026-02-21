<?php

namespace wcf\system\form\builder\data\processor;

use BadMethodCallException;
use InvalidArgumentException;
use wcf\data\IStorableObject;
use wcf\system\form\builder\container\IFormContainer;
use wcf\system\form\builder\field\IFormField;
use wcf\system\form\builder\IFormDocument;
use wcf\system\form\builder\IFormNode;

/**
 * data processor putting the given container's children data into an array
 *
 * @author      Florian Gail
 * @copyright   Florian Gail; 2018 - 2022; <https://www.mysterycode.de>
 */
class MCGroupedFormDataProcessor extends AbstractFormDataProcessor
{
    /**
     * processor id primarily used for error messages
     *
     * @var string
     */
    protected string $id;

    /**
     * Initializes a new PrefixedFormDataProcessor object.
     *
     * @param string    $nodeId    processor id primarily used for error messages, does not have to be unique
     *
     * @throws InvalidArgumentException if either id or processor callable are invalid
     */
    public function __construct(string $nodeId)
    {
        if (\preg_match('~^[a-z][A-z\d-]*$~', $nodeId) !== 1) {
            throw new InvalidArgumentException("Invalid id '{$nodeId}' given.");
        }

        $this->id = $nodeId;
    }

    /**
     * @inheritDoc
     */
    public function processFormData(IFormDocument $document, array $parameters): array
    {
        $container = $document->getNodeById($this->id);
        if (!($container instanceof IFormContainer) || $container->children() === []) {
            if (ENABLE_DEBUG_MODE) {
                throw new BadMethodCallException("Cannot find node with id '{$this->id}'.");
            }

            return $parameters;
        }

        $this->processNode($container, $parameters);

        return $parameters;
    }

    /**
     * @param IFormNode $node
     * @param array<string, mixed> $parameters
     */
    protected function processNode(IFormNode $node, array &$parameters): void
    {
        if ($node instanceof IFormContainer) {
            foreach ($node->children() as $childNode) {
                $this->processNode($childNode, $parameters[$node->getId()]);
            }

            return;
        }

        if (!($node instanceof IFormField)) {
            return;
        }

        unset($parameters['data'][$node->getId()]);

        $parameters[$node->getId()] = $node->getValue();
    }

    /**
     * @inheritDoc
     */
    public function processObjectData(IFormDocument $document, array $data, IStorableObject $object): array
    {
        $container = $document->getNodeById($this->id);
        if (!($container instanceof IFormContainer) || $container->children() === []) {
            if (ENABLE_DEBUG_MODE) {
                throw new BadMethodCallException("Cannot find node with id '{$this->id}'.");
            }

            return $data;
        }

        if (isset($data[$container->getId()])) {
            $this->processNodeObject($container, $data[$container->getId()], $data[$container->getId()]);
            unset($data[$container->getId()]);
        }

        return $data;
    }

    /**
     * @param    IFormNode    $node
     * @param    array<string, mixed> $data
     * @param    array<string, mixed> $tmpData
     */
    protected function processNodeObject(IFormNode $node, array &$data, array $tmpData): void
    {
        if (!isset($tmpData[$node->getId()])) {
            return;
        }

        if ($node instanceof IFormContainer) {
            foreach ($node->children() as $childNode) {
                $this->processNodeObject($childNode, $data, $tmpData[$childNode->getId()]);
            }

            return;
        }

        if (!($node instanceof IFormField)) {
            return;
        }

        $data[$node->getId()] = $tmpData[$node->getId()];
    }
}
