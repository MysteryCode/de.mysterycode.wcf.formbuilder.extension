<?php

namespace wcf\system\form\builder\field\wysiwyg;

use wcf\system\attachment\AttachmentHandler;
use wcf\system\form\builder\data\processor\CustomFormDataProcessor;
use wcf\system\form\builder\field\AbstractFormField;
use wcf\system\form\builder\IFormDocument;
use wcf\system\form\builder\TWysiwygFormNode;
use wcf\system\WCF;
use wcf\util\StringUtil;

class MCWysiwygAttachmentFormField extends AbstractFormField {
	use TWysiwygFormNode;
	
	/**
	 * attachment handler
	 *
	 * @var null|AttachmentHandler
	 */
	protected ?AttachmentHandler $attachmentHandler;
	
	/**
	 * @inheritDoc
	 */
	protected $javaScriptDataHandlerModule = 'WoltLabSuite/Core/Form/Builder/Field/Wysiwyg/Attachment';
	
	/**
	 * @inheritDoc
	 */
	protected $templateName = '__mcWysiwygAttachmentFormField';
	
	/**
	 * Creates a new instance of `WysiwygAttachmentFormField`.
	 */
	public function __construct() {
		$this->addClass('wide');
	}
	
	/**
	 * Sets the attachment handler object for the uploaded attachments. If `null` is given,
	 * the previously set attachment handler is unset.
	 *
	 * For the initial attachment handler set by this method, the temporary hashes will be
	 * automatically set by either reading them from the session variables if the form handles
	 * AJAX requests or by creating a new one. If the temporary hashes are read from session,
	 * the session variable will be unregistered afterwards.
	 *
	 * @param null|AttachmentHandler $attachmentHandler
	 * @return  MCWysiwygAttachmentFormField
	 */
	public function attachmentHandler(?AttachmentHandler $attachmentHandler = null) : MCWysiwygAttachmentFormField {
		if ($attachmentHandler !== null) {
			if (empty($this->attachmentHandler)) {
				$tmpHash = StringUtil::getRandomID();
				if ($this->getDocument()->isAjax()) {
					/** @deprecated 5.5 see QuickReplyManager::setTmpHash() */
					$sessionTmpHash = WCF::getSession()->getVar('__wcfAttachmentTmpHash');
					if ($sessionTmpHash !== null) {
						$tmpHash = $sessionTmpHash;
						
						WCF::getSession()->unregister('__wcfAttachmentTmpHash');
					}
				}
				
				$attachmentHandler->setTmpHashes([$tmpHash]);
			}
			else {
				// preserve temporary hashes
				$attachmentHandler->setTmpHashes($this->attachmentHandler->getTmpHashes());
			}
		}
		
		$this->attachmentHandler = $attachmentHandler;
		
		if ($this->attachmentHandler !== null) {
			$this->description('wcf.attachment.upload.limits', ['attachmentHandler' => $this->attachmentHandler,]);
		}
		else {
			$this->description();
		}
		
		return $this;
	}
	
	/**
	 * Returns the attachment handler object for the uploaded attachments or `null` if no attachment
	 * upload is supported.
	 *
	 * @return  null|AttachmentHandler
	 */
	public function getAttachmentHandler() : ?AttachmentHandler {
		return $this->attachmentHandler ?? null;
	}
	
	/**
	 * @inheritDoc
	 */
	public function hasSaveValue() {
		return false;
	}
	
	/**
	 * @inheritDoc
	 */
	public function isAvailable() {
		return parent::isAvailable() && $this->getAttachmentHandler() !== null && $this->getAttachmentHandler()->canUpload();
	}
	
	/**
	 * @inheritDoc
	 */
	public function populate() {
		parent::populate();
		
		$this->getDocument()->getDataHandler()->addProcessor(new CustomFormDataProcessor($this->getId(), function (IFormDocument $document, array $parameters) {
			if ($this->getAttachmentHandler() !== null) {
				$parameters[$this->getWysiwygId() . '_attachmentHandler'] = $this->getAttachmentHandler();
			}
			
			return $parameters;
		}));
		
		return $this;
	}
	
	/**
	 * @inheritDoc
	 */
	public function readValue() {
		if ($this->getDocument()->hasRequestData($this->getPrefixedId() . '_tmpHash')) {
			$tmpHash = $this->getDocument()->getRequestData($this->getPrefixedId() . '_tmpHash');
			if (\is_string($tmpHash)) {
				$this->getAttachmentHandler()->setTmpHashes([$tmpHash]);
			}
			else if (\is_array($tmpHash)) {
				$this->getAttachmentHandler()->setTmpHashes($tmpHash);
			}
		}
	}
}
