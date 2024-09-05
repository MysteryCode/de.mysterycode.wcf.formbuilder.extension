<?php

namespace wcf\system\form\builder\field\wysiwyg;

use BadMethodCallException;
use InvalidArgumentException;
use wcf\data\IMessageQuoteAction;
use wcf\data\IStorableObject;
use wcf\data\language\item\LanguageItemList;
use wcf\data\object\type\ObjectTypeCache;
use wcf\system\bbcode\BBCodeHandler;
use wcf\system\exception\SystemException;
use wcf\system\form\builder\data\processor\CustomFormDataProcessor;
use wcf\system\form\builder\field\AbstractFormField;
use wcf\system\form\builder\field\IAttributeFormField;
use wcf\system\form\builder\field\IMaximumLengthFormField;
use wcf\system\form\builder\field\IMinimumLengthFormField;
use wcf\system\form\builder\field\TInputAttributeFormField;
use wcf\system\form\builder\field\TMaximumLengthFormField;
use wcf\system\form\builder\field\TMinimumLengthFormField;
use wcf\system\form\builder\field\validation\FormFieldValidationError;
use wcf\system\form\builder\IFormDocument;
use wcf\system\form\builder\IFormNode;
use wcf\system\form\builder\IObjectTypeFormNode;
use wcf\system\form\builder\TObjectTypeFormNode;
use wcf\system\html\input\HtmlInputProcessor;
use wcf\system\language\I18nHandler;
use wcf\system\language\LanguageFactory;
use wcf\system\message\censorship\Censorship;
use wcf\system\message\quote\MessageQuoteManager;
use wcf\system\Regex;
use wcf\system\WCF;
use wcf\util\ArrayUtil;
use wcf\util\StringUtil;

/**
 * i18n implementation of WysiwygFormField
 *
 * @author      Florian Gail
 * @copyright   Florian Gail; 2018 - 2022; <https://www.mysterycode.de>
 */
