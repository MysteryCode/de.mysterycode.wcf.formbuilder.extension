<?php

namespace wcf\system\form\builder\container;

use wcf\system\form\builder\data\processor\CustomFormDataProcessor;
use wcf\system\form\builder\IFormDocument;

class MCRecurringElementsFormContainer extends FormContainer
{
    /**
     * @inheritDoc
     */
    public function populate()
    {
        parent::populate();

        $this->getDocument()->getDataHandler()->addProcessor(
            new CustomFormDataProcessor($this->getId(), static function (IFormDocument $document, array $parameters) {
                return $parameters;
            })
        );

        return $this;
    }
}
