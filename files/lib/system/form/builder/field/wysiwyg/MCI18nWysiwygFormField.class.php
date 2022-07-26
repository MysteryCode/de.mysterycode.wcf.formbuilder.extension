<?php

namespace wcf\system\form\builder\field\wysiwyg;

use BadMethodCallException;
use InvalidArgumentException;
use wcf\data\IStorableObject;
use wcf\data\language\item\LanguageItemList;
use wcf\system\bbcode\BBCodeHandler;
use wcf\system\exception\SystemException;
use wcf\system\form\builder\data\processor\CustomFormDataProcessor;
use wcf\system\form\builder\field\AbstractFormField;
use wcf\system\form\builder\field\II18nFormField;
use wcf\system\form\builder\field\validation\FormFieldValidationError;
use wcf\system\form\builder\IFormDocument;
use wcf\system\html\input\HtmlInputProcessor;
use wcf\system\language\I18nHandler;
use wcf\system\language\LanguageFactory;
use wcf\system\message\censorship\Censorship;
use wcf\system\message\quote\MessageQuoteManager;
use wcf\system\Regex;
use wcf\system\WCF;
use wcf\util\ArrayUtil;
use wcf\util\StringUtil;

class MCI18nWysiwygFormField extends WysiwygFormField implements II18nFormField
{
    /**
     * @inheritDoc
     */
    protected $templateName = '__mcWysiwygFormField';

    /**
     * input processor containing the wysiwyg text
     * @var HtmlInputProcessor[]
     */
    protected array $htmlInputProcessors;

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
    protected ?string $languageItemPattern;

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
    public function getValue()
    {
        if ($this->isI18n()) {
            if ($this->hasI18nValues()) {
                $values = I18nHandler::getInstance()->getValues($this->getPrefixedId());

                // handle legacy values from the past when multilingual values
                // were available
                if (\count(LanguageFactory::getInstance()->getLanguages()) === 1) {
                    if (isset($values[WCF::getLanguage()->languageID])) {
                        return $values[WCF::getLanguage()->languageID];
                    }

                    return \current($values);
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
    public function i18n($i18n = true): self
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
    public function i18nRequired($i18nRequired = true): self
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
    public function languageItemPattern($pattern): self
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
     * @return static this field
     *
     * @throws SystemException
     */
    public function readValue(): self
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
    public function populate()
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        AbstractFormField::populate();

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
                /** @noinspection PhpUndefinedFieldInspection */
                $disallowedBBCodesPermission = $this->getObjectType()->disallowedBBCodesPermission;
                if ($disallowedBBCodesPermission === null) {
                    $disallowedBBCodesPermission = 'user.message.disallowedBBCodes';
                }
                BBCodeHandler::getInstance()->setDisallowedBBCodes(
                    \explode(
                        ',',
                        WCF::getSession()->getPermission($disallowedBBCodesPermission)
                    )
                );

                // TODO
                foreach ($this->getValue() as $languageID => $value) {
                    $this->htmlInputProcessors[$languageID] = new HtmlInputProcessor();
                    $this->htmlInputProcessors[$languageID]->process($value, $this->getObjectType()->objectType);

                    if ($this->isRequired() && $this->htmlInputProcessors[$languageID]->appearsToBeEmpty()) {
                        $this->addValidationError(new FormFieldValidationError('empty'));
                    } else {
                        $disallowedBBCodes = $this->htmlInputProcessors[$languageID]->validate();
                        if (!empty($disallowedBBCodes)) {
                            $this->addValidationError(
                                new FormFieldValidationError(
                                    'disallowedBBCodes',
                                    'wcf.message.error.disallowedBBCodes',
                                    ['disallowedBBCodes' => $disallowedBBCodes]
                                )
                            );
                        } else {
                            $message = $this->htmlInputProcessors[$languageID]->getTextContent();
                            if ($message !== '') {
                                $this->validateMinimumLength($message);
                                $this->validateMaximumLength($message);

                                if (ENABLE_CENSORSHIP && empty($this->getValidationErrors())) {
                                    $result = Censorship::getInstance()->test($message);
                                    if ($result) {
                                        $this->addValidationError(
                                            new FormFieldValidationError(
                                                'censoredWords',
                                                'wcf.message.error.censoredWordsFound',
                                                ['censoredWords' => $result]
                                            )
                                        );
                                    }
                                }
                            }
                        }
                    }
                }

                AbstractFormField::validate();
            } else {
                parent::validate();
            }
        } else {
            parent::validate();
        }

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
}
