/**
 * Data handler for a user form builder field in an Ajax form.
 *
 * @author      Florian Gail
 * @module      MysteryCode/Form/Builder/LabelPreviewHandler
 */

export class LabelPreviewHandler {
	protected _defaultText: string;
	protected _dependentElement: HTMLElement;
	protected _field: HTMLElement;

	constructor(dependentElementId: string, fieldId: string, defaultText: string) {
		this._defaultText = defaultText;
		this._dependentElement = document.getElementById(dependentElementId)!;
		if (this._dependentElement === null) {
			throw new Error("Unknown dependent element with container id '" + dependentElementId + "Container'.");
		}

		this._field = document.getElementById(fieldId)!;
		if (this._field === null) {
			throw new Error("Unknown field with id '" + fieldId + "'.");
		}

		if (
			this._field.tagName === "INPUT" &&
			((this._field as HTMLInputElement).type === "checkbox" ||
				(this._field as HTMLInputElement).type === "radio" ||
				(this._field as HTMLInputElement).type === "hidden")
		) {
			this._field.addEventListener("change", () => this.updatePreview());
		} else {
			this._field.addEventListener("input", () => this.updatePreview());
		}
	}

	/**
	 * Updates the label text.
	 */
	public updatePreview(): void {
		let value;

		if (this._field !== null) {
			switch (this._field.tagName) {
				case "INPUT": {
					const field = this._field as HTMLInputElement;
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
					value = (this._field as HTMLTextAreaElement).value;
					break;
				}
			}
		}

		if (value === '') {
			value = this._defaultText;
		}

		if (value !== null) {
			this._dependentElement.querySelectorAll('.jsLabelPreview').forEach((element: HTMLSpanElement) => {
				element.innerText = value;
			});
		}
	}
}

export default LabelPreviewHandler;
