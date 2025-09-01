(function(){
  function lineCount(text){return (text.match(/\n/g)||[]).length + 1;}
  function buildLines(n){var s=''; for(var i=1;i<=n;i++) s+='<div class="line">'+i+'</div>'; return s;}
  function syncGutter(ed){var n=lineCount(ed.ta.value); if(n!==ed.lines){ed.lines=n; ed.gutter.innerHTML=buildLines(n);}}
  function syncScroll(ed){ed.gutter.scrollTop = ed.ta.scrollTop;}
  function onInput(ed){syncGutter(ed);}
  function attachEditor(textareaId){
    var ta=document.getElementById(textareaId); if(!ta) return null;
    var wrap=document.createElement('div'); wrap.className='ch-editor';
    var gut=document.createElement('div'); gut.className='gutter';
    var parent=ta.parentNode; parent.insertBefore(wrap, ta); wrap.appendChild(gut); wrap.appendChild(ta);
    ta.classList.add('ch-textarea'); ta.spellcheck=false;
    var ed={ta:ta,gutter:gut,lines:0}; syncGutter(ed);
    ta.addEventListener('input', function(){onInput(ed);}); 
    ta.addEventListener('scroll', function(){syncScroll(ed);});
    ta.addEventListener('keydown', function(e){
      if(e.key==='Tab'){e.preventDefault(); var s=ta.selectionStart; var v=ta.value; ta.value=v.slice(0,s)+'\t'+v.slice(ta.selectionEnd); ta.selectionStart=ta.selectionEnd=s+1; onInput(ed);}
    });
    function autoH(){ta.style.height='auto'; ta.style.height=(ta.scrollHeight+4)+'px';}
    ta.addEventListener('input', autoH); window.addEventListener('load', autoH); autoH();
    return ed;
  }
  window.CHEditor = {attachEditor:attachEditor};
})();