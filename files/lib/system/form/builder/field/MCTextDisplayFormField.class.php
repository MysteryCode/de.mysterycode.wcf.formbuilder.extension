<?php

namespace wcf\system\form\builder\field;

/**
 * Shows the field's label and a given text instead of an input
 *
 * @author      Florian Gail
 * @copyright   Florian Gail; 2018 - 2022; <https://www.mysterycode.de>
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
