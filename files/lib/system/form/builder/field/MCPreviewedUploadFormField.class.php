<?php

namespace wcf\system\form\builder\field;

use wcf\data\IStorableObject;
use wcf\system\file\upload\UploadFile;
use wcf\util\ImageUtil;

/**
 * Implementation of a form field for to uploads.
 * This extension supports UploadFile-objects and strings for file-locations.
 *
 * @author  Joshua Ruesweg, Florian Gail
 * @copyright   2001-2019 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package WoltLabSuite\Core\System\Form\Builder\Field
 * @since   5.2
 */
class MCPreviewedUploadFormField extends UploadFormField {
	/**
	 * @inheritDoc
	 *
	 * @throws \InvalidArgumentException    if the getter for the value provides invalid values
	 */
	public function updatedObject(array $data, IStorableObject $object, $loadValues = true) {
		if ($loadValues) {
			// first check, whether an getter for the field exists
			if (\method_exists($object, 'get' . \ucfirst($this->getObjectProperty()) . 'UploadFileLocations')) {
				$value = \call_user_func([
					$object,
					'get' . \ucfirst($this->getObjectProperty()) . 'UploadFileLocations',
				]);
				$method = "method '" . \get_class($object) . "::get" . \ucfirst($this->getObjectProperty()) . "UploadFileLocations()'";
			}
			elseif (\method_exists($object, 'get' . \ucfirst($this->getObjectProperty()))) {
				$value = \call_user_func([
					$object,
					'get' . \ucfirst($this->getObjectProperty())
				]);
				$method = "method '" . \get_class($object) . "::get" . \ucfirst($this->getObjectProperty()) . "()'";
			}
			else {
				$value = $data[$this->getObjectProperty()];
				$method = "variable '" . \get_class($object) . "::$" . $this->getObjectProperty() . "'";
			}
			
			if (\is_array($value)) {
				$value = \array_map(function ($v) use ($method) {
					if ($v instanceof UploadFile) {
						if (!file_exists($v->getLocation())) {
							throw new \InvalidArgumentException("The " . $method . " must return an array of strings or object of class '" . UploadFile::class . "' with the valid file locations.");
						}
						
						return $v;
					}
					else {
						if (!\is_string($v) || !\file_exists($v)) {
							throw new \InvalidArgumentException("The " . $method . " must return an array of strings or object of class '" . UploadFile::class . "' with the valid file locations.");
						}
						
						return new UploadFile(
							$v,
							\basename($v),
							ImageUtil::isImage($v, \basename($v), $this->svgImageAllowed()),
							true,
							$this->svgImageAllowed()
						);
					}
				}, $value);
				
				$this->value($value);
			}
			else {
				throw new \InvalidArgumentException("The " . $method . " must return an array of strings with the file locations.");
			}
		}
		
		return $this;
	}
}
