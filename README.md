<p align="center">
  <img src="assets/images/logo/logo.png" width="120" alt="Implose.gg Logo" />
</p>

<h1 align="center">Implose.gg</h1>

<p align="center">
  A pixel-styled PWA that turns solo study into a live multiplayer quiz — with AI explanations, a course marketplace, achievements, and rewards.
</p>

---

## Overview

Implose.gg is a browser-based learning game inspired by boss-battle quiz apps. Players join or host quiz rooms, answer questions under a timer, and take chunks off a shared boss's HP for every correct answer. Wrong answers get a per-question AI explanation. Creators publish their own courses to a public marketplace where other players can fork, play, and rate them. Admins and moderators keep an eye on content, users, and feedback from separate role-scoped panels.

The app is a PHP + MySQL stack served through XAMPP, installable as a fullscreen landscape PWA.

---

## Screenshots

### Guest — Public

| Welcome | Sign in | Sign up |
|:---:|:---:|:---:|
| ![Welcome](screenshots/welcome.png) | ![Sign in](screenshots/sign-in.png) | ![Sign up](screenshots/sign-up.png) |

| Forgot password | About | FAQ |
|:---:|:---:|:---:|
| ![Forgot password](screenshots/forgot-password.png) | ![About](screenshots/about.png) | ![FAQ](screenshots/faq.png) |

| Contact | Download | Policy |
|:---:|:---:|:---:|
| ![Contact](screenshots/contact.png) | ![Download](screenshots/download.png) | ![Policy](screenshots/policy.png) |

### User

**Home & hosting**

| Home | Host room | Room lobby |
|:---:|:---:|:---:|
| ![Home](screenshots/user-home.png) | ![Host room](screenshots/user-host-room.png) | ![Room lobby](screenshots/user-room-lobby.png) |

**Live gameplay**

| Player lobby | Live quiz | Live leaderboard |
|:---:|:---:|:---:|
| ![Player lobby](screenshots/user-player-lobby.png) | ![Live quiz](screenshots/user-live-quiz.png) | ![Live leaderboard](screenshots/user-live-leaderboard.png) |

**AI tutor**

| AI explanation |
|:---:|
| ![AI explanation](screenshots/user-ai-explanation.png) |

**Course builder**

| My courses | Course setup | Create course |
|:---:|:---:|:---:|
| ![My courses](screenshots/user-view-course.png) | ![Course setup](screenshots/user-edit-course.png) | ![Create course](screenshots/user-create-course.png) |

**Marketplace**

| Browse | Course detail | Publish |
|:---:|:---:|:---:|
| ![Marketplace](screenshots/user-marketplace.png) | ![Course detail](screenshots/user-marketplace-detail.png) | ![Publish](screenshots/user-publish.png) |

| My forks | Manage courses | |
|:---:|:---:|:---:|
| ![My forks](screenshots/user-forks.png) | ![Manage courses](screenshots/user-manage-courses.png) | |

**Progress & rewards**

| Achievements | Rewards | Global leaderboard |
|:---:|:---:|:---:|
| ![Achievements](screenshots/user-achievements.png) | ![Rewards](screenshots/user-rewards.png) | ![Leaderboard](screenshots/user-leaderboard.png) |

| Learning analytics | | |
|:---:|:---:|:---:|
| ![Analytics](screenshots/user-analytics.png) | | |

**Account centre**

| Account | Active sessions |
|:---:|:---:|
| ![Account](screenshots/user-account.png) | ![Sessions](screenshots/user-sessions.png) |

### Admin

| Dashboard | Users | Achievements |
|:---:|:---:|:---:|
| ![Dashboard](screenshots/admin-dashboard.png) | ![Users](screenshots/admin-users.png) | ![Achievements](screenshots/admin-achievements.png) |

| Rewards | Feedback | Learning analytics |
|:---:|:---:|:---:|
| ![Rewards](screenshots/admin-rewards.png) | ![Feedback](screenshots/admin-feedback.png) | ![Analytics](screenshots/admin-analytics.png) |

| AI settings | Logs | Reports |
|:---:|:---:|:---:|
| ![AI](screenshots/admin-ai.png) | ![Logs](screenshots/admin-logs.png) | ![Reports](screenshots/admin-reports.png) |

| File management | | |
|:---:|:---:|:---:|
| ![File management](screenshots/admin-files.png) | | |

### Moderator

| Dashboard | Achievements | Feedback |
|:---:|:---:|:---:|
| ![Dashboard](screenshots/mod-dashboard.png) | ![Achievements](screenshots/mod-achievements.png) | ![Feedback](screenshots/mod-feedback.png) |

---

## Features

### Guest (Public)
- Landing page with tap-to-start entry into the game
- Learn what Implose.gg is — about, FAQ, download instructions
- Contact form for general questions and bug reports
- Read the privacy and terms policy

### User
- **Home** — hero panel with your streak, points, and quick access to hosting or joining a room
- **Host room** — build a quiz session from any of your courses, share a room code, and start the countdown
- **Live quiz** — real-time boss-battle rounds: everyone answers under a timer, correct answers damage the boss, chat runs alongside
- **AI explanations** — get a tailored explanation for every wrong answer, powered by a Claude-compatible endpoint
- **Marketplace** — browse public courses, fork them into your own library, rate what you played, publish your own
- **Course builder** — create courses, add levels (quizzes), and author multiple-choice or short-answer questions with per-question time limits and marks
- **Achievements** — unlock badges triggered by in-game events
- **Rewards** — redeem hard-earned points for admin-managed rewards, receive a redemption token
- **Leaderboard** — global rankings by total score, damage, accuracy, and completion time
- **Account centre** — profile, avatar, security (change password, active sessions), and personal feedback thread

