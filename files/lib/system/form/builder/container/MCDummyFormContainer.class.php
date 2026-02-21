<?php

namespace wcf\system\form\builder\container;

use wcf\system\WCF;

/**
 * Container without own code; representing its children only
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
        return WCF::getTPL()->render(
            'wcf',
            'shared_formContainerChildren',
            \array_merge($this->getHtmlVariables(), [
                'container' => $this,
            ])
        );
    }
}
