<?php

namespace wcf\system\form\builder\field;

/**
 * Implementation of UrlFormField, but nullable
 *
 * @author      Florian Gail
 * @copyright   Florian Gail; 2018 - 2022; <https://www.mysterycode.de>
 */
class MCNullableUrlFormField extends UrlFormField implements INullableFormField
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
