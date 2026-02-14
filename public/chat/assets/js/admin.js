(() => {
  const csrf = window.PromptAllChat?.csrf || '';
  const $ = (id) => document.getElementById(id);

  async function apiPost(url, data){
    const form = new URLSearchParams();
    Object.entries(data).forEach(([k,v]) => form.set(k, String(v)));
    const r = await fetch(url, {
      method:'POST',
      headers: { 'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8', 'X-Requested-With':'XMLHttpRequest', 'X-CSRF-Token': csrf },
      body: form.toString()
    });
    const j = await r.json().catch(()=>({ok:false,error:'BAD_JSON'}));
    if(!j.ok) throw new Error(j.error || 'ERR');
    return j;
  }

  $('createUserBtn')?.addEventListener('click', async () => {
    const username = $('newUser').value.trim();
    const password = $('newPass').value;
    const role = $('newRole').value;
    $('userResult').textContent = '...';
    try{
      await apiPost('api/admin_create_user.php', { username, password, role, csrf });
      $('userResult').textContent = '✅ ساخته شد. صفحه را رفرش کنید.';
    }catch(e){
      $('userResult').textContent = '⛔ ' + e.message;
    }
  });

  $('createRoomBtn')?.addEventListener('click', async () => {
    const name = $('roomNameInput').value.trim();
    const type = $('roomType').value;
    const is_readonly = $('roomReadonly').checked ? 1 : 0;
    $('roomResult').textContent = '...';
    try{
      await apiPost('api/admin_create_room.php', { name, type, is_readonly, csrf });
      $('roomResult').textContent = '✅ ساخته شد. صفحه را رفرش کنید.';
    }catch(e){
      $('roomResult').textContent = '⛔ ' + e.message;
    }
  });

  document.querySelectorAll('[data-del-upload]')?.forEach(btn => {
    btn.addEventListener('click', async () => {
      if(!confirm('این فایل حذف شود؟')) return;
      const upload_id = btn.getAttribute('data-del-upload');
      $('uploadResult').textContent = '...';
      try{
        await apiPost('api/admin_delete_upload.php', { upload_id, csrf });
        $('uploadResult').textContent = '✅ حذف شد. صفحه را رفرش کنید.';
      }catch(e){
        $('uploadResult').textContent = '⛔ ' + e.message;
      }
    });
  });

  document.querySelectorAll('[data-del-room]')?.forEach(btn => {
    btn.addEventListener('click', async () => {
      if(!confirm('این گروه/کانال حذف شود؟ (پیام‌ها هم پاک می‌شوند)')) return;
      const room_id = btn.getAttribute('data-del-room');
      $('roomResult').textContent = '...';
      try{
        await apiPost('api/admin_delete_room.php', { room_id, csrf });
        $('roomResult').textContent = '✅ حذف شد. صفحه را رفرش کنید.';
      }catch(e){
        $('roomResult').textContent = '⛔ ' + e.message;
      }
    });
  });
})();