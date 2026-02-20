# Memory Note: Speaking Curriculum

Date: 2026-02-20

## Summary
- Built a full speaking-first CEFR curriculum (A1-C2).
- Grammar is ordered by communication value, not academic order.
- Each level has topic sets, speaking goals, and expected outcomes.
- Added training loop: shadowing, retell, role-play, and error journal.
- Added rubric and progression gates.

## Source of Truth
- `docs/SPEAKING_CURRICULUM_CEFR.md`

## Ops Update
- Serena onboarding completed for project `ai-teacher` on 2026-02-20.
- Serena memories created:
  - `project_overview.md`
  - `coding_style_and_conventions.md`
  - `suggested_commands.md`
  - `task_completion_checklist.md`

## Scaffold Update
- Added plain PHP app scaffold:
  - `public/index.php`
  - `src/bootstrap.php`
  - `src/Router.php`
  - `src/Controllers/*`
  - `src/Support/Response.php`
  - `src/Database/Database.php`
- Added MySQL schema:
  - `database/schema.sql`

## Frontend/API Integration Update
- Frontend is now connected to API:
  - `GET /api/topics` is consumed on page load.
  - `POST /api/chat` is used by demo chat form.
- Added frontend script:
  - `public/assets/app.js`
- Updated UI styles for chat:
  - `public/assets/styles.css`
- Current `/api/chat` response is mock and ready to be replaced with OpenAI integration.

## OpenAI + DB Runtime Update
- Replaced mock chat with real OpenAI integration:
  - `src/Services/OpenAIService.php`
  - `src/Controllers/ApiController.php`
- Added dialogue persistence service:
  - `src/Services/DialogueService.php`
- Frontend now persists conversation session via `dialogue_id`:
  - `public/assets/app.js`
- Added local MySQL runtime setup:
  - `docker-compose.yml`
- Added TLS CA bundle flow for PHP cURL:
  - `certs/cacert.pem`
  - `.env.example` now includes `OPENAI_CA_BUNDLE`
