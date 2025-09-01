function copyToClipboard(elId){
  const el=document.getElementById(elId);
  if(!el) return;
  const text = el.innerText || el.textContent || '';
  navigator.clipboard.writeText(text).then(()=>{ alert("کد در کلیپ‌بورد کپی شد"); });
}