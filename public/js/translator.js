document.addEventListener('DOMContentLoaded', function () {
    var BATCH_SIZE = 25;
    var FETCH_TIMEOUT_MS = 25000;
    var CACHE_KEY = 'eco_trip_lang';
    var SUPPORTED = ['fr', 'en', 'es'];
    var defaultLang = 'fr';
    var MAX_NODES = 80;
    var isTranslating = false;
    var translatingBanner = null;

    var langText = document.getElementById('current-lang-text');
    var langDropdown = document.getElementById('langDropdown');
    var langDropdownMenu = document.getElementById('langDropdownMenu');

    var path = (window.location.pathname || '/').replace(/\/+$/, '') || '/';
    var isHomePage = path === '' || path === '/';

    // French = default, no API. Read saved lang from localStorage or from ?lang= on first load.
    var currentLang = defaultLang;
    try {
        var saved = localStorage.getItem(CACHE_KEY);
        if (saved && SUPPORTED.indexOf(saved) !== -1) currentLang = saved;
    } catch (err) {}
    if (isHomePage && window.location.search) {
        var m = window.location.search.match(/[?&]lang=([a-z]{2})/i);
        if (m && SUPPORTED.indexOf(m[1].toLowerCase()) !== -1) {
            currentLang = m[1].toLowerCase();
            try { localStorage.setItem(CACHE_KEY, currentLang); } catch (err) {}
        }
    }
    updateLangUI(currentLang);

    // If user had chosen English or Spanish, translate this page (any page). French = never call API.
    if (currentLang !== defaultLang) {
        setTimeout(function () { runTranslationInBackground(defaultLang, currentLang); }, 200);
    }

    var langSelector = document.getElementById('langSelector');
    if (langSelector) {
        langSelector.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (langDropdown) {
                langDropdown.classList.toggle('show');
                if (langDropdownMenu) langDropdownMenu.classList.toggle('show');
            }
            return false;
        });
    }

    document.addEventListener('click', function (e) {
        if (langDropdown && !langDropdown.contains(e.target)) {
            langDropdown.classList.remove('show');
            if (langDropdownMenu) langDropdownMenu.classList.remove('show');
        }
    });

    document.addEventListener('click', function (e) {
        var clickEl = e.target && e.target.nodeType === 1 ? e.target : (e.target && e.target.parentElement) || null;
        var opt = clickEl && clickEl.closest ? clickEl.closest('.lang-opt') : null;
        if (!opt) return;

        e.preventDefault();
        e.stopPropagation();

        var newLang = opt.getAttribute('data-lang');
        if (!newLang || newLang === currentLang) {
            closeDropdown();
            return false;
        }

        var oldLang = currentLang;
        currentLang = newLang;
        try { localStorage.setItem(CACHE_KEY, currentLang); } catch (err) {}
        updateLangUI(currentLang);
        closeDropdown();

        // French = reload to show French content, no API
        if (currentLang === defaultLang) {
            window.location.href = path === '' || path === '/' ? '/' : window.location.pathname;
            return false;
        }

        // English or Spanish = translate current page via API (navbar + body)
        runTranslationInBackground(oldLang, currentLang);
        return false;
    });

    function closeDropdown() {
        if (langDropdown) langDropdown.classList.remove('show');
        if (langDropdownMenu) langDropdownMenu.classList.remove('show');
    }

    function updateLangUI(lang) {
        if (langText) langText.textContent = lang.toUpperCase();
        var opts = document.querySelectorAll('.lang-opt');
        for (var i = 0; i < opts.length; i++) {
            var o = opts[i];
            if (o.getAttribute('data-lang') === lang) {
                o.classList.add('active');
                o.setAttribute('aria-current', 'true');
            } else {
                o.classList.remove('active');
                o.removeAttribute('aria-current');
            }
        }
    }

    function showTranslatingBanner() {
        hideTranslatingBanner();
        translatingBanner = document.createElement('div');
        translatingBanner.id = 'ecotrip-translating-banner';
        translatingBanner.setAttribute('role', 'status');
        translatingBanner.style.cssText = 'position:fixed;top:0;left:0;right:0;z-index:99999;background:linear-gradient(135deg,#2d5016,#1e4620);color:#fff;padding:10px 20px;text-align:center;font-weight:600;font-size:0.9rem;box-shadow:0 4px 12px rgba(0,0,0,0.2);';
        translatingBanner.textContent = 'Traduction en cours...';
        document.body.appendChild(translatingBanner);
    }
    function hideTranslatingBanner() {
        var el = document.getElementById('ecotrip-translating-banner');
        if (el && el.parentNode) el.parentNode.removeChild(el);
        translatingBanner = null;
    }

    function runTranslationInBackground(source, target) {
        if (target === defaultLang) return; // French = no API
        if (isTranslating) return;
        isTranslating = true;
        showTranslatingBanner();
        translatePage(source, target)
            .then(function () { isTranslating = false; hideTranslatingBanner(); })
            .catch(function (err) {
                isTranslating = false;
                hideTranslatingBanner();
                var msg = 'La traduction est temporairement indisponible. Vous pouvez réessayer plus tard ou continuer en français.';
                if (typeof window.showToast === 'function') window.showToast(msg, 'error');
            });
    }

    function translatePage(source, target) {
        var root = document.body;
        var nodes = [];
        var walker = document.createTreeWalker(
            root,
            NodeFilter.SHOW_TEXT,
            {
                acceptNode: function (node) {
                    var parent = node.parentElement;
                    if (!parent) return NodeFilter.FILTER_REJECT;
                    var tag = parent.tagName ? parent.tagName.toLowerCase() : '';
                    var ignored = ['script', 'style', 'noscript', 'canvas', 'code', 'pre', 'textarea'];
                    if (ignored.indexOf(tag) !== -1) return NodeFilter.FILTER_REJECT;
                    var t = node.textContent ? node.textContent.trim() : '';
                    if (t.length < 2) return NodeFilter.FILTER_REJECT;
                    if (parent.dataset && parent.dataset.translated === target) return NodeFilter.FILTER_REJECT;
                    if (nodes.length >= MAX_NODES) return NodeFilter.FILTER_REJECT;
                    return NodeFilter.FILTER_ACCEPT;
                }
            }
        );
        var n;
        while ((n = walker.nextNode())) nodes.push(n);

        if (nodes.length === 0) return Promise.resolve();

        var batchStart = 0;

        function processNextBatch() {
            if (batchStart >= nodes.length) return Promise.resolve();
            var batchNodes = nodes.slice(batchStart, batchStart + BATCH_SIZE);
            var batchTexts = batchNodes.map(function (node) { return node.textContent.trim(); });
            batchStart += BATCH_SIZE;

            var controller = new AbortController();
            var timeoutId = setTimeout(function () { controller.abort(); }, FETCH_TIMEOUT_MS);
            return fetch('/api/translate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ text: batchTexts, source: source, target: target }),
                signal: controller.signal
            })
                .then(function (r) {
                    clearTimeout(timeoutId);
                    if (!r.ok) return r.json().then(function (j) { throw new Error(j.error || 'API error'); }).catch(function (e) { if (e.message) throw e; throw new Error('API error'); });
                    return r.json();
                })
                .then(function (result) {
                    if (result.success && result.translations && Array.isArray(result.translations)) {
                        batchNodes.forEach(function (node, index) {
                            var translation = result.translations[index];
                            var original = batchTexts[index];
                            if (translation && node.textContent) {
                                if (node.textContent.trim() === original) {
                                    node.textContent = translation;
                                } else {
                                    node.textContent = node.textContent.replace(original, translation);
                                }
                                if (node.parentElement) node.parentElement.dataset.translated = target;
                            }
                        });
                    }
                })
                .then(function () { return processNextBatch(); });
        }
        return processNextBatch();
    }
});
