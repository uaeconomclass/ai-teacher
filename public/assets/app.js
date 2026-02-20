const levelSelect = document.getElementById('level');
const topicSelect = document.getElementById('topic');
const form = document.getElementById('chat-form');
const input = document.getElementById('message');
const messages = document.getElementById('messages');
const apiStatus = document.getElementById('api-status');
const sendBtn = document.getElementById('send-btn');
const chips = Array.from(document.querySelectorAll('.chip'));
let dialogueId = null;
let isSending = false;

const appendMessage = (sender, text) => {
  const row = document.createElement('div');
  row.className = `msg ${sender}`;
  row.textContent = text;
  messages.appendChild(row);
  messages.scrollTop = messages.scrollHeight;
};

const setSending = (value) => {
  isSending = value;
  sendBtn.disabled = value;
  sendBtn.textContent = value ? 'Sending...' : 'Send';
};

const checkHealth = async () => {
  try {
    const res = await fetch('/api/health');
    const json = await res.json();
    apiStatus.textContent = res.ok ? json.status.toUpperCase() : 'DOWN';
  } catch (e) {
    apiStatus.textContent = 'DOWN';
  }
};

const loadTopics = async () => {
  const res = await fetch('/api/topics');
  const json = await res.json();
  const items = json.data || [];
  topicSelect.innerHTML = '';

  items.forEach((item) => {
    const opt = document.createElement('option');
    opt.value = item.slug;
    opt.textContent = `${item.level} - ${item.title}`;
    topicSelect.appendChild(opt);
  });
};

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  if (isSending) return;

  const message = input.value.trim();
  if (!message) return;

  appendMessage('user', message);
  input.value = '';
  setSending(true);

  const payload = {
    level: levelSelect.value,
    topic: topicSelect.value || 'introductions',
    message,
    dialogue_id: dialogueId,
  };

  const res = await fetch('/api/chat', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  const json = await res.json();
  setSending(false);

  if (!res.ok) {
    appendMessage('ai', json.error || 'Request failed');
    return;
  }

  if (json.data?.dialogue_id) {
    dialogueId = json.data.dialogue_id;
  }

  appendMessage('ai', json.data.reply);
  if (json.data.feedback?.tip) {
    appendMessage('ai', `Tip: ${json.data.feedback.tip}`);
  }
});

chips.forEach((chip) => {
  chip.addEventListener('click', () => {
    const text = chip.getAttribute('data-text') || '';
    input.value = text;
    input.focus();
  });
});

checkHealth();
loadTopics().catch(() => {
  appendMessage('ai', 'Failed to load topics.');
});
