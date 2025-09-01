
(function(){
  function esc(s){return s.replace(/[&<>]/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;'}[m]));}
  function wrap(re, cls, s){return s.replace(re, m=>'<span class="'+cls+'">'+m+'</span>');}
  function highlight(code, lang){
    var s=esc(code);
    s=wrap(/(\"(?:\\.|[^"])*\"|'(?:\\.|[^'])*')/g, 'tok-string', s);
    if(lang==='php'||lang==='javascript'||lang==='typescript'||lang==='c'||lang==='cpp'||lang==='java'||lang==='go'){
      s=wrap(/(\/\/.*?$|\/\*[\س\S]*?\*\/)/gm, 'tok-comment', s);
      s=wrap(/\b(class|function|return|if|else|elseif|for|foreach|while|switch|case|break|continue|new|try|catch|finally|throw|extends|implements|public|private|protected|static|var|let|const|import|export|namespace|use)\b/g, 'tok-keyword', s);
      s=wrap(/\b(\d+(\.\d+)?)\b/g, 'tok-number', s); return s;
    }
    if(lang==='css'){ s=wrap(/\/\*[\س\S]*?\*\//g, 'tok-comment', s); s=wrap(/\b(\d+(\.\d+)?)(px|rem|em|%)\b/g, 'tok-number', s); return s; }
    if(lang==='html'||lang==='xml'){
      s=s.replace(/(&lt;\/?)([a-zA-Z0-9\-]+)([^&]*?)(&gt;)/g,function(_,a,tag,attrs,b){
        attrs=attrs.replace(/([a-zA-Z\-:]+)(=)("[^"]*"|'[^']*')/g,'<span class="tok-attr">$1</span>$2<span class="tok-string">$3</span>');
        return '<span class="tok-tag">'+a+tag+'</span>'+attrs+'<span class="tok-tag">'+b+'</span>';
      }); return s;
    }
    if(lang==='sql'){ s=wrap(/\b(SELECT|INSERT|UPDATE|DELETE|FROM|WHERE|AND|OR|GROUP BY|ORDER BY|JOIN|LEFT|RIGHT|INNER|OUTER|ON|LIMIT|OFFSET|VALUES|INTO)\b/gi,'tok-keyword',s); s=wrap(/\b(\d+(\.\d+)?)\b/g,'tok-number',s); return s; }
    if(lang==='json'){ s=wrap(/(\"(?:\\.|[^"])*\")(\s*:)/g,'tok-attr',s); s=wrap(/\b(true|false|null)\b/g,'tok-keyword',s); s=wrap(/\b(\d+(\.\d+)?)\b/g,'tok-number',s); return s; }
    s=wrap(/\b(\d+(\.\d+)?)\b/g,'tok-number',s); return s;
  }
  function run(){ document.querySelectorAll('pre[data-ch-lang], code[data-ch-lang]').forEach(function(el){
    var lang=(el.getAttribute('data-ch-lang')||'text').toLowerCase();
    var code=el.textContent; el.innerHTML=highlight(code, lang);
    if(!el.classList.contains('ch-code')) el.classList.add('ch-code');
  });}
  window.CHHighlight={run:run, highlight:highlight};
  document.addEventListener('DOMContentLoaded', run);
})();
