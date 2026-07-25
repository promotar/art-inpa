## 2026-06-28 - AI Assistant Conversation Persistence and CSRF Fix

### User Request

Fix AI Assistant plugin issues where frontend chat returned `CSRF token mismatch`, the popup did not minimize/close correctly, and conversations disappeared after refresh.

### Execution

- Added database-backed conversation history loading through `GET /ai-assistant/messages`.
- Added explicit conversation deletion through `DELETE /ai-assistant/conversation`.
- Updated the widget and full chat page to include CSRF token, history URL, and close URL.
- Updated the frontend JavaScript to send `same-origin` credentials and `X-CSRF-TOKEN`.
- Changed minimize to hide the panel without deleting the conversation.
- Changed close to delete the active conversation and clear the session pointer.
- Added CSS so `.ai-assistant-panel[hidden]` actually hides despite the panel flex layout.
- Bound authenticated conversations by `user_id`.
- Bound guest conversations by Laravel `session_id`.
- Added user/guest context to the AI Gateway request.
- Added plugin manifest function entries for history and close behavior.

### Backup

```text
/root/codex-backups/ai-assistant-plugin-install-20260628-013824
```

### Verification

- `php -l` passed for changed PHP files.
- Routes registered:
  - `GET /ai-assistant/chat`
  - `GET /ai-assistant/messages`
  - `POST /ai-assistant/message`
  - `DELETE /ai-assistant/conversation`
- Frontend widget includes CSRF/history/close attributes.
- Frontend POST to `/ai-assistant/message` succeeded without CSRF mismatch.
- History endpoint kept the conversation after refresh/session reuse.
- Close endpoint deleted the conversation.
- `php artisan test --no-ansi`: 25 passed, 61 assertions.
- `php artisan view:cache`, `route:cache`, and `config:cache` completed.

### Report

```text
/var/www/store.z4rank.com/laravel/docs/project-management/implementation-reports/AI-ASSISTANT-CONVERSATION-FIX-REPORT.md
```

### Credential Handling

Server access used existing project credentials from `passwords.txt`. No plaintext secret was copied into project documentation, reports, logs, commits, or public output.
