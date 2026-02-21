/**
 * Data handler for a ItemList-bases form builder field in an Ajax form.
 *
 * @author Florian Gail
 * @module MysteryCode/Form/Builder/Field/Dependency/ItemList
 */
define(["require", "exports", "tslib", "WoltLabSuite/Core/Form/Builder/Field/Dependency/Abstract", "WoltLabSuite/Core/Ui/ItemList", "WoltLabSuite/Core/Form/Builder/Field/Dependency/Manager"], function (require, exports, tslib_1, Abstract_1, UiItemList, DependencyManager) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.ItemList = void 0;
    Abstract_1 = tslib_1.__importDefault(Abstract_1);
    UiItemList = tslib_1.__importStar(UiItemList);
    DependencyManager = tslib_1.__importStar(DependencyManager);
    var State;
    (function (State) {
        State[State["Empty"] = 1] = "Empty";
        State[State["NonEmpty"] = 2] = "NonEmpty";
    })(State || (State = {}));
    class ItemList extends Abstract_1.default {
        _values = null;
        _state = State.NonEmpty;
        _isNegated = false;
        /**
         * Sets if the field value may not have any of the set values.
         */
        negate(negate) {
            this._isNegated = negate;
            return this;
        }
        /**
         * Sets the possible values the field may have for the dependency to be met.
         */
        values(values) {
            this._values = values;
            return this;
        }
        /**
         * Sets the state of the dependency-field to be met.
         */
        state(state) {
            this._state = state;
            return this;
        }
        checkDependency() {
            if (this._field === null || DependencyManager.isHiddenByDependencies(this._field)) {
                return false;
            }
            const values = UiItemList.getValues(this._field.id);
            if (this._state === State.Empty) {
                return values.length === 0;
            }
            if (this._state !== State.NonEmpty || values.length === 0) {
                return false;
            }
            if (this._values === null) {
                return true;
            }
            const selectedValues = new Set();
            values.forEach((selectedValue) => {
                selectedValues.add(String(selectedValue.value));
                selectedValues.add(String(selectedValue.objectId));
            });
            const foundMatch = this._values.some((value) => selectedValues.has(String(value)));
            return this._isNegated ? !foundMatch : foundMatch;
        }
    }
    exports.ItemList = ItemList;
    exports.default = ItemList;
});
