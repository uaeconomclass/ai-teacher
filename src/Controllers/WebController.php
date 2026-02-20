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
      <p class="subtitle">Minimal MVP interface with a real AI conversation loop.</p>
      <div class="stats">
        <div class="stat">
          <span>Stack</span>
          <strong>PHP + MySQL</strong>
        </div>
        <div class="stat">
          <span>Chat API</span>
          <strong id="api-status">Checking...</strong>
        </div>
      </div>
      <div class="chips">
        <button type="button" class="chip" data-text="Hi! I want to practice introductions.">Intro</button>
        <button type="button" class="chip" data-text="Can we practice daily routine?">Routine</button>
        <button type="button" class="chip" data-text="Help me speak about travel plans.">Travel</button>
      </div>
      <p class="hint">Voice (STT/TTS) is the next step.</p>
    </aside>

    <section class="chat-panel">
      <header class="chat-head">
        <div>
          <h2>Conversation</h2>
          <p>Pick level and topic, then start speaking in text mode.</p>
        </div>
      </header>

      <div class="controls">
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
      </div>

      <div id="messages" class="messages"></div>

      <form id="chat-form" class="chat-form">
        <input id="message" type="text" placeholder="Type your sentence in English..." autocomplete="off">
        <button id="send-btn" type="submit">Send</button>
      </form>
      <p class="footnote">Command: <code>php -S localhost:8000 -t public</code></p>
    </section>
  </main>
  <script src="/assets/app.js"></script>
</body>
</html>
HTML;

        Response::html($html);
    }
}
