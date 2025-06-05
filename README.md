# VAT Corrector

A Laravel + Docker tool for validating, correcting, and analyzing Italian VAT numbers.

![Laravel](https://img.shields.io/badge/Laravel-10.x-red)
![Docker](https://img.shields.io/badge/Docker-Ready-blue)
![Status](https://img.shields.io/badge/status-in--development-yellow)

## 🎯 Goals

- Validate Italian VAT numbers (must start with `"IT"` followed by exactly 11 digits)
- Automatically correct malformed VAT codes (e.g. add missing `"IT"` prefix)
- Reject invalid entries (e.g. wrong length, non-numeric characters)
- Allow users to upload a CSV file containing VAT numbers
- Classify VAT numbers into:
  - ✅ Valid VAT numbers (e.g. `IT12345678901`)
  - 🔁 Corrected VAT numbers (e.g. `98765432158` → `IT98765432158`)
  - ❌ Invalid VAT numbers (e.g. `IT12345`, `123-hello`)
- Display the results clearly via:
  - An HTML interface grouped by category
  - A downloadable output file (CSV or JSON)
- Provide a form to test a single VAT number:
  - Return status: valid / corrected / invalid
  - If corrected: show what was changed
  - If invalid: explain why it was rejected
---

## 🚀 Installation

### 1. Clone the repository

```bash
git clone git@github.com:LeonardD93/vat-corrector.git
cd vat-corrector

```
### 2. Start Docker containers

Requirement: Docker must be installed and running

```bash
./vendor/bin/sail up
 #or
 sail up -d 

```

### 3. Install PHP dependencies

```bash
sail composer install

```

### 4. Copy .env and generate app key

```bash
cp .env.example .env
sail artisan key:generate

```

### 5. Run database migrations

```bash
sail artisan migrate

```

## ⚙️ Frontend Setup

This project uses [Vite](https://vitejs.dev/) to build frontend assets (JS/CSS), integrated with Laravel.

Laravel reads the compiled assets from `public/build/manifest.json`. This file is generated when running either `npm run dev` or `npm run build`.

### ✅ Required Node.js version

You must use **Node.js 18.x (LTS)**. Other versions (like 21 or 23) may not be compatible with dependencies like Rollup.

We recommend using [`nvm`](https://github.com/nvm-sh/nvm) to manage your Node version:

```bash
nvm install 18
nvm use 18

```

### 🔨 Build or Dev Mode
You have two options:

🧪 Development mode (auto-reload):

```bash
npm install
npm run dev

```

Or with Sail:

```bash
sail npm install
sail npm run dev

```

This will start Vite in development mode with hot module reload.

✅ Production / One-time build:
If you prefer to run only Laravel (without a separate Vite process), generate the static assets once:

```bash
npm run build
# or
sail npm run build

```

This will create the public/build/manifest.json file required by Laravel to load JS and CSS.

After that, Laravel will serve the compiled assets directly — no need to run Vite continuously.





### 🛰️ Running Services (via Docker/Sail)

The following services are started automatically by Laravel Sail and are accessible locally:

| Service         | URL / Host                                | Description                    |
|-----------------|--------------------------------------------|--------------------------------|
| **Laravel App** | [http://localhost](http://localhost)       | Main Laravel application       |
| **PhpMyAdmin**  | [http://localhost:8081](http://localhost:8081) | Web-based MySQL database viewer |
| **Mailpit**     | [http://localhost:8025](http://localhost:8025) | Catch-all email testing tool   |
| **MeiliSearch** | [http://localhost:7700](http://localhost:7700) | Fast search engine (optional)  |
| **MySQL**       | `mysql:3306` (Docker network)              | MySQL 8 database service       |
| **Redis**       | `redis:6379` (Docker network)              | Redis key/value store          |

> All internal service names (like `mysql`, `redis`) are available inside the Docker network.