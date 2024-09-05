<?php

namespace wcf\system\form\builder\field;

use wcf\system\attachment\AttachmentHandler;
use wcf\system\form\builder\data\processor\CustomFormDataProcessor;
use wcf\system\form\builder\IFormDocument;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * i18n implementation of WysiwygFormContainer
 *
 * @author      Florian Gail
 * @copyright   Florian Gail; 2018 - 2022; <https://www.mysterycode.de>
 */
class MCAttachmentFormField extends AbstractFormField
{
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
    protected $templateName = '__wysiwygAttachmentFormField';

    /**
     * Creates a new instance of `AttachmentFormField`.
     */
    public function __construct()
    {
        $this->addClass('wide');
    }

    /**
     * Sets the attachment handler object for the uploaded attachments. If `null` is given,
     * the previously set attachment handler is unset.
     *
     * For the initial attachment handler set by this method, the temporary hashes will be
     * automatically set by either reading them from the session variables if the form handles
     * AJAX requests or by creating a new one. If the temporary hashes are read from session,
     * the session variable will be unregistered afterward.
     *
     * @param null|AttachmentHandler $attachmentHandler
     * @return static
     */
    public function attachmentHandler(?AttachmentHandler $attachmentHandler = null): self
    {
        if ($attachmentHandler !== null) {
            if (empty($this->attachmentHandler)) {
                $tmpHash = StringUtil::getRandomID();

                if ($this->getDocument()->isAjax()) {
                    $sessionTmpHash = WCF::getSession()->getVar('__wcfAttachmentTmpHash');
                    if ($sessionTmpHash !== null) {
                        $tmpHash = $sessionTmpHash;

                        WCF::getSession()->unregister('__wcfAttachmentTmpHash');
                    }
                }

                $attachmentHandler->setTmpHashes([$tmpHash]);
            } else {
                // preserve temporary hashes
                $attachmentHandler->setTmpHashes($this->attachmentHandler->getTmpHashes());
            }
        }

        $this->attachmentHandler = $attachmentHandler;

        if ($this->attachmentHandler !== null) {
            $this->description('wcf.attachment.upload.limits', [
                'attachmentHandler' => $this->attachmentHandler,
            ]);
        } else {
            $this->description();
        }

        return $this;
    }

    /**
     * Returns the attachment handler object for the uploaded attachments or `null` if no attachment
     * upload is supported.
     *
     * @return null|AttachmentHandler
     */
    public function getAttachmentHandler(): ?AttachmentHandler
    {
        return $this->attachmentHandler ?? null;
    }

    /**
     * @inheritDoc
     */
    public function hasSaveValue(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function isAvailable(): bool
    {
        return parent::isAvailable() && $this->getAttachmentHandler() !== null;
    }

    /**
     * @inheritDoc
     */
    public function populate(): self|static
    {
        parent::populate();

        $this->getDocument()->getDataHandler()->addProcessor(
            new CustomFormDataProcessor($this->getId(), function (IFormDocument $document, array $parameters) {
                if ($this->getAttachmentHandler() !== null) {
                    $parameters[$this->getId() . '_attachmentHandler'] = $this->getAttachmentHandler();
                }

                return $parameters;
            })
        );

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function readValue(): void
    {
        if ($this->getDocument()->hasRequestData($this->getPrefixedId() . '_tmpHash')) {
            $tmpHash = $this->getDocument()->getRequestData($this->getPrefixedId() . '_tmpHash');
            if (\is_string($tmpHash)) {
                $this->getAttachmentHandler()?->setTmpHashes([$tmpHash]);
            } elseif (\is_array($tmpHash)) {
                $this->getAttachmentHandler()?->setTmpHashes($tmpHash);
            }
        }
    }

    /**
     * Dummy method in order to be able to use the wysiwyg attachment template
     *
     * @return string
     */
    public function getPrefixedWysiwygId(): string
    {
        return '';
    }
}
