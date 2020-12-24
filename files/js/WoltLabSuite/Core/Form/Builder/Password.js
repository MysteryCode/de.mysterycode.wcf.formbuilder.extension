/**
 * Data handler for a form builder field in an Ajax form that stores its value in an input's value
 * attribute.
 *
 * @author	Florian Gail
 * @copyright	2020 - Florian Gail - www.mysterycode.de
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @module	WoltLabSuite/Core/Form/Builder/Field/Password
 */
define(['Core', './Field'], function(Core, FormBuilderField) {
	"use strict";
	
	/**
	 * @constructor
	 */
	function FormBuilderFieldPassword(fieldId) {
		this.init(fieldId);
	};
	Core.inherit(FormBuilderFieldPassword, FormBuilderField, {
		/**
		 * @see	WoltLabSuite/Core/Form/Builder/Field/Field#_getData
		 */
		_getData: function() {
			var data = {};
			
			data[this._fieldId] = this._field.value;
			if (this._verdict !== null) {
				data[this._fieldId + '_passwordStrengthVerdict'] = this._verdict.value;
			}
			
			return data;
		},
		
		/**
		 * @see	WoltLabSuite/Core/Form/Builder/Field/Field#_readField
		 */
		_readField: function() {
			this._field = elById(this._fieldId);
			
			if (this._field === null) {
				throw new Error("Unknown field with id '" + this._fieldId + "'.");
			}
			
			this._verdict = elById(this._fieldId + '_passwordStrengthVerdict');
		},
	});
	
	return FormBuilderFieldPassword;
});
