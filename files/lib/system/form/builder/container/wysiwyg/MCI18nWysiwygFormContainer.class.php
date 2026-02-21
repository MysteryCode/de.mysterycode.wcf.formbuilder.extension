<?php

namespace wcf\system\form\builder\container\wysiwyg;

use BadMethodCallException;
use InvalidArgumentException;
use wcf\data\IStorableObject;
use wcf\data\object\type\ObjectTypeCache;
use wcf\system\attachment\AttachmentHandler;
use wcf\system\event\EventHandler;
use wcf\system\exception\SystemException;
use wcf\system\form\builder\button\wysiwyg\WysiwygPreviewFormButton;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\container\IFormContainer;
use wcf\system\form\builder\field\TMaximumLengthFormField;
use wcf\system\form\builder\field\TMinimumLengthFormField;
use wcf\system\form\builder\field\wysiwyg\MCI18nWysiwygFormField;
use wcf\system\form\builder\field\wysiwyg\WysiwygAttachmentFormField;
use wcf\system\form\builder\IFormChildNode;
use wcf\system\form\builder\Psr15DialogForm;
use wcf\system\form\builder\TWysiwygFormNode;
use wcf\system\Regex;
use wcf\system\style\FontAwesomeIcon;

use const MODULE_SMILEY;

/**
 * i18n implementation of WysiwygFormContainer
 *
 * @author      Florian Gail
 * @copyright   Florian Gail; 2018 - 2022; <https://www.mysterycode.de>
 */
class MCI18nWysiwygFormContainer extends FormContainer
{
    use TMaximumLengthFormField;
    use TMinimumLengthFormField;
    use TWysiwygFormNode;

    protected WysiwygAttachmentFormField $attachmentField;

    /**
     * attachment-related data used to create an `AttachmentHandler` object for the attachment
     * form field
     *
     * @var array{objectType: string, parentObjectID: int}|null
     */
    protected ?array $attachmentData = null;

    protected bool $enablePreviewButton = true;

    protected string $messageObjectType = '';

    protected int $objectId = 0;

    protected string $preselect = 'true';

    protected ?string $pollObjectType = null;

    protected FormContainer $settingsContainer;

    protected WysiwygPollFormContainer $pollContainer;

    protected WysiwygQuoteFormContainer $quoteContainer;

    /**
     * quote-related data used to create the JavaScript quote manager
     *
     * @var array{actionClass: string, objectType: string, selectors: array<string, string>}|null
     */
    protected ?array $quoteData = null;

    protected bool $required = false;

    /**
     * setting nodes that will be added to the settings container when it is created
     * @var IFormChildNode[]
     */
    protected array $settingsNodes = [];

    protected WysiwygSmileyFormContainer $smiliesContainer;

    protected bool $supportMentions = false;

    protected bool $supportQuotes = false;

    protected bool $supportSmilies = true;

    // custom properties below

    protected MCI18nWysiwygFormField $wysiwygField;

    protected bool $i18n = false;

    protected bool $i18nRequired = false;

    /**
     * pattern for the language item used to save the i18n values
     */
    protected ?string $languageItemPattern = null;

    /**
     * @inheritDoc
     */
    public static function create($id): static
    {
        // the actual id is used for the form field containing the text
        return parent::create($id . 'Container')
            ->addClass('mcWysiwygContainer');
    }

    /**
     * Adds a node that will be appended to the settings form container when it is built and
     * returns this container.
     */
    public function addSettingsNode(IFormChildNode $settingsNode): static
    {
        if (isset($this->settingsContainer)) {
            // if settings container has already been created, add it directly
            $this->settingsContainer->appendChild($settingsNode);
        } else {
            $this->settingsNodes[] = $settingsNode;
        }

        return $this;
    }

    /**
     * Adds nodes that will be appended to the settings form container when it is built and
     * returns this container.
     *
     * @param IFormChildNode[] $settingsNodes
     */
    public function addSettingsNodes(array $settingsNodes): static
    {
        foreach ($settingsNodes as $settingsNode) {
            $this->addSettingsNode($settingsNode);
        }

        return $this;
    }

