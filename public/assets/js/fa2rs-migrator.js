// ReadyCRM – FA to RS migrator (final v1.0)
(function(){
  const MAP = {
    'fa-user':'user','fa-users':'users','fa-home':'home','fa-house':'home',
    'fa-cog':'settings','fa-gear':'settings','fa-gear-alt':'settings',
    'fa-bell':'bell','fa-bell-o':'bell','fa-chart-bar':'chart','fa-chart-line':'chart',
    'fa-file':'file','fa-envelope':'mail','fa-paper-plane':'send','fa-download':'download',
    'fa-upload':'upload','fa-trash':'trash','fa-edit':'edit','fa-pen':'edit','fa-check':'check',
    'fa-times':'close','fa-xmark':'close','fa-search':'search','fa-phone':'phone','fa-tag':'tag',
    'fa-tags':'tags','fa-calendar':'calendar','fa-clock':'clock','fa-lock':'lock','fa-unlock':'unlock',
    'fa-warning':'warning','fa-exclamation-triangle':'warning','fa-info-circle':'info','fa-question-circle':'help'
  };

  function defaultSizePx(){ return 20; }

  function convertEl(el){
    if (el.hasAttribute('data-icon')) return;
    const cl = Array.from(el.classList);
    const iconClass = cl.find(c => /^fa\-/.test(c) && c !== 'fa' && !/^fa\-(solid|regular|light|brands)$/.test(c));
    if (!iconClass) return;

    const faKey = iconClass; // e.g., fa-user
    const name = MAP[faKey] || faKey.replace(/^fa\-/, '');

    // strip FA classes
    cl.forEach(c => { if (c.startsWith('fa')) el.classList.remove(c); });

    // apply RS data-* API
    el.setAttribute('data-icon', name);
    if (!el.hasAttribute('data-size')) el.setAttribute('data-size', String(defaultSizePx()));
  }

  function scan(root){
    (root || document).querySelectorAll('i[class*="fa-"], span[class*="fa-"]').forEach(convertEl);
  }

  document.addEventListener('DOMContentLoaded', function(){
    scan(document);
    const obs = new MutationObserver(muts=>{
      muts.forEach(m=>{
        m.addedNodes && m.addedNodes.forEach(n=>{ if (n.nodeType===1) scan(n); });
      });
    });
    obs.observe(document.documentElement, {childList:true, subtree:true});
  });
})();
