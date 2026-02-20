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
<html lang="uk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AI Teacher</title>
  <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>
  <main class="wrap">
    <h1>AI Teacher</h1>
    <p class="subtitle">Каркас платформи: сайт + API на PHP 8.3. Тепер з підключеним demo chat.</p>
    <span class="pill">MVP Scaffold</span>

    <section class="grid top-grid">
      <article class="card">
        <h2>Frontend</h2>
        <p>Сторінка читає теми з API і відправляє повідомлення в chat endpoint.</p>
      </article>
      <article class="card">
        <h2>API</h2>
        <p>Ендпоінти: <code>/api/health</code>, <code>/api/topics</code>, <code>/api/chat</code>.</p>
      </article>
      <article class="card">
        <h2>Database</h2>
        <p>Схема MySQL для users/topics/lessons/dialogues в <code>database/schema.sql</code>.</p>
      </article>
    </section>

    <section class="chat card">
      <h2>Demo Speaking Chat</h2>
      <div class="row">
        <label for="level">Level</label>
        <select id="level">
          <option>A1</option>
          <option>A2</option>
          <option>B1</option>
          <option>B2</option>
          <option>C1</option>
          <option>C2</option>
        </select>
      </div>
      <div class="row">
        <label for="topic">Topic</label>
        <select id="topic"></select>
      </div>
      <div id="messages" class="messages"></div>
      <form id="chat-form" class="chat-form">
        <input id="message" type="text" placeholder="Напиши фразу англійською..." autocomplete="off" />
        <button type="submit">Send</button>
      </form>
      <small>Voice кнопка буде наступним кроком (STT/TTS інтеграція).</small>
    </section>

    <pre class="code">php -S localhost:8000 -t public</pre>
    <p>Далі: підключаємо auth, voice flow, і адмін-контент.</p>
  </main>
  <script src="/assets/app.js"></script>
</body>
</html>
HTML;

        Response::html($html);
    }
}
