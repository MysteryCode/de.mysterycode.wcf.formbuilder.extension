<?php

/**
 * @deprecated 1.5 Use `WysiwygAttachmentFormField` or `FileProcessorFormField` instead.
 */

namespace wcf\system\form\builder\field;

use wcf\system\form\builder\field\wysiwyg\WysiwygAttachmentFormField;

\class_alias(WysiwygAttachmentFormField::class, __NAMESPACE__ . '\\MCAttachmentFormField');
