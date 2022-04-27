/**
 * Data handler for a user form builder field in an Ajax form.
 *
 * @author      Florian Gail
 * @module      MysteryCode/Form/Builder/Field/Dependency/Numeric
 */

import Abstract from "WoltLabSuite/Core/Form/Builder/Field/Dependency/Abstract";

export class Numeric extends Abstract {
	protected _referenceValue: number | null = null;
	protected _operator: string | null = null;

	referenceValue(referenceValue: number): Numeric {
		this._referenceValue = referenceValue;

		return this;
	}

	operator(operator: string): Numeric {
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

		const comparison = {
			'>': (a: number, b: number) => {
				return a > b;
			},
			'>=': (a: number, b: number) => {
				return a >= b;
			},
			'<': (a: number, b: number) => {
				return a < b;
			},
			'<=': (a: number, b: number) => {
				return a <= b;
			},
			'==': (a: number, b: number) => {
				return a == b;
			},
			'===': (a: number, b: number) => {
				return a === b;
			},
		}

		if (this._field) {
			const field = this._field as HTMLInputElement;
			if (field.value === '') {
				return false;
			}

			return comparison[this._operator](field.value, this._referenceValue);
		}

		return false;
	}
}
