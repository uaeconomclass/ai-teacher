const levelSelect = document.getElementById('level');
const topicSelect = document.getElementById('topic');
const form = document.getElementById('chat-form');
const input = document.getElementById('message');
const messages = document.getElementById('messages');
const sendBtn = document.getElementById('send-btn');
const chips = Array.from(document.querySelectorAll('.chip'));
const recordBtn = document.getElementById('record-btn');
const voiceModeBtn = document.getElementById('voice-mode-btn');
const voiceStatus = document.getElementById('voice-status');
const player = document.getElementById('player');

let dialogueId = null;
let isSending = false;

let mediaRecorder = null;
let audioChunks = [];
let isRecording = false;

let voiceModeOn = false;
let streamRef = null;
let audioCtx = null;
let analyser = null;
let silenceInterval = null;
let lastLoudAt = 0;
let heardSpeech = false;
let chunkStartedAt = 0;

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

const startSession = async () => {
  const payload = {
    level: levelSelect.value,
    topic: topicSelect.value || '',
  };

  const res = await fetch('/api/session/start', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });
  const json = await res.json();
  if (!res.ok) throw new Error(json.error || 'Session start failed');
  dialogueId = json.data?.dialogue_id || null;
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

const ensureAudio = async () => {
  if (streamRef) return;
  streamRef = await navigator.mediaDevices.getUserMedia({ audio: true });
  audioCtx = new (window.AudioContext || window.webkitAudioContext)();
  const source = audioCtx.createMediaStreamSource(streamRef);
  analyser = audioCtx.createAnalyser();
  analyser.fftSize = 512;
  source.connect(analyser);
};

const stopSilenceWatcher = () => {
  if (silenceInterval) {
    clearInterval(silenceInterval);
    silenceInterval = null;
  }
};

const watchSilence = () => {
  stopSilenceWatcher();
  const data = new Uint8Array(analyser.fftSize);
  const threshold = 18;
  const silenceMs = 1200;
  const minChunkMs = 1000;
  lastLoudAt = Date.now();
  heardSpeech = false;
  chunkStartedAt = Date.now();

  silenceInterval = setInterval(() => {
    if (!isRecording || !analyser) return;
    analyser.getByteTimeDomainData(data);

    let sum = 0;
    for (let i = 0; i < data.length; i++) {
      sum += Math.abs(data[i] - 128);
    }
    const level = sum / data.length;
    const now = Date.now();

    if (level > threshold) {
      heardSpeech = true;
      lastLoudAt = now;
      return;
    }

    if (
      heardSpeech &&
      now - lastLoudAt > silenceMs &&
      now - chunkStartedAt > minChunkMs &&
      mediaRecorder &&
      mediaRecorder.state === 'recording'
    ) {
      mediaRecorder.stop();
    }
  }, 120);
};

const startRecorderChunk = () => {
  if (!streamRef || isRecording || isSending) return;
  audioChunks = [];
  try {
    mediaRecorder = new MediaRecorder(streamRef, { mimeType: 'audio/webm' });
  } catch (e) {
    mediaRecorder = new MediaRecorder(streamRef);
  }

  mediaRecorder.ondataavailable = (e) => {
    if (e.data && e.data.size > 0) audioChunks.push(e.data);
  };

  mediaRecorder.onstop = async () => {
    isRecording = false;
    stopSilenceWatcher();
    setVoiceState(voiceModeOn ? 'Processing...' : 'Idle');

    const blob = new Blob(audioChunks, { type: 'audio/webm' });
    if (blob.size < 1200) {
      if (voiceModeOn) {
        setTimeout(() => startRecorderChunk(), 120);
      }
      return;
    }

    try {
      const text = await transcribeBlob(blob);
      if (text.trim()) {
        await sendChatMessage(text);
      }
    } catch (e) {
      appendMessage('ai', 'Voice recognition failed.');
    }

    if (voiceModeOn) {
      setTimeout(() => startRecorderChunk(), 180);
    } else {
      setVoiceState('Idle');
    }
  };

  mediaRecorder.start();
  isRecording = true;
  setVoiceState(voiceModeOn ? 'Voice mode listening...' : 'Recording...', true);
  watchSilence();
};

const stopVoiceMode = async () => {
  voiceModeOn = false;
  voiceModeBtn.classList.remove('active');
  voiceModeBtn.textContent = 'Voice Mode: OFF';
  stopSilenceWatcher();

  if (mediaRecorder && mediaRecorder.state === 'recording') {
    mediaRecorder.stop();
  } else {
    setVoiceState('Idle');
  }
};

const startVoiceMode = async () => {
  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    setVoiceState('Microphone not supported.');
    return;
  }

  try {
    await ensureAudio();
    voiceModeOn = true;
    voiceModeBtn.classList.add('active');
    voiceModeBtn.textContent = 'Voice Mode: ON';
    setVoiceState('Voice mode enabled');
    startRecorderChunk();
  } catch (e) {
    setVoiceState('Microphone permission denied.');
  }
};

const startRecording = async () => {
  if (voiceModeOn || isRecording) return;
  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    setVoiceState('Microphone not supported.');
    return;
  }

  try {
    await ensureAudio();
    startRecorderChunk();
  } catch (e) {
    setVoiceState('Microphone permission denied.');
  }
};

const stopRecording = async () => {
  if (voiceModeOn) return;
  if (!isRecording || !mediaRecorder) return;
  if (mediaRecorder.state === 'recording') {
    mediaRecorder.stop();
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
  loadTopics()
    .then(() => startSession())
    .then(() => {
      messages.innerHTML = '';
      appendMessage('ai', 'New session started.');
    })
    .catch(() => {
      appendMessage('ai', 'Failed to load topics for level.');
    });
});

topicSelect.addEventListener('change', () => {
  startSession()
    .then(() => {
      messages.innerHTML = '';
      appendMessage('ai', 'New session started.');
    })
    .catch(() => {
      appendMessage('ai', 'Failed to start session.');
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

voiceModeBtn.addEventListener('click', async () => {
  if (voiceModeOn) {
    await stopVoiceMode();
  } else {
    await startVoiceMode();
  }
});

loadTopics()
  .then(() => startSession())
  .then(() => appendMessage('ai', 'Session ready. Start speaking.'))
  .catch(() => {
    appendMessage('ai', 'Failed to initialize app.');
  });
