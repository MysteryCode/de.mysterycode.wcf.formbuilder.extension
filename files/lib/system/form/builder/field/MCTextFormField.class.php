<?php

namespace wcf\system\form\builder\field;

/**
 * Implementation of TextFormField, but nullable
 *
 * @author      Florian Gail
 * @copyright   Florian Gail; 2018 - 2022; <https://www.mysterycode.de>
 */
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
