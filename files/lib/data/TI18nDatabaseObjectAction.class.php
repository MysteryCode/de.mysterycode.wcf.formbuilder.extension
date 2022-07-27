<?php

namespace wcf\data;

use wcf\system\attachment\AttachmentHandler;
use wcf\system\html\input\HtmlInputProcessor;
use wcf\system\language\I18nHandler;
use wcf\system\message\embedded\object\MessageEmbeddedObjectManager;
use wcf\util\StringUtil;

/**
 * Trait providing simple usage functions for handling i18n values in DatabaseObjectActions
 *
 * @notice meant to be used in extensions of `AbstractDatabaseObjectAction` only
 */
trait TI18nDatabaseObjectAction
{
    /**
     * @param    string         $propertyName
     * @param    string|null    $value
     */
    protected function enforceValue(string $propertyName, ?string $value = null): void
    {
        if (isset($this->parameters['data'][$propertyName])) {
            return;
        }

        if (
            !isset($this->parameter[$propertyName . '_htmlInputProcessors'])
            && !isset($this->parameters[$propertyName . '_i18n'])
        ) {
            return;
        }

        if ($value === null) {
            $value = StringUtil::getRandomID();
        }

        $this->parameter['data'][$propertyName] = $value;
    }

    /**
     * @param    string            $propertyName
     * @param    string            $languageItemPattern
     * @param    string            $languageItemCategory
     * @param    DatabaseObject    $object
     * @param    int               $packageID
     * @return array
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

        if (isset($this->parameter[$propertyName . '_htmlInputProcessors'])) {
            $values = [];
            /** @var HtmlInputProcessor $htmlInputProcessor */
            foreach ($this->parameter[$propertyName . '_htmlInputProcessors'] as $languageID => $htmlInputProcessor) {
                $htmlInputProcessor->setObjectID($object->getObjectID());
                $hasEmbeddedObjects = MessageEmbeddedObjectManager::getInstance()->registerObjects(
                    $htmlInputProcessor,
                    true
                ) || $hasEmbeddedObjects;
                $values[$languageID] = $htmlInputProcessor->getHtml();
            }

            if (isset($this->parameter[$propertyName . '_attachmentHandler'])) {
                $attachmentHandler = $this->parameter[$propertyName . '_attachmentHandler'];
                \assert($attachmentHandler instanceof AttachmentHandler);
                $attachmentHandler->updateObjectID($object->getObjectID());
                $attachmentCount = $attachmentHandler->count();
            }
        } elseif (isset($this->parameters[$propertyName . '_i18n'])) {
            $values = $this->parameter[$propertyName . '_i18n'];
        }

        if (!empty($values)) {
            I18nHandler::getInstance()->save($values, $languageItem, $languageItemCategory, $packageID);
            if ($object->$propertyName !== $languageItem) {
                $className = $this->getClassName();
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
