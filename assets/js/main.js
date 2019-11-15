if (typeof il === 'undefined') {
	window.il = {}
}

(function (root, scope, factory) {
	scope.proctorioSettings = factory(root, root.jQuery);
}(window, il, function init(root, $) {
	"use strict";

	const defaults = {
		postVar: "",
		disabled: false,
		imgHttpBasePath: "",
		images: [],
		modeValues: {},
		activeSettingCssClass: "active",
		binarySettingCssClass: "binary",
		modeSettingCssClass: "modes",
		titleSelector: ".card-title",
		imageSelector: "> img:first-child",
		deckSectionSelector: ".proctorio-settings-deck",
		deckDescription: ".section-description",
		settingElementSelector: ".proctorio-settings-card",
	};

	let globalSettings = defaults,
		deckDescriptions = {},
		methods = {};

	const Img = function (src) {
		return new Promise(function (resolve, reject) {
			let img = new Image();
			img.addEventListener('load', function (e) {
				resolve(src)
				img.addEventListener('error', function () {
					reject(new Error("Failed to load image's URL: " + src));
				});
			});
			img.src = src;
		});
	};

	const Images = function (sources) {
		let promises = sources.map(function (value, key) {
			return Img(value);
		});

		return Promise.all(promises);
	};

	/**
	 *
	 * @param settings
	 */
	methods.init = function (settings) {
		globalSettings = $.extend({}, defaults, settings);

		Images(globalSettings.images).then(function () {
		}).catch(function (e) {
		});

		$(globalSettings.deckSectionSelector).each(function () {
			let $this = $(this);

			deckDescriptions[$this.data("section-key")] = $this.find(globalSettings.deckDescription).html();
		});

		$(globalSettings.settingElementSelector).on("click", function (e) {
			if (globalSettings.disabled) {
				return false;
			}

			let $this = $(this);
			const key = $this.data("key"), currentValue = $this.data("current-value");

			if ($this.hasClass(globalSettings.binarySettingCssClass)) {
				const isActive = !!$this.hasClass(globalSettings.activeSettingCssClass);
				$this.toggleClass(globalSettings.activeSettingCssClass);

				$this
					.find('[name=*"' + key + '"]')
					.prop("checked", isActive);

				$this.data("current-value", isActive ? key : '');
			} else if ($this.hasClass(globalSettings.modeSettingCssClass)) {
				let nextRadioValue = "", nextTitle = "", nextImage = "";

				$this.find('input[type="radio"]').prop("checked", false);
				if (0 === currentValue.length) {
					$this.addClass(globalSettings.activeSettingCssClass);
					nextRadioValue = globalSettings.modeValues[key][0];
					nextTitle = il.Language.txt("setting_" + nextRadioValue);
					nextImage = globalSettings.imgHttpBasePath + nextRadioValue + ".svg";
				} else if (currentValue === globalSettings.modeValues[key][globalSettings.modeValues[key].length - 1]) {
					$this.removeClass(globalSettings.activeSettingCssClass);
					nextTitle = il.Language.txt("setting_" + globalSettings.modeValues[key][0]);
					nextImage = globalSettings.imgHttpBasePath + globalSettings.modeValues[key][0] + ".svg";
				} else {
					$this.addClass(globalSettings.activeSettingCssClass);
					const currentIndex = globalSettings.modeValues[key].indexOf(currentValue);
					const nextIndex = (currentIndex + 1) % globalSettings.modeValues[key].length;
					nextRadioValue = globalSettings.modeValues[key][nextIndex];

					nextTitle = il.Language.txt("setting_" + nextRadioValue);
					nextImage = globalSettings.imgHttpBasePath + nextRadioValue + ".svg";
				}
				
				const changeState = function() {
					$this.data("current-value", nextRadioValue);
					$this.find(globalSettings.imageSelector).attr("src", nextImage);
					$this.find(globalSettings.titleSelector).html(nextTitle);
					if (0 !== nextRadioValue.length) {
						$this
							.find('[name*="' + key + '"][value="' + nextRadioValue + '"]')
							.prop("checked", true);
					}
				};

				Img(nextImage)
					.then(() => changeState())
					.catch(() => changeState());
			}

			e.preventDefault();
			e.stopPropagation();
		}).on("mouseover", function (e) {
			let $this = $(this);
			const key = $this.data("key");

			$this
				.closest(globalSettings.deckSectionSelector)
				.find(globalSettings.deckDescription)
				.html(il.Language.txt('setting_' + key + '_info'));
		}).on("mouseleave", function (e) {
			let $this = $(this), $section = $this.closest(globalSettings.deckSectionSelector);

			$section
				.find(globalSettings.deckDescription)
				.html(deckDescriptions[$section.data("section-key")]);
		});
	};

	return methods;
}));