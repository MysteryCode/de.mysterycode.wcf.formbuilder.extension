<?php

namespace wcf\system\form\builder\container;

use wcf\system\WCF;

class MCDummyFormContainer extends FormContainer
{
    /**
     * @inheritDoc
     */
    public function getHtml(): string
    {
        return WCF::getTPL()->fetch(
            '__formContainerChildren',
            'wcf',
            \array_merge($this->getHtmlVariables(), [
                'container' => $this,
            ]),
            true
        );
    }
}
