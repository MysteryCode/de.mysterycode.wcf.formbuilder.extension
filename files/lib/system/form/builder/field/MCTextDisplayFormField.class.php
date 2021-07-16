<?php
namespace wcf\system\form\builder\field;

/**
 * Implementation of a form field for single-line text values.
 * 
 * @author	Matthias Schmidt
 * @copyright	2001-2019 WoltLab GmbH
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package	WoltLabSuite\Core\System\Form\Builder\Field
 * @since	5.2
 */
class MCTextDisplayFormField extends AbstractFormField {
	/**
	 * @inheritDoc
	 */
	protected $templateName = '__mcTextDisplayFormField';
	
	/**
	 * Displayed text
	 *
	 * @var string
	 */
	protected $text = '';
	
	/**
	 * Indicator whether field text should use HTML.
	 *
	 * @var boolean
	 */
	protected $supportHtml = false;
	
	/**
	 * @inheritDoc
	 */
	public function readValue() {
		return $this;
	}
	
	public function hasSaveValue() {
		return false;
	}
	
	/**
	 * @inheritDoc
	 */
	public function supportsHTML() {
		return $this->supportHtml;
	}
	
	/**
	 * @inheritDoc
	 */
	public function supportHTML($supportHTML = true) {
		$this->supportHtml = $supportHTML;
		return $this;
	}
	
	/**
	 * @inheritDoc
	 */
	public function getValue() {
		return $this->getText();
	}
	
	/**
	 * @inheritDoc
	 */
	public function getText() {
		return $this->text;
	}
	
	/**
	 * @inheritDoc
	 */
	public function text($text = '') {
		$this->text = $text;
		$this->value($text);
		return $this;
	}
	
	
}
