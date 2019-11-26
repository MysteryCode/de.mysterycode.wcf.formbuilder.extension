<?php

namespace wcf\system\form\builder\container;

use wcf\system\WCF;

class DummyFormContainer extends FormContainer {
	/**
	 * @inheritDoc
	 */
	public function __construct() {}
	
	/**
	 * @inheritDoc
	 */
	public function getHtml() {
		return WCF::getTPL()->fetch('__formContainerChildren', 'wcf', array_merge($this->getHtmlVariables(), [
			'container' => $this
		]), true);
	}
}