    /**
     * Sets the attachment-related data used to create an `AttachmentHandler` object for the
     * attachment form field. If no attachment data is set, attachments are not supported.
     *
     * By default, no attachment data is set.
     *
     * @throws  BadMethodCallException         if the attachment form field has already been initialized
     * @throws SystemException
     */
    public function attachmentData(?string $objectType = null, int $parentObjectID = 0): static
    {
        if (isset($this->attachmentField)) {
            throw new BadMethodCallException(
                "The attachment form field '{$this->getId()}' has already been initialized. 
                Use the attachment form field directly to manipulate attachment data."
            );
        }

        if ($objectType === null) {
            $this->attachmentData = null;
        } else {
            if (
                ObjectTypeCache::getInstance()->getObjectTypeByName(
                    'com.woltlab.wcf.attachment.objectType',
                    $objectType
                ) === null
            ) {
                throw new InvalidArgumentException(
                    "Unknown attachment object type '{$objectType}' for container '{$this->getId()}'."
                );
            }

            $this->attachmentData = [
                'objectType' => $objectType,
                'parentObjectID' => $parentObjectID,
            ];
        }

        return $this;
    }

    /**
     * Sets whether the preview button should be shown or not and returns this form container.
     *
     * By default, the preview button is shown.
     *
     * @throws      BadMethodCallException         if the form field container has already been populated yet
     */
    public function enablePreviewButton(bool $enablePreviewButton = true): static
    {
        if ($this->isPopulated) {
            throw new BadMethodCallException(
                "Enabling and disabling the preview button is only possible before the form "
                . "has been built for container '{$this->getId()}'."
            );
        }

        $this->enablePreviewButton = $enablePreviewButton;

        return $this;
    }

    /**
     * Returns the form field handling attachments.
     *
     * @throws BadMethodCallException if the form field container has not been populated yet /
     *                                form has not been built yet
     */
    public function getAttachmentField(): WysiwygAttachmentFormField
    {
        if (!isset($this->attachmentField)) {
            throw new BadMethodCallException(
                "Wysiwyg form field can only be requested after the form has been built for "
                . "container '{$this->getId()}'."
            );
        }

        return $this->attachmentField;
    }

    /**
     * Returns the id of the edited object or `0` if no object is edited.
     */
    public function getObjectId(): int
    {
        return $this->objectId ?? 0;
    }

    /**
     * Returns the value of the wysiwyg tab menu's `data-preselect` attribute used to determine
     * which tab is preselected.
     *
     * By default, `'true'` is returned which is used to pre-select the first tab.
     */
    public function getPreselect(): string
    {
        return $this->preselect;
    }

    /**
     * Returns the wysiwyg form container with all poll-related fields.
     *
     * @throws BadMethodCallException if the form field container has not been populated yet /
     *                                form has not been built yet
     */
    public function getPollContainer(): WysiwygPollFormContainer
    {
        if (!isset($this->pollContainer)) {
            throw new BadMethodCallException(
                "Wysiwyg form field can only be requested after the form has been built for "
                . "container '{$this->getId()}'."
            );
        }

        return $this->pollContainer;
    }

    /**
     * Returns the form container for all settings-related fields.
     *
     * @throws BadMethodCallException if the form field container has not been populated yet /
     *                                form has not been built yet
     */
    public function getSettingsContainer(): FormContainer
    {
        if (!isset($this->settingsContainer)) {
            throw new BadMethodCallException(
                "Wysiwyg form field can only be requested after the form has been built for "
                . "container '{$this->getId()}'."
            );
        }

        return $this->settingsContainer;
    }

    /**
     * Returns the form container for smiley categories.
     *
     * @throws BadMethodCallException if the form field container has not been populated yet /
     *                                form has not been built yet
     */
    public function getSmiliesContainer(): WysiwygSmileyFormContainer
    {
        if (!isset($this->smiliesContainer)) {
            throw new BadMethodCallException(
                "Smilies form field container can only be requested after the form has been built "
                . "for container '{$this->getId()}'."
            );
        }

        return $this->smiliesContainer;
    }

    /**
     * @inheritDoc
     */
    public function id($id): static
    {
        $this->wysiwygId(\substr($id, 0, -\strlen('Container')));

        return parent::id($id);
    }

    /**
     * Returns `true` if the wysiwyg field has to be filled out and returns `false` otherwise.
     * By default, the wysiwyg field does not have to be filled out.
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * Returns `true` if the preview button will be shown and returns `false` otherwise.
     *
     * By default, the preview button is shown.
     */
    public function isPreviewButtonEnabled(): bool
    {
        return $this->enablePreviewButton;
    }

    /**
     * @inheritDoc
     */
    public function markAsRequired(): bool
    {
        return $this->getWysiwygField()->isRequired();
    }

