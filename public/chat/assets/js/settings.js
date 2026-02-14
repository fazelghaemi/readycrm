(() => {
  const csrf = window.PromptAllChat?.csrf || '';
  const $ = (id) => document.getElementById(id);

  async function apiPostForm(url, form){
    const r = await fetch(url, { method:'POST', headers:{ 'X-Requested-With':'XMLHttpRequest', 'X-CSRF-Token': csrf }, body: form });
    const j = await r.json().catch(()=>({ok:false,error:'BAD_JSON'}));
    if(!j.ok) throw new Error(j.error || 'ERR');
    return j;
  }

  $('saveProfile')?.addEventListener('click', async () => {
    const display_name = $('displayName').value.trim();
    const bio = $('bio').value.trim();
    const avatar = $('avatarFile').files?.[0] || null;

    $('saveRes').textContent = '...';
    try{
      const form = new FormData();
      form.append('display_name', display_name);
      form.append('bio', bio);
      form.append('csrf', csrf);
      if (avatar) form.append('avatar', avatar);

      await apiPostForm('api/profile_update.php', form);
      $('saveRes').textContent = '✅ ذخیره شد. برای دیدن تغییرات، صفحه چت را رفرش کنید.';
    }catch(e){
      $('saveRes').textContent = '⛔ ' + e.message;
    }
  });
})();