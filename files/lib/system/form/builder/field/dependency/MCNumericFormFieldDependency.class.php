<?php

namespace wcf\system\form\builder\field\dependency;

use InvalidArgumentException;

class MCNumericFormFieldDependency extends AbstractFormFieldDependency
{
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
    protected $referenceValue;

    /**
     * @var string
     */
    public const AVAILABLE_OPERATORS = ['<', '<=', '>', '>=', '==', '==='];

    /**
     * @param string $operator
     * @return MCNumericFormFieldDependency
     */
    public function operator(string $operator): self
    {
        if (!\in_array($operator, self::AVAILABLE_OPERATORS, true)) {
            throw new InvalidArgumentException("Unknown operator '" . $operator . "'.");
        }

        $this->operator = $operator;

        return $this;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * @param integer|float $value
     * @return MCNumericFormFieldDependency
     */
    public function referenceValue($value): self
    {
        $this->referenceValue = $value;

        return $this;
    }

    /**
     * @return int|float
     */
    public function getReferenceValue()
    {
        return $this->referenceValue;
    }

    /**
     * @inheritDoc
     */
    public function checkDependency(): bool
    {
        return $this->checkValue();
    }

    /**
     * @return boolean
     */
    protected function checkValue(): bool
    {
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
                /** @noinspection TypeUnsafeComparisonInspection */
                return $value == $this->getReferenceValue();

            case '===':
                return $value === $this->getReferenceValue();

            default:
                return false;
        }
    }
}
