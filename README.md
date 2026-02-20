# AI Teacher

Voice-first platform for learning English by topics.

## Stack status
Backend stack is not finalized yet.

Candidates:
- Laravel (PHP)
- Node.js (NestJS/Express)
- Other backend option to be decided

## MVP scope
- Topic-based lessons
- Chat-style conversation practice
- Voice input and AI voice output
- Progress tracking by topic

## Project status
Project scaffold and architecture setup in progress.

## Quick start
1. Copy env:
   - `Copy-Item .env.example .env`
2. Run web app:
   - `php -S localhost:8000 -t public`
3. Run DB migrations:
   - `php scripts/migrate.php`

## API endpoints
- `GET /api/topics?level=A1` dynamic topics by CEFR level
- `POST /api/chat` text chat + AI reply + TTS audio_url
- `POST /api/speech-to-text` multipart form with `audio` file
- `POST /api/text-to-speech` JSON `{ "text": "..." }`

## Local MySQL (Docker)
1. Start MySQL:
   - `docker compose up -d mysql`
2. Set DB vars in `.env`:
   - `DB_HOST=127.0.0.1`
   - `DB_PORT=3306`
   - `DB_NAME=ai_teacher`
   - `DB_USER=root`
   - `DB_PASS=root`
3. Run migrations:
   - `php scripts/migrate.php`
