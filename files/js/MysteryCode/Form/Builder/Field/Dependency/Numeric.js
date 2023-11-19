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
            const comparison = {
                ">": (a, b) => {
                    return a > b;
                },
                ">=": (a, b) => {
                    return a >= b;
                },
                "<": (a, b) => {
                    return a < b;
                },
                "<=": (a, b) => {
                    return a <= b;
                },
                "==": (a, b) => {
                    return a == b;
                },
                "===": (a, b) => {
                    return a === b;
                },
            };
            if (this._field) {
                const field = this._field;
                if (field.value === "") {
                    return false;
                }
                return comparison[this._operator](field.value, this._referenceValue);
            }
            return false;
        }
    }
    exports.Numeric = Numeric;
});
