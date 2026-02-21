<?php

namespace wcf\system\form\builder\field\dependency;

use BadMethodCallException;
use InvalidArgumentException;

/**
 * Dependency handling UiItemList-based fields as requirement.
 *
 * @author      Florian Gail
 * @copyright   Florian Gail; 2018 - 2022; <https://www.mysterycode.de>
 */
class MCItemListFormFieldDependency extends AbstractFormFieldDependency
{
    /**
     * @inheritDoc
     */
    protected $templateName = 'shared_mcItemListFormFieldDependency';

    /**
     * @var integer
     */
    public const STATE_EMPTY = 1;

    /**
     * @var integer
     */
    public const STATE_NON_EMPTY = 2;

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
     *
     * @var boolean
     */
    protected bool $negate = false;

    /**
     * possible values the field may have for the dependency to be met
     *
     * @var list<int|string>|null
     */
    protected ?array $values = null;

    public function __construct()
    {
        $this->state(self::STATE_NON_EMPTY);
    }

    /**
     * Set's the required state of the item list field.
     *
     * @param integer $state
     * @return static
     */
    public function state(int $state): self
    {
        if (!\in_array($state, self::$supportedStates, true)) {
            throw new InvalidArgumentException(
                "Value '{$state}' is not allowed for dependency '{$this->getId()}'"
                . "on node '{$this->getDependentNode()->getId()}'."
            );
        }

        $this->state = $state;

        return $this;
    }

    /**
     * Returns the required state.
     *
     * @return integer
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * Sets the possible values the field may have for the dependency to be met.
     *
     * @param list<int|string> $values
     * @return static
     */
    public function values(array $values): self
    {
        $this->values = $values;

        return $this;
    }

    /**
     * Returns the possible values the field may have for the dependency to be met.
     *
     * @return list<int|string>|null
     */
    public function getValues(): ?array
    {
        return $this->values;
    }

    /**
     * Returns `true` if the field value may not have any of the set values and
     * otherwise `false`.
     *
     * @return boolean
     */
    public function isNegated(): bool
    {
        return $this->negate;
    }

    /**
     * Sets if the field value may not have any of the set values.
     *
     * @param boolean $negate
     * @return static $this this dependency
     */
    public function negate(bool $negate = true): self
    {
        if ($this->getValues() === null) {
            throw new BadMethodCallException('Cannot negate values before they are set.');
        }

        $this->negate = $negate;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function checkDependency(): bool
    {
        $field = $this->getField();
        $check = false;

        if ($this->getState() === self::STATE_EMPTY) {
            $check = empty($field->getValue());
        } elseif ($this->getState() === self::STATE_NON_EMPTY) {
            $check = !empty($field->getValue());
            $values = $this->getValues();

            if ($values !== null && $check) {
                if (\is_array($field->getValue())) {
                    // do not use `array_diff` because we use weak comparison
                    foreach ($this->getValues() as $possibleValue) {
                        foreach ($field->getValue() as $actualValue) {
                            /** @noinspection TypeUnsafeComparisonInspection */
                            if ($possibleValue == $actualValue) {
                                $check = true;
                                break;
                            }
                        }
                    }
                } else {
                    $check = \in_array($field->getValue(), $this->getValues(), false);
                }

                if ($this->isNegated()) {
                    return !$check;
                }
            }
        }

        return $check;
    }
}
