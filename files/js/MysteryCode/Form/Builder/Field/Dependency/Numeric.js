/**
 * Data handler for a user form builder field in an Ajax form.
 *
 * @author Florian Gail
 * @module MysteryCode/Form/Builder/Field/Dependency/Numeric
 */
define(["require", "exports", "tslib", "WoltLabSuite/Core/Form/Builder/Field/Dependency/Abstract"], function (require, exports, tslib_1, Abstract_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.Numeric = void 0;
    Abstract_1 = tslib_1.__importDefault(Abstract_1);
    const comparison = {
        ">": (a, b) => a > b,
        ">=": (a, b) => a >= b,
        "<": (a, b) => a < b,
        "<=": (a, b) => a <= b,
        "==": (a, b) => a === b,
        "===": (a, b) => a === b,
    };
    class Numeric extends Abstract_1.default {
        _referenceValue = null;
        _operator = null;
        referenceValue(referenceValue) {
            this._referenceValue = referenceValue;
            return this;
        }
        operator(operator) {
            this._operator = operator;
            return this;
        }
        checkDependency() {
            if (this._referenceValue === null) {
                throw new Error("Value has not been set.");
            }
            if (this._operator === null) {
                throw new Error("Operator has not been set.");
            }
            if (!(this._field instanceof HTMLInputElement) || this._field.value === "") {
                return false;
            }
            const value = Number.parseFloat(this._field.value);
            if (Number.isNaN(value)) {
                return false;
            }
            return comparison[this._operator](value, this._referenceValue);
        }
    }
    exports.Numeric = Numeric;
});
