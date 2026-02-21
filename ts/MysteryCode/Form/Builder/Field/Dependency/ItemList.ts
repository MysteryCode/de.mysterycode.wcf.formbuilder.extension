/**
 * Data handler for a ItemList-bases form builder field in an Ajax form.
 *
 * @author Florian Gail
 * @module MysteryCode/Form/Builder/Field/Dependency/ItemList
 */

import Abstract from "WoltLabSuite/Core/Form/Builder/Field/Dependency/Abstract";
import * as UiItemList from "WoltLabSuite/Core/Ui/ItemList";
import { ItemData } from "WoltLabSuite/Core/Ui/ItemList";
import * as DependencyManager from "WoltLabSuite/Core/Form/Builder/Field/Dependency/Manager";

enum State {
  Empty = 1,
  NonEmpty = 2,
}

export class ItemList extends Abstract {
  protected _values: string[] | number[] | null = null;
  protected _state: State = State.NonEmpty;
  protected _isNegated: boolean = false;

  /**
   * Sets if the field value may not have any of the set values.
   */
  negate(negate: boolean): ItemList {
    this._isNegated = negate;

    return this;
  }

  /**
   * Sets the possible values the field may have for the dependency to be met.
   */
  values(values: string[] | number[] | null): ItemList {
    this._values = values;

    return this;
  }

  /**
   * Sets the state of the dependency-field to be met.
   */
  state(state: State): ItemList {
    this._state = state;

    return this;
  }

  public checkDependency(): boolean {
    if (this._field === null || DependencyManager.isHiddenByDependencies(this._field)) {
      return false;
    }

    const values: ItemData[] = UiItemList.getValues(this._field.id);

    if (this._state === State.Empty) {
      return values.length === 0;
    }

    if (this._state !== State.NonEmpty || values.length === 0) {
      return false;
    }

    if (this._values === null) {
      return true;
    }

    const selectedValues = new Set<string>();

    values.forEach((selectedValue) => {
      selectedValues.add(String(selectedValue.value));
      selectedValues.add(String(selectedValue.objectId));
    });

    const foundMatch = this._values.some((value) => selectedValues.has(String(value)));

    return this._isNegated ? !foundMatch : foundMatch;
  }
}

export default ItemList;