### Admin
- **Dashboard** — live counts of users, courses, sessions, and pending items
- **User management** — create, edit, suspend, and role-change any account
- **Course & content** — manage marketplace listings, feedback replies, and reports
- **Achievements & rewards** — full CRUD with icon uploads and stock tracking
- **Learning analytics** — quiz-level performance, topic accuracy, response-time distribution
- **Reports** — moderate flagged messages and marketplace courses
- **Logs** — full system activity log, filterable and exportable
- **AI settings** — configure the AI endpoint, model, and API key used for explanations
- **File management** — audit and clean up the uploads folder
- **Export report** — generate a PDF report bundle

### Moderator
- **Dashboard** — pending reports and feedback awaiting review
- **Achievements** — create and edit shared achievement definitions
- **Feedback & reports** — respond to user submissions and resolve reports
- **Profile** — personal profile management

---

## Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.2 |
| Database | MySQL / MariaDB |
| Server | Apache 2.4 (XAMPP) |
| Frontend | Vanilla HTML / CSS / JS, pixel-art design system |
| PWA | `manifest.json` — fullscreen landscape, installable |
| PDFs | [dompdf/dompdf](https://github.com/dompdf/dompdf) `^3.1` (generate) + [smalot/pdfparser](https://github.com/smalot/pdfparser) `^2.12` (parse) |
| Email | [resend/resend-php](https://github.com/resend/resend-php) `^1.3` — OTP verification, password reset |
| Captcha | Cloudflare Turnstile |
| AI | Any Claude-compatible `/v1/chat/completions` endpoint |
| Package manager | Composer |

---

## Getting Started

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) with Apache and MySQL running
- [Composer](https://getcomposer.org/)
- PHP 8.2 or higher

### Setup

1. **Clone into your XAMPP htdocs as `Implose.gg-src`** (the folder name matters — all in-app paths reference `/Implose.gg-src/...`):
   ```bash
   cd /path/to/xampp/htdocs
   git clone https://github.com/AaronKhoolb/Implose.gg.git Implose.gg-src
   cd Implose.gg-src
   ```

2. **Install PHP dependencies**:
   ```bash
   composer install
   ```

3. **Create the database**:
   - Open phpMyAdmin (`http://localhost/phpmyadmin`)
   - Create a database named `implose.gg`
   - Import `implose_gg.sql`

4. **Configure environment**:
   Copy `.env` and fill in your values:
   ```env
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=implose.gg
   DB_USERNAME=root
   DB_PASSWORD=your_mysql_password

   SMTP_API_KEY=your_resend_api_key
   SMTP_EMAIL="Implose.gg <noreply@yourdomain.com>"

   TURNSTILE_SITE_KEY=your_turnstile_site_key
   TURNSTILE_SECRET_KEY=your_turnstile_secret_key
   ```

5. **Set the AI key** (optional — required for in-game AI explanations):
   Log in as admin and open **Admin → AI Settings**, or update `AI_SETTING_T` directly with your endpoint and key.

6. **Open the app**:
   ```
   http://localhost/Implose.gg-src/
   ```

---

## Default Credentials

All seed accounts share the same password: `password123`

| Role | Email | Password |
|---|---|---|
| Admin | `admin@example.com` | `password123` |
| Moderator | `moderator@example.com` | `password123` |
| User | `user@example.com` | `password123` |

> Change these before you deploy anywhere public.

---

## Project Structure

```
Implose.gg-src/
├── index.php                  # Root landing page
├── manifest.json              # PWA manifest
├── composer.json              # PHP dependencies
├── implose_gg.sql             # Database dump (schema + seed)
├── .env                       # Environment config (fill in locally)
├── actions/                   # Form handlers (POST endpoints)
│   ├── auth/                  # Sign up / in / OTP / password reset
│   ├── user/                  # Game, marketplace, quiz-room, chat
│   ├── moderator/
│   └── admin/
├── api/                       # JSON endpoints for live gameplay
│   └── game/
│       ├── ai_explanation/    # AI-powered explanations on wrong answers
│       ├── chat/              # In-room chat polling
│       └── quiz_room/         # Room / boss / leaderboard state
├── includes/                  # Shared PHP: db, auth, header, AI engine
├── pages/                     # Rendered views
│   ├── auth/                  # Sign in / up / OTP / reset
│   ├── public/                # About, FAQ, contact, policy
│   ├── user/                  # Home, game, marketplace, account, etc.
│   ├── moderator/
│   └── admin/
├── assets/                    # CSS / JS / fonts / images
│   ├── css/
│   ├── js/
│   ├── fonts/                 # Press Start 2P, Pixelify Sans, Saira, Inter
│   └── images/
├── uploads/                   # User-uploaded content (avatars, files, etc.)
└── vendor/                    # Composer packages
```

---

## Contributors

<a href="https://github.com/AaronKhoolb/Implose.gg/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=AaronKhoolb/Implose.gg&v=1" alt="Contributors" />
</a>
