(function () {
	'use strict';

	var fields = {
		visual_bg: '--spcp-bg',
		visual_border: '--spcp-border',
		visual_text: '--spcp-text',
		visual_muted: '--spcp-muted',
		visual_link: '--spcp-link',
		visual_primary_bg: '--spcp-primary-bg',
		visual_primary_text: '--spcp-primary-text',
		visual_secondary_bg: '--spcp-secondary-bg',
		visual_secondary_border: '--spcp-secondary-border',
		visual_focus: '--spcp-focus'
	};

	function hexToRgb(hex) {
		var clean = String(hex || '').replace('#', '');
		if (clean.length === 3) {
			clean = clean.charAt(0) + clean.charAt(0) + clean.charAt(1) + clean.charAt(1) + clean.charAt(2) + clean.charAt(2);
		}
		if (!/^[0-9a-f]{6}$/i.test(clean)) {
			return '0, 0, 0';
		}
		return [
			parseInt(clean.slice(0, 2), 16),
			parseInt(clean.slice(2, 4), 16),
			parseInt(clean.slice(4, 6), 16)
		].join(', ');
	}

	function activeTemplate(editor) {
		var selected = editor.querySelector('[data-spcp-template]:checked');
		return selected ? selected.value : 'glass';
	}

	function setPreviewClass(preview, template) {
		preview.className = preview.className.replace(/\bspcp-live-preview--[a-z0-9_-]+\b/g, '').trim();
		preview.classList.add('spcp-live-preview--' + template);
	}

	function syncPreview(editor) {
		var preview = editor.querySelector('[data-spcp-preview]');
		if (!preview) {
			return;
		}

		setPreviewClass(preview, activeTemplate(editor));
		Object.keys(fields).forEach(function (key) {
			var input = editor.querySelector('[data-spcp-color="' + key + '"]');
			if (!input) {
				return;
			}
			var cssVar = fields[key];
			var code = editor.querySelector('[data-spcp-color-code="' + key + '"]');
			preview.style.setProperty(cssVar, input.value);
			preview.style.setProperty(cssVar + '-rgb', hexToRgb(input.value));
			if (code) {
				code.textContent = input.value;
			}
		});
	}

	function applyTemplatePalette(editor) {
		var presets = window.SPCPVisualPresets || {};
		var template = activeTemplate(editor);
		var preset = presets[template] && presets[template].palette;
		if (!preset) {
			return;
		}

		Object.keys(fields).forEach(function (key) {
			var input = editor.querySelector('[data-spcp-color="' + key + '"]');
			if (input && preset[key]) {
				input.value = preset[key];
			}
		});
		syncPreview(editor);
	}

	document.addEventListener('DOMContentLoaded', function () {
		var editor = document.querySelector('[data-spcp-visual-editor]');
		if (!editor) {
			return;
		}

		editor.addEventListener('input', function (event) {
			if (event.target.matches('[data-spcp-color]')) {
				syncPreview(editor);
			}
		});

		editor.addEventListener('change', function (event) {
			if (event.target.matches('[data-spcp-template]')) {
				applyTemplatePalette(editor);
			}
		});

		editor.addEventListener('click', function (event) {
			if (event.target.matches('[data-spcp-apply-template]')) {
				event.preventDefault();
				applyTemplatePalette(editor);
			}
		});

		syncPreview(editor);
	});
}());
