(function () {
	'use strict';

	var config = window.SimplePrivacyCookiePolicyConfig || {};
	var cookieName = config.cookieName || 'simple_privacy_cookie_consent';
	var categories = ['preferences', 'statistics', 'marketing'];
	var lastFocus = null;

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
			var input = root.querySelector('[data-lde-cookie-category="' + category + '"]');
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
			var input = root.querySelector('[data-lde-cookie-category="' + category + '"]');
			if (input) {
				input.checked = !!(consent && consent[category]);
			}
		});
	}

	function updateGoogleConsent(consent) {
		if (typeof window.gtag !== 'function') {
			return;
		}

		var marketing = consent.marketing ? 'granted' : 'denied';
		var statistics = consent.statistics ? 'granted' : 'denied';
		var preferences = consent.preferences ? 'granted' : 'denied';

		window.gtag('consent', 'update', {
			ad_storage: marketing,
			ad_user_data: marketing,
			ad_personalization: marketing,
			analytics_storage: statistics,
			functionality_storage: preferences,
			personalization_storage: preferences,
			security_storage: 'granted'
		});
	}

	function dispatchConsent(consent) {
		window.dispatchEvent(new CustomEvent('simplePrivacyCookieConsentChanged', {
			detail: consent
		}));
	}

	function activateBlockedScripts(consent) {
		var nodes = document.querySelectorAll('script[type="text/plain"][data-lde-consent]');
		nodes.forEach(function (node) {
			var category = node.getAttribute('data-lde-consent');
			if (!consent[category] || node.getAttribute('data-lde-loaded') === '1') {
				return;
			}

			var script = document.createElement('script');
			for (var i = 0; i < node.attributes.length; i++) {
				var attr = node.attributes[i];
				if (attr.name === 'type' || attr.name === 'data-lde-consent' || attr.name === 'data-lde-loaded') {
					continue;
				}
				if (attr.name === 'data-src') {
					script.setAttribute('src', attr.value);
				} else {
					script.setAttribute(attr.name, attr.value);
				}
			}
			script.text = node.text || node.textContent || '';
			node.setAttribute('data-lde-loaded', '1');
			node.parentNode.insertBefore(script, node.nextSibling);
		});
	}

	function saveConsent(root, consent) {
		writeCookie(consent);
		updateGoogleConsent(consent);
		activateBlockedScripts(consent);
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
		document.documentElement.classList.toggle('lde-cookie-modal-open', hasOpenModal(root));
	}

	function activeModal(root) {
		var selectors = [
			'[data-lde-accessibility-modal]',
			'[data-lde-privacy-modal]',
			'[data-lde-cookie-policy-modal]',
			'[data-lde-cookie-modal]'
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
		var modal = root.querySelector('[data-lde-cookie-modal]');
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
		syncInputs(root, currentConsent() || allConsent(false));
		openModal(root);
	}

	function closeModal(root) {
		var modal = root.querySelector('[data-lde-cookie-modal]');
		if (!modal) {
			return;
		}
		var restoreTo = lastFocus;
		if (restoreTo && modal.contains(restoreTo)) {
			restoreTo = root.querySelector('.lde-cookie__reopen');
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
			restoreTo = root.querySelector(openerSelector) || root.querySelector('.lde-cookie__reopen');
		}
		modal.hidden = true;
		syncModalLock(root);
		if (restoreTo && typeof restoreTo.focus === 'function') {
			restoreTo.focus();
		}
	}

	function openPrivacyModal(root) {
		openTemplateModal(root, '[data-lde-privacy-modal]', '[data-lde-privacy-body]', '[data-lde-privacy-template]');
	}

	function closePrivacyModal(root) {
		closeTemplateModal(root, '[data-lde-privacy-modal]', '[data-lde-privacy-open]');
	}

	function openPolicyModal(root) {
		openTemplateModal(root, '[data-lde-cookie-policy-modal]', '[data-lde-cookie-policy-body]', '[data-lde-cookie-policy-template]');
	}

	function closePolicyModal(root) {
		closeTemplateModal(root, '[data-lde-cookie-policy-modal]', '[data-lde-cookie-policy-open]');
	}

	function openAccessibilityModal(root) {
		openTemplateModal(root, '[data-lde-accessibility-modal]', '[data-lde-accessibility-body]', '[data-lde-accessibility-template]');
	}

	function closeAccessibilityModal(root) {
		closeTemplateModal(root, '[data-lde-accessibility-modal]', '[data-lde-accessibility-open]');
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
			hideBanner(root);
		}

		root.addEventListener('click', function (event) {
			var target = event.target.closest('button, a');
			if (!target) {
				return;
			}
			if (target.matches('[data-lde-privacy-open]')) {
				event.preventDefault();
				openPrivacyModal(root);
			} else if (target.matches('[data-lde-privacy-close]')) {
				event.preventDefault();
				closePrivacyModal(root);
			} else if (target.matches('[data-lde-cookie-policy-open]')) {
				event.preventDefault();
				openPolicyModal(root);
			} else if (target.matches('[data-lde-cookie-policy-close]')) {
				event.preventDefault();
				closePolicyModal(root);
			} else if (target.matches('[data-lde-accessibility-open]')) {
				event.preventDefault();
				openAccessibilityModal(root);
			} else if (target.matches('[data-lde-accessibility-close]')) {
				event.preventDefault();
				closeAccessibilityModal(root);
			} else if (target.matches('[data-lde-cookie-open]')) {
				event.preventDefault();
				openPreferences(root);
			} else if (target.matches('[data-lde-cookie-close]')) {
				event.preventDefault();
				closeModal(root);
			} else if (target.matches('[data-lde-cookie-accept]')) {
				event.preventDefault();
				saveConsent(root, allConsent(true));
			} else if (target.matches('[data-lde-cookie-reject]')) {
				event.preventDefault();
				saveConsent(root, allConsent(false));
			} else if (target.matches('[data-lde-cookie-save]')) {
				event.preventDefault();
				saveConsent(root, consentFromInputs(root));
			}
		});

		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape') {
				if (root.querySelector('[data-lde-accessibility-modal]:not([hidden])')) {
					closeAccessibilityModal(root);
				} else if (root.querySelector('[data-lde-privacy-modal]:not([hidden])')) {
					closePrivacyModal(root);
				} else if (root.querySelector('[data-lde-cookie-policy-modal]:not([hidden])')) {
					closePolicyModal(root);
				} else {
					closeModal(root);
				}
			}
			trapFocus(root, event);
		});
	}

	function initExternalOpeners() {
		document.addEventListener('click', function (event) {
			var opener = event.target.closest('[data-lde-cookie-open], [data-lde-privacy-open], [data-lde-cookie-policy-open], [data-lde-accessibility-open], [data-cc-open-consent], .cc-cookie-link, a[href="#cmplz-manage-consent-container"]');
			var root = document.querySelector('[data-lde-cookie-root]');
			if (!opener || !root || root.contains(opener)) {
				return;
			}
			event.preventDefault();
			if (opener.matches('[data-lde-privacy-open]')) {
				openPrivacyModal(root);
			} else if (opener.matches('[data-lde-cookie-policy-open]')) {
				openPolicyModal(root);
			} else if (opener.matches('[data-lde-accessibility-open]')) {
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
			var root = document.querySelector('[data-lde-cookie-root]');
			if (root) {
				openPreferences(root);
			}
		},
		showBanner: function () {
			var root = document.querySelector('[data-lde-cookie-root]');
			if (root) {
				showBanner(root);
			}
		},
		openPolicy: function () {
			var root = document.querySelector('[data-lde-cookie-root]');
			if (root) {
				openPolicyModal(root);
			}
		},
		openPrivacy: function () {
			var root = document.querySelector('[data-lde-cookie-root]');
			if (root) {
				openPrivacyModal(root);
			}
		},
		openAccessibility: function () {
			var root = document.querySelector('[data-lde-cookie-root]');
			if (root) {
				openAccessibilityModal(root);
			}
		},
		acceptAll: function () {
			var root = document.querySelector('[data-lde-cookie-root]');
			if (root) {
				saveConsent(root, allConsent(true));
			}
		},
		rejectAll: function () {
			var root = document.querySelector('[data-lde-cookie-root]');
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
				var root = document.querySelector('[data-lde-cookie-root]');
				if (root) {
					showBanner(root);
					openPreferences(root);
				}
			};
		}

		if (typeof window.cmplz_set_banner_status !== 'function') {
			window.cmplz_set_banner_status = function (status) {
				var root = document.querySelector('[data-lde-cookie-root]');
				if (!root || status !== 'show') {
					return;
				}
				showBanner(root);
				openPreferences(root);
			};
		}
	}

	installCompatibilityApi();
	document.addEventListener('DOMContentLoaded', function () {
		var root = document.querySelector('[data-lde-cookie-root]');
		if (root) {
			initRoot(root);
		}
		initExternalOpeners();
		installCompatibilityApi();
	});
}());
