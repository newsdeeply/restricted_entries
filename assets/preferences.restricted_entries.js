/**
 * Restricted Entries
 * Preference page behavior
 */
(function ($, S) {
	'use strict';
	
	var selectSection;
	var selectField;
	
	var loadSections = function () {
		var currentSection = selectSection.attr('data-value');
		$.ajax({
			type: 'GET',
			dataType: 'json',
			url: S.Context.get('symphony') + '/ajax/sections/?sort=name',
			success: function(result) {
				var options = $();
				var mustSelectedField = false;
				if (result.sections.length) {
					$.each(result.sections, function (i, section) {
						var o = $('<option>')
							.text(section.name)
							.attr('value', section.id)
							.data('section', section);
						if (section.id === currentSection) {
							o.attr('selected', 'selected');
							mustSelectedField = true;
						}
						options = options.add(o);
					});
				}
				selectSection.empty().removeAttr('disabled').append(options);
				if (mustSelectedField) {
					updateFieldOptions();
				}
			}
		});
	};
	
	var updateFieldOptions = function () {
		var currentField = selectField.attr('data-value');
		var options = $();
		var selectedSection = selectSection.find('option:selected');
		var section = selectedSection.data('section');
		var options = $();
		if (!!section && section.fields) {
			$.each(section.fields, function (i, field) {
				var o = $('<option>')
					.text(field.name)
					.attr('value', field.id);
				if (field.id === currentField) {
					o.attr('selected', 'selected');
				}
				options = options.add(o);
			});
		}
		selectField.empty().removeAttr('disabled').append(options);
	}
	
	var sectionChanged = function (e) {
		updateFieldOptions();
	};
	
	var init = function () {
		selectSection = $('.js-restricted-entries-section');
		selectField = $('.js-restricted-entries-field');
		loadSections();
		selectSection.on('change', sectionChanged);
	};
	
	$(init);
	
})(window.jQuery, window.Symphony);