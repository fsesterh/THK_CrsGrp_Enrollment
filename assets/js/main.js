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
		deckSectionSelector: ".proctorio-settings-deck",
		deckDescription: ".section-description",
		settingElementSelector: ".proctorio-settings-card",
	};

	let globalSettings = defaults,
		deckDescriptions = {},
		methods = {};

	/**
	 *
	 * @param settings
	 */
	methods.init = function (settings) {
		globalSettings = $.extend({}, defaults, settings);

		$(globalSettings.deckSectionSelector).each(function () {
			let $this = $(this);

			deckDescriptions[$this.data("section-key")] = $this.find(globalSettings.deckDescription).html();
		});

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
			let $this = $(this), currentValue = $this.data("current-value");

			$this
				.closest(globalSettings.deckSectionSelector)
				.find(globalSettings.deckDescription)
				.html(il.Language.txt('setting_' + currentValue + '_info'));
		}).on("mouseleave", function(e) {
			let $this = $(this), $section = $this.closest(globalSettings.deckSectionSelector);

			$section
				.find(globalSettings.deckDescription)
				.html(deckDescriptions[$section.data("section-key")]);
		});
	};

	return methods;
}));