<?php

namespace wcf\system\form\builder\container\wysiwyg;

use BadMethodCallException;
use InvalidArgumentException;
use wcf\system\event\EventHandler;
use wcf\system\exception\SystemException;
use wcf\system\form\builder\button\wysiwyg\WysiwygPreviewFormButton;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\container\TabFormContainer;
use wcf\system\form\builder\field\wysiwyg\MCI18nWysiwygFormField;
use wcf\system\form\builder\field\wysiwyg\WysiwygAttachmentFormField;
use wcf\system\Regex;

/**
 * i18n implementation of WysiwygFormContainer
 *
 * @author      Florian Gail
 * @copyright   Florian Gail; 2018 - 2022; <https://www.mysterycode.de>
 */
class MCI18nWysiwygFormContainer extends WysiwygFormContainer
{
    /**
     * actual wysiwyg form field
     *
     * @var MCI18nWysiwygFormField
     */
    protected $wysiwygField;

    /**
     * `true` if this field supports i18n input and `false` otherwise
     *
     * @var bool
     */
    protected bool $i18n = false;

    /**
     * `true` if this field requires i18n input and `false` otherwise
     *
     * @var bool
     */
    protected bool $i18nRequired = false;

    /**
     * pattern for the language item used to save the i18n values
     *
     * @var null|string
     */
    protected string $languageItemPattern;

    /**
     * Returns the wysiwyg form field handling the actual text.
     *
     * @return MCI18nWysiwygFormField
     * @throws BadMethodCallException if the form field container has not been populated yet,
     *                                or form has not been built yet
     */
    public function getWysiwygField(): MCI18nWysiwygFormField
    {
        if ($this->wysiwygField === null) {
            throw new BadMethodCallException(
                'Wysiwyg form field can only be requested after the form has been built.'
            );
        }

        return $this->wysiwygField;
    }

    /**
     * @inheritDoc
     *
     * @throws SystemException
     */
    public function populate()
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        FormContainer::populate();

        $this->wysiwygField = MCI18nWysiwygFormField::create($this->wysiwygId)
            ->objectType($this->messageObjectType)
            ->minimumLength($this->getMinimumLength())
            ->maximumLength($this->getMaximumLength())
            ->required($this->isRequired())
            ->supportAttachments($this->attachmentData !== null)
            ->supportMentions($this->supportMentions)
            ->supportQuotes($this->supportQuotes)
            ->i18n($this->isI18n())
            ->i18nRequired($this->isI18nRequired())
            ->languageItemPattern($this->getLanguageItemPattern());

        if (!empty($this->quoteData)) {
            $this->wysiwygField->quoteData(
                $this->quoteData['objectType'],
                $this->quoteData['actionClass'],
                $this->quoteData['selectors']
            );
        }

        $this->smiliesContainer = WysiwygSmileyFormContainer::create($this->wysiwygId . 'SmiliesTab')
            ->wysiwygId($this->getWysiwygId())
            ->label('wcf.message.smilies')
            ->available($this->supportSmilies);

        $this->attachmentField = WysiwygAttachmentFormField::create($this->wysiwygId . 'Attachments')
            ->wysiwygId($this->getWysiwygId());

        $this->settingsContainer = FormContainer::create($this->wysiwygId . 'SettingsContainer')
            ->appendChildren($this->settingsNodes);

        $this->pollContainer = WysiwygPollFormContainer::create($this->wysiwygId . 'PollContainer')
            ->wysiwygId($this->getWysiwygId());

        if ($this->pollObjectType) {
            $this->pollContainer->objectType($this->pollObjectType);
        }

