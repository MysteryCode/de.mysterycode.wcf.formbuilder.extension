/**
 * Data handler for a user form builder field in an Ajax form.
 *
 * @author Florian Gail
 * @module MysteryCode/Form/Builder/Field/Dependency/Numeric
 */

import Abstract from "WoltLabSuite/Core/Form/Builder/Field/Dependency/Abstract";

type Operator = ">" | ">=" | "<" | "<=" | "==" | "===";

const comparison: Record<Operator, (a: number, b: number) => boolean> = {
  ">": (a, b) => a > b,
  ">=": (a, b) => a >= b,
  "<": (a, b) => a < b,
  "<=": (a, b) => a <= b,
  "==": (a, b) => a === b,
  "===": (a, b) => a === b,
};

export class Numeric extends Abstract {
  protected _referenceValue: number | null = null;
  protected _operator: Operator | null = null;

  referenceValue(referenceValue: number): Numeric {
    this._referenceValue = referenceValue;

    return this;
  }

  operator(operator: Operator): Numeric {
    this._operator = operator;

    return this;
  }

  checkDependency(): boolean {
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
