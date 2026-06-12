(function () {
	'use strict';

	var config = window.SimplePrivacyCookiePolicyConfig || {};
	var cookieName = config.cookieName || 'simple_privacy_cookie_consent';
	var categories = ['preferences', 'statistics', 'marketing'];
	var lastFocus = null;
	var facebookSdkLoading = false;
	var facebookSdkLoaded = false;
	var facebookSdkCallbacks = [];

	function consentId() {
		var source = String(Date.now()) + ':' + Math.random().toString(36).slice(2);
		if (window.crypto && typeof window.crypto.randomUUID === 'function') {
			return window.crypto.randomUUID();
		}
		return source.replace(/[^a-z0-9:.-]/gi, '').slice(0, 64);
	}

	function readCookie(name) {
		var parts = document.cookie ? document.cookie.split('; ') : [];
		for (var i = 0; i < parts.length; i++) {
			var pair = parts[i].split('=');
			if (decodeURIComponent(pair[0]) === name) {
				try {
					return JSON.parse(decodeURIComponent(pair.slice(1).join('=')));
				} catch (e) {
					return null;
				}
			}
		}
		return null;
	}

	function isCurrentConsent(consent) {
		return !!(
			consent &&
			String(consent.version || '') === String(config.version || '1') &&
			consent.necessary === true
		);
	}

	function currentConsent() {
		var consent = readCookie(cookieName);
		return isCurrentConsent(consent) ? consent : null;
	}

	function writeCookie(value) {
		var maxAge = Math.max(180, parseInt(config.days || 180, 10)) * 24 * 60 * 60;
		var secure = window.location.protocol === 'https:' ? '; Secure' : '';
		document.cookie = encodeURIComponent(cookieName) + '=' + encodeURIComponent(JSON.stringify(value)) + '; Path=/; Max-Age=' + maxAge + '; SameSite=Lax' + secure;
	}

	function consentFromInputs(root) {
		var data = {
			version: String(config.version || '1'),
			date: new Date().toISOString(),
			id: consentId(),
			method: 'save_preferences',
			necessary: true,
			preferences: false,
			statistics: false,
			marketing: false
		};
		categories.forEach(function (category) {
			var input = root.querySelector('[data-spcp-cookie-category="' + category + '"]');
			data[category] = !!(input && input.checked);
		});
		return data;
	}

	function allConsent(value) {
		return {
			version: String(config.version || '1'),
			date: new Date().toISOString(),
			id: consentId(),
			method: value ? 'accept_all' : 'reject_non_essential',
			necessary: true,
			preferences: value,
			statistics: value,
			marketing: value
		};
	}

	function syncInputs(root, consent) {
		categories.forEach(function (category) {
			var input = root.querySelector('[data-spcp-cookie-category="' + category + '"]');
			if (input) {
				input.checked = !!(consent && consent[category]);
			}
		});
	}

	function updateGoogleConsent(consent) {
		var marketing = consent.marketing ? 'granted' : 'denied';
		var statistics = consent.statistics ? 'granted' : 'denied';
		var preferences = consent.preferences ? 'granted' : 'denied';

		var update = {
			ad_storage: marketing,
			ad_user_data: marketing,
			ad_personalization: marketing,
			analytics_storage: statistics,
			functionality_storage: preferences,
			personalization_storage: preferences,
			security_storage: 'granted'
		};

		if (typeof window.gtag === 'function') {
			window.gtag('consent', 'update', update);
			return;
		}

		// gtag.js not defined yet (deferred/delayed loader or consent-gated):
		// push to the dataLayer so Google Tag picks up the update when it loads,
		// instead of silently dropping the consent change.
		window.dataLayer = window.dataLayer || [];
		window.dataLayer.push(['consent', 'update', update]);
	}

	function dispatchConsent(consent) {
		window.dispatchEvent(new CustomEvent('simplePrivacyCookieConsentChanged', {
			detail: consent
		}));
	}

	function updateFacebookMessage(embed, message) {
		var node = embed.querySelector('[data-spcp-facebook-message]');
		if (node) {
			node.textContent = message;
		}
	}

	function facebookSdkReady() {
		return !!(window.FB && window.FB.XFBML && typeof window.FB.XFBML.parse === 'function');
	}

	function flushFacebookSdk(success) {
		var callbacks = facebookSdkCallbacks.slice();
		facebookSdkCallbacks = [];
		callbacks.forEach(function (callback) {
			callback(success);
		});
	}

	function loadFacebookSdk(callback) {
		if (facebookSdkReady()) {
			facebookSdkLoaded = true;
			callback(true);
			return;
		}

		facebookSdkCallbacks.push(callback);
		if (facebookSdkLoading) {
			return;
		}

		facebookSdkLoading = true;
		var previousFbAsyncInit = window.fbAsyncInit;
		window.fbAsyncInit = function () {
			if (typeof previousFbAsyncInit === 'function') {
				previousFbAsyncInit();
			}
			facebookSdkLoaded = true;
			facebookSdkLoading = false;
			flushFacebookSdk(true);
		};

		var script = document.getElementById('facebook-jssdk');
		var createdScript = false;
		if (!script) {
			script = document.createElement('script');
			script.id = 'facebook-jssdk';
			script.async = true;
			script.defer = true;
			script.crossOrigin = 'anonymous';
			script.src = 'https://connect.facebook.net/it_IT/sdk.js#xfbml=1&version=v20.0';
			createdScript = true;
		}

		script.addEventListener('load', function () {
			window.setTimeout(function () {
				if (!facebookSdkReady() || facebookSdkLoaded) {
					return;
				}
				facebookSdkLoaded = true;
				facebookSdkLoading = false;
				flushFacebookSdk(true);
			}, 0);
		}, {once: true});

		script.addEventListener('error', function () {
			facebookSdkLoading = false;
			if (script.parentNode) {
				script.parentNode.removeChild(script);
			}
			flushFacebookSdk(false);
		}, {once: true});

		if (createdScript) {
			document.body.appendChild(script);
		}

		window.setTimeout(function () {
			if (facebookSdkLoaded || facebookSdkReady()) {
				return;
			}
			facebookSdkLoading = false;
			if (script.parentNode) {
				script.parentNode.removeChild(script);
			}
			flushFacebookSdk(false);
		}, 7000);
	}

	function facebookContent(embed) {
		var content = embed.querySelector('[data-spcp-facebook-content]');
		if (content && !content.getAttribute('data-spcp-original-html')) {
			content.setAttribute('data-spcp-original-html', content.innerHTML);
		}
		return content;
	}

	function restoreFacebookContent(embed) {
		var content = facebookContent(embed);
		if (!content) {
			return null;
		}
		var original = content.getAttribute('data-spcp-original-html');
		if (original) {
			content.innerHTML = original;
		}
		content.hidden = true;
		return content;
	}

	function hasFacebookFrame(embed) {
		return !!embed.querySelector('.fb_iframe_widget iframe, iframe[src*="facebook.com"], iframe[src*="fbcdn.net"]');
	}

	function markFacebookReady(embed) {
		var content = facebookContent(embed);
		if (content) {
			content.hidden = false;
		}
		embed.classList.remove('is-loading', 'is-blocked');
		embed.classList.add('is-ready', 'has-marketing-consent');
		embed.setAttribute('data-spcp-facebook-state', 'ready');
	}

	function markFacebookBlocked(embed) {
		restoreFacebookContent(embed);
		embed.classList.remove('is-loading', 'is-ready');
		embed.classList.add('is-blocked', 'has-marketing-consent');
		embed.setAttribute('data-spcp-facebook-state', 'blocked');
		updateFacebookMessage(embed, 'Il consenso marketing risulta attivo, ma il browser sta bloccando il collegamento a Facebook. Puoi aprire la pagina direttamente.');
	}

	function parseFacebookEmbed(embed) {
		var state = embed.getAttribute('data-spcp-facebook-state');
		var content = facebookContent(embed);
		if (!content || state === 'loading' || state === 'ready' || state === 'blocked') {
			return;
		}

		restoreFacebookContent(embed);
		content = facebookContent(embed);
		content.hidden = false;
		embed.classList.remove('is-blocked', 'is-ready');
		embed.classList.add('is-loading', 'has-marketing-consent');
		embed.setAttribute('data-spcp-facebook-state', 'loading');
		updateFacebookMessage(embed, 'Caricamento del contenuto Facebook in corso...');

		loadFacebookSdk(function (success) {
			if (!success) {
				markFacebookBlocked(embed);
				return;
			}

			try {
				window.FB.XFBML.parse(content);
			} catch (e) {
				markFacebookBlocked(embed);
				return;
			}

			window.setTimeout(function () {
				if (hasFacebookFrame(embed)) {
					markFacebookReady(embed);
				} else {
					markFacebookBlocked(embed);
				}
			}, 3500);
		});
	}

	function syncFacebookEmbeds(consent) {
		var embeds = document.querySelectorAll('[data-spcp-facebook-embed]');
		embeds.forEach(function (embed) {
			if (consent && consent.marketing) {
				parseFacebookEmbed(embed);
				return;
			}

			restoreFacebookContent(embed);
			embed.classList.remove('has-marketing-consent', 'is-loading', 'is-ready', 'is-blocked');
			embed.setAttribute('data-spcp-facebook-state', 'idle');
			updateFacebookMessage(embed, 'Per visualizzare il contenuto Facebook serve il consenso marketing.');
		});
	}

	function syncFacebookEmbedsFromStoredConsent() {
		syncFacebookEmbeds(currentConsent() || allConsent(false));
	}

	function scheduleFacebookEmbedsSync(delay) {
		window.setTimeout(syncFacebookEmbedsFromStoredConsent, delay);
	}

	function activateBlockedScripts(consent) {
		var nodes = document.querySelectorAll('script[type="text/plain"][data-spcp-consent]');
		nodes.forEach(function (node) {
			var category = node.getAttribute('data-spcp-consent');
			if (!consent[category] || node.getAttribute('data-spcp-loaded') === '1') {
				return;
			}

			var script = document.createElement('script');
			for (var i = 0; i < node.attributes.length; i++) {
				var attr = node.attributes[i];
				if (attr.name === 'type' || attr.name === 'data-spcp-consent' || attr.name === 'data-spcp-loaded') {
					continue;
				}
				if (attr.name === 'data-src') {
					script.setAttribute('src', attr.value);
				} else {
					script.setAttribute(attr.name, attr.value);
				}
			}
			script.text = node.text || node.textContent || '';
			node.setAttribute('data-spcp-loaded', '1');
			node.parentNode.insertBefore(script, node.nextSibling);
		});
		syncFacebookEmbeds(consent);
	}

	function saveConsent(root, consent) {
		writeCookie(consent);
		updateGoogleConsent(consent);
		activateBlockedScripts(consent);
		syncFacebookEmbeds(consent);
		dispatchConsent(consent);
		syncInputs(root, consent);
		hideBanner(root);
		closeModal(root);
	}

	function hideBanner(root) {
		var banner = root.querySelector('[role="region"]');
		if (banner) {
			banner.hidden = true;
		}
	}

	function showBanner(root) {
		var banner = root.querySelector('[role="region"]');
		if (banner) {
			banner.hidden = false;
		}
	}

	function hasOpenModal(root) {
		return !!activeModal(root);
	}

	function syncModalLock(root) {
		document.documentElement.classList.toggle('spcp-cookie-modal-open', hasOpenModal(root));
	}

	function activeModal(root) {
		var selectors = [
			'[data-spcp-accessibility-modal]',
			'[data-spcp-privacy-modal]',
			'[data-spcp-cookie-policy-modal]',
			'[data-spcp-cookie-modal]'
		];
		for (var i = 0; i < selectors.length; i++) {
			var modal = root.querySelector(selectors[i]);
			if (modal && !modal.hidden) {
				return modal;
			}
		}
		return null;
	}

	function openModal(root) {
		var modal = root.querySelector('[data-spcp-cookie-modal]');
		var dialog = modal ? modal.querySelector('[role="dialog"]') : null;
		if (!modal || !dialog) {
			return;
		}
		lastFocus = document.activeElement;
		modal.hidden = false;
		syncModalLock(root);
		dialog.focus();
	}

	function openPreferences(root) {
		var consent = currentConsent() || allConsent(false);
		syncInputs(root, consent);
		syncFacebookEmbeds(consent);
		openModal(root);
	}

	function closeModal(root) {
		var modal = root.querySelector('[data-spcp-cookie-modal]');
		if (!modal) {
			return;
		}
		var restoreTo = lastFocus;
		if (restoreTo && modal.contains(restoreTo)) {
			restoreTo = root.querySelector('.spcp-cookie__reopen');
		}
		modal.hidden = true;
		syncModalLock(root);
		if (restoreTo && typeof restoreTo.focus === 'function') {
			restoreTo.focus();
		}
	}

	function openTemplateModal(root, modalSelector, bodySelector, templateSelector) {
		var modal = root.querySelector(modalSelector);
		var dialog = modal ? modal.querySelector('[role="dialog"]') : null;
		if (!modal || !dialog) {
			return;
		}
		var body = modal.querySelector(bodySelector);
		var template = root.querySelector(templateSelector);
		if (body && template && !body.hasChildNodes()) {
			body.appendChild(template.content.cloneNode(true));
		}
		lastFocus = document.activeElement;
		modal.hidden = false;
		syncModalLock(root);
		dialog.focus();
	}

	function closeTemplateModal(root, modalSelector, openerSelector) {
		var modal = root.querySelector(modalSelector);
		if (!modal) {
			return;
		}
		var restoreTo = lastFocus;
		if (restoreTo && modal.contains(restoreTo)) {
			restoreTo = root.querySelector(openerSelector) || root.querySelector('.spcp-cookie__reopen');
		}
		modal.hidden = true;
		syncModalLock(root);
		if (restoreTo && typeof restoreTo.focus === 'function') {
			restoreTo.focus();
		}
	}

	function openPrivacyModal(root) {
		openTemplateModal(root, '[data-spcp-privacy-modal]', '[data-spcp-privacy-body]', '[data-spcp-privacy-template]');
	}

	function closePrivacyModal(root) {
		closeTemplateModal(root, '[data-spcp-privacy-modal]', '[data-spcp-privacy-open]');
	}

	function openPolicyModal(root) {
		openTemplateModal(root, '[data-spcp-cookie-policy-modal]', '[data-spcp-cookie-policy-body]', '[data-spcp-cookie-policy-template]');
	}

	function closePolicyModal(root) {
		closeTemplateModal(root, '[data-spcp-cookie-policy-modal]', '[data-spcp-cookie-policy-open]');
	}

	function openAccessibilityModal(root) {
		openTemplateModal(root, '[data-spcp-accessibility-modal]', '[data-spcp-accessibility-body]', '[data-spcp-accessibility-template]');
	}

	function closeAccessibilityModal(root) {
		closeTemplateModal(root, '[data-spcp-accessibility-modal]', '[data-spcp-accessibility-open]');
	}

	function trapFocus(root, event) {
		if (event.key !== 'Tab') {
			return;
		}
		var modal = activeModal(root);
		if (!modal) {
			return;
		}
		var focusable = modal.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])');
		if (!focusable.length) {
			return;
		}
		var first = focusable[0];
		var last = focusable[focusable.length - 1];
		if (event.shiftKey && document.activeElement === first) {
			event.preventDefault();
			last.focus();
		} else if (!event.shiftKey && document.activeElement === last) {
			event.preventDefault();
			first.focus();
		}
	}

	function initRoot(root) {
		var existing = currentConsent();
		if (existing) {
			syncInputs(root, existing);
			updateGoogleConsent(existing);
			activateBlockedScripts(existing);
			syncFacebookEmbeds(existing);
			hideBanner(root);
		} else {
			syncFacebookEmbeds(allConsent(false));
			// The consent cookie is the single source of truth for banner visibility.
			// The server may render the banner with a cached `hidden` attribute (full-page
			// cache), so re-assert visibility client-side when no valid consent exists.
			showBanner(root);
		}
		scheduleFacebookEmbedsSync(250);
		scheduleFacebookEmbedsSync(1500);

		root.addEventListener('click', function (event) {
			var target = event.target.closest('button, a');
			if (!target) {
				return;
			}
			if (target.matches('[data-spcp-privacy-open]')) {
				event.preventDefault();
				openPrivacyModal(root);
			} else if (target.matches('[data-spcp-privacy-close]')) {
				event.preventDefault();
				closePrivacyModal(root);
			} else if (target.matches('[data-spcp-cookie-policy-open]')) {
				event.preventDefault();
				openPolicyModal(root);
			} else if (target.matches('[data-spcp-cookie-policy-close]')) {
				event.preventDefault();
				closePolicyModal(root);
			} else if (target.matches('[data-spcp-accessibility-open]')) {
				event.preventDefault();
				openAccessibilityModal(root);
			} else if (target.matches('[data-spcp-accessibility-close]')) {
				event.preventDefault();
				closeAccessibilityModal(root);
			} else if (target.matches('[data-spcp-cookie-open]')) {
				event.preventDefault();
				openPreferences(root);
			} else if (target.matches('[data-spcp-cookie-close]')) {
				event.preventDefault();
				closeModal(root);
			} else if (target.matches('[data-spcp-cookie-accept]')) {
				event.preventDefault();
				saveConsent(root, allConsent(true));
			} else if (target.matches('[data-spcp-cookie-reject]')) {
				event.preventDefault();
				saveConsent(root, allConsent(false));
			} else if (target.matches('[data-spcp-cookie-save]')) {
				event.preventDefault();
				saveConsent(root, consentFromInputs(root));
			}
		});

		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape') {
				if (root.querySelector('[data-spcp-accessibility-modal]:not([hidden])')) {
					closeAccessibilityModal(root);
				} else if (root.querySelector('[data-spcp-privacy-modal]:not([hidden])')) {
					closePrivacyModal(root);
				} else if (root.querySelector('[data-spcp-cookie-policy-modal]:not([hidden])')) {
					closePolicyModal(root);
				} else if (root.querySelector('[data-spcp-cookie-modal]:not([hidden])')) {
					closeModal(root);
				}
			}
			trapFocus(root, event);
		});
	}

	function initExternalOpeners() {
		document.addEventListener('click', function (event) {
			var opener = event.target.closest('[data-spcp-cookie-open], [data-spcp-privacy-open], [data-spcp-cookie-policy-open], [data-spcp-accessibility-open], [data-cc-open-consent], .cc-cookie-link, a[href="#cmplz-manage-consent-container"]');
			var root = document.querySelector('[data-spcp-cookie-root]');
			if (!opener || !root || root.contains(opener)) {
				return;
			}
			event.preventDefault();
			if (opener.matches('[data-spcp-privacy-open]')) {
				openPrivacyModal(root);
			} else if (opener.matches('[data-spcp-cookie-policy-open]')) {
				openPolicyModal(root);
			} else if (opener.matches('[data-spcp-accessibility-open]')) {
				openAccessibilityModal(root);
			} else {
				openPreferences(root);
			}
		});
	}

	window.SimplePrivacyCookiePolicy = {
		get: function () {
			return currentConsent();
		},
		has: function (category) {
			var consent = currentConsent();
			return !!(consent && consent[category]);
		},
		open: function () {
			var root = document.querySelector('[data-spcp-cookie-root]');
			if (root) {
				openPreferences(root);
			}
		},
		showBanner: function () {
			var root = document.querySelector('[data-spcp-cookie-root]');
			if (root) {
				showBanner(root);
			}
		},
		openPolicy: function () {
			var root = document.querySelector('[data-spcp-cookie-root]');
			if (root) {
				openPolicyModal(root);
			}
		},
		openPrivacy: function () {
			var root = document.querySelector('[data-spcp-cookie-root]');
			if (root) {
				openPrivacyModal(root);
			}
		},
		openAccessibility: function () {
			var root = document.querySelector('[data-spcp-cookie-root]');
			if (root) {
				openAccessibilityModal(root);
			}
		},
		acceptAll: function () {
			var root = document.querySelector('[data-spcp-cookie-root]');
			if (root) {
				saveConsent(root, allConsent(true));
			}
		},
		rejectAll: function () {
			var root = document.querySelector('[data-spcp-cookie-root]');
			if (root) {
				saveConsent(root, allConsent(false));
			}
		}
	};

	function installCompatibilityApi() {
		window.codycloudOpenConsentPreferences = function () {
			window.SimplePrivacyCookiePolicy.open();
		};

		if (typeof window.show_cookie_banner !== 'function') {
			window.show_cookie_banner = function () {
				var root = document.querySelector('[data-spcp-cookie-root]');
				if (root) {
					showBanner(root);
					openPreferences(root);
				}
			};
		}

		if (typeof window.cmplz_set_banner_status !== 'function') {
			window.cmplz_set_banner_status = function (status) {
				var root = document.querySelector('[data-spcp-cookie-root]');
				if (!root || status !== 'show') {
					return;
				}
				showBanner(root);
				openPreferences(root);
			};
		}
	}

	installCompatibilityApi();
	window.addEventListener('simplePrivacyCookieConsentChanged', function (event) {
		syncFacebookEmbeds(event.detail || currentConsent() || allConsent(false));
	});
	window.addEventListener('pageshow', syncFacebookEmbedsFromStoredConsent);
	document.addEventListener('DOMContentLoaded', function () {
		var root = document.querySelector('[data-spcp-cookie-root]');
		if (root) {
			initRoot(root);
		} else {
			syncFacebookEmbedsFromStoredConsent();
			scheduleFacebookEmbedsSync(250);
			scheduleFacebookEmbedsSync(1500);
		}
		initExternalOpeners();
		installCompatibilityApi();
	});
}());
