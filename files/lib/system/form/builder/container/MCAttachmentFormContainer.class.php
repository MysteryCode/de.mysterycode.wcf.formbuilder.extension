<?php

namespace wcf\system\form\builder\container;

use BadMethodCallException;
use InvalidArgumentException;
use wcf\data\IStorableObject;
use wcf\data\object\type\ObjectTypeCache;
use wcf\system\attachment\AttachmentHandler;
use wcf\system\event\EventHandler;
use wcf\system\exception\SystemException;
use wcf\system\form\builder\field\wysiwyg\WysiwygAttachmentFormField;

/**
 * Container providing the possibility to upload attachments without the need of a wysiwyg-editor
 *
 * @author      Florian Gail
 * @copyright   Florian Gail; 2018 - 2022; <https://www.mysterycode.de>
 *
 * @deprecated 1.5 Use `FormContainer` with `WysiwygAttachmentFormField` or `FileProcessorFormField` instead.
 */
class MCAttachmentFormContainer extends FormContainer
{
    /**
     * attachment form field
     */
    protected ?WysiwygAttachmentFormField $attachmentField = null;

    /**
     * attachment-related data used to create an `AttachmentHandler` object for the attachment form field
     *
     * @var array{objectType: string, parentObjectID: int}|null
     */
    protected ?array $attachmentData = null;

    /**
     * id of the edited object
     */
    protected int $objectId = 0;

    /**
     * id of the field itself
     */
    protected string $fieldId = '';

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct();

        $this->label('wcf.attachment.attachments');
    }

    /**
     * Sets the attachment-related data used to create an `AttachmentHandler` object for the
     * attachment form field. If no attachment data is set, attachments are not supported.
     *
     * By default, no attachment data is set.
     *
     * @param null|string $objectType name of attachment object type or `null` to unset previous attachment data
     * @param integer $parentObjectID id of the parent of the object the attachments belong to,
     *                                or `0` if no such parent exists
     * @return static                 this form container
     *
     * @throws BadMethodCallException  if the attachment form field has already been initialized
     * @throws SystemException
     */
    public function attachmentData(?string $objectType = null, int $parentObjectID = 0): self
    {
        if ($this->attachmentField !== null) {
            throw new BadMethodCallException(
                "The attachment form field has already been initialized. "
                . "Use the attachment form field directly to manipulate attachment data."
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
                throw new InvalidArgumentException("Unknown attachment object type '{$objectType}'.");
            }

            $this->attachmentData = [
                'objectType' => $objectType,
                'parentObjectID' => $parentObjectID,
            ];
        }

        return $this;
    }

    /**
     * Returns the form field handling attachments.
     *
     * @return WysiwygAttachmentFormField|null
     */
    public function getAttachmentField(): ?WysiwygAttachmentFormField
    {
        if (empty($this->attachmentField)) {
            throw new BadMethodCallException(
                "attachment form field can only be requested after the form has been built."
            );
        }

        return $this->attachmentField;
    }

    /**
     * Returns the id of the edited object or `0` if no object is edited.
     *
     * @return integer
     */
    public function getObjectId(): int
    {
        return $this->objectId ?? 0;
    }

    /**
     * @inheritDoc
     */
    public function id($id): self
    {
        $this->fieldId = (string)$id;

        return parent::id($this->fieldId . 'Container');
    }

    /**
     * @throws SystemException
     */
    public function updatedObject(
        array $data,
        IStorableObject $object,
        $loadValues = true
    ): self | FormContainer {
        $this->objectId = $object->{$object::getDatabaseTableIndexName()};

        if ($this->attachmentData !== null) {
            // updated attachment handler with object id
            $this->attachmentField->attachmentHandler(
                new AttachmentHandler(
                    $this->attachmentData['objectType'],
                    $this->getObjectId(),
                    '.',
                    $this->attachmentData['parentObjectID']
                )
            );
        }

        return parent::updatedObject($data, $object, $loadValues);
    }

    /**
     * @inheritDoc
     *
     * @throws SystemException
     */
    public function populate(): static
    {
        parent::populate();

        $this->attachmentField = WysiwygAttachmentFormField::create($this->fieldId)
            ->wysiwygId($this->fieldId);

        $this->appendChildren([
            $this->attachmentField,
        ]);

        if ($this->attachmentData !== null) {
            $this->attachmentField->attachmentHandler(
                // the temporary hash may not be empty (at the same time as the
                // object id) and it will be changed anyway by the called method
                new AttachmentHandler(
                    $this->attachmentData['objectType'],
                    $this->getObjectId(),
                    '.',
                    $this->attachmentData['parentObjectID']
                )
            );
        }

        EventHandler::getInstance()->fireAction($this, 'populate');

        return $this;
    }
}
