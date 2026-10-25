/**
 * @file plugins/generic/zytekAccessibility/js/zytekAccessibility.js
 *
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @brief Accessibility panel: opens with the floating button or Alt+Shift+A.
 *  Each tile turns a tool on, moves it to its next step or turns it off; the
 *  reader's choices become classes on <html> and are stored in this browser
 *  only (localStorage). No dependencies, no network requests.
 */
(function() {
	'use strict';

	// PKP prints frontend scripts before the footer hook output, so start once the
	// whole document (including the panel markup) has been parsed.
	var init = function() {
		var root = document.getElementById('zytekA11y');
		if (!root) {
			return;
		}

		// Themes print the footer hook inside their own wrappers; a filter or
		// transform there would trap the fixed panel, so it lives on <body>.
		if (root.parentNode !== document.body) {
			document.body.appendChild(root);
		}

		var STORAGE_KEY = 'zytekA11y';
		var HIDDEN_KEY = 'zytekA11yHidden';
		var ACTIONS = ['pageStructure'];
		var READ_RATES = {normal: 1, slow: 0.8, fast: 1.25};
		var html = document.documentElement;
		var readJson = function(name) {
			return JSON.parse(root.getAttribute('data-' + name) || '{}');
		};
		var features = readJson('features');
		var levels = readJson('levels');
		var scales = readJson('scales');
		var strings = readJson('strings');
		var panel = root.querySelector('.zytek-a11y__panel');
		var openButton = root.querySelector('[data-a11y-open]');
		var announcer = root.querySelector('[data-a11y-announce]');

		var has = function(feature) {
			return features.indexOf(feature) !== -1;
		};
		var format = function(text, values) {
			return String(text || '').replace(/\{\$(\w+)\}/g, function(m, key) {
				return values[key] !== undefined ? values[key] : m;
			});
		};
		var announce = function(text) {
			announcer.textContent = '';
			window.setTimeout(function() { announcer.textContent = text; }, 60);
		};
		var tileFor = function(feature) {
			return root.querySelector('[data-a11y-feature="' + feature + '"]');
		};

		// --- Preferences ------------------------------------------------------

		// Values from version 1.0 (text size 0-5, contrast "off"/"mono") become
		// the current steps, so returning readers keep their choices.
		var migrate = function(prefs) {
			if (typeof prefs.textSize === 'number') {
				prefs.textSize = prefs.textSize > 0 ? String([0, 1, 1, 2, 3, 4][Math.min(5, prefs.textSize)]) : undefined;
			}
			if (prefs.contrast === 'mono') {
				prefs.grayscale = true;
			}
			if (prefs.contrast === 'off' || prefs.contrast === 'mono') {
				delete prefs.contrast;
			}
			return prefs;
		};
		var load = function() {
			try {
				return migrate(JSON.parse(window.localStorage.getItem(STORAGE_KEY) || '{}') || {});
			} catch (e) {
				return {};
			}
		};
		var prefs = load();
		var save = function() {
			try {
				window.localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
			} catch (e) {
				// Private mode or storage disabled: preferences last for this page only
			}
		};

		// Current step of a tool: a step name, true for on/off tools, or null
		var stateOf = function(feature) {
			var value = prefs[feature];
			if (levels[feature]) {
				return levels[feature].indexOf(value) !== -1 ? value : null;
			}
			return value ? true : null;
		};

		var stepLabel = function(feature, step) {
			if (feature === 'textSize') {
				return format(strings.textSize, {percent: Math.round(scales[step] * 100)});
			}
			return strings.levels && strings.levels[feature] ? strings.levels[feature][step] : '';
		};

		// --- Text size ----------------------------------------------------------

		// Font size of the theme before any scaling, so 200% means twice the theme's text
		var measureBaseFontSize = function() {
			var scaled = html.getAttribute('data-zytek-a11y-text');
			html.removeAttribute('data-zytek-a11y-text');
			var base = parseFloat(window.getComputedStyle(html).fontSize) || 16;
			if (scaled) html.setAttribute('data-zytek-a11y-text', scaled);
			return base;
		};

		// Elements holding text of their own (not the panel)
		var textElements = function() {
			return Array.prototype.filter.call(document.body.querySelectorAll('*'), function(el) {
				if (root.contains(el) || /^(SCRIPT|STYLE|NOSCRIPT|SVG|IMG|VIDEO|CANVAS|IFRAME|BR)$/i.test(el.tagName)) return false;
				for (var n = el.firstChild; n; n = n.nextSibling) {
					if (n.nodeType === 3 && n.nodeValue.trim()) return true;
				}
				return false;
			});
		};

		var undoPixelText = function() {
			var fixed = document.querySelectorAll('[data-zytek-a11y-fs]');
			for (var i = 0; i < fixed.length; i++) {
				fixed[i].style.removeProperty('font-size');
				fixed[i].style.removeProperty('line-height');
				fixed[i].removeAttribute('data-zytek-a11y-fs');
			}
		};

		// Themes that size text in px ignore the root font size. Measure every
		// text element without and with scaling, and correct the ones that did not
		// reach their target. Sizes are set from the unscaled measure, so nested
		// text is never enlarged twice.
		var LARGE_TEXT = 28;
		var scalePixelText = function(scale) {
			var elements = textElements();
			var level = html.getAttribute('data-zytek-a11y-text');
			html.removeAttribute('data-zytek-a11y-text');
			var before = elements.map(function(el) {
				var style = window.getComputedStyle(el);
				return {size: parseFloat(style.fontSize), line: style.lineHeight};
			});
			html.setAttribute('data-zytek-a11y-text', level);
			var after = elements.map(function(el) {
				return parseFloat(window.getComputedStyle(el).fontSize);
			});
			elements.forEach(function(el, i) {
				if (!before[i].size) return;
				// Display text that is already large grows half as much (as with
				// dynamic type on phones), so headings keep whole words on screen
				var target = before[i].size * (before[i].size >= LARGE_TEXT ? 1 + (scale - 1) / 2 : scale);
				if (Math.abs(after[i] - target) < target * 0.03) return;
				el.setAttribute('data-zytek-a11y-fs', '');
				el.style.setProperty('font-size', target.toFixed(2) + 'px', 'important');
				// Not !important: readers' own spacing styles must still win (WCAG 1.4.12)
				if (/px$/.test(before[i].line)) {
					el.style.setProperty('line-height', (parseFloat(before[i].line) / before[i].size).toFixed(3));
				}
			});
		};

		// URLs, DOIs and emails are single huge words: with larger text they may
		// break at any character (only these, so ordinary words stay whole)
		var longWordsMarked = false;
		var markLongWords = function() {
			if (longWordsMarked) return;
			longWordsMarked = true;
			textElements().forEach(function(el) {
				for (var n = el.firstChild; n; n = n.nextSibling) {
					if (n.nodeType === 3 && /\S{18,}/.test(n.nodeValue)) {
						el.classList.add('zytek-a11y-longword');
						return;
					}
				}
			});
		};

		var applyTextSize = function() {
			var step = stateOf('textSize');
			undoPixelText();
			if (!step) {
				html.removeAttribute('data-zytek-a11y-text');
				return;
			}
			html.style.setProperty('--zytek-a11y-base', prefs.base + 'px');
			html.style.setProperty('--zytek-a11y-scale', scales[step]);
			html.setAttribute('data-zytek-a11y-text', step);
			scalePixelText(scales[step]);
			markLongWords();
		};

		// --- Tools ----------------------------------------------------------------

		var setClasses = function(feature, state) {
			var classes = ['zytek-a11y-' + feature].concat((levels[feature] || []).map(function(step) {
				return 'zytek-a11y-' + feature + '-' + step;
			}));
			classes.forEach(function(name) {
				html.classList.remove(name);
			});
			if (state) {
				html.classList.add('zytek-a11y-' + feature);
				if (state !== true) html.classList.add('zytek-a11y-' + feature + '-' + state);
			}
		};

		var updateTile = function(feature, state) {
			var tile = tileFor(feature);
			if (!tile) return;
			tile.setAttribute('aria-pressed', state ? 'true' : 'false');
			var value = tile.querySelector('[data-a11y-value]');
			value.textContent = state && state !== true ? stepLabel(feature, state) : '';
			var steps = tile.querySelectorAll('.zytek-a11y__step');
			var reached = state && levels[feature] ? levels[feature].indexOf(state) : -1;
			for (var i = 0; i < steps.length; i++) {
				steps[i].classList.toggle('is-active', i <= reached);
			}
		};

		var applyFeature = function(feature) {
			var state = stateOf(feature);
			if (feature === 'textSize') {
				applyTextSize();
			} else {
				setClasses(feature, state);
			}
			if (feature === 'stopAnimations' && state) pauseAllMedia();
			if (feature === 'readingGuide') {
				guide.hidden = !state;
				// Start in the upper third until the pointer or focus moves it
				if (state) moveGuide(window.innerHeight / 3);
			}
			if (feature === 'tooltips' && !state) hideTip();
			if (feature === 'readAloud') {
				if (!state) stopReading();
				if (player) player.hidden = !state;
			}
			updateTile(feature, state);
		};

		var applyAll = function() {
			features.forEach(function(feature) {
				if (ACTIONS.indexOf(feature) === -1) applyFeature(feature);
			});
		};

		// Off -> first step -> ... -> last step -> off
		var nextState = function(feature) {
			var state = stateOf(feature);
			if (!levels[feature]) return state ? undefined : true;
			var steps = levels[feature];
			var index = steps.indexOf(state);
			return index + 1 < steps.length ? steps[index + 1] : undefined;
		};

		var cycle = function(feature) {
			var next = nextState(feature);
			if (next === undefined) {
				delete prefs[feature];
			} else {
				prefs[feature] = next;
			}
			applyFeature(feature);
			save();
			var tile = tileFor(feature);
			var label = tile.querySelector('.zytek-a11y__tile-label').textContent;
			announce(label + ': ' + (next === undefined ? strings.off : next === true ? strings.on : stepLabel(feature, next)));
		};

		// --- Panel --------------------------------------------------------------

		var langButton = root.querySelector('[data-a11y-lang]');
		var langMenu = root.querySelector('#zytekA11yLangMenu');
		var setLangMenu = function(open) {
			if (!langButton) return;
			langMenu.hidden = !open;
			langButton.setAttribute('aria-expanded', open ? 'true' : 'false');
		};

		var isOpen = function() {
			return !panel.hidden;
		};
		var openPanel = function() {
			panel.hidden = false;
			openButton.setAttribute('aria-expanded', 'true');
			var first = panel.querySelector('.zytek-a11y__tile');
			(first || panel).focus();
		};
		var closePanel = function(returnFocus) {
			setLangMenu(false);
			panel.hidden = true;
			openButton.setAttribute('aria-expanded', 'false');
			if (returnFocus) openButton.focus();
		};

		// Hiding the button lasts for this visit; Alt+Shift+A brings it back
		var buttonHidden = function() {
			return html.classList.contains('zytek-a11y-buttonHidden');
		};
		var setButtonHidden = function(hidden) {
			html.classList.toggle('zytek-a11y-buttonHidden', hidden);
			try {
				if (hidden) {
					window.sessionStorage.setItem(HIDDEN_KEY, '1');
				} else {
					window.sessionStorage.removeItem(HIDDEN_KEY);
				}
			} catch (e) {
				// Storage disabled: hidden for this page only
			}
		};

		openButton.addEventListener('click', function() {
			isOpen() ? closePanel(false) : openPanel();
		});
		root.querySelector('[data-a11y-close]').addEventListener('click', function() {
			closePanel(true);
		});
		root.querySelector('[data-a11y-hide]').addEventListener('click', function() {
			closePanel(false);
			setButtonHidden(true);
			announce(strings.hidden);
		});
		if (langButton) {
			langButton.addEventListener('click', function() {
				setLangMenu(langMenu.hidden);
				if (!langMenu.hidden) langMenu.querySelector('a').focus();
			});
		}
		document.addEventListener('keydown', function(e) {
			if (e.altKey && e.shiftKey && (e.code === 'KeyA' || e.key === 'A' || e.key === 'a')) {
				e.preventDefault();
				if (buttonHidden()) setButtonHidden(false);
				if (isOpen()) {
					closePanel(true);
				} else {
					openPanel();
				}
			} else if (e.key === 'Escape') {
				if (tipTarget) {
					hideTip();
				} else if (langButton && !langMenu.hidden && root.contains(document.activeElement)) {
					setLangMenu(false);
					langButton.focus();
				} else if (isOpen() && root.contains(document.activeElement)) {
					closePanel(true);
				} else if (reading) {
					stopReading();
				}
			}
		});
		document.addEventListener('click', function(e) {
			if (!root.contains(e.target)) {
				if (isOpen()) closePanel(false);
			} else if (langButton && !langButton.contains(e.target) && !langMenu.contains(e.target)) {
				setLangMenu(false);
			}
		});

		// --- Tiles and reset ----------------------------------------------------

		root.addEventListener('click', function(e) {
			var target = e.target.closest ? e.target.closest('button') : null;
			if (!target || !root.contains(target)) return;
			var feature = target.getAttribute('data-a11y-feature');
			if (feature === 'pageStructure') {
				toggleStructure();
			} else if (feature) {
				cycle(feature);
			} else if (target.hasAttribute('data-a11y-reset')) {
				stopReading();
				prefs = {base: prefs.base};
				applyAll();
				save();
				announce(strings.reset);
			}
		});

		// The report link carries the page the reader is on
		var reportLink = root.querySelector('[data-a11y-report]');
		if (reportLink) {
			var reportTo = reportLink.getAttribute('href');
			reportLink.addEventListener('click', function() {
				reportLink.setAttribute('href', reportTo + '?subject=' + encodeURIComponent(strings.reportSubject) +
					'&body=' + encodeURIComponent(format(strings.reportBody, {url: window.location.href})));
			});
		}

		// --- Stop animations: pause videos now and whenever one tries to play ----

		var pauseAllMedia = function() {
			var media = document.querySelectorAll('video, audio');
			for (var i = 0; i < media.length; i++) {
				if (root.contains(media[i])) continue;
				media[i].removeAttribute('autoplay');
				media[i].pause();
			}
		};
		document.addEventListener('play', function(e) {
			if (stateOf('stopAnimations') && has('stopAnimations') && e.target && e.target.pause) {
				e.target.pause();
			}
		}, true);

		// --- Reading guide: a horizontal band that follows the pointer or focus ---

		var guide = root.querySelector('[data-a11y-guide]');
		guide.hidden = true;
		var moveGuide = function(y) {
			guide.style.transform = 'translateY(' + Math.round(y - guide.offsetHeight / 2) + 'px)';
		};
		document.addEventListener('pointermove', function(e) {
			if (stateOf('readingGuide') && e.pointerType === 'mouse') moveGuide(e.clientY);
		}, {passive: true});
		document.addEventListener('focusin', function(e) {
			if (stateOf('readingGuide') && !root.contains(e.target) && e.target.getBoundingClientRect) {
				var rect = e.target.getBoundingClientRect();
				moveGuide(rect.top + rect.height / 2);
			}
		});

		// --- Tooltips: show title, image alt and button labels on hover or focus --

		var tooltip = root.querySelector('[data-a11y-tooltip]');
		var tipTarget = null;
		var tipTimer = null;
		var TIP_SELECTOR = '[title], img[alt], a[aria-label], button[aria-label], [role="button"][aria-label], input[aria-label], select[aria-label]';
		var tipText = function(el) {
			var text = el.getAttribute('data-zytek-a11y-title') || el.getAttribute('title') ||
				(el.tagName === 'IMG' ? el.getAttribute('alt') : '') || el.getAttribute('aria-label') || '';
			text = text.replace(/\s+/g, ' ').trim();
			// Nothing to add when the element already shows the same words
			return text && text !== el.textContent.replace(/\s+/g, ' ').trim() ? text : '';
		};
		var findTipTarget = function(node) {
			var el = node && node.closest ? node.closest(TIP_SELECTOR) : null;
			return el && !root.contains(el) && tipText(el) ? el : null;
		};
		var hideTip = function() {
			window.clearTimeout(tipTimer);
			if (tipTarget && tipTarget.hasAttribute('data-zytek-a11y-title')) {
				tipTarget.setAttribute('title', tipTarget.getAttribute('data-zytek-a11y-title'));
				tipTarget.removeAttribute('data-zytek-a11y-title');
			}
			tipTarget = null;
			tooltip.hidden = true;
		};
		var showTip = function(el) {
			hideTip();
			tooltip.textContent = tipText(el);
			// The browser's own tooltip would cover ours: park the title meanwhile
			if (el.hasAttribute('title')) {
				el.setAttribute('data-zytek-a11y-title', el.getAttribute('title'));
				el.removeAttribute('title');
			}
			tipTarget = el;
			tooltip.hidden = false;
			var rect = el.getBoundingClientRect();
			var tipRect = tooltip.getBoundingClientRect();
			var top = rect.bottom + 8;
			if (top + tipRect.height > window.innerHeight - 8) top = Math.max(8, rect.top - tipRect.height - 8);
			var left = Math.min(Math.max(8, rect.left), window.innerWidth - tipRect.width - 8);
			tooltip.style.top = Math.round(top) + 'px';
			tooltip.style.left = Math.round(Math.max(8, left)) + 'px';
		};
		var tipFrom = function(e) {
			if (!stateOf('tooltips')) return;
			var el = findTipTarget(e.target);
			if (el && el !== tipTarget) showTip(el);
			if (el) window.clearTimeout(tipTimer);
		};
		var tipLeave = function(e) {
			if (!tipTarget) return;
			var to = e.relatedTarget;
			if (to && (tipTarget.contains(to) || tooltip.contains(to))) return;
			// A short delay lets the pointer move onto the tooltip (WCAG 1.4.13)
			window.clearTimeout(tipTimer);
			tipTimer = window.setTimeout(hideTip, 300);
		};
		document.addEventListener('pointerover', tipFrom);
		document.addEventListener('focusin', tipFrom);
		document.addEventListener('pointerout', tipLeave);
		document.addEventListener('focusout', tipLeave);
		tooltip.addEventListener('pointerenter', function() { window.clearTimeout(tipTimer); });
		tooltip.addEventListener('pointerleave', function() { tipTimer = window.setTimeout(hideTip, 300); });

		// --- Page structure: landmarks and headings to jump to --------------------

		var structureTile = tileFor('pageStructure');
		var structureBox = root.querySelector('#zytekA11yStructure');
		var LANDMARK_SELECTOR = 'header, nav, main, aside, footer, [role="banner"], [role="navigation"], [role="main"], [role="complementary"], [role="contentinfo"], [role="search"]';
		var landmarkRole = function(el) {
			var role = el.getAttribute('role');
			if (role) return role;
			return {HEADER: 'banner', NAV: 'navigation', MAIN: 'main', ASIDE: 'complementary', FOOTER: 'contentinfo'}[el.tagName];
		};
		var visible = function(el) {
			return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
		};
		var jumpTo = function(el) {
			if (!el.hasAttribute('tabindex')) el.setAttribute('tabindex', '-1');
			closePanel(false);
			el.scrollIntoView({block: 'start'});
			el.focus({preventScroll: true});
		};
		var buildList = function(title, items) {
			var section = document.createElement('div');
			var heading = document.createElement('h4');
			heading.className = 'zytek-a11y__subtitle';
			heading.textContent = title;
			var list = document.createElement('ul');
			list.className = 'zytek-a11y__list';
			items.forEach(function(item) {
				var li = document.createElement('li');
				var button = document.createElement('button');
				button.type = 'button';
				button.className = 'zytek-a11y__jump' + (item.level ? ' zytek-a11y__jump--h' + item.level : '');
				button.textContent = item.label;
				button.addEventListener('click', function() { jumpTo(item.el); });
				li.appendChild(button);
				list.appendChild(li);
			});
			section.appendChild(heading);
			section.appendChild(list);
			return section;
		};
		var buildStructure = function() {
			structureBox.textContent = '';
			var seen = [];
			var landmarks = Array.prototype.filter.call(document.querySelectorAll(LANDMARK_SELECTOR), function(el) {
				if (root.contains(el) || !visible(el) || seen.indexOf(el) !== -1 || !landmarkRole(el)) return false;
				seen.push(el);
				// A <header>/<footer> inside an article or section is not a page region
				var isSectioningChild = (el.tagName === 'HEADER' || el.tagName === 'FOOTER') && !el.getAttribute('role') &&
					el.parentElement && el.parentElement.closest('article, aside, main, nav, section');
				return !isSectioningChild;
			}).map(function(el) {
				var name = el.getAttribute('aria-label') || '';
				var role = landmarkRole(el);
				return {el: el, label: (strings[role] || role) + (name ? ': ' + name : '')};
			});
			var headings = Array.prototype.filter.call(document.querySelectorAll('h1, h2, h3, h4'), function(el) {
				return !root.contains(el) && visible(el) && el.textContent.trim();
			}).map(function(el) {
				return {el: el, label: el.textContent.replace(/\s+/g, ' ').trim(), level: parseInt(el.tagName.charAt(1), 10)};
			});
			if (!landmarks.length && !headings.length) {
				structureBox.textContent = strings.structureEmpty;
				return;
			}
			if (landmarks.length) structureBox.appendChild(buildList(strings.landmarks, landmarks));
			if (headings.length) structureBox.appendChild(buildList(strings.headings, headings));
		};
		var toggleStructure = function() {
			var expand = structureBox.hidden;
			if (expand) buildStructure();
			structureBox.hidden = !expand;
			structureTile.setAttribute('aria-expanded', expand ? 'true' : 'false');
			if (expand) structureBox.scrollIntoView({block: 'nearest'});
		};

		// --- Read aloud (browser speech synthesis) --------------------------------
		// While the tool is on, a click on a paragraph, title or list item reads it,
		// selected text is read when the selection ends, and the player offers to
		// read the whole page. The step of the tile is the speed.

		var player = root.querySelector('[data-a11y-player]');
		var playButton = root.querySelector('[data-a11y-read-play]');
		var stopButton = root.querySelector('[data-a11y-read-stop]');
		var readStatus = root.querySelector('[data-a11y-read-status]');
		var synth = window.speechSynthesis;
		var canSpeak = !!(synth && window.SpeechSynthesisUtterance);
		var queue = [];
		var reading = false;
		var readingEl = null;
		var READABLE = 'p, li, h1, h2, h3, h4, h5, h6, dd, dt, td, th, figcaption, blockquote, label, a, button';

		// Rendered text of the main region: innerText skips hidden content and
		// keeps line breaks between blocks, which become pauses.
		var mainText = function() {
			var main = document.querySelector('main, [role="main"], .pkp_structure_main') || document.body;
			return (main.innerText || main.textContent || '').replace(/[ \t ]+/g, ' ').trim();
		};
		var MAX_CHUNK = 220;
		// Short utterances: some engines stop after ~15 s of continuous speech
		var chunk = function(text) {
			// Punctuation ends a piece only before a space or the end, so "2.1",
			// "3.4.0" or "www.upec.edu.ec" are never read as two sentences
			var pieces = text.match(/(?:[^.!?;:\n]|[.!?;:](?!\s|$))+[.!?;:]*|\n+/g) || [text];
			var chunks = [];
			var current = '';
			var push = function() {
				if (current.trim()) chunks.push(current.trim());
				current = '';
			};
			pieces.forEach(function(piece) {
				if (/^\n+$/.test(piece)) {
					push();
					return;
				}
				// A piece with no punctuation (lists, titles) is split by words
				while (piece.length > MAX_CHUNK) {
					var cut = piece.lastIndexOf(' ', MAX_CHUNK);
					if (cut <= 0) cut = MAX_CHUNK;
					push();
					chunks.push(piece.slice(0, cut).trim());
					piece = piece.slice(cut);
				}
				if ((current + piece).length > MAX_CHUNK) push();
				current += piece;
			});
			push();
			return chunks;
		};
		var pickVoice = function() {
			var lang = (html.getAttribute('lang') || 'es').toLowerCase();
			var voices = synth.getVoices();
			return voices.filter(function(v) { return v.lang.toLowerCase() === lang; })[0] ||
				voices.filter(function(v) { return v.lang.toLowerCase().indexOf(lang.split('-')[0]) === 0; })[0] || null;
		};
		var markReading = function(el) {
			if (readingEl) readingEl.classList.remove('zytek-a11y-reading');
			readingEl = el;
			if (el) el.classList.add('zytek-a11y-reading');
		};
		var setReadState = function(state) {
			reading = state !== 'idle';
			if (!playButton) return;
			playButton.textContent = state === 'playing' ? strings.pause : state === 'paused' ? strings.resume : strings.readPage;
			playButton.setAttribute('aria-pressed', state === 'playing' ? 'true' : 'false');
			stopButton.disabled = state === 'idle';
			if (state === 'idle') markReading(null);
		};
		var speakNext = function() {
			if (!queue.length) {
				setReadState('idle');
				return;
			}
			var utterance = new window.SpeechSynthesisUtterance(queue.shift());
			utterance.lang = html.getAttribute('lang') || 'es';
			utterance.rate = READ_RATES[stateOf('readAloud')] || 1;
			var voice = pickVoice();
			if (voice) utterance.voice = voice;
			utterance.onend = function() { if (reading && !synth.paused) speakNext(); };
			utterance.onerror = function() { setReadState('idle'); };
			synth.speak(utterance);
		};
		var speak = function(text, el) {
			if (!canSpeak || !text) return;
			queue = chunk(text);
			synth.cancel();
			setReadState('playing');
			markReading(el || null);
			speakNext();
		};
		var stopReading = function() {
			if (!canSpeak) return;
			queue = [];
			synth.cancel();
			setReadState('idle');
		};
		if (player) {
			if (!canSpeak) {
				playButton.disabled = true;
				readStatus.textContent = strings.unsupported;
			} else {
				readStatus.textContent = strings.readHint;
				playButton.addEventListener('click', function() {
					if (reading && !synth.paused) {
						synth.pause();
						setReadState('paused');
					} else if (reading && synth.paused) {
						synth.resume();
						setReadState('playing');
					} else {
						speak(mainText(), null);
					}
				});
				stopButton.addEventListener('click', stopReading);
				window.addEventListener('pagehide', stopReading);
			}
		}
		var selectedText = function() {
			var selection = window.getSelection ? window.getSelection() : null;
			if (!selection || !selection.rangeCount || root.contains(selection.anchorNode)) return '';
			return selection.toString().trim();
		};
		document.addEventListener('mouseup', function(e) {
			if (!stateOf('readAloud') || root.contains(e.target)) return;
			var selection = selectedText();
			if (selection) {
				speak(selection, null);
				return;
			}
			var block = e.target.closest ? e.target.closest(READABLE) : null;
			if (block && !root.contains(block)) speak(block.innerText || block.textContent, block);
		});
		document.addEventListener('keyup', function(e) {
			// Text selected with the keyboard (Shift + arrows)
			if (stateOf('readAloud') && e.shiftKey && /^Arrow|^Home$|^End$/.test(e.key)) {
				var selection = selectedText();
				if (selection) speak(selection, null);
			}
		});

		// --- Statement link in the footer ------------------------------------------

		// The theme's link colour may not suit the journal's footer background
		// (a dark band, for instance). Any solid colour reaches 4.5:1 with white
		// or with near-black, so the better of the two is used only when needed.
		var rgbOf = function(colour) {
			var m = colour.match(/[\d.]+/g);
			return m && (m.length < 4 || parseFloat(m[3]) > 0) ? m.slice(0, 3).map(Number) : null;
		};
		var luminance = function(rgb) {
			return [0.2126, 0.7152, 0.0722].reduce(function(sum, weight, i) {
				var c = rgb[i] / 255;
				return sum + weight * (c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4));
			}, 0);
		};
		var contrast = function(a, b) {
			var la = luminance(a);
			var lb = luminance(b);
			return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05);
		};
		var ensureReadable = function(el) {
			var bg = null;
			for (var node = el; node && !bg; node = node.parentElement) {
				bg = rgbOf(window.getComputedStyle(node).backgroundColor);
			}
			bg = bg || [255, 255, 255];
			var fg = rgbOf(window.getComputedStyle(el).color);
			if (fg && contrast(fg, bg) >= 4.5) return;
			el.style.color = contrast([255, 255, 255], bg) >= contrast([17, 17, 17], bg) ? '#fff' : '#111';
		};

		if (root.getAttribute('data-footer-link') === '1') {
			var url = root.getAttribute('data-statement-url');
			var menu = document.querySelector('#navigationFooter, .zytek_footer_menu');
			var link = document.createElement('a');
			link.href = url;
			link.textContent = strings.footerLink;
			if (menu && menu.tagName === 'UL') {
				var item = document.createElement('li');
				item.appendChild(link);
				menu.appendChild(item);
			} else {
				// Most specific first: the footer content block keeps the colours the
				// journal chose for its footer text (the outer wrapper may be a coloured band)
				var footer = null;
				['.pkp_footer_content', '.pkp_structure_footer', 'footer[role="contentinfo"]', '[role="contentinfo"]'].some(function(selector) {
					footer = document.querySelector(selector);
					return footer;
				});
				if (footer) {
					var p = document.createElement('p');
					p.className = 'zytek-a11y-footer-link';
					p.appendChild(link);
					footer.appendChild(p);
					ensureReadable(link);
				}
			}
		}

		// --- Start -------------------------------------------------------------------

		if (!prefs.base) prefs.base = measureBaseFontSize();
		applyAll();
		save();
		root.hidden = false;
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
