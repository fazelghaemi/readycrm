// ReadyCRM Icon Auto-Replacer (v2)
// - Replaces <i data-icon="name" data-size="24" data-class="..."></i> with inline SVG
// - Observes DOM mutations to handle late-rendered nodes (AJAX, SPA fragments)
// - Enforces currentColor, strips width/height, sanitizes basicly

(function () {
  var ICON_BASE = '/assets/icons/'; // relative to web root
  var processed = new WeakSet();

  function sanitizeSVG(svg) {
    svg = svg.replace(/<\?xml[^>]*>\s*/ig, '');
    svg = svg.replace(/<!DOCTYPE[^>]*>\s*/ig, '');
    // strip scripts and event handlers
    svg = svg.replace(/<script\b[^>]*>.*?<\/script>/igs, '');
    svg = svg.replace(/\son[a-z]+\s*=\s*"[^"]*"/ig, '');
    // colors -> currentColor (except none)
    svg = svg.replace(/fill\s*=\s*"(?!none)(#[0-9a-fA-F]{3,6}|rgb\([^)]+\)|[^"]+)"/ig, 'fill="currentColor"');
    svg = svg.replace(/stroke\s*=\s*"(?!none)(#[0-9a-fA-F]{3,6}|rgb\([^)]+\)|[^"]+)"/ig, 'stroke="currentColor"');
    // remove width/height
    svg = svg.replace(/\s(width|height)\s*=\s*"[^"]*"/ig, '');
    // ensure viewBox
    if (!/viewBox\s*=\s*"[0-9\.\s\-]+"/i.test(svg)) {
      svg = svg.replace(/<svg\b/i, '<svg viewBox="0 0 24 24"', 1);
    }
    // ensure xmlns
    if (!/\sxmlns=/.test(svg)) {
      svg = svg.replace(/<svg\b/i, '<svg xmlns="http://www.w3.org/2000/svg"', 1);
    }
    return svg.trim();
  }

  function sizeToPx(size) {
    var map = { xxs:12, xs:14, sm:16, md:24, lg:32, xl:40, '2xl':48 };
    if (!size) return 20;
    if (/^\d+$/.test(size)) return Math.max(8, parseInt(size, 10));
    size = String(size).toLowerCase();
    return map[size] || 20;
  }

  function applyIcon(el) {
    if (!el || processed.has(el)) return;
    var name = el.getAttribute('data-icon');
    if (!name) return;

    var extraClass = (el.getAttribute('data-class') || '').replace(/[^A-Za-z0-9\-\_\s]/g, '').trim();
    var sizeAttr = el.getAttribute('data-size');
    var px = sizeToPx(sizeAttr);

    fetch(ICON_BASE + name + '.svg', { cache: 'force-cache' })
      .then(function (r) { return r.ok ? r.text() : ''; })
      .then(function (raw) {
        if (!raw) return;
        var svg = sanitizeSVG(raw);

        // wrapper classes
        el.classList.add('rs-icon', 'rs-' + px);
        if (extraClass) extraClass.split(/\s+/).forEach(function(c){ el.classList.add(c); });

        // ARIA: if no aria-label, hide from a11y tree
        if (!el.hasAttribute('aria-label')) {
          el.setAttribute('aria-hidden', 'true');
          el.setAttribute('role', 'img');
        } else {
          el.setAttribute('role', 'img');
        }

        // set fixed box size to avoid layout shifts
        el.style.width = px + 'px';
        el.style.height = px + 'px';

        el.innerHTML = svg;
        el.removeAttribute('data-icon');
        processed.add(el);
      })
      .catch(function(){ /* silent */ });
  }

  function scan(root) {
    (root || document).querySelectorAll('[data-icon]').forEach(applyIcon);
  }

  document.addEventListener('DOMContentLoaded', function () {
    scan(document);
    // observe DOM for dynamic content
    var obs = new MutationObserver(function (mutations) {
      mutations.forEach(function (m) {
        if (m.addedNodes) {
          m.addedNodes.forEach(function (n) {
            if (n.nodeType === 1) {
              if (n.hasAttribute && n.hasAttribute('data-icon')) applyIcon(n);
              scan(n);
            }
          });
        }
      });
    });
    obs.observe(document.documentElement, { childList: true, subtree: true });
  });
})();
