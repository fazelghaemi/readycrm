// Auto replace [data-icon] with inline SVG from assets/icons
(function(){
  function replace(el){
    var name = el.getAttribute('data-icon');
    if(!name) return;
    fetch('assets/icons/' + name + '.svg')
      .then(function(r){ return r.text(); })
      .then(function(svg){
        // پاک‌سازی مقدماتی
        svg = svg.replace(/<\?xml[^>]*>\s*/ig,'').replace(/<!DOCTYPE[^>]*>\s*/ig,'');
        // همه‌ی رنگ‌ها به currentColor (به جز none)
        svg = svg.replace(/fill\s*=\s*"(?!none)(#[0-9a-fA-F]{3,6}|rgb\([^)]+\)|[^"]+)"/ig,'fill="currentColor"');
        svg = svg.replace(/stroke\s*=\s*"(?!none)(#[0-9a-fA-F]{3,6}|rgb\([^)]+\)|[^"]+)"/ig,'stroke="currentColor"');
        // عرض/ارتفاع داخلی حذف شود
        svg = svg.replace(/\s(width|height)\s*=\s*"[^"]*"/ig,'');
        el.innerHTML = svg;
        el.classList.add('rs-icon');
        el.removeAttribute('data-icon');
      }).catch(function(){ /* silent */ });
  }
  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('[data-icon]').forEach(replace);
  });
})();
