<?php

namespace wcf\data;

use wcf\system\attachment\AttachmentHandler;
use wcf\system\exception\SystemException;
use wcf\system\html\input\HtmlInputProcessor;
use wcf\system\language\I18nHandler;
use wcf\system\message\embedded\object\MessageEmbeddedObjectManager;
use wcf\util\StringUtil;

/**
 * Trait providing simple usage functions for handling i18n values in DatabaseObjectActions
 *
 * @notice meant to be used in extensions of `AbstractDatabaseObjectAction` only
 *
 * @mixin AbstractDatabaseObjectAction<DatabaseObject, DatabaseObjectEditor<DatabaseObject>>
 * @phpstan-require-extends AbstractDatabaseObjectAction
 */
/** @phpstan-ignore trait.unused */
trait MCTI18nDatabaseObjectAction
{
    protected function enforceValue(string $propertyName, ?string $value = null): void
    {
        if (isset($this->parameters['data'][$propertyName])) {
            return;
        }

        $_htmlInputProcessors = $propertyName . '_htmlInputProcessors';
        $_i18n = $propertyName . '_i18n';

        if (!isset($this->parameters[$_htmlInputProcessors]) && !isset($this->parameters[$_i18n])) {
            return;
        }

        if ($value === null) {
            $value = StringUtil::getRandomID();
        }

        if (isset($this->parameters)) {
            $this->parameters['data'][$propertyName] = $value;
        }
    }

    /**
     * @throws SystemException
     */
    protected function saveI18nValues(
        string $propertyName,
        string $languageItemPattern,
        string $languageItemCategory,
        DatabaseObject $object,
        int $packageID = PACKAGE_ID
    ): array {
        $values = null;
        $hasEmbeddedObjects = false;
        $attachmentCount = 0;
        $languageItem = \str_replace('\\d+', $object->getObjectID(), $languageItemPattern);

        $_htmlInputProcessors = $propertyName . '_htmlInputProcessors';
        $_i18n = $propertyName . '_i18n';
        $_attachmentHandler = $propertyName . '_attachmentHandler';

        if (isset($this->parameters[$_htmlInputProcessors])) {
            $values = [];
            /** @var HtmlInputProcessor $htmlInputProcessor */
            foreach ($this->parameters[$propertyName . '_htmlInputProcessors'] as $languageID => $htmlInputProcessor) {
                $htmlInputProcessor->setObjectID($object->getObjectID());
                $hasEmbeddedObjects = MessageEmbeddedObjectManager::getInstance()->registerObjects(
                    $htmlInputProcessor,
                    true
                ) || $hasEmbeddedObjects;
                $values[$languageID] = $htmlInputProcessor->getHtml();
            }

            if (isset($this->parameters[$_attachmentHandler])) {
                $attachmentHandler = $this->parameters[$propertyName . '_attachmentHandler'];
                \assert($attachmentHandler instanceof AttachmentHandler);
                $attachmentHandler->updateObjectID($object->getObjectID());
                $attachmentCount = $attachmentHandler->count();
            }
        } elseif (isset($this->parameters[$_i18n])) {
            $values = $this->parameters[$_i18n];
        }

        if (!empty($values)) {
            I18nHandler::getInstance()->save($values, $languageItem, $languageItemCategory, $packageID);

            if ($object->{$propertyName} !== $languageItem) {
                $className = static::class;

                if (\mb_substr($className, -6) === 'Action') {
                    $className = \mb_substr($className, 0, -6) . 'Editor';
                }
                if (\method_exists($this, 'getClassName')) {
                    $resolvedClassName = $this->getClassName();

                    if (\is_string($resolvedClassName) && $resolvedClassName !== '') {
                        $className = $resolvedClassName;
                    }
                }

                /** @noinspection PhpMethodParametersCountMismatchInspection */
                (new $className($object))->update([
                    $propertyName => $languageItem,
                ]);
            }
        }

        return [
            'languageItem' => $languageItem,
            'hasEmbeddedObjects' => $hasEmbeddedObjects,
            'hasAttachments' => $attachmentCount > 0,
            'attachmentCount' => $attachmentCount,
        ];
    }
}
