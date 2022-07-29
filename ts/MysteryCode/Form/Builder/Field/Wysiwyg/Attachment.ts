/**
 * Data handler for a wysiwyg attachment form builder field that stores the temporary hash.
 *
 * @author  Matthias Schmidt
 * @copyright 2001-2020 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @module  MysteryCode/Form/Builder/Field/Field/Wysiwyg/Attachment
 * @since 5.2
 */

import Value from "WoltLabSuite/Core/Form/Builder/Field/Value";

export class Attachment extends Value {
  constructor(fieldId: string) {
    super(fieldId + "_tmpHash");
  }
}

export default Attachment;