class MCI18nWysiwygFormField extends AbstractFormField implements
    IAttributeFormField,
    IMaximumLengthFormField,
    IMinimumLengthFormField,
    IObjectTypeFormNode
{
    use TInputAttributeFormField {
        getReservedFieldAttributes as private inputGetReservedFieldAttributes;
    }
    use TMaximumLengthFormField;
    use TMinimumLengthFormField;
    use TObjectTypeFormNode;

    protected string $autosaveId = '';

    protected HtmlInputProcessor $htmlInputProcessor;

    /**
     * last time the field has been edited; if `0`, the last edit time is unknown
     */
    protected int $lastEditTime = 0;

    /**
     * quote-related data used to create the JavaScript quote manager
     */
    protected ?array $quoteData;

    protected bool $supportAttachments = false;

    protected bool $supportMentions = false;

    protected bool $supportQuotes = false;

    /**
     * @inheritDoc
     */
    protected $javaScriptDataHandlerModule = 'WoltLabSuite/Core/Form/Builder/Field/Ckeditor';

    // custom properties below

    /**
     * @inheritDoc
     */
    protected $templateName = '__mcWysiwygFormField';

    /**
     * input processor containing the wysiwyg text
     * @var HtmlInputProcessor[]
     */
    protected array $htmlInputProcessors;

    protected bool $i18n = false;

    protected bool $i18nRequired = false;

    /**
     * pattern for the language item used to save the i18n values
     */
    protected ?string $languageItemPattern;

    /**
     * Sets the identifier used to autosave the field value and returns this field.
     *
     * @param string $autosaveId identifier used to autosave field value
     * @return  WysiwygFormField|MCI18nWysiwygFormField        this field
     */
    public function autosaveId(string $autosaveId): WysiwygFormField|self
    {
        $this->autosaveId = $autosaveId;

        return $this;
    }

    /**
     * @inheritDoc
     *
     * @throws SystemException
     */
    public function cleanup(): static
    {
        MessageQuoteManager::getInstance()->saved();

        return $this;
    }

    /**
     * Returns the identifier used to autosave the field value. If autosave is disabled,
     * an empty string is returned.
     *
     * @return  string
     */
    public function getAutosaveId(): string
    {
        return $this->autosaveId;
    }

    /**
     * @inheritDoc
     *
     * @throws SystemException
     */
    public function getFieldHtml(): string
    {
        if ($this->supportsQuotes()) {
            MessageQuoteManager::getInstance()->assignVariables();
        }

        /** @noinspection PhpUndefinedFieldInspection */
        $disallowedBBCodesPermission = $this->getObjectType()->disallowedBBCodesPermission;

        if ($disallowedBBCodesPermission === null) {
            $disallowedBBCodesPermission = 'user.message.disallowedBBCodes';
        }

        BBCodeHandler::getInstance()->setDisallowedBBCodes(\explode(
            ',',
            WCF::getSession()->getPermission($disallowedBBCodesPermission)
        ));

        return parent::getFieldHtml();
    }

    /**
     * @inheritDoc
     */
    public function getObjectTypeDefinition(): string
    {
        return 'com.woltlab.wcf.message';
    }

    /**
     * Returns the last time the field has been edited. If no last edit time has
     * been set, `0` is returned.
     *
     * @return  int
     */
    public function getLastEditTime(): int
    {
        return $this->lastEditTime;
    }

    /**
     * Returns all quote data or specific quote data if an argument is given.
     *
     * @param string|null $index quote data index
     * @return  string[]|string|null
     *
     * @throws  BadMethodCallException     if quotes are not supported for this field
     * @throws  InvalidArgumentException   if unknown quote data is requested
     * @throws SystemException
     */
    public function getQuoteData(?string $index = null): array|string|null
    {
        if (!$this->supportQuotes()) {
            throw new BadMethodCallException("Quotes are not supported for field '{$this->getId()}'.");
        }

        if ($index === null) {
            return $this->quoteData;
        }

        if (!isset($this->quoteData[$index])) {
            throw new InvalidArgumentException("Unknown quote data '{$index}' for field '{$this->getId()}'.");
        }

        return $this->quoteData[$index];
    }

    /**
     * @inheritDoc
     *
     * @throws SystemException
     */
    public function getSaveValue()
    {
        if ($this->isI18n() && $this->hasI18nValues()) {
            return '';
        }

        return $this->htmlInputProcessor->getHtml();
    }

    /**
     * Sets the last time this field has been edited and returns this field.
     *
     * @param int $lastEditTime last time field has been edited
     * @return  WysiwygFormField|MCI18nWysiwygFormField    this field
     */
    public function lastEditTime(int $lastEditTime): WysiwygFormField|self
    {
        $this->lastEditTime = $lastEditTime;

        return $this;
    }

    /**
     * Sets the data required for advanced quote support for when quotable content is present
     * on the active page and returns this field.
     *
     * Calling this method automatically enables quote support for this field.
     *
     * @param string $objectType name of the relevant `com.woltlab.wcf.message.quote` object type
     * @param string $actionClass action class implementing `wcf\data\IMessageQuoteAction`
     * @param string[] $selectors selectors for the quotable content (required keys: `container`, `messageBody`, and `messageContent`)
     * @return  static
     *
     * @throws SystemException
     */
    public function quoteData(string $objectType, string $actionClass, array $selectors = []): self
    {
        if (
            ObjectTypeCache::getInstance()->getObjectTypeByName(
                'com.woltlab.wcf.message.quote',
                $objectType
            ) === null
        ) {
            throw new InvalidArgumentException(
                "Unknown message quote object type '{$objectType}' for field '{$this->getId()}'."
            );
        }

        if (!\class_exists($actionClass)) {
            throw new InvalidArgumentException("Unknown class '{$actionClass}' for field '{$this->getId()}'.");
        }
        if (!\is_subclass_of($actionClass, IMessageQuoteAction::class)) {
            throw new InvalidArgumentException(
                "'{$actionClass}' does not implement '" . IMessageQuoteAction::class . "' for field '{$this->getId()}'."
            );
        }

        if (!empty($selectors)) {
            foreach (['container', 'messageBody', 'messageContent'] as $selector) {
                if (!isset($selectors[$selector])) {
                    throw new InvalidArgumentException("Missing selector '{$selector}' for field '{$this->getId()}'.");
                }
            }
        }

        $this->supportQuotes();

        $this->quoteData = [
            'actionClass' => $actionClass,
            'objectType' => $objectType,
            'selectors' => $selectors,
        ];

        return $this;
    }

    /**
     * Sets if the form field supports attachments and returns this field.
     *
     * @param bool $supportAttachments
     * @return  WysiwygFormField|MCI18nWysiwygFormField        this field
     */
    public function supportAttachments(bool $supportAttachments = true): WysiwygFormField|self
    {
        $this->supportAttachments = $supportAttachments;

        return $this;
    }

    /**
     * Sets if the form field supports mentions and returns this field.
     *
     * @param bool $supportMentions
     * @return  WysiwygFormField|MCI18nWysiwygFormField        this field
     */
    public function supportMentions(bool $supportMentions = true): WysiwygFormField|self
    {
        $this->supportMentions = $supportMentions;

        return $this;
    }

    /**
     * Sets if the form field supports quotes and returns this field.
     *
     * @param bool $supportQuotes
     * @return  WysiwygFormField|MCI18nWysiwygFormField        this field
     *
     * @throws SystemException
     */
    public function supportQuotes(bool $supportQuotes = true): WysiwygFormField|self
    {
        $this->supportQuotes = $supportQuotes;

        if (!$this->supportsQuotes()) {
            // unset previously set quote data
            $this->quoteData = null;
        } else {
            MessageQuoteManager::getInstance()->readParameters();
        }

        return $this;
    }

    /**
     * Returns `true` if the form field supports attachments and returns `false` otherwise.
     *
     * Important: If this method returns `true`, it does not necessarily mean that attachment
     * support will also work as that is the task of `WysiwygAttachmentFormField`. This method
     * is primarily relevant to inform the JavaScript API that the field supports attachments
     * so that the relevant editor plugin is loaded.
     *
     * By default, attachments are not supported.
     *
     * @return  bool
     */
    public function supportsAttachments(): bool
    {
        return $this->supportAttachments;
    }

    /**
     * Returns `true` if the form field supports mentions and returns `false` otherwise.
     *
     * By default, mentions are not supported.
     *
     * @return  bool
     */
    public function supportsMentions(): bool
    {
        return $this->supportMentions;
    }

    /**
     * Returns `true` if the form field supports quotes and returns `false` otherwise.
     *
     * By default, quotes are not supported.
     *
     * @return  bool
     */
    public function supportsQuotes(): bool
    {
        return $this->supportQuotes;
    }

    /**
     * @inheritDoc
     * @since       5.4
     */
    protected static function getReservedFieldAttributes(): array
    {
        return \array_merge(
            static::inputGetReservedFieldAttributes(),
            [
                'data-autosave',
                'data-autosave-last-edit-time',
                'data-disable-attachments',
                'data-support-mention',
            ]
        );
    }

    // custom methods below

    /**
     * Returns additional template variables used to generate the html representation
     * of this node.
     *
     * @return array additional template variables
     *
     * @throws SystemException
     */
    public function getHtmlVariables(): array
    {
        if ($this->isI18n()) {
            I18nHandler::getInstance()->assignVariables();

            return [
                'elementIdentifier' => $this->getPrefixedId(),
                'forceSelection' => $this->isI18nRequired(),
            ];
        }

        return [];
    }

    /**
     * Returns the pattern for the language item used to save the i18n values.
     *
     * @return null|string language item pattern
     *
     * @throws BadMethodCallException if i18n is disabled for this field or no language item has been set
     */
    public function getLanguageItemPattern(): ?string
    {
        if (!$this->isI18n()) {
            throw new BadMethodCallException(
                'You can only get the language item pattern for fields with i18n enabled.'
            );
        }

        if ($this->languageItemPattern === null) {
            throw new BadMethodCallException('Language item pattern has not been set.');
        }

        return $this->languageItemPattern;
    }

    /**
     * Returns `true` if the current field value is an i18n value and returns `false`
     * otherwise or if no value has been set.
     *
     * @return bool
     *
     * @throws SystemException
     */
    public function hasI18nValues(): bool
    {
        return I18nHandler::getInstance()->hasI18nValues($this->getPrefixedId());
    }

    /**
     * Returns `true` if the current field value is a plain value and returns `false`
     * otherwise or if no value has been set.
     *
     * @return bool
     *
     * @throws SystemException
     */
    public function hasPlainValue(): bool
    {
        return I18nHandler::getInstance()->isPlainValue($this->getPrefixedId());
    }

    /**
     * Returns the value of this field or `null` if no value has been set.
     *
     * @return mixed
     *
     * @throws SystemException
     */
    public function getValue(): mixed
    {
        if ($this->isI18n()) {
            if ($this->hasI18nValues()) {
                $values = I18nHandler::getInstance()->getValues($this->getPrefixedId());

                // handle legacy values from the past when multilingual values
                // were available
                if (\count(LanguageFactory::getInstance()->getLanguages()) === 1) {
                    return $values[WCF::getLanguage()->languageID] ?? \current($values);
                }

                return $values;
            }

            if ($this->hasPlainValue()) {
                return I18nHandler::getInstance()->getValue($this->getPrefixedId());
            }

            return '';
        }

        return parent::getValue();
    }

    /**
     * Sets whether this field is supports i18n input and returns this field.
     *
     * @param bool $i18n determines if field supports i18n input
     * @return static this field
     */
    public function i18n(bool $i18n = true): self
    {
        //if ($this->javaScriptDataHandlerModule) {
        //    if ($this->isI18n() && !$i18n) {
        //        $this->javaScriptDataHandlerModule = $this->nonI18nJavaScriptDataHandlerModule;
        //    }
        //    else if (!$this->isI18n() && $i18n) {
        //        $this->nonI18nJavaScriptDataHandlerModule = $this->javaScriptDataHandlerModule;
        //        $this->javaScriptDataHandlerModule = 'WoltLabSuite/Core/Form/Builder/Field/ValueI18n';
        //    }
        //}

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
            throw new BadMethodCallException('The language item pattern can only be set for fields with i18n enabled.');
        }

        if (!Regex::compile($pattern)->isValid()) {
            throw new InvalidArgumentException('Given pattern is invalid.');
        }

        $this->languageItemPattern = $pattern;

        return $this;
    }

    /**
     * @inheritDoc
     *
     * @throws SystemException
     */
    public function updatedObject(array $data, IStorableObject $object, $loadValues = true)
    {
        if ($loadValues && isset($data[$this->getObjectProperty()])) {
            $value = $data[$this->getObjectProperty()];

            if ($this->isI18n()) {
                // do not use `I18nHandler::setOptions()` because then `I18nHandler` only
                // reads the values when assigning the template variables and the values
                // are not available in this class via `getValue()`
                $this->setStringValue($value);
            } else {
                $this->value = $value;
            }
        }

        return $this;
    }

    /**
     * Reads the value of this field from request data and return this field.
     *
     * @throws SystemException
     */
    public function readValue(): static
    {
        if ($this->isI18n()) {
            I18nHandler::getInstance()->readValues($this->getDocument()->getRequestData());
        } elseif ($this->getDocument()->hasRequestData($this->getPrefixedId())) {
            $value = $this->getDocument()->getRequestData($this->getPrefixedId());

            if (\is_string($value)) {
                $this->value = StringUtil::trim($value);
            }
        }

        if ($this->supportsQuotes()) {
            MessageQuoteManager::getInstance()->readFormParameters();
        }

        return $this;
    }

    /**
     * Sets the value of this form field based on the given value.
     * If the value is a language item matching the language item pattern,
     * the relevant language items are loaded and their values are used as
     * field values.
     *
     * @param string $value set value
     *
     * @throws SystemException
     */
    protected function setStringValue(string $value): void
    {
        if (Regex::compile('^' . $this->getLanguageItemPattern() . '$')->match($value)) {
            $languageItemList = new LanguageItemList();
            $languageItemList->getConditionBuilder()->add('languageItem = ?', [$value]);
            $languageItemList->readObjects();
            $values = [];

            foreach ($languageItemList as $languageItem) {
                $values[$languageItem->languageID] = $languageItem->languageItemValue;
            }

            I18nHandler::getInstance()->setValues($this->getPrefixedId(), $values);
        } else {
            I18nHandler::getInstance()->setValue($this->getPrefixedId(), $value, !$this->isI18nRequired());
        }
    }

    /**
     * Sets the value of this field and returns this field.
     *
     * @param string|string[] $value new field value
     * @return static                  this field
     *
     * @throws SystemException if the given value is of an invalid type or otherwise is invalid
     */
    public function value($value): self
    {
        if ($this->isI18n()) {
            if (\is_string($value) || \is_numeric($value)) {
                $this->setStringValue($value);
            } elseif (\is_array($value)) {
                if (!empty($value)) {
                    I18nHandler::getInstance()->setValues($this->getPrefixedId(), $value);
                }
            } else {
                throw new InvalidArgumentException(
                    'Given value is neither a nor an array, ' . \gettype($value) . ' given.'
                );
            }
        } else {
            if (!\is_string($value) && !\is_numeric($value)) {
                throw new InvalidArgumentException('Given value is no string, ' . \gettype($value) . ' given.');
            }

            return parent::value($value);
        }

        return $this;
    }

    /**
     * @inheritDoc
     *
     * @throws SystemException
     */
    public function populate(): IFormNode|self
    {
        parent::populate();

        if ($this->isI18n()) {
            I18nHandler::getInstance()->unregister($this->getPrefixedId());
            I18nHandler::getInstance()->register($this->getPrefixedId());
        }

        $this->getDocument()->getDataHandler()->addProcessor(
            new CustomFormDataProcessor(
                'wysiwyg',
                function (IFormDocument $document, array $parameters) {
                    if ($this->checkDependencies()) {
                        if ($this->isI18n() && $this->hasI18nValues()) {
                            $parameters[$this->getObjectProperty(
                            ) . '_htmlInputProcessors'] = $this->htmlInputProcessors;
                        } else {
                            $parameters[$this->getObjectProperty() . '_htmlInputProcessor'] = $this->htmlInputProcessor;
                        }
                    }

                    return $parameters;
                }
            )
        );

        return $this;
    }

    /**
     * @inheritDoc
     *
     * @throws SystemException
     */
    public function validate(): void
    {
        $errorEmpty = $errorMultilingual = false;

        if ($this->isI18n()) {
            // If i18n is required for a non-required field and the field is
            // empty in all languages, `I18nHandler::validateValue()` will mark
            // as invalid even though it is a valid state for this form field,
            // thus the additional condition.
            if ($this->isRequired() || !empty(ArrayUtil::trim($this->getValue()))) {
                if (
                    !I18nHandler::getInstance()->validateValue(
                        $this->getPrefixedId(),
                        $this->isI18nRequired(),
                        !$this->isRequired()
                    )
                ) {
                    if ($this->hasPlainValue()) {
                        $errorEmpty = true;
                        //$this->addValidationError(new FormFieldValidationError('empty'));
                    } else {
                        $errorMultilingual = true;
                        //$this->addValidationError(new FormFieldValidationError('multilingual'));
                    }
                }
            }

            if ($this->hasI18nValues() && \is_array($this->getValue())) {
                $this->validateI18n();
            } else {
                $this->validatePlaintext();
            }
        } else {
            $this->validatePlaintext();
        }

        parent::validate();

        foreach ($this->getValidationErrors() as $item) {
            if ($item->getType() === 'empty') {
                $errorEmpty = false;
            } elseif ($item->getType() === 'multilingual') {
                $errorMultilingual = false;
            }
        }

        if ($errorMultilingual) {
            $this->addValidationError(new FormFieldValidationError('multilingual'));
        } elseif ($errorEmpty) {
            $this->addValidationError(new FormFieldValidationError('empty'));
        }
    }

    /**
     * @throws SystemException
     */
    protected function validateI18n(): void
    {
        /** @noinspection PhpUndefinedFieldInspection */
        $disallowedBBCodesPermission = $this->getObjectType()->disallowedBBCodesPermission;
        if ($disallowedBBCodesPermission === null) {
            $disallowedBBCodesPermission = 'user.message.disallowedBBCodes';
        }

        BBCodeHandler::getInstance()->setDisallowedBBCodes(\explode(
            ',',
            WCF::getSession()->getPermission($disallowedBBCodesPermission)
        ));

        // TODO
        foreach ($this->getValue() as $languageID => $value) {
            $this->htmlInputProcessors[$languageID] = new HtmlInputProcessor();
            $this->htmlInputProcessors[$languageID]->process($value, $this->getObjectType()->objectType);

            if ($this->isRequired() && $this->htmlInputProcessors[$languageID]->appearsToBeEmpty()) {
                $this->addValidationError(new FormFieldValidationError('empty'));
            } else {
                $disallowedBBCodes = $this->htmlInputProcessors[$languageID]->validate();
                if (!empty($disallowedBBCodes)) {
                    $this->addValidationError(new FormFieldValidationError(
                        'disallowedBBCodes',
                        'wcf.message.error.disallowedBBCodes',
                        ['disallowedBBCodes' => $disallowedBBCodes]
                    ));
                } else {
                    $message = $this->htmlInputProcessors[$languageID]->getTextContent();
                    if ($message !== '') {
                        $this->validateMinimumLength($message);
                        $this->validateMaximumLength($message);

                        if (empty($this->getValidationErrors())) {
                            $censoredWords = Censorship::getInstance()->test($message);
                            if ($censoredWords) {
                                $this->addValidationError(new FormFieldValidationError(
                                    'censoredWords',
                                    'wcf.message.error.censoredWordsFound',
                                    ['censoredWords' => $censoredWords]
                                ));
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @throws SystemException
     */
    protected function validatePlaintext(): void
    {
        $this->htmlInputProcessor = new HtmlInputProcessor();
        $this->htmlInputProcessor->process($this->getValue(), $this->getObjectType()->objectType);

        if ($this->isRequired() && $this->htmlInputProcessor->appearsToBeEmpty()) {
            $this->addValidationError(new FormFieldValidationError('empty'));
        } else {
            $disallowedBBCodes = $this->htmlInputProcessor->validate();
            if (!empty($disallowedBBCodes)) {
                $this->addValidationError(new FormFieldValidationError(
                    'disallowedBBCodes',
                    'wcf.message.error.disallowedBBCodes',
                    ['disallowedBBCodes' => $disallowedBBCodes]
                ));
            } else {
                $message = $this->htmlInputProcessor->getTextContent();
                if ($message !== '') {
                    $this->validateMinimumLength($message);
                    $this->validateMaximumLength($message);

                    if (empty($this->getValidationErrors())) {
                        $censoredWords = Censorship::getInstance()->test($message);
                        if ($censoredWords) {
                            $this->addValidationError(new FormFieldValidationError(
                                'censoredWords',
                                'wcf.message.error.censoredWordsFound',
                                ['censoredWords' => $censoredWords]
                            ));
                        }
                    }
                }
            }
        }
    }
}
