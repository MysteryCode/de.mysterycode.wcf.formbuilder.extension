<?php

namespace wcf\system\form\builder\field;

/**
 * Implementation of a form field for single-line text values.
 *
 * @author    Matthias Schmidt
 * @copyright    2001-2019 WoltLab GmbH
 * @license    GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package    WoltLabSuite\Core\System\Form\Builder\Field
 * @since    5.2
 */
class MCTextDisplayFormField extends AbstractFormField
{
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
     * @var bool
     */
    protected bool $supportHtml = false;

    /**
     * @inheritDoc
     */
    public function readValue()
    {
        return $this;
    }

    /**
     * @return bool
     */
    public function hasSaveValue(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function supportsHTML(): bool
    {
        return $this->supportHtml;
    }

    /**
     * @param bool $supportHTML
     * @return $this
     */
    public function supportHTML(bool $supportHTML = true): self
    {
        $this->supportHtml = $supportHTML;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getValue()
    {
        return $this->getText();
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function text(string $text = ''): self
    {
        $this->text = $text;
        $this->value($text);

        return $this;
    }
}
