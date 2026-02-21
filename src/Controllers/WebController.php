<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Support\Response;

final class WebController
{
    public function home(): void
    {
        $html = <<<'HTML'
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AI Teacher</title>
  <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>
  <div class="bg-shape"></div>
  <main class="layout">
    <aside class="side">
      <div class="brand">
        <span class="brand-dot"></span>
        <strong>AI Teacher</strong>
      </div>
      <h1>Speak English<br>Every Day</h1>
      <p class="subtitle">Practice real conversation by level and topic with instant feedback.</p>
    </aside>

    <section class="chat-panel">
      <header class="chat-head">
        <div>
          <h2>Conversation</h2>
          <p>Choose your level and topic, then start your session.</p>
        </div>
      </header>

      <div class="controls">
        <label>
          <span>Mode</span>
          <select id="mode">
            <option value="conversation">Conversation</option>
            <option value="lesson">Lesson (UKR -> EN)</option>
          </select>
        </label>
        <label>
          <span>Level</span>
          <select id="level">
            <option>A1</option>
            <option>A2</option>
            <option>B1</option>
            <option>B2</option>
            <option>C1</option>
            <option>C2</option>
          </select>
        </label>
        <label>
          <span>Topic</span>
          <select id="topic"></select>
        </label>
        <label>
          <span>Grammar Focus</span>
          <select id="grammar-focus"></select>
        </label>
      </div>

      <div class="prompt-preview">
        <div class="prompt-head">
          <strong>Prompt Preview</strong>
          <button id="copy-prompt-btn" type="button">Copy Prompt</button>
        </div>
        <textarea id="prompt-preview-text" readonly></textarea>
        <p id="prompt-copy-status" class="prompt-copy-status"></p>
      </div>

      <div id="messages" class="messages"></div>

      <form id="chat-form" class="chat-form">
        <input id="message" type="text" placeholder="Type your sentence in English..." autocomplete="off">
        <button id="send-btn" type="submit">Send</button>
      </form>
      <div class="voice-row">
        <button id="record-btn" type="button" class="voice-btn">Hold to Talk</button>
        <button id="voice-mode-btn" type="button" class="voice-btn mode-btn">Voice Mode: OFF</button>
        <span id="voice-status" class="voice-status">Idle</span>
      </div>
      <audio id="player" class="player" controls></audio>
    </section>
  </main>
  <script src="/assets/app.js"></script>
</body>
</html>
HTML;

        Response::html($html);
    }
}
