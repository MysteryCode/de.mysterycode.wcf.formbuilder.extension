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
	protected string $text = '';
	
	/**
	 * Indicator whether field text should use HTML.
	 *
	 * @var boolean
	 */
	protected bool $supportHtml = false;
	
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
	public function supportsHTML() : bool {
		return $this->supportHtml;
	}
	
	/**
	 * @inheritDoc
	 */
	public function supportHTML(bool $supportHTML = true) : MCTextDisplayFormField {
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
	public function getText() : string {
		return $this->text;
	}
	
	/**
	 * @inheritDoc
	 */
	public function text(string $text = '') : MCTextDisplayFormField {
		$this->text = $text;
		$this->value($text);
		
		return $this;
	}
	
	
}
