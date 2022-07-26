<?php

namespace wcf\system\form\builder\field;

class MCTextFormField extends TextFormField implements INullableFormField
{
    use TNullableFormField;

    /**
     * @inheritDoc
     */
    public function getSaveValue()
    {
        if ($this->getValue() === null && !$this->isNullable()) {
            return '';
        }

        return parent::getSaveValue();
    }
}
