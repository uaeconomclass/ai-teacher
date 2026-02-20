const levelSelect = document.getElementById('level');
const topicSelect = document.getElementById('topic');
const form = document.getElementById('chat-form');
const input = document.getElementById('message');
const messages = document.getElementById('messages');
const sendBtn = document.getElementById('send-btn');
const chips = Array.from(document.querySelectorAll('.chip'));
const recordBtn = document.getElementById('record-btn');
const voiceStatus = document.getElementById('voice-status');
const player = document.getElementById('player');

let dialogueId = null;
let isSending = false;
let mediaRecorder = null;
let audioChunks = [];
let isRecording = false;

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

const setVoiceState = (text, recording = false) => {
  voiceStatus.textContent = text;
  recordBtn.classList.toggle('recording', recording);
};

const loadTopics = async () => {
  const level = levelSelect.value;
  const res = await fetch(`/api/topics?level=${encodeURIComponent(level)}`);
  const json = await res.json();
  const items = json.data || [];
  topicSelect.innerHTML = '';

  items.forEach((item) => {
    const opt = document.createElement('option');
    opt.value = item.slug;
    opt.textContent = item.title;
    topicSelect.appendChild(opt);
  });
};

const sendChatMessage = async (message) => {
  if (isSending) return;
  if (!message || !message.trim()) return;

  appendMessage('user', message.trim());
  setSending(true);

  const payload = {
    level: levelSelect.value,
    topic: topicSelect.value || 'introductions',
    message: message.trim(),
    dialogue_id: dialogueId,
  };

  try {
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

    if (json.data?.reply) {
      appendMessage('ai', json.data.reply);
    }
    if (json.data?.feedback?.tip) {
      appendMessage('ai', `Tip: ${json.data.feedback.tip}`);
    }

    if (json.data?.audio_url) {
      player.src = json.data.audio_url;
      player.play().catch(() => {});
    }
  } catch (e) {
    appendMessage('ai', 'Network error. Try again.');
  } finally {
    setSending(false);
  }
};

const transcribeBlob = async (blob) => {
  const formData = new FormData();
  formData.append('audio', blob, 'speech.webm');
  const res = await fetch('/api/speech-to-text', { method: 'POST', body: formData });
  const json = await res.json();
  if (!res.ok) throw new Error(json.error || 'STT failed');
  return json.data?.text || '';
};

const startRecording = async () => {
  if (isRecording) return;
  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    setVoiceState('Microphone not supported.');
    return;
  }

  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    audioChunks = [];
    mediaRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });
    mediaRecorder.ondataavailable = (e) => {
      if (e.data && e.data.size > 0) audioChunks.push(e.data);
    };
    mediaRecorder.start();
    isRecording = true;
    setVoiceState('Recording...', true);
  } catch (e) {
    setVoiceState('Microphone permission denied.');
  }
};

const stopRecording = async () => {
  if (!isRecording || !mediaRecorder) return;

  await new Promise((resolve) => {
    mediaRecorder.onstop = resolve;
    mediaRecorder.stop();
  });

  mediaRecorder.stream.getTracks().forEach((t) => t.stop());
  isRecording = false;
  setVoiceState('Transcribing...');

  try {
    const blob = new Blob(audioChunks, { type: 'audio/webm' });
    const text = await transcribeBlob(blob);
    input.value = text;
    setVoiceState('Done. Sending...');
    await sendChatMessage(text);
    input.value = '';
    setVoiceState('Idle');
  } catch (e) {
    setVoiceState('Voice failed. Try again.');
  }
};

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  const message = input.value.trim();
  if (!message) return;
  input.value = '';
  await sendChatMessage(message);
});

levelSelect.addEventListener('change', () => {
  loadTopics().catch(() => {
    appendMessage('ai', 'Failed to load topics for level.');
  });
});

chips.forEach((chip) => {
  chip.addEventListener('click', () => {
    const text = chip.getAttribute('data-text') || '';
    input.value = text;
    input.focus();
  });
});

recordBtn.addEventListener('mousedown', () => { startRecording(); });
recordBtn.addEventListener('mouseup', () => { stopRecording(); });
recordBtn.addEventListener('mouseleave', () => { stopRecording(); });
recordBtn.addEventListener('touchstart', (e) => {
  e.preventDefault();
  startRecording();
}, { passive: false });
recordBtn.addEventListener('touchend', (e) => {
  e.preventDefault();
  stopRecording();
}, { passive: false });

loadTopics().catch(() => {
  appendMessage('ai', 'Failed to load topics.');
});
