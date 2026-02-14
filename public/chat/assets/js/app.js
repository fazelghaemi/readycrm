(() => {
  const csrf = window.PromptAllChat?.csrf || '';
  const me = window.PromptAllChat?.me || {};

  const roomListEl = document.getElementById('roomList');
  const dmListEl = document.getElementById('dmList');
  const userListEl = document.getElementById('userList');

  const tabChatsBtn = document.querySelector('[data-tab="chats"]');
  const tabUsersBtn = document.querySelector('[data-tab="users"]');
  const tabChats = document.getElementById('tabChats');
  const tabUsers = document.getElementById('tabUsers');

  const msgListEl = document.getElementById('msgList');
  const roomNameEl = document.getElementById('roomName');
  const roomMetaEl = document.getElementById('roomMeta');
  const roomAvatarEl = document.getElementById('roomAvatar');
  const participantsBtn = document.getElementById('participantsBtn');

  const inputEl = document.getElementById('msgInput');
  const sendBtn = document.getElementById('sendBtn');
  const attachBtn = document.getElementById('attachBtn');
  const fileInput = document.getElementById('fileInput');
  const uploadPreview = document.getElementById('uploadPreview');
  const conn = document.getElementById('connState');

  const modal = document.getElementById('modal');
  const modalTitle = document.getElementById('modalTitle');
  const modalBody = document.getElementById('modalBody');
  const modalClose = document.getElementById('modalClose');

  let rooms = [];
  let dmRooms = [];
  let usersCache = [];

  let activeRoomId = null;
  let lastId = 0;
  let polling = false;
  let pollTimer = null;
  let pendingFile = null;

  function setConn(on){
    conn?.classList.toggle('online', !!on);
    conn?.classList.toggle('offline', !on);
    if (conn) conn.title = on ? 'متصل' : 'قطع';
  }

  function escapeHtml(s){
    return (s ?? '').toString().replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
  }

  function bytesToSize(bytes){
    const b = Number(bytes||0);
    if (b < 1024) return b + ' B';
    const kb = b / 1024;
    if (kb < 1024) return kb.toFixed(1) + ' KB';
    return (kb/1024).toFixed(1) + ' MB';
  }

  async function apiGet(url){
    const r = await fetch(url, { headers: { 'X-Requested-With':'XMLHttpRequest' }});
    const j = await r.json();
    if(!j.ok) throw new Error(j.error || 'ERR');
    return j;
  }
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
  async function apiPostForm(url, form){
    const r = await fetch(url, { method:'POST', headers:{ 'X-Requested-With':'XMLHttpRequest', 'X-CSRF-Token': csrf }, body: form });
    const j = await r.json().catch(()=>({ok:false,error:'BAD_JSON'}));
    if(!j.ok) throw new Error(j.error || 'ERR');
    return j;
  }

  function roomLabel(r){
    if (r.type === 'dm') return 'پیام خصوصی';
    return (r.type === 'channel' ? 'کانال' : 'گروه') + (Number(r.is_readonly) === 1 ? ' • فقط ادمین' : '');
  }

  function setRoomHeader(r){
    roomNameEl.textContent = r.name || 'پیام خصوصی';
    roomMetaEl.textContent = roomLabel(r);
    roomAvatarEl.textContent = (r.type === 'channel') ? 'CH' : (r.type === 'dm' ? 'DM' : 'GR');
  }

  function renderRoomItem(r, isActive){
    const el = document.createElement('div');
    el.className = 'room-item' + (isActive ? ' active' : '');
    el.innerHTML = `
      <div>
        <div>${escapeHtml(r.name || 'پیام خصوصی')}</div>
        <div class="meta">${escapeHtml(roomLabel(r))}</div>
      </div>
      <div class="meta">#${r.id}</div>
    `;
    el.addEventListener('click', () => switchRoom(r));
    return el;
  }

  function renderRooms(){
    roomListEl.innerHTML = '';
    (rooms || []).forEach(r => roomListEl.appendChild(renderRoomItem(r, r.id===activeRoomId)));
  }
  function renderDmRooms(){
    dmListEl.innerHTML = '';
    if (!(dmRooms || []).length){
      dmListEl.innerHTML = '<div class="room-item muted">هنوز پیامی ندارید.</div>';
      return;
    }
    (dmRooms || []).forEach(r => dmListEl.appendChild(renderRoomItem(r, r.id===activeRoomId)));
  }

  function renderUsers(){
    userListEl.innerHTML = '';
    if (!usersCache.length){
      userListEl.innerHTML = '<div class="room-item muted">کاربری یافت نشد.</div>';
      return;
    }
    usersCache.forEach(u => {
      const el = document.createElement('div');
      el.className = 'room-item';
      const avatar = u.avatar_path ? `<img src="${escapeHtml(u.avatar_path)}" alt="">`
        : escapeHtml((u.display_name||u.username||'?').slice(0,1).toUpperCase());
      el.innerHTML = `
        <div class="user-row">
          <div class="uavatar">${avatar}</div>
          <div>
            <div class="uname">${escapeHtml(u.display_name || u.username)}</div>
            <div class="ubio">${escapeHtml(u.bio || '')}</div>
          </div>
        </div>
        <div class="meta">پیام</div>
      `;
      el.addEventListener('click', async () => {
        try{
          const j = await apiPost('api/dm_open.php', { other_user_id: u.id, csrf });
          await loadRooms();
          const found = dmRooms.find(x => Number(x.id) === Number(j.room_id));
          if (found) switchRoom(found);
          tabChatsBtn.click();
        }catch(e){
          alert('خطا: ' + e.message);
        }
      });
      userListEl.appendChild(el);
    });
  }

  function renderMessage(m){
    const isMe = String(m.username) === String(me.username);
    const row = document.createElement('div');
    row.className = 'msg-row' + (isMe ? ' me' : '');
    const name = m.display_name || m.username;
    const time = (m.created_at || '').toString().slice(11,16);
    const avatar = m.avatar_path ? `<img src="${escapeHtml(m.avatar_path)}" alt="">`
      : escapeHtml((name||'?').slice(0,1).toUpperCase());

    let fileHtml = '';
    if (m.file_url) {
      const isImg = (m.mime || '').startsWith('image/');
      const meta = `${escapeHtml(m.mime||'file')} • ${escapeHtml(bytesToSize(m.size_bytes))}`;
      if (isImg) {
        fileHtml = `
          <div class="file">
            <div style="flex:1">
              <div class="name">${escapeHtml(m.original_name || 'image')}</div>
              <div class="meta">${meta}</div>
              <div style="margin-top:8px">
                <a class="link" href="${escapeHtml(m.file_url)}" target="_blank"><img src="${escapeHtml(m.file_url)}" alt=""></a>
              </div>
            </div>
          </div>`;
      } else {
        fileHtml = `
          <div class="file">
            <div>
              <div class="name">${escapeHtml(m.original_name || 'file')}</div>
              <div class="meta">${meta}</div>
            </div>
            <a class="pill" href="${escapeHtml(m.file_url)}" target="_blank">دانلود</a>
          </div>`;
      }
    }

    const bubble = document.createElement('div');
    bubble.className = 'msg' + (isMe ? ' me' : '');
    bubble.innerHTML = `
      <div class="top">
        <div class="user">${escapeHtml(name)}</div>
        <div class="time">${escapeHtml(time)}</div>
      </div>
      ${m.body ? `<div class="body">${escapeHtml(m.body)}</div>` : ''}
      ${fileHtml}
    `;

    const av = document.createElement('div');
    av.className = 'mavatar';
    av.innerHTML = avatar;

    row.appendChild(av);
    row.appendChild(bubble);
    return row;
  }

  function scrollBottom(){ msgListEl.scrollTop = msgListEl.scrollHeight; }

  async function fetchNew(forceScroll=false){
    if(polling || !activeRoomId) return;
    polling = true;
    try{
      const beforeHeight = msgListEl.scrollHeight;
      const j = await apiGet(`api/messages_fetch.php?room_id=${activeRoomId}&after_id=${lastId}&limit=80`);
      (j.messages || []).forEach(m => {
        lastId = Math.max(lastId, Number(m.id));
        msgListEl.appendChild(renderMessage(m));
      });
      setConn(true);
      const nearBottom = (msgListEl.scrollTop + msgListEl.clientHeight) >= (beforeHeight - 140);
      if(forceScroll || nearBottom) scrollBottom();
    }catch(e){
      setConn(false);
    }finally{
      polling = false;
    }
  }

  async function switchRoom(r){
    if(activeRoomId === r.id) return;
    activeRoomId = r.id;
    lastId = 0;
    msgListEl.innerHTML = '';
    setRoomHeader(r);
    renderRooms(); renderDmRooms();
    await fetchNew(true);
  }

  function setPendingFile(file){
    pendingFile = file || null;
    if (!pendingFile) {
      uploadPreview.style.display = 'none';
      uploadPreview.textContent = '';
      return;
    }
    uploadPreview.style.display = 'block';
    uploadPreview.textContent = `فایل انتخاب شد: ${pendingFile.name} (${bytesToSize(pendingFile.size)})`;
  }

  async function send(){
    const body = (inputEl.value || '').trim();
    if(!body && !pendingFile) return;
    sendBtn.disabled = true;
    try{
      const form = new FormData();
      form.append('room_id', String(activeRoomId));
      form.append('body', body);
      form.append('csrf', csrf);
      if (pendingFile) form.append('file', pendingFile);

      await apiPostForm('api/message_send.php', form);
      inputEl.value = '';
      setPendingFile(null);
      fileInput.value = '';
      await fetchNew(true);
    }catch(e){
      alert('ارسال نشد: ' + e.message);
    }finally{
      sendBtn.disabled = false;
      inputEl.focus();
    }
  }

  function startPolling(){
    if(pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(() => fetchNew(false), 1200);
  }

  async function loadRooms(){
    const j = await apiGet('api/rooms.php');
    rooms = j.rooms || [];
    dmRooms = j.dm_rooms || [];
    renderRooms();
    renderDmRooms();
  }

  async function loadUsers(){
    const j = await apiGet('api/users_list.php');
    usersCache = j.users || [];
    renderUsers();
  }

  function openModal(title, html){
    modalTitle.textContent = title;
    modalBody.innerHTML = html;
    modal.style.display = 'flex';
  }
  function closeModal(){ modal.style.display = 'none'; }
  modalClose?.addEventListener('click', closeModal);
  modal?.addEventListener('click', (e) => { if(e.target === modal) closeModal(); });

  async function showParticipants(){
    if (!activeRoomId) return;
    try{
      const j = await apiGet(`api/participants.php?room_id=${activeRoomId}`);
      const rows = (j.users || []).map(u => {
        const name = escapeHtml(u.display_name || u.username);
        const bio = escapeHtml(u.bio || '');
        const av = u.avatar_path
          ? `<img src="${escapeHtml(u.avatar_path)}" style="width:28px;height:28px;border-radius:50%;object-fit:cover;border:1px solid rgba(255,255,255,.08)">`
          : `<span style="display:inline-flex;width:28px;height:28px;border-radius:50%;align-items:center;justify-content:center;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);font-weight:900">${name.slice(0,1).toUpperCase()}</span>`;
        return `<div style="display:flex;gap:10px;align-items:center;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.06)">
                  ${av}
                  <div style="flex:1">
                    <div style="font-weight:900">${name}</div>
                    <div style="color:var(--muted);font-size:12px">${bio}</div>
                  </div>
                </div>`;
      }).join('');
      openModal('اعضای این گفتگو', rows || '<div class="hint">کسی نیست.</div>');
    }catch(e){
      alert('خطا: ' + e.message);
    }
  }

  function setTab(tab){
    if (tab === 'users'){
      tabUsersBtn.classList.add('active'); tabChatsBtn.classList.remove('active');
      tabUsers.style.display = ''; tabChats.style.display = 'none';
      loadUsers();
    } else {
      tabChatsBtn.classList.add('active'); tabUsersBtn.classList.remove('active');
      tabChats.style.display = ''; tabUsers.style.display = 'none';
    }
  }
  tabChatsBtn?.addEventListener('click', () => setTab('chats'));
  tabUsersBtn?.addEventListener('click', () => setTab('users'));
  participantsBtn?.addEventListener('click', showParticipants);

  async function bootstrap(){
    try{
      await loadRooms();
      const first = rooms[0] || dmRooms[0];
      if (first) {
        activeRoomId = first.id;
        setRoomHeader(first);
        renderRooms(); renderDmRooms();
        await fetchNew(true);
        startPolling();
      } else {
        roomListEl.innerHTML = '<div class="room-item muted">هیچ Roomی وجود ندارد.</div>';
      }

      inputEl.addEventListener('keydown', (ev) => {
        if(ev.key === 'Enter' && !ev.shiftKey){
          ev.preventDefault();
          send();
        }
      });
      sendBtn.addEventListener('click', send);
      attachBtn.addEventListener('click', () => fileInput.click());
      fileInput.addEventListener('change', () => setPendingFile(fileInput.files?.[0] || null));
    }catch(e){
      roomListEl.innerHTML = '<div class="room-item muted">خطا در اتصال.</div>';
      setConn(false);
    }
  }

  bootstrap();
})();