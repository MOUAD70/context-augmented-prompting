# Context-Augmented Local LLM Chatbot

> An internal AI assistant for **FireSecure SARL**, powered by a locally-running LLM via [Ollama](https://ollama.com) and a context-aware knowledge retrieval system — no cloud API keys required.

---

## Overview

FireBot is a **Laravel 12** backend that exposes a REST API endpoint for chatting with a locally-hosted language model (Microsoft **phi3** via Ollama). Instead of sending raw user queries to the LLM, FireBot first retrieves the most relevant entries from a structured **knowledge base** stored in the database, then injects them as context into the prompt. This approach — known as **context-augmented prompting (RAG-lite)** — significantly improves response accuracy and keeps answers grounded in company-specific data.

### Key Characteristics

| Feature | Detail |
|---|---|
| LLM | `phi3` (Microsoft) via Ollama |
| Backend | Laravel 12 / PHP 8.2+ |
| Frontend | Blade + Tailwind CSS v4 + Vite |
| Database | MySQL (SQLite supported for dev) |
| Auth | Laravel Sanctum |
| Retrieval | Keyword scoring with basic stemming |

---

## Architecture

```
User Message
     │
     ▼
┌─────────────────────────────────────────────┐
│  ChatbotController::chat()                   │
│                                              │
│  1. Extract & clean keywords (stop-word     │
│     removal, basic stemming)                │
│                                              │
│  2. Score all chatbot_knowledge rows        │
│     (title match: +3, category: +2,         │
│      content: +1), take top 5              │
│                                              │
│  3. Build structured prompt with context   │
│                                              │
│  4. POST to Ollama → phi3                  │
│                                              │
│  5. Return JSON { reply: "..." }           │
└─────────────────────────────────────────────┘
```

---

## Prerequisites

Before you begin, ensure you have the following installed:

- **PHP** >= 8.2 with extensions: `pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`
- **Composer** >= 2.x
- **Node.js** >= 18.x and **npm**
- **MySQL** >= 8.x (or use SQLite for quick local dev)
- **Ollama** — [install from ollama.com](https://ollama.com)

---

## Getting Started

### 1. Clone the repository

```bash
git clone https://github.com/MOUAD70/context-augmented-prompting.git
cd local-llm
```

### 2. Pull the LLM model

FireBot uses **phi3** by default. Pull it with Ollama before starting the app:

```bash
ollama pull phi3
```

Verify Ollama is running (it should be available at `http://localhost:11434`):

```bash
ollama list
```

### 3. Install PHP dependencies

```bash
composer install
```

### 4. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and update your database connection:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=local_llm
DB_USERNAME=root
DB_PASSWORD=your_password
```

> **Tip:** For a quick start without MySQL, set `DB_CONNECTION=sqlite` and create the file:
> ```bash
> touch database/database.sqlite
> ```

### 5. Run database migrations

```bash
php artisan migrate
```

### 6. Start the development server

Run the project:

```bash
php artisan serve` — Laravel on `http://localhost:8000`
```

---

## Knowledge Base

FireBot answers questions **only** from data stored in the `chatbot_knowledge` table. You must seed this table with your company's information.

### Schema

| Column | Type | Description |
|---|---|---|
| `id` | bigint | Auto-increment primary key |
| `title` | string | Short topic title (used for keyword matching, weight ×3) |
| `category` | string (nullable) | Grouping label, e.g. `procedures`, `roles` (weight ×2) |
| `content` | longText | The actual knowledge content (weight ×1) |
| `created_at`, `updated_at` | timestamp | Timestamps |

### Adding Knowledge Entries

You can insert entries directly via Tinker:

```bash
php artisan tinker
```

```php
\App\Models\ChatbotKnowledge::create([
    'title'    => 'SSIAP Roles',
    'category' => 'roles',
    'content'  => 'SSIAP1 is a security agent, SSIAP2 is a supervisor, SSIAP3 is the chief.',
]);
```

Or create a seeder for bulk insertion:

```bash
php artisan make:seeder ChatbotKnowledgeSeeder
php artisan db:seed --class=ChatbotKnowledgeSeeder
```

---

## API Reference

### `POST /api/chat`

Send a user message and receive an AI-generated reply grounded in the knowledge base.

**Request**

```http
POST /api/chat
Content-Type: application/json

{
  "message": "What are the responsibilities of an SSIAP2?"
}
```

**Success Response** `200 OK`

```json
{
  "reply": "An SSIAP2 is a supervisor responsible for managing a team of SSIAP1 agents..."
}
```

**No Knowledge Match Response** `200 OK`

```json
{
  "reply": "I don't have that information in the company knowledge base."
}
```

**Ollama Error Response** `500`

```json
{
  "error": "Ollama request failed",
  "status": 503,
  "detail": "..."
}
```

**Validation**

| Field | Rules |
|---|---|
| `message` | Required, string, max 2000 characters |

---

##  How the Retrieval Works

1. **Stop-word filtering** — Common words (`the`, `is`, `how`, etc.) are stripped from the query.
2. **Stemming** — Basic suffix removal (`-ing`, `-ed`, `-s`) reduces words to their root form.
3. **Scoring** — Every knowledge row is scored against the remaining keywords:
   - Match in `title` → **+3 points**
   - Match in `category` → **+2 points**
   - Match in `content` → **+1 point**
4. **Top-5 selection** — The highest-scoring rows are injected as context into the LLM prompt.
5. **Ollama call** — The prompt is sent to `phi3` with `temperature: 0.1` for deterministic, focused answers.
