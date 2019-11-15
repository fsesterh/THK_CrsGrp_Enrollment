if (typeof il === 'undefined') {
	window.il = {}
}

(function (root, scope, factory) {
	scope.proctorioSettings = factory(root, root.jQuery);
}(window, il, function init(root, $) {
	"use strict";

	const defaults = {
		imgHttpBasePath: "",
		activeSettingCssClass: "active",
		binarySettingCssClass: "binary",
		modeSettingCssClass: "modes",
		settingElementSelector: ".proctorio-settings-card",
	};

	let globalSettings = defaults,
		methods = {};

	/**
	 *
	 * @param settings
	 */
	methods.init = function (settings) {
		globalSettings = $.extend({}, defaults, settings);

		$(globalSettings.settingElementSelector).on("click", function(e) {
			let $this = $(this);

			if ($this.hasClass(globalSettings.binarySettingCssClass)) {
				$this.toggleClass(globalSettings.activeSettingCssClass);

				$this
					.find('[name="' + $this.data("key") + '"]')
					.prop("checked", !!$this.hasClass(globalSettings.activeSettingCssClass));
			} else if ($this.hasClass(globalSettings.modeSettingCssClass)) {
				
			} 
		}).on("mouseover", function(e) {

		}).on("mouseleave", function(e) {

		});
	};

	return methods;
}));