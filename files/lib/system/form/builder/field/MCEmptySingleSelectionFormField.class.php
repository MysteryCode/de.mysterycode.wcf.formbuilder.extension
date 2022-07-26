<?php

namespace wcf\system\form\builder\field;

use wcf\system\WCF;

class MCEmptySingleSelectionFormField extends SingleSelectionFormField
{
    /**
     * @var bool
     */
    protected bool $allowEmptySelection = false;

    /**
     * @var string
     */
    protected string $emptyOptionLanguageItem = 'wcf.global.noSelection';

    /**
     * @var mixed
     */
    protected $emptyOptionValue = 0;

    /**
     * @param bool $allowEmptySelection
     * @param string $languageItem
     * @return static
     */
    public function allowEmptySelection(
        bool $allowEmptySelection = true,
        string $languageItem = 'wcf.global.noSelection'
    ): self {
        $this->allowEmptySelection = $allowEmptySelection;
        $this->emptyOptionLanguageItem = $languageItem;

        return $this;
    }

    /**
     * @param mixed $value
     * @return static
     */
    public function emptyOptionValue($value): self
    {
        $this->emptyOptionValue = $value;

        return $this;
    }

    /**
     * @return bool
     */
    public function allowsEmptySelection(): bool
    {
        return $this->allowEmptySelection;
    }

    /**
     * @return string
     */
    public function getEmptyOptionLabel(): string
    {
        return WCF::getLanguage()->get($this->emptyOptionLanguageItem);
    }

    /**
     * @return mixed
     */
    public function getEmptyOptionValue()
    {
        return $this->emptyOptionValue;
    }

    /**
     * @inheritDoc
     */
    public function getOptions(): array
    {
        $options = parent::getOptions();

        if ($this->allowsEmptySelection()) {
            $options[$this->getEmptyOptionValue()] = $this->getEmptyOptionLabel();
        }

        return $options;
    }

    /**
     * @inheriDoc
     */
    public function validate(): void
    {
        if ($this->allowsEmptySelection() && $this->getValue() === $this->getEmptyOptionValue()) {
            AbstractFormField::validate();
        } else {
            parent::validate();
        }
    }

    /**
     * @inheritDoc
     */
    public function getNestedOptions(): array
    {
        $options = parent::getNestedOptions();

        if ($this->allowsEmptySelection()) {
            $options = \array_merge([
                [
                    'depth' => 0,
                    'isSelectable' => true,
                    'label' => $this->getEmptyOptionLabel(),
                    'value' => $this->getEmptyOptionValue(),
                ],
            ], $options);
        }

        return $options;
    }

    /**
     * @inheritDoc
     */
    public function getSaveValue()
    {
        if (
            $this->allowsEmptySelection()
            && $this->isNullable()
            && $this->getValue() === $this->getEmptyOptionValue()
        ) {
            return null;
        }

        return parent::getSaveValue();
    }
}
