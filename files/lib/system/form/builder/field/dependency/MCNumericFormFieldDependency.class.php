<?php

namespace wcf\system\form\builder\field\dependency;

use InvalidArgumentException;

/**
 * Dependency handling numeric form fields using several operators.
 *
 * @author      Florian Gail
 * @copyright   Florian Gail; 2018 - 2022; <https://www.mysterycode.de>
 */
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
    protected int|float $referenceValue;

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
     * @param float|integer $value
     * @return MCNumericFormFieldDependency
     */
    public function referenceValue(float|int $value): self
    {
        $this->referenceValue = $value;

        return $this;
    }

    /**
     * @return int|float
     */
    public function getReferenceValue(): float|int
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

        return match ($this->getOperator()) {
            '>' => $value > $this->getReferenceValue(),
            '>=' => $value >= $this->getReferenceValue(),
            '<' => $value < $this->getReferenceValue(),
            '<=' => $value <= $this->getReferenceValue(),
            '==' => $value == $this->getReferenceValue(),
            '===' => $value === $this->getReferenceValue(),
            default => false,
        };
    }
}