    /**
     * Sets the message object type used by the wysiwyg form field.
     *
     * @throws  InvalidArgumentException       if the given string is no message object type
     * @throws SystemException
     */
    public function messageObjectType(string $messageObjectType): static
    {
        if (
            ObjectTypeCache::getInstance()->getObjectTypeByName(
                'com.woltlab.wcf.message',
                $messageObjectType
            ) === null
        ) {
            throw new InvalidArgumentException(
                "Unknown message object type '{$messageObjectType}' for container '{$this->getId()}'."
            );
        }

        if (isset($this->wysiwygField)) {
            $this->wysiwygField->objectType($messageObjectType);
        } else {
            $this->messageObjectType = $messageObjectType;
        }

        return $this;
    }

    /**
     * @inheritDoc
     *
     * @throws SystemException
     */
    public function updatedObject(
        array $data,
        IStorableObject $object,
        $loadValues = true
    ): FormContainer | IFormContainer {
        $this->objectId = $object->{$object::getDatabaseTableIndexName()};

        $this->setAttachmentHandler();

        return parent::updatedObject($data, $object);
    }

    /**
     * Sets the poll object type used by the poll form field container.
     *
     * By default, no poll object type is set, thus the poll form field container is not available.
     *
     * @throws  InvalidArgumentException       if the given string is no poll object type
     * @throws SystemException
     */
    public function pollObjectType(string $pollObjectType): static
    {
        if (ObjectTypeCache::getInstance()->getObjectTypeByName('com.woltlab.wcf.poll', $pollObjectType) === null) {
            throw new InvalidArgumentException(
                "Unknown poll object type '{$pollObjectType}' for container '{$this->getId()}'."
            );
        }

        if (isset($this->pollContainer)) {
            $this->pollContainer->objectType($pollObjectType);
        } else {
            $this->pollObjectType = $pollObjectType;
        }

        return $this;
    }

    /**
     * Sets the value of the wysiwyg tab menu's `data-preselect` attribute used to determine which
     * tab is preselected.
     */
    public function preselect(string $preselect = 'true'): static
    {
        $this->preselect = $preselect;

        return $this;
    }

    /**
     * Sets the data required for advanced quote support for when quotable content is present
     * on the active page and returns this container.
     *
     * Calling this method automatically enables quote support for this container.
     *
     * @param string $objectType name of the relevant `com.woltlab.wcf.message.quote` object type
     * @param string $actionClass action class used for quote actions
     * @param string[] $selectors selectors for the quotable content
     *                            (required keys: `container`, `messageBody`, and
     *                            `messageContent`)
     * @return static
     *
     * @throws SystemException
     */
    public function quoteData(string $objectType, string $actionClass, array $selectors = []): static
    {
        if (isset($this->wysiwygField)) {
            $this->wysiwygField->quoteData($objectType, $actionClass, $selectors);
        } else {
            $this->supportQuotes();

            // the parameters are validated by `WysiwygFormField`
            $this->quoteData = [
                'actionClass' => $actionClass,
                'objectType' => $objectType,
                'selectors' => $selectors,
            ];
        }

        return $this;
    }