        $this->appendChildren([
            $this->wysiwygField,
            WysiwygTabMenuFormContainer::create($this->wysiwygId . 'Tabs')
                ->attribute('data-preselect', $this->getPreselect())
                ->attribute('data-wysiwyg-container-id', $this->wysiwygId)
                ->useAnchors(false)
                ->appendChildren([
                    $this->smiliesContainer,
                    TabFormContainer::create($this->wysiwygId . 'AttachmentsTab')
                        ->addClass('formAttachmentContent')
                        ->label('wcf.attachment.attachments')
                        ->appendChild(
                            FormContainer::create($this->wysiwygId . 'AttachmentsContainer')
                                ->appendChild($this->attachmentField)
                        ),
                    TabFormContainer::create($this->wysiwygId . 'SettingsTab')
                        ->label('wcf.message.settings')
                        ->appendChild($this->settingsContainer)
                        ->available(MODULE_SMILEY),
                    TabFormContainer::create($this->wysiwygId . 'PollTab')
                        ->label('wcf.poll.management')
                        ->appendChild($this->pollContainer),
                ]),
        ]);

        if ($this->attachmentData !== null) {
            $this->setAttachmentHandler();
        }

        if ($this->enablePreviewButton) {
            $this->getDocument()->addButton(
                WysiwygPreviewFormButton::create($this->getWysiwygId() . 'PreviewButton')
                    ->objectType($this->messageObjectType)
                    ->wysiwygId($this->getWysiwygId())
                    ->objectId($this->getObjectId())
            );
        }

        EventHandler::getInstance()->fireAction($this, 'populate');
    }

    /**
     * Returns the pattern for the language item used to save the i18n values.
     *
     * @return string language item pattern
     *
     * @throws BadMethodCallException if i18n is disabled for this field or no language item has been set
     */
    public function getLanguageItemPattern(): string
    {
        if (!$this->isI18n()) {
            throw new BadMethodCallException(
                "You can only get the language item pattern for fields with i18n enabled for field '{$this->getId()}'."
            );
        }

        if (empty($this->languageItemPattern)) {
            throw new BadMethodCallException("Language item pattern has not been set for field '{$this->getId()}'.");
        }

        return $this->languageItemPattern;
    }

    /**
     * Sets whether this field is supports i18n input and returns this field.
     *
     * @param bool $i18n determines if field supports i18n input
     * @return static this field
     */
    public function i18n(bool $i18n = true): self
    {
        $this->i18n = $i18n;

        return $this;
    }

    /**
     * Sets whether this field's value must be i18n input and returns this field.
     *
     * If this method sets that the field's value must be i18n input, it also must
     * ensure that i18n support is enabled.
     *
     * @param bool $i18nRequired determines if field value must be i18n input
     * @return static this field
     */
    public function i18nRequired(bool $i18nRequired = true): self
    {
        $this->i18nRequired = $i18nRequired;
        $this->i18n();

        return $this;
    }

    /**
     * Returns `true` if this field supports i18n input and returns `false` otherwise.
     * By default, fields do not support i18n input.
     *
     * @return bool
     */
    public function isI18n(): bool
    {
        return $this->i18n;
    }

    /**
     * Returns `true` if this field's value must be i18n input and returns `false` otherwise.
     * By default, fields do not support i18n input.
     *
     * @return bool
     */
    public function isI18nRequired(): bool
    {
        return $this->i18nRequired;
    }

    /**
     * Sets the pattern for the language item used to save the i18n values
     * and returns this field.
     *
     * @param string $pattern language item pattern
     * @return static this field
     *
     * @throws BadMethodCallException if i18n is disabled for this field
     * @throws InvalidArgumentException if the given pattern is invalid
     */
    public function languageItemPattern(string $pattern): self
    {
        if (!$this->isI18n()) {
            throw new BadMethodCallException(
                "The language item pattern can only be set for fields with i18n enabled for field '{$this->getId()}'."
            );
        }

        if (!Regex::compile($pattern)->isValid()) {
            throw new InvalidArgumentException("Given pattern is invalid for field '{$this->getId()}'.");
        }

        $this->languageItemPattern = $pattern;

        return $this;
    }
}
