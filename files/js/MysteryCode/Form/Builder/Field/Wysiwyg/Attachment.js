/**
 * Data handler for a wysiwyg attachment form builder field that stores the temporary hash.
 *
 * @author  Matthias Schmidt
 * @copyright 2001-2020 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @module  MysteryCode/Form/Builder/Field/Field/Wysiwyg/Attachment
 * @since 5.2
 */
define(["require", "exports", "tslib", "WoltLabSuite/Core/Form/Builder/Field/Value"], function (require, exports, tslib_1, Value_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.Attachment = void 0;
    Value_1 = tslib_1.__importDefault(Value_1);
    class Attachment extends Value_1.default {
        constructor(fieldId) {
            super(fieldId + "_tmpHash");
        }
    }
    exports.Attachment = Attachment;
    exports.default = Attachment;
});