    /**
     * Sets whether it is required to fill out the wysiwyg field and returns this container.
     */
    public function required(bool $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    /**
     * Sets the attachment handler of the attachment form field.
     *
     * @throws SystemException
     */
    protected function setAttachmentHandler(): void
    {
        if ($this->attachmentData !== null) {
            $this->attachmentField->attachmentHandler(
                new AttachmentHandler(
                    $this->attachmentData['objectType'],
                    $this->getObjectId(),
                    '.',
                    $this->attachmentData['parentObjectID']
                )
            );
        }
    }

    /**
     * Sets if mentions are supported by the editor field and returns this form container.
     *
     * By default, mentions are not supported.
     */
    public function supportMentions(bool $supportMentions = true): static
    {
        if (isset($this->wysiwygField)) {
            $this->wysiwygField->supportMentions($supportMentions);
        } else {
            $this->supportMentions = $supportMentions;
        }

        return $this;
    }

    /**
     * Sets if quotes are supported by the editor field and returns this form container.
     *
     * By default, quotes are not supported.
     */
    public function supportQuotes(bool $supportQuotes = true): static
    {
        $this->supportQuotes = $supportQuotes;

        if (isset($this->quoteContainer)) {
            $this->quoteContainer->available($supportQuotes);
        }

        if (isset($this->wysiwygField)) {
            $this->wysiwygField->supportQuotes($supportQuotes);
        }

        return $this;
    }

    /**
     * Sets if smilies are supported for this form container and returns this form container.
     *
     * By default, smilies are supported.
     */
    public function supportSmilies(bool $supportSmilies = true): static
    {
        if (!MODULE_SMILEY) {
            $supportSmilies = false;
        }

        if (isset($this->smiliesContainer)) {
            $this->smiliesContainer->available($supportSmilies);
        } else {
            $this->supportSmilies = $supportSmilies;
        }

        return $this;
    }

    // custom methods below

    /**
     * Returns the wysiwyg form field handling the actual text.
     */
    public function getWysiwygField(): MCI18nWysiwygFormField
    {
        if (!isset($this->wysiwygField)) {
            throw new BadMethodCallException(
                "Wysiwyg form field can only be requested after the form has been built for "
                . "container '{$this->getId()}'."
            );
        }

        return $this->wysiwygField;
    }

    /**
     * @inheritDoc
     *
     * @throws SystemException
     */
    public function populate(): static
    {
        parent::populate();

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

        if ($this->pollObjectType !== null) {
            $this->pollContainer->objectType($this->pollObjectType);
        }

        $this->quoteContainer = WysiwygQuoteFormContainer::create($this->wysiwygId . 'QuoteContainer')
            ->wysiwygId($this->getWysiwygId())
            ->available($this->supportQuotes);

        $this->appendChildren([
            $this->wysiwygField,
            WysiwygTabMenuFormContainer::create($this->wysiwygId . 'Tabs')
                ->attribute('data-preselect', $this->getPreselect())
                ->attribute('data-wysiwyg-container-id', $this->wysiwygId)
                ->useAnchors(false)
                ->appendChildren([
                    $this->smiliesContainer,

                    WysiwygTabFormContainer::create($this->wysiwygId . 'AttachmentsTab')
                        ->addClass('formAttachmentContent')
                        ->label('wcf.attachment.attachments')
                        ->name('attachments')
                        ->icon(FontAwesomeIcon::fromValues('paperclip'))
                        ->wysiwygId($this->getWysiwygId())
                        ->appendChild(
                            FormContainer::create($this->wysiwygId . 'AttachmentsContainer')
                                ->appendChild($this->attachmentField)
                        ),

                    WysiwygTabFormContainer::create($this->wysiwygId . 'SettingsTab')
                        ->label('wcf.message.settings')
                        ->name('settings')
                        ->icon(FontAwesomeIcon::fromValues('gear'))
                        ->wysiwygId($this->getWysiwygId())
                        ->appendChild($this->settingsContainer),

                    WysiwygTabFormContainer::create($this->wysiwygId . 'PollTab')
                        ->label('wcf.poll.management')
                        ->name('poll')
                        ->icon(FontAwesomeIcon::fromValues('chart-bar'))
                        ->wysiwygId($this->getWysiwygId())
                        ->appendChild($this->pollContainer),

                    $this->quoteContainer,
                ]),
        ]);

        if ($this->attachmentData !== null) {
            $this->setAttachmentHandler();
        }
        $this->wysiwygField->supportAttachments($this->attachmentField->isAvailable());

        if ($this->enablePreviewButton && !($this->getDocument() instanceof Psr15DialogForm)) {
            $this->getDocument()->addButton(
                WysiwygPreviewFormButton::create($this->getWysiwygId() . 'PreviewButton')
                    ->objectType($this->messageObjectType)
                    ->wysiwygId($this->getWysiwygId())
                    ->objectId($this->getObjectId())
            );
        }

        EventHandler::getInstance()->fireAction($this, 'populate');

        return $this;
    }

    /**
     * Returns the pattern for the language item used to save the i18n values.
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
     */
    public function isI18n(): bool
    {
        return $this->i18n;
    }

    /**
     * Returns `true` if this field's value must be i18n input and returns `false` otherwise.
     * By default, fields do not support i18n input.
     */
    public function isI18nRequired(): bool
    {
        return $this->i18nRequired;
    }

    /**
     * Sets the pattern for the language item used to save the i18n values
     * and returns this field.
     *
     * @throws BadMethodCallException if i18n is disabled for this field
     * @throws InvalidArgumentException if the given pattern is invalid
     */
    public function languageItemPattern(string $pattern): static
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
