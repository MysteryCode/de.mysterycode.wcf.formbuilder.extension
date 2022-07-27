<?php

namespace wcf\system\form\builder\container;

use wcf\system\WCF;

/**
 * Container without own code; representing it's children only
 *
 * @author      Florian Gail
 * @copyright   Florian Gail; 2018 - 2022; <https://www.mysterycode.de>
 */
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
