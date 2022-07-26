<?php

namespace wcf\system\form\builder\field;

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
