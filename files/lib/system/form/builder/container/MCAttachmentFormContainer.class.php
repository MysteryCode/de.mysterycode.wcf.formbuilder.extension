<?php

namespace wcf\system\form\builder\container;

use wcf\data\IStorableObject;
use wcf\data\object\type\ObjectTypeCache;
use wcf\system\attachment\AttachmentHandler;
use wcf\system\event\EventHandler;
use wcf\system\form\builder\field\MCAttachmentFormField;

class MCAttachmentFormContainer extends FormContainer {
	/**
	 * attachment form field
	 *
	 * @var        MCAttachmentFormField
	 */
	protected $attachmentField;
	
	/**
	 * attachment-related data used to create an `AttachmentHandler` object for the attachment
	 * form field
	 * @var	null|array
	 */
	protected $attachmentData;
	
	/**
	 * id of the edited object
	 * @var	integer
	 */
	protected $objectId;
	
	/**
	 * id of the field itself
	 * @var	integer
	 */
	protected $fieldId;
	
	/**
	 * @inheritDoc
	 */
	public function __construct() {
		parent::__construct();
		
		$this->label('wcf.attachment.attachments');
	}
	
	/**
	 * Sets the attachment-related data used to create an `AttachmentHandler` object for the
	 * attachment form field. If no attachment data is set, attachments are not supported.
	 *
	 * By default, no attachment data is set.
	 *
	 * @param	null|string	$objectType		name of attachment object type or `null` to unset previous attachment data
	 * @param	integer		$parentObjectID		id of the parent of the object the attachments belong to or `0` if no such parent exists
	 * @return	static					this form container
	 * @throws	\BadMethodCallException			if the attachment form field has already been initialized
	 */
	public function attachmentData($objectType = null, $parentObjectID = 0) {
		if ($this->attachmentField !== null) {
			throw new \BadMethodCallException("The attachment form field has already been initialized. Use the atatchment form field directly to manipulate attachment data.");
		}
		
		if ($objectType === null) {
			$this->attachmentData = null;
		}
		else {
			if (ObjectTypeCache::getInstance()->getObjectTypeByName('com.woltlab.wcf.attachment.objectType', $objectType) === null) {
				throw new \InvalidArgumentException("Unknown attachment object type '{$objectType}'.");
			}
			
			$this->attachmentData = [
				'objectType' => $objectType,
				'parentObjectID' => $parentObjectID
			];
		}
		
		return $this;
	}
	
	/**
	 * Returns the form field handling attachments.
	 *
	 * @return        MCAttachmentFormField
	 * @throws	\BadMethodCallException		if the form field container has not been populated yet/form has not been built yet
	 */
	public function getAttachmentField() {
		if ($this->attachmentField === null) {
			throw new \BadMethodCallException("attachment form field can only be requested after the form has been built.");
		}
		
		return $this->attachmentField;
	}
	
	/**
	 * Returns the id of the edited object or `0` if no object is edited.
	 *
	 * @return	integer
	 */
	public function getObjectId() {
		return $this->objectId;
	}
	
	/**
	 * @inheritDoc
	 */
	public function id($id) {
		$this->fieldId = $id;
		
		return parent::id($id . 'Container');
	}
	
	/**
	 * @inheritDoc
	 */
	public function loadValues(array $data, IStorableObject $object) {
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
	 */
	public function populate() {
		parent::populate();
		
		$this->attachmentField = MCAttachmentFormField::create($this->fieldId);
		
		$this->appendChildren([
			$this->attachmentField
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
