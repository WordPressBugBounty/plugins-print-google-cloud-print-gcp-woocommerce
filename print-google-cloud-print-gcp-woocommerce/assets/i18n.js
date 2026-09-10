(function (root) {
	'use strict';

	var DEFAULT_LOCALE = 'en';
	var config = root.ZPRINT_I18N_CONFIG || {};
	var language =
		String(config.locale || DEFAULT_LOCALE)
			.toLowerCase()
			.split(/[-_]/)[0] || DEFAULT_LOCALE;
	var resources = config.resources
		? { [language]: { translation: config.resources } }
		: undefined;
	var i18n = root.i18next.createInstance();

	i18n.init({
		lng: language,
		fallbackLng: false,
		load: 'languageOnly',
		keySeparator: false,
		nsSeparator: false,
		compatibilityJSON: 'v3',
		returnEmptyString: false,
		returnNull: false,
		initImmediate: false,
		resources: resources,
		interpolation: {
			escapeValue: false,
		},
	});

	root.zprintI18n = i18n;
})(window);
