# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

WeTalk is a **mobile-first mini-game portal + customer service chat platform**. Users browse games and chat with customer service agents via real-time messaging.

## Architecture

- **Backend**: PHP Webman (Workerman) at `app/` — serves HTTP API on port 8787, WebSocket on port 8788
- **User Frontend**: Vue 3 + TypeScript + Vant 4 at `frontend/` — mobile browser app (port 5173)
- **CS Workspace**: Vue 3 + TypeScript + Vant 4 at `cs-frontend/` — mobile browser app (port 5174)
- **Admin Panel**: Vue 3 + TypeScript + Element Plus at `admin/` — desktop browser app (port 5175)

## Commands

```bash
# Backend
cd app && php windows.php          # Start Webman server (Windows)
cd app && php webman start         # Start Webman server (Linux)

# Frontend - User
cd frontend && npm run dev         # Dev server (5173)

# Frontend - CS Workspace
cd cs-frontend && npm run dev      # Dev server (5174)

# Frontend - Admin
cd admin && npm run dev            # Dev server (5175)
```

## Database

MySQL database `wetalk`. Schema in `database/init.sql`. Run it once to initialize tables and admin account (admin/admin123).

## Three Roles

| Role | Access |
|------|--------|
| `admin` | Admin panel — manages users, CS agents, albums, games, settings |
| `customer_service` | CS workspace — chats with users, sends albums |
| `user` | User app — plays games, chats with CS after entering access code |

## Key Patterns

- JWT auth via `Authorization: Bearer <token>` header on all `/api` routes except login
- Admin routes under `/api/admin/*` require `role === 'admin'`
- WebSocket auth: client sends `{ event: "auth", data: { token } }` after connecting
- User login: first time requires verify_code; subsequent logins only need username (no password)
- CS login: username + password, only `role === 'customer_service'` accounts allowed
- Admin creates users with username, nickname, password (for CS login) and auto-generates verify_code (for user first login)
- Chat access requires a system-configured access code (`settings.chat_access_code`)
- Album messages in chat: `type === 'album'`, `content` is album ID
