# CSV Processor

A CSV processing backend application built as part of a developer technical assignment.

- **Backend:** PHP 8.2 (Laravel 11)
- **Database:** PostgreSQL 16 (dockerized)
- **Architecture:** Fully Dockerized — no host-side PHP, Composer, or PostgreSQL installation required.

---

### Features

- Vendor management with individual export configurations
- Rule-based CSV transformation logic:
  - **Multiply** — multiplies numeric column values by a defined factor
  - **Remove** — removes specified columns from export
  - **Regex** — applies regular expression replacements to column values
- Configurable export columns and CSV formatting options (delimiter, enclosure, escape, header)
- File upload endpoint for vendor-specific CSV processing
- Processed data exportable as CSV
- REST API endpoints for vendor CRUD, rule CRUD, CSV upload, and CSV export
- Comprehensive OpenAPI documentation via Swagger UI
- Automated feature and unit tests

---

## CSV Processing Logic

Each vendor has a configurable set of processing rules and export settings.
When a CSV file is uploaded for processing:

- The file is parsed row by row.
- Each transformation rule (Multiply, Remove, Regex) is applied in sequence.
- The resulting dataset is stored in the database for export or inspection.

**Export:**
- Normalized data can be exported via the `/api/vendors/{vendor}/export` endpoint.

---

## REST API Overview

Key resource endpoints include:

| Resource | Description |
|-----------|--------------|
| `GET /api/vendors` | List all vendors |
| `POST /api/vendors` | Create a new vendor |
| `GET /api/vendors/{vendor}` | Get vendor details |
| `PATCH /api/vendors/{vendor}` | Update vendor configuration |
| `DELETE /api/vendors/{vendor}` | Delete vendor |
| `GET /api/vendors/{vendor}/rules` | List vendor rules |
| `POST /api/vendors/{vendor}/rules` | Create rule |
| `PATCH /api/rules/{rule}` | Update rule |
| `DELETE /api/rules/{rule}` | Delete rule |
| `POST /api/vendors/{vendor}/upload` | Upload CSV for processing |
| `GET /api/vendors/{vendor}/export` | Export processed data as CSV |

* `{vendor}` and `{rule}` are replaced with target vendor and rule id

---

### API Documentation

The API is fully documented using **Swagger UI** powered by an OpenAPI YAML definition.

After starting the Docker containers, the documentation is available at:

```
http://localhost:8080/docs
```

It includes:
- All endpoints with example requests/responses
- Parameter and schema definitions

---

## Prerequisites

- [Docker](https://docs.docker.com/get-docker/) (tested with Docker Desktop on Windows using WSL2)
- [Docker Compose](https://docs.docker.com/compose/install/) (typically bundled with Docker Desktop)

---


### Environment Configuration Note

For convenience and to ensure the project can be launched immediately via Docker without any additional setup,
the `.env` file containing database credentials and application keys is included in the repository.

This would **not be done in a production environment**. Credentials are included here solely for local testing and simplification purposes.

---

## Quick Start Guide

The following commands are intended for a Unix-style shell environment such as WSL2 (Windows Subsystem for Linux), native Linux, or MacOS. Docker Desktop for Windows users are recommended to use WSL2 integration.

### Clone the repository

```bash
git clone https://github.com/kjlellep/csv-processor
cd csv-processor
```

### Build and start containers

```bash
docker compose up --build
```

This builds and starts:
- Laravel backend (http://localhost:8080)
- PostgreSQL database (port 5432 inside Docker)

After the initial build, install PHP dependencies:

```bash
docker compose exec app composer install
```

### Run migrations

```bash
docker compose exec app php artisan migrate
```

This creates all database tables for vendors, rules, and processed CSV data.

---

### Test in swagger

The system is now ready for functional testing at: http://localhost:8080/docs

#### (Optional) Seed demo data

To simplify functional testing, a **DemoSeeder** is included that creates a sample vendor (`Foo`) with three processing rules:

| Rule Type | Target Column | Description |
|------------|----------------|--------------|
| `MULTIPLY` | `Price` | Multiplies price by 1.24 with 2-decimal rounding |
| `REGEX` | `SKU` | Removes non-numeric prefix from SKU |
| `REMOVE` | `Quantity` | Removes the `Quantity` column from export |

You can run the seeder with the following command:

```bash
docker compose exec app php artisan migrate --seed
```

This will create:
- A vendor named “Foo”
- Default export columns (ProductName, Price, SKU, Quantity)
- A ready-to-use rule set for testing CSV upload and export endpoints

Once seeded, you can immediately:

- Upload a CSV file using POST /api/vendors/{vendor}/upload
  - An example CSV for upload is included with the repository at ```storage/app/private/examples/data.csv```
- Export the processed file using GET /api/vendors/{vendor}/export

---

### Run tests

The test suite uses a dedicated test database defined in `.env.testing`.

Before running the tests for the first time run migrations for the test environment as well:
 - Select **yes** when asked whether to create a new database
```bash
docker compose exec app php artisan migrate --env=testing
```

Finally, execute the tests:

```bash
docker compose exec app php artisan test
```

---

### Useful Docker Commands

Stop all containers:
```bash
docker compose down
```

Access PostgreSQL inside container:
```bash
docker exec -it csv_db psql -U laravel -d laravel
```

---

### Author

Built by **Karl-Jontahan Lellep**
