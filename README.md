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