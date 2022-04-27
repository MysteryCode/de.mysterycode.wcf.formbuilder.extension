/**
 * Data handler for a user form builder field in an Ajax form.
 *
 * @author      Florian Gail
 * @module      MysteryCode/Form/Builder/LabelPreviewHandler
 */
define(["require", "exports"], function (require, exports) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.LabelPreviewHandler = void 0;
    class LabelPreviewHandler {
        constructor(dependentElementId, fieldId, defaultText) {
            this._defaultText = defaultText;
            this._dependentElement = document.getElementById(dependentElementId);
            if (this._dependentElement === null) {
                throw new Error("Unknown dependent element with container id '" + dependentElementId + "Container'.");
            }
            this._field = document.getElementById(fieldId);
            if (this._field === null) {
                throw new Error("Unknown field with id '" + fieldId + "'.");
            }
            if (this._field.tagName === "INPUT" &&
                (this._field.type === "checkbox" ||
                    this._field.type === "radio" ||
                    this._field.type === "hidden")) {
                this._field.addEventListener("change", () => this.updatePreview());
            }
            else {
                this._field.addEventListener("input", () => this.updatePreview());
            }
        }
        /**
         * Updates the label text.
         */
        updatePreview() {
            let value;
            if (this._field !== null) {
                switch (this._field.tagName) {
                    case "INPUT": {
                        const field = this._field;
                        switch (field.type) {
                            case "checkbox":
                            case "radio":
                                return;
                            default:
                                value = field.value;
                                break;
                        }
                        break;
                    }
                    case "TEXTAREA": {
                        value = this._field.value;
                        break;
                    }
                }
            }
            if (value === '') {
                value = this._defaultText;
            }
            if (value !== null) {
                this._dependentElement.querySelectorAll('.jsLabelPreview').forEach((element) => {
                    element.innerText = value;
                });
            }
        }
    }
    exports.LabelPreviewHandler = LabelPreviewHandler;
    exports.default = LabelPreviewHandler;
});
