const modeSelect = document.getElementById('mode');
const levelSelect = document.getElementById('level');
const topicSelect = document.getElementById('topic');
const grammarFocusSelect = document.getElementById('grammar-focus');
const form = document.getElementById('chat-form');
const input = document.getElementById('message');
const messages = document.getElementById('messages');
const sendBtn = document.getElementById('send-btn');
const recordBtn = document.getElementById('record-btn');
const voiceModeBtn = document.getElementById('voice-mode-btn');
const voiceStatus = document.getElementById('voice-status');
const player = document.getElementById('player');
const promptPreviewText = document.getElementById('prompt-preview-text');
const copyPromptBtn = document.getElementById('copy-prompt-btn');
const promptCopyStatus = document.getElementById('prompt-copy-status');
const browserSpeech = window.speechSynthesis || null;
const SETTINGS_STORAGE_KEY = 'ai_teacher_ui_settings_v1';

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

const readSavedSettings = () => {
  try {
    const raw = window.localStorage.getItem(SETTINGS_STORAGE_KEY);
    if (!raw) return {};
    const parsed = JSON.parse(raw);
    if (!parsed || typeof parsed !== 'object') return {};
    return parsed;
  } catch (e) {
    return {};
  }
};

const hasSelectOption = (select, value) => Array.from(select.options).some((opt) => opt.value === value);

const saveSettings = () => {
  const settings = {
    mode: modeSelect.value || 'conversation',
    level: levelSelect.value || 'A1',
    topic: topicSelect.value || '',
    grammarFocus: grammarFocusSelect.value || '',
  };

  try {
    window.localStorage.setItem(SETTINGS_STORAGE_KEY, JSON.stringify(settings));
  } catch (e) {
    // no-op
  }
};

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

const speakWithBrowserTts = (text) => {
  if (!browserSpeech || !text || !text.trim()) return;
  browserSpeech.cancel();
  const utterance = new SpeechSynthesisUtterance(text.trim());
  utterance.lang = 'en-US';
  utterance.rate = 0.95;
  utterance.pitch = 1.0;
  browserSpeech.speak(utterance);
};

const setMessagePlaceholder = () => {
  if (modeSelect.value === 'lesson') {
    input.placeholder = 'Translate the Ukrainian sentence into English...';
    return;
  }
  input.placeholder = 'Type your sentence in English...';
};

const loadPromptPreview = async () => {
  const params = new URLSearchParams({
    mode: modeSelect.value || 'conversation',
    level: levelSelect.value || 'A1',
    topic: topicSelect.value || '',
    grammar_focus: grammarFocusSelect.value || '',
  });

  const res = await fetch(`/api/prompt-preview?${params.toString()}`);
  const json = await res.json();
  const prompt = json?.data?.prompt || '';
  promptPreviewText.value = prompt;
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

const loadGrammarTopics = async () => {
  const level = levelSelect.value;
  const res = await fetch(`/api/grammar-topics?level=${encodeURIComponent(level)}`);
  const json = await res.json();
  const items = json.data || [];
  grammarFocusSelect.innerHTML = '';

  items.forEach((item) => {
    const opt = document.createElement('option');
    opt.value = item.title;
    opt.textContent = item.title;
    grammarFocusSelect.appendChild(opt);
  });
};

const startSession = async () => {
  const payload = {
    mode: modeSelect.value || 'conversation',
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
    mode: modeSelect.value || 'conversation',
    tts_mode: 'browser',
    level: levelSelect.value,
    topic: topicSelect.value || 'introductions',
    grammar_focus: grammarFocusSelect.value || '',
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
      speakWithBrowserTts(json.data.reply);
    }
    if (json.data?.feedback?.tip) {
      appendMessage('ai', `Tip: ${json.data.feedback.tip}`);
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

const restartSession = (readyMessage) => {
  startSession()
    .then(() => loadPromptPreview())
    .then(() => {
      messages.innerHTML = '';
      appendMessage('ai', readyMessage);
    })
    .catch(() => {
      appendMessage('ai', 'Failed to start session.');
    });
};

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  const message = input.value.trim();
  if (!message) return;
  input.value = '';
  await sendChatMessage(message);
});

modeSelect.addEventListener('change', () => {
  setMessagePlaceholder();
  saveSettings();
  const readyMessage = modeSelect.value === 'lesson'
    ? 'Lesson mode ready. I will send a Ukrainian sentence, and you translate it to English.'
    : 'New conversation session started.';
  restartSession(readyMessage);
});

levelSelect.addEventListener('change', () => {
  Promise.all([loadTopics(), loadGrammarTopics()])
    .then(() => loadPromptPreview())
    .then(() => startSession())
    .then(() => {
      saveSettings();
      messages.innerHTML = '';
      appendMessage('ai', 'New session started.');
    })
    .catch(() => {
      appendMessage('ai', 'Failed to load topics for level.');
    });
});

topicSelect.addEventListener('change', () => {
  saveSettings();
  restartSession('New session started.');
});

grammarFocusSelect.addEventListener('change', () => {
  saveSettings();
  loadPromptPreview().catch(() => {
    promptPreviewText.value = 'Failed to load prompt preview.';
  });
});

copyPromptBtn.addEventListener('click', async () => {
  const text = promptPreviewText.value.trim();
  if (!text) {
    promptCopyStatus.textContent = 'Nothing to copy yet.';
    return;
  }

  try {
    await navigator.clipboard.writeText(text);
    promptCopyStatus.textContent = 'Prompt copied.';
  } catch (e) {
    promptCopyStatus.textContent = 'Copy failed. Select text and copy manually.';
  }
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

const initApp = async () => {
  const saved = readSavedSettings();

  if (typeof saved.mode === 'string' && hasSelectOption(modeSelect, saved.mode)) {
    modeSelect.value = saved.mode;
  }
  if (typeof saved.level === 'string' && hasSelectOption(levelSelect, saved.level)) {
    levelSelect.value = saved.level;
  }

  setMessagePlaceholder();

  await loadTopics();
  if (typeof saved.topic === 'string' && hasSelectOption(topicSelect, saved.topic)) {
    topicSelect.value = saved.topic;
  }

  await loadGrammarTopics();
  if (typeof saved.grammarFocus === 'string' && hasSelectOption(grammarFocusSelect, saved.grammarFocus)) {
    grammarFocusSelect.value = saved.grammarFocus;
  }

  await loadPromptPreview();
  await startSession();
  saveSettings();
  appendMessage('ai', 'Session ready. Start speaking.');
};

initApp().catch(() => {
  appendMessage('ai', 'Failed to initialize app.');
});
