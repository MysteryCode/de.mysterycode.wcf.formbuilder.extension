<?php

namespace wcf\system\form\builder\data\processor;

use InvalidArgumentException;
use wcf\data\IStorableObject;
use wcf\system\form\builder\container\IFormContainer;
use wcf\system\form\builder\container\MCDummyFormContainer;
use wcf\system\form\builder\field\IFormField;
use wcf\system\form\builder\IFormDocument;
use wcf\system\form\builder\IFormNode;

/**
 * Container grouping it's children's data into an array
 *
 * @author      Florian Gail
 * @copyright   Florian Gail; 2018 - 2022; <https://www.mysterycode.de>
 */
class MCPrefixedFormDataProcessor extends AbstractFormDataProcessor
{
    /**
     * processor id primarily used for error messages
     *
     * @var string
     */
    protected string $id;

    /**
     * @var string
     */
    protected string $wrapperProperty;

    /**
     * @var string
     */
    protected string $targetProperty;

    /**
     * Initializes a new PrefixedFormDataProcessor object.
     *
     * @param string $id processor id primarily used for error messages, does not have to be unique
     * @param string $wrapperProperty
     * @param string $targetProperty
     *
     * @throws InvalidArgumentException if either id or processor callable are invalid
     */
    public function __construct(string $id, string $wrapperProperty, string $targetProperty = '')
    {
        if (\preg_match('~^[a-z][A-z\d-]*$~', $id) !== 1) {
            throw new InvalidArgumentException("Invalid id '{$id}' given.");
        }

        $this->id = $id;
        $this->wrapperProperty = $wrapperProperty;
        $this->targetProperty = $targetProperty;
    }

    /**
     * @inheritDoc
     */
    public function processFormData(IFormDocument $document, array $parameters): array
    {
        /** @var MCDummyFormContainer $container */
        $container = $document->getNodeById($this->id);
        if ($container === null || !$container->hasChildren()) {
            return $parameters;
        }

        $this->processNode($container, $parameters);

        return $parameters;
    }

    /**
     * @param IFormNode $node
     * @param array $parameters
     */
    protected function processNode(IFormNode $node, array &$parameters): void
    {
        if ($node instanceof IFormContainer) {
            foreach ($node->children() as $childNode) {
                $this->processNode($childNode, $parameters);
            }
        } elseif ($node instanceof IFormField) {
            $id = $node->getPrefixedId();

            if (\preg_match('/^([^\[\]]+)((?:\[[^]]+])+)$/', $id, $matches)) {
                unset($parameters['data'][$id]);

                $property = '$parameters[\'data\'][\'' . $matches[1] . '\']' . \str_replace(
                    ['[', ']'],
                    ['[\'', '\']'],
                    $matches[2]
                );

                $value = $node->getSaveValue();

                $test = $property . '=$value;';
                eval($test);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function processObjectData(IFormDocument $document, array $data, IStorableObject $object): array
    {
        /** @var MCDummyFormContainer $container */
        $container = $document->getNodeById($this->id);

        if ($container === null || !$container->hasChildren()) {
            return $data;
        }

        $this->processNodeObject($container, $data);
        unset($data[$container->getPrefixedId()]);

        return $data;
    }

    /**
     * @param IFormNode $node
     * @param array $data
     */
    protected function processNodeObject(IFormNode $node, array &$data): void
    {
        if ($node instanceof IFormContainer) {
            foreach ($node->children() as $childNode) {
                $this->processNodeObject($childNode, $data);
            }
        } elseif ($node instanceof IFormField) {
            $id = $node->getPrefixedId();

            if (\preg_match('/^([^\[\]]+)((?:\[[^]]+])+)$/', $id)) {
                $data[$id] = $this->getDataFromArray($data, $id);
            }
        }
    }

    /**
     * @param array $data
     * @param string $index
     * @return mixed|null
     */
    protected function getDataFromArray(array $data, string $index)
    {
        \preg_match('/^([^\[]+)(\[.*])$/', $index, $matches);
        if (!empty($matches[2])) {
            unset($matches[0]);

            \preg_match_all('/\[([^]]+)]/', $matches[2], $parts);
            if (!empty($parts[1])) {
                $i = 0;
                $source = $data[$matches[1]];
                if (!\is_array($source)) {
                    $source = \unserialize($source, ['allowed_classes' => false]);
                }
                while (!empty($parts[1][$i]) && \is_array($source)) {
                    $source = $source[$parts[1][$i]] ?? null;
                    $i++;
                }

                return $source;
            }
        }

        return null;
    }
}
