<?php

namespace wcf\system\form\builder\container;

use BadMethodCallException;
use InvalidArgumentException;
use wcf\data\IStorableObject;
use wcf\data\object\type\ObjectTypeCache;
use wcf\system\attachment\AttachmentHandler;
use wcf\system\event\EventHandler;
use wcf\system\exception\SystemException;
use wcf\system\form\builder\field\MCAttachmentFormField;

/**
 * Container providing the possibility to upload attachments without the need of a wysiwyg-editor
 *
 * @author      Florian Gail
 * @copyright   Florian Gail; 2018 - 2022; <https://www.mysterycode.de>
 */
class MCAttachmentFormContainer extends FormContainer
{
    /**
     * attachment form field
     */
    protected ?MCAttachmentFormField $attachmentField = null;

    /**
     * attachment-related data used to create an `AttachmentHandler` object for the attachment form field
     */
    protected ?array $attachmentData;

    /**
     * id of the edited object
     */
    protected int $objectId = 0;

    /**
     * id of the field itself
     */
    protected int $fieldId = 0;

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
     * @return MCAttachmentFormField|null
     */
    public function getAttachmentField(): ?MCAttachmentFormField
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
        $this->fieldId = $id;

        return parent::id($id . 'Container');
    }

    /**
     * @throws SystemException
     */
    public function loadValues(array $data, IStorableObject $object)
    {
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

        return parent::loadValues($data, $object);
    }

    /**
     * @inheritDoc
     *
     * @throws SystemException
     */
    public function populate(): void
    {
        parent::populate();

        $this->attachmentField = MCAttachmentFormField::create($this->fieldId);

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
    }
}
