<?php
/**
 * Portal — Messages View (student & parent)
 * Full AJAX messaging with file uploads, image preview, real-time polling.
 * Uses msg_* tables.
 */

$userId  = portal_user_id();
$role    = portal_role();

// Initial data for SSR
$unreadCount = (int) db_fetch_value(
    "SELECT COUNT(DISTINCT ms.message_id) FROM msg_message_status ms
     JOIN msg_messages m ON m.id = ms.message_id
     JOIN msg_conversation_participants cp ON cp.conversation_id = m.conversation_id AND cp.user_id = ms.user_id AND cp.is_deleted = 0
     WHERE ms.user_id = ? AND ms.status != 'read'",
    [$userId]
);

// Pre-load conversations for initial render
$conversations = db_fetch_all("
    SELECT c.id, c.type, c.subject,
        (SELECT m2.body FROM msg_messages m2 WHERE m2.conversation_id = c.id ORDER BY m2.created_at DESC LIMIT 1) AS last_body,
        (SELECT m3.created_at FROM msg_messages m3 WHERE m3.conversation_id = c.id ORDER BY m3.created_at DESC LIMIT 1) AS last_msg_at,
        (SELECT m4.sender_id FROM msg_messages m4 WHERE m4.conversation_id = c.id ORDER BY m4.created_at DESC LIMIT 1) AS last_sender_id,
        (SELECT COUNT(*) FROM msg_message_status ms JOIN msg_messages mm ON mm.id = ms.message_id
            WHERE mm.conversation_id = c.id AND ms.user_id = ? AND ms.status != 'read') AS unread_count,
        ou.id AS other_user_id, ou.full_name AS other_name
    FROM msg_conversations c
    JOIN msg_conversation_participants cp ON cp.conversation_id = c.id AND cp.user_id = ? AND cp.is_deleted = 0
    LEFT JOIN msg_conversation_participants ocp ON ocp.conversation_id = c.id AND ocp.user_id != ?
    LEFT JOIN users ou ON ou.id = ocp.user_id
    WHERE EXISTS (SELECT 1 FROM msg_messages m WHERE m.conversation_id = c.id)
    ORDER BY last_msg_at DESC LIMIT 30
", [$userId, $userId, $userId]);

$activeNav = 'messages';
portal_head('Messages', portal_url('dashboard'));
?>

<!-- Main messaging container -->
<div id="msg-app">
  <!-- ═══ Inbox View ═══ -->
  <div id="inbox-view">
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-2">
        <h2 class="font-bold text-gray-900 text-lg">Messages</h2>
        <?php if ($unreadCount > 0): ?>
        <span id="inbox-badge" class="badge badge-red animate-pulse"><?= $unreadCount ?> new</span>
        <?php else: ?>
        <span id="inbox-badge" class="badge badge-red hidden">0 new</span>
        <?php endif; ?>
      </div>
      <button onclick="showCompose()" class="btn-primary !px-4 !py-2.5 !text-sm !rounded-full shadow-md shadow-primary-200">
        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Compose
      </button>
    </div>

    <!-- Conversation list -->
    <div id="conv-list" class="space-y-2 mb-5">
      <?php if (empty($conversations)): ?>
      <div id="empty-inbox" class="card text-center py-12 text-gray-400">
        <div class="text-5xl mb-3">💬</div>
        <p class="text-sm font-medium">No messages yet</p>
        <p class="text-xs mt-1">Start a conversation with your teacher or admin</p>
        <button onclick="showCompose()" class="btn-primary mt-4 !text-sm !px-5">Send First Message</button>
      </div>
      <?php else: ?>
      <?php foreach ($conversations as $conv):
        $initials = mb_substr($conv['other_name'] ?? '?', 0, 1);
        $isSentByMe = (int)($conv['last_sender_id'] ?? 0) === $userId;
        $time = $conv['last_msg_at'] ? date('d M', strtotime($conv['last_msg_at'])) : '';
      ?>
      <div onclick="openConversation(<?= (int)$conv['id'] ?>, '<?= e(addslashes($conv['other_name'] ?? 'Unknown')) ?>', <?= (int)($conv['other_user_id'] ?? 0) ?>)"
           class="card flex items-center gap-3 cursor-pointer transition-all hover:shadow-md hover:border-primary-200 active:scale-[0.98]
                  <?= $conv['unread_count'] > 0 ? 'border-primary-300 bg-primary-50/50' : 'bg-white' ?>"
           data-conv-id="<?= (int)$conv['id'] ?>">
        <div class="flex-shrink-0 w-11 h-11 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-bold text-lg">
          <?= e($initials) ?>
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-center justify-between gap-2">
            <span class="text-sm font-bold text-gray-900 truncate"><?= e($conv['other_name'] ?? 'Unknown') ?></span>
            <span class="text-[10px] text-gray-400 flex-shrink-0"><?= e($time) ?></span>
          </div>
          <?php if ($conv['subject']): ?>
          <p class="text-xs font-semibold text-gray-700 truncate"><?= e($conv['subject']) ?></p>
          <?php endif; ?>
          <p class="text-xs text-gray-500 truncate">
            <?= $isSentByMe ? 'You: ' : '' ?><?= e(mb_substr($conv['last_body'] ?? '', 0, 60)) ?>
          </p>
        </div>
        <?php if ($conv['unread_count'] > 0): ?>
        <div class="conv-badge flex-shrink-0 w-6 h-6 rounded-full bg-primary-600 text-white flex items-center justify-center text-xs font-bold">
          <?= (int)$conv['unread_count'] ?>
        </div>
        <?php else: ?>
        <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- ═══ Compose View ═══ -->
  <div id="compose-view" class="hidden">
    <div class="flex items-center gap-3 mb-4">
      <button onclick="showInbox()" class="p-2 rounded-xl hover:bg-gray-100 transition-colors">
        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
        </svg>
      </button>
      <h2 class="font-bold text-gray-900 text-lg">New Message</h2>
    </div>
    <div class="card border-primary-200 bg-gradient-to-br from-primary-50 to-white">
      <div class="mb-3">
        <label class="form-label">To</label>
        <select id="compose-to" class="form-input" required>
          <option value="">— Loading staff... —</option>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Subject</label>
        <input type="text" id="compose-subject" class="form-input" placeholder="What's this about?" maxlength="255">
      </div>
      <div class="mb-3">
        <label class="form-label">Message</label>
        <textarea id="compose-body" rows="4" class="form-input resize-none"
                  placeholder="Type your message here..." maxlength="5000"></textarea>
      </div>
      <!-- File attachment -->
      <div class="mb-4">
        <label class="form-label">Attachments</label>
        <div class="flex items-center gap-2">
          <label class="flex items-center gap-2 px-3 py-2 rounded-xl border border-dashed border-gray-300 cursor-pointer hover:border-primary-400 hover:bg-primary-50 transition-all text-sm text-gray-500">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
            </svg>
            <span>Choose files</span>
            <input type="file" id="compose-files" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx" class="hidden"
                   onchange="previewFiles(this, 'compose-preview')">
          </label>
        </div>
        <div id="compose-preview" class="flex flex-wrap gap-2 mt-2"></div>
      </div>
      <div class="flex gap-2">
        <button onclick="sendCompose()" id="compose-send-btn" class="btn-primary flex-1">
          <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
          </svg>
          Send
        </button>
        <button onclick="showInbox()" class="btn-secondary px-4">Cancel</button>
      </div>
    </div>
  </div>

  <!-- ═══ Thread View ═══ -->
  <div id="thread-view" class="hidden flex flex-col" style="min-height: calc(100vh - 10rem);">
    <!-- Thread header -->
    <div class="flex items-center gap-3 mb-3 flex-shrink-0">
      <button onclick="showInbox()" class="p-2 rounded-xl hover:bg-gray-100 transition-colors">
        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
        </svg>
      </button>
      <div class="flex-1 min-w-0">
        <h2 id="thread-name" class="font-bold text-gray-900 text-base truncate"></h2>
        <p class="text-xs text-gray-500">Direct Message</p>
      </div>
    </div>

    <!-- Messages area -->
    <div id="thread-messages" class="flex-1 overflow-y-auto space-y-3 pb-2" style="max-height: calc(100vh - 18rem);"></div>

    <!-- Sticky bottom input -->
    <div class="flex-shrink-0 bg-gray-50 border-t border-gray-100 pt-3 pb-1 -mx-4 px-4 mt-auto"
         style="position: sticky; bottom: calc(4.5rem + env(safe-area-inset-bottom, 0px)); z-index: 10;">
      <!-- File preview row -->
      <div id="thread-file-preview" class="flex flex-wrap gap-2 mb-2"></div>
      <div class="flex items-end gap-2">
        <!-- Attachment button -->
        <label class="flex-shrink-0 p-3 rounded-full bg-gray-100 hover:bg-gray-200 cursor-pointer transition-colors active:scale-95">
          <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
          </svg>
          <input type="file" id="thread-files" multiple accept="image/*,.pdf,.doc,.docx,.xls,.xlsx" class="hidden"
                 onchange="previewFiles(this, 'thread-file-preview')">
        </label>
        <!-- Message input -->
        <div class="flex-1 relative">
          <textarea id="thread-input" rows="1" class="form-input !rounded-2xl !pr-4 !py-3 resize-none"
                    placeholder="Type a message..." maxlength="5000"
                    oninput="autoGrow(this)" onkeydown="handleEnter(event)"></textarea>
        </div>
        <!-- Send button -->
        <button onclick="sendReply()" id="thread-send-btn" class="flex-shrink-0 p-3 rounded-full bg-primary-600 text-white hover:bg-primary-700 transition-colors active:scale-95 shadow-md shadow-primary-200">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
          </svg>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ═══ Image Lightbox ═══ -->
<div id="lightbox" class="fixed inset-0 z-50 bg-black/90 hidden flex items-center justify-center" onclick="closeLightbox(event)">
  <button onclick="closeLightbox()" class="absolute top-4 right-4 p-2 rounded-full bg-white/20 text-white hover:bg-white/30 z-10">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
    </svg>
  </button>
  <a id="lightbox-download" href="#" download class="absolute top-4 left-4 p-2 rounded-full bg-white/20 text-white hover:bg-white/30 z-10">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
    </svg>
  </a>
  <img id="lightbox-img" src="" class="max-w-[90vw] max-h-[85vh] object-contain rounded-lg" onclick="event.stopPropagation()">
</div>

<style>
  #thread-input { max-height: 120px; min-height: 44px; line-height: 1.4; }
  .msg-bubble { animation: msgIn 0.2s ease-out; }
  @keyframes msgIn { from { opacity:0; transform:translateY(8px) scale(0.97); } to { opacity:1; transform:translateY(0) scale(1); } }
  #thread-messages::-webkit-scrollbar { width: 3px; }
  #thread-messages::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 999px; }
  .file-chip { display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:10px;
               font-size:12px; font-weight:500; cursor:pointer; transition:all .15s; }
  .file-chip:hover { transform: translateY(-1px); }
  .sending { opacity: 0.6; pointer-events: none; }
</style>

<script>
const PORTAL_MSG_URL = '<?= rtrim(portal_url("messages"), "?") ?>';
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';
const CURRENT_USER_ID = <?= $userId ?>;
let currentConvId = null;
let currentOtherUserId = null;
let lastMsgId = 0;
let pollTimer = null;
let convPollTimer = null;

// ── View switching ──
function showInbox() {
  document.getElementById('inbox-view').classList.remove('hidden');
  document.getElementById('compose-view').classList.add('hidden');
  document.getElementById('thread-view').classList.add('hidden');
  currentConvId = null;
  if (pollTimer) clearInterval(pollTimer);
  pollTimer = null;
  // Refresh conversation list
  refreshConversations();
  startConvPolling();
}

function showCompose() {
  document.getElementById('inbox-view').classList.add('hidden');
  document.getElementById('compose-view').classList.remove('hidden');
  document.getElementById('thread-view').classList.add('hidden');
  if (convPollTimer) clearInterval(convPollTimer);
  loadStaffList();
}

function showThread() {
  document.getElementById('inbox-view').classList.add('hidden');
  document.getElementById('compose-view').classList.add('hidden');
  document.getElementById('thread-view').classList.remove('hidden');
  if (convPollTimer) clearInterval(convPollTimer);
}

// ── Open conversation ──
async function openConversation(convId, name, otherUserId) {
  currentConvId = convId;
  currentOtherUserId = otherUserId;
  lastMsgId = 0;
  document.getElementById('thread-name').textContent = name;
  document.getElementById('thread-messages').innerHTML =
    '<div class="flex justify-center py-8"><div class="w-6 h-6 border-2 border-primary-300 border-t-primary-600 rounded-full animate-spin"></div></div>';
  document.getElementById('thread-input').value = '';
  document.getElementById('thread-file-preview').innerHTML = '';
  const fi = document.getElementById('thread-files');
  if (fi) fi.value = '';
  showThread();

  try {
    const resp = await fetch(PORTAL_MSG_URL + '?_fetch_thread=' + convId, { credentials: 'same-origin' });
    if (!resp.ok) {
      const txt = await resp.text();
      console.error('fetch_thread error', resp.status, txt);
      const snippet = escHtml((txt || '').substring(0, 300));
      document.getElementById('thread-messages').innerHTML =
        `<p class="text-center text-red-500 text-sm py-8">Failed to load messages (${resp.status}) ${snippet ? '- ' + snippet : ''}</p>`;
      return;
    }
    // Read text once and try parse JSON from it to avoid double-read errors
    const txt = await resp.text();
    let data;
    try {
      data = txt ? JSON.parse(txt) : {};
    } catch (err) {
      console.error('fetch_thread invalid json', txt);
      document.getElementById('thread-messages').innerHTML =
        '<p class="text-center text-red-500 text-sm py-8">Failed to load messages: invalid server response</p>';
      return;
    }
    if (data.error) {
      showToast(data.error, 'error');
      document.getElementById('thread-messages').innerHTML =
        `<p class="text-center text-red-500 text-sm py-8">${escHtml(data.error)}</p>`;
      return;
    }
    renderThread(data.messages || []);
    startPolling();
  } catch (e) {
    console.error('fetch_thread exception', e);
    document.getElementById('thread-messages').innerHTML =
      '<p class="text-center text-red-500 text-sm py-8">Failed to load messages</p>';
  }
}

// ── Render thread ──
function renderThread(messages, append = false) {
  const container = document.getElementById('thread-messages');
  if (!append) container.innerHTML = '';
  if (!messages.length && !append) {
    container.innerHTML = '<p class="text-center text-gray-400 text-sm py-8">No messages yet. Say hello!</p>';
    return;
  }
  messages.forEach(m => {
    if (m.id > lastMsgId) lastMsgId = m.id;
    container.insertAdjacentHTML('beforeend', buildBubble(m));
  });
  container.scrollTop = container.scrollHeight;
}

function buildBubble(m) {
  const mine = m.is_mine;
  let attachHtml = '';
  if (m.attachments && m.attachments.length) {
    m.attachments.forEach(a => {
      if (a.is_image) {
        attachHtml += `<div class="mt-2 rounded-xl overflow-hidden cursor-pointer" onclick="openLightbox('${escHtml(a.url)}', '${escHtml(a.file_name)}')">
          <img src="${escHtml(a.url)}" alt="${escHtml(a.file_name)}" class="max-w-full rounded-xl" style="max-height:200px;object-fit:cover;" loading="lazy">
        </div>`;
      } else {
        const icon = getFileIcon(a.mime_type);
        const size = formatSize(a.file_size);
        attachHtml += `<a href="${escHtml(a.url)}" download="${escHtml(a.file_name)}" target="_blank"
          class="file-chip mt-2 ${mine ? 'bg-white/20 text-white hover:bg-white/30' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}">
          ${icon} <span class="truncate max-w-[140px]">${escHtml(a.file_name)}</span>
          <span class="opacity-60 text-[10px]">${size}</span>
          <svg class="w-3.5 h-3.5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
          </svg>
        </a>`;
      }
    });
  }

  const bodyHtml = m.body ? `<p class="text-sm whitespace-pre-wrap">${escHtml(m.body)}</p>` : '';

  return `<div class="flex ${mine ? 'justify-end' : 'justify-start'} msg-bubble">
    <div class="max-w-[85%] ${mine
      ? 'bg-primary-600 text-white rounded-2xl rounded-br-md'
      : 'bg-white border border-gray-200 text-gray-900 rounded-2xl rounded-bl-md'} px-4 py-3 shadow-sm">
      ${!mine ? `<p class="text-xs font-semibold text-primary-600 mb-1">${escHtml(m.sender_name)}</p>` : ''}
      ${bodyHtml}
      ${attachHtml}
      <p class="text-[10px] mt-1 ${mine ? 'text-primary-200' : 'text-gray-400'} text-right">${escHtml(m.time)}</p>
    </div>
  </div>`;
}

// ── Send reply in thread ──
async function sendReply() {
  const input = document.getElementById('thread-input');
  const body = input.value.trim();
  const fileInput = document.getElementById('thread-files');
  const hasFiles = fileInput && fileInput.files && fileInput.files.length > 0;

  if (!body && !hasFiles) return;
  if (!currentConvId || !currentOtherUserId) return;

  const btn = document.getElementById('thread-send-btn');
  btn.classList.add('sending');

  const fd = new FormData();
  fd.append('receiver_id', currentOtherUserId);
  fd.append('conversation_id', currentConvId);
  fd.append('body', body);
  fd.append('<?= CSRF_TOKEN_NAME ?>', CSRF_TOKEN);
  if (hasFiles) {
    for (let i = 0; i < fileInput.files.length; i++) {
      fd.append('attachments[]', fileInput.files[i]);
    }
  }

  console.debug('sendReply: sending', { to: currentOtherUserId, conv: currentConvId, bodyLen: body.length, files: hasFiles ? fileInput.files.length : 0 });
  try {
    const resp = await fetch(PORTAL_MSG_URL + '?_ajax_send', { method: 'POST', body: fd, credentials: 'same-origin' });
    if (!resp.ok) {
      const txt = await resp.text();
      console.error('sendReply failed', resp.status, txt);
      showToast('Failed to send: ' + (txt || resp.status), 'error');
      btn.classList.remove('sending');
      return;
    }
    const txt = await resp.text();
    let data;
    try { data = txt ? JSON.parse(txt) : {}; } catch (err) {
      console.error('sendReply invalid json', txt);
      showToast('Failed to send: invalid server response', 'error');
      btn.classList.remove('sending');
      return;
    }
    if (data.error) {
      showToast(data.error, 'error');
    } else if (data.message) {
      renderThread([data.message], true);
      input.value = '';
      input.style.height = '44px';
      document.getElementById('thread-file-preview').innerHTML = '';
      if (fileInput) fileInput.value = '';
    }
  } catch (e) {
    console.error('sendReply exception', e);
    showToast('Failed to send', 'error');
  }
  btn.classList.remove('sending');
}

// ── Send compose ──
async function sendCompose() {
  const to = document.getElementById('compose-to').value;
  const subject = document.getElementById('compose-subject').value.trim();
  const body = document.getElementById('compose-body').value.trim();
  const fileInput = document.getElementById('compose-files');
  const hasFiles = fileInput && fileInput.files && fileInput.files.length > 0;

  if (!to) { showToast('Please select a recipient', 'error'); return; }
  if (!body && !hasFiles) { showToast('Please type a message or attach a file', 'error'); return; }

  const btn = document.getElementById('compose-send-btn');
  btn.classList.add('sending');

  const fd = new FormData();
  fd.append('receiver_id', to);
  fd.append('subject', subject);
  fd.append('body', body);
  fd.append('<?= CSRF_TOKEN_NAME ?>', CSRF_TOKEN);
  if (hasFiles) {
    for (let i = 0; i < fileInput.files.length; i++) {
      fd.append('attachments[]', fileInput.files[i]);
    }
  }

  console.debug('sendCompose: sending', { to, subject, bodyLen: body.length, files: hasFiles ? fileInput.files.length : 0 });
  try {
    const resp = await fetch(PORTAL_MSG_URL + '?_ajax_send', { method: 'POST', body: fd, credentials: 'same-origin' });
    if (!resp.ok) {
      const txt = await resp.text();
      console.error('sendCompose failed', resp.status, txt);
      showToast('Failed to send: ' + (txt || resp.status), 'error');
      btn.classList.remove('sending');
      return;
    }
    const txt = await resp.text();
    let data;
    try { data = txt ? JSON.parse(txt) : {}; } catch (err) {
      const raw = txt.substring(0,300);
      console.error('sendCompose invalid json', raw);
      showToast('Failed to send: invalid server response', 'error');
      btn.classList.remove('sending');
      return;
    }
    if (data.error) {
      showToast(data.error, 'error');
    } else if (data.conversation_id) {
      showToast('Message sent!', 'success');
      // Open the conversation
      const sel = document.getElementById('compose-to');
      const name = sel.options[sel.selectedIndex]?.text?.replace(/\s*\(.*\)$/, '') || 'Unknown';
      openConversation(data.conversation_id, name, parseInt(to));
    }
  } catch (e) {
    console.error('sendCompose exception', e);
    showToast('Failed to send', 'error');
  }
  btn.classList.remove('sending');
}

// ── Staff list ──
async function loadStaffList() {
  const sel = document.getElementById('compose-to');
  sel.innerHTML = '<option value="">— Loading... —</option>';
  try {
    const resp = await fetch(PORTAL_MSG_URL + '?_fetch_staff', { credentials: 'same-origin' });
    if (!resp.ok) {
      console.error('fetch_staff failed', resp.status);
      sel.innerHTML = '<option value="">— Failed to load —</option>';
      return;
    }
    let data;
    try { data = await resp.json(); } catch (err) { console.error('fetch_staff invalid json'); sel.innerHTML = '<option value="">— Failed to load —</option>'; return; }
    sel.innerHTML = '<option value="">— Select recipient —</option>';
    (data.staff || []).forEach(s => {
      const opt = document.createElement('option');
      opt.value = s.id;
      opt.textContent = s.full_name + ' (' + s.role_name + ')';
      sel.appendChild(opt);
    });
  } catch (e) {
    console.error('fetch_staff exception', e);
    sel.innerHTML = '<option value="">— Failed to load —</option>';
  }
}

// ── Polling for new messages in thread ──
function startPolling() {
  if (pollTimer) clearInterval(pollTimer);
  pollTimer = setInterval(async () => {
    if (!currentConvId) return;
    try {
      const resp = await fetch(PORTAL_MSG_URL + '?_fetch_thread=' + currentConvId + '&after=' + lastMsgId, { credentials: 'same-origin' });
      if (!resp.ok) {
        const txt = await resp.text();
        console.error('poll fetch_thread failed', resp.status, txt);
        return;
      }
      let data;
      try { data = await resp.json(); } catch (err) { console.error('poll invalid json'); return; }
      if (data.messages && data.messages.length > 0) {
        renderThread(data.messages, true);
      }
    } catch (e) { console.error('poll exception', e); }
  }, 4000);
}

// ── Polling for conversation list updates ──
function startConvPolling() {
  if (convPollTimer) clearInterval(convPollTimer);
  convPollTimer = setInterval(() => refreshConversations(), 10000);
}

async function refreshConversations() {
  try {
    const resp = await fetch(PORTAL_MSG_URL + '?_fetch_conversations', { credentials: 'same-origin' });
    if (!resp.ok) {
      const txt = await resp.text();
      console.error('fetch_conversations failed', resp.status, txt);
      return;
    }
    let data;
    try { data = await resp.json(); } catch (err) { console.error('fetch_conversations invalid json'); return; }
    if (!data.conversations) return;
    renderConversationList(data.conversations);

    // Update unread badge
    const totalUnread = data.conversations.reduce((sum, c) => sum + parseInt(c.unread_count || 0), 0);
    const badge = document.getElementById('inbox-badge');
    if (badge) {
      badge.textContent = totalUnread + ' new';
      badge.classList.toggle('hidden', totalUnread === 0);
    }
  } catch (e) { console.error('fetch_conversations exception', e); }
}

function renderConversationList(convs) {
  const container = document.getElementById('conv-list');
  if (!convs.length) {
    container.innerHTML = `<div class="card text-center py-12 text-gray-400">
      <div class="text-5xl mb-3">💬</div>
      <p class="text-sm font-medium">No messages yet</p>
      <button onclick="showCompose()" class="btn-primary mt-4 !text-sm !px-5">Send First Message</button>
    </div>`;
    return;
  }
  container.innerHTML = convs.map(c => {
    const unread = parseInt(c.unread_count || 0);
    return `<div onclick="openConversation(${c.id}, '${escHtml(addSlashes(c.other_name || 'Unknown'))}', ${c.other_user_id || 0})"
         class="card flex items-center gap-3 cursor-pointer transition-all hover:shadow-md hover:border-primary-200 active:scale-[0.98]
                ${unread > 0 ? 'border-primary-300 bg-primary-50/50' : 'bg-white'}" data-conv-id="${c.id}">
      <div class="flex-shrink-0 w-11 h-11 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-bold text-lg">
        ${escHtml(c.initials)}
      </div>
      <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between gap-2">
          <span class="text-sm font-bold text-gray-900 truncate">${escHtml(c.other_name || 'Unknown')}</span>
          <span class="text-[10px] text-gray-400 flex-shrink-0">${escHtml(c.time)}</span>
        </div>
        ${c.subject ? `<p class="text-xs font-semibold text-gray-700 truncate">${escHtml(c.subject)}</p>` : ''}
        <p class="text-xs text-gray-500 truncate">${c.is_mine ? 'You: ' : ''}${escHtml((c.last_body || '').substring(0, 60))}</p>
      </div>
      ${unread > 0
        ? `<div class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-600 text-white flex items-center justify-center text-xs font-bold">${unread}</div>`
        : `<svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>`}
    </div>`;
  }).join('');
}

// ── File preview ──
function previewFiles(input, previewId) {
  const container = document.getElementById(previewId);
  container.innerHTML = '';
  if (!input.files || !input.files.length) return;
  Array.from(input.files).forEach((file, i) => {
    const isImage = file.type.startsWith('image/');
    if (isImage) {
      const reader = new FileReader();
      reader.onload = e => {
        container.insertAdjacentHTML('beforeend',
          `<div class="relative group">
            <img src="${e.target.result}" class="w-16 h-16 rounded-xl object-cover border border-gray-200">
            <button type="button" onclick="removeFile(this, '${input.id}', ${i})"
              class="absolute -top-1.5 -right-1.5 w-5 h-5 rounded-full bg-red-500 text-white flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity">×</button>
          </div>`);
      };
      reader.readAsDataURL(file);
    } else {
      container.insertAdjacentHTML('beforeend',
        `<div class="relative group file-chip bg-gray-100 text-gray-700">
          ${getFileIcon(file.type)} <span class="truncate max-w-[100px]">${escHtml(file.name)}</span>
          <button type="button" onclick="removeFile(this, '${input.id}', ${i})"
            class="w-4 h-4 rounded-full bg-red-500 text-white flex items-center justify-center text-[10px] opacity-0 group-hover:opacity-100 transition-opacity">×</button>
        </div>`);
    }
  });
}

function removeFile(btn, inputId, idx) {
  const input = document.getElementById(inputId);
  const dt = new DataTransfer();
  Array.from(input.files).forEach((f, i) => { if (i !== idx) dt.items.add(f); });
  input.files = dt.files;
  previewFiles(input, btn.closest('[id$="-preview"]')?.id || 'thread-file-preview');
}

// ── Lightbox ──
function openLightbox(url, filename) {
  document.getElementById('lightbox-img').src = url;
  document.getElementById('lightbox-download').href = url;
  document.getElementById('lightbox-download').download = filename || 'image';
  document.getElementById('lightbox').classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}

function closeLightbox(e) {
  if (e && e.target !== e.currentTarget && !e.target.closest('button')) return;
  document.getElementById('lightbox').classList.add('hidden');
  document.body.style.overflow = '';
}

// ── Helpers ──
function escHtml(s) {
  if (!s) return '';
  const d = document.createElement('div');
  d.textContent = s;
  return d.innerHTML;
}

function addSlashes(s) {
  return (s || '').replace(/'/g, "\\'").replace(/"/g, '\\"');
}

function autoGrow(el) {
  el.style.height = '44px';
  el.style.height = Math.min(el.scrollHeight, 120) + 'px';
}

function handleEnter(e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendReply();
  }
}

function getFileIcon(mime) {
  if (!mime) return '📄';
  if (mime.includes('pdf')) return '<svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>';
  if (mime.includes('word') || mime.includes('document')) return '<svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
  if (mime.includes('sheet') || mime.includes('excel')) return '<svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>';
  return '📄';
}

function formatSize(bytes) {
  if (!bytes) return '';
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / 1048576).toFixed(1) + ' MB';
}

function showToast(msg, type) {
  const colors = { success: 'bg-green-500', error: 'bg-red-500' };
  const toast = document.createElement('div');
  toast.className = `fixed top-20 left-1/2 -translate-x-1/2 ${colors[type] || colors.error} text-white text-sm font-medium px-5 py-2.5 rounded-full shadow-lg z-50 animate-slide-up`;
  toast.textContent = msg;
  document.body.appendChild(toast);
  setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity .3s'; setTimeout(() => toast.remove(), 300); }, 2500);
}

// ── Init ──
startConvPolling();

// Handle back button on thread
window.addEventListener('popstate', (e) => {
  if (!document.getElementById('thread-view').classList.contains('hidden')) {
    e.preventDefault();
    showInbox();
  }
});
</script>

<?php portal_foot($activeNav); ?>
