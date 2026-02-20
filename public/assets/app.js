const levelSelect = document.getElementById('level');
const topicSelect = document.getElementById('topic');
const form = document.getElementById('chat-form');
const input = document.getElementById('message');
const messages = document.getElementById('messages');
let dialogueId = null;

const appendMessage = (sender, text) => {
  const row = document.createElement('div');
  row.className = `msg ${sender}`;
  row.textContent = `${sender === 'user' ? 'You' : 'AI'}: ${text}`;
  messages.appendChild(row);
  messages.scrollTop = messages.scrollHeight;
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
  const message = input.value.trim();
  if (!message) return;

  appendMessage('user', message);
  input.value = '';

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

loadTopics().catch(() => {
  appendMessage('ai', 'Failed to load topics.');
});
