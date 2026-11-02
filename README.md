# Phường/Xã Super App

**Polyglot Federated Modular Monolith** with a Svelte frontend and multiple backend services (PHP, Go, Python, Kotlin, Rust, Zig).

---

## Overview

The Phường/Xã Super App is a citizen-focused platform integrating services such as health, education, finance, social, volunteering, and more. It demonstrates a modular monolith architecture with independent modules and clear layering (Contracts → Domain → Application → Infrastructure).

This repository contains:

- **Frontend**: Svelte SPA under `frontend/`
- **Backends**:
  - **PHP Symfony**: `php-symfony/`
  - **Go (Gin)**: `go/`
  - **Python (FastAPI)**: `python/`
  - **Kotlin (Ktor)**: `kotlin/`
  - **Rust (axum)**: `rust/`
  - **Zig**: `zig/`
- **Shared documentation**: `docs/`

---

## Architecture

Each backend implements a subset of the overall domain modules. Modules are designed to be pluggable and follow a layered architecture:

```
Contracts   – Interfaces and DTOs
Domain      – Business entities and rules
Application – Use cases and services
Infrastructure – Database, external APIs, messaging
```

Shared cross-cutting concerns: logging, metrics, security, configuration.

---

## Repository Structure

```
.
├── docs/                 # Project documentation, evolution log, metrics
├── frontend/             # Svelte single page application
├── php-symfony/          # PHP backend (Symfony)
├── go/                   # Go backend (Gin)
├── python/               # Python backend (FastAPI)
├── kotlin/               # Kotlin backend (Ktor)
├── rust/                 # Rust backend (axum)
├── zig/                  # Zig backend (std http)
└── ...
```

---

## Getting Started

### Prerequisites

- **Python**: 3.11+, pip, venv
- **Go**: 1.21+
- **Rust**: stable toolchain (`rustup`, `cargo`)
- **Zig**: 0.16.0 or later
- **Node.js**: 18+ (for frontend)
- ** Databases**: PostgreSQL, Redis (can be run via Docker compose)
- **Quality Tools**: See [Quality Gate Setup](#quality-gate-setup)

### Setup

1. Clone the repository.
2. Install runtime dependencies:
   ```bash
   make install-deps
   ```
3. Install quality gate tools (optional but required for `make quality`):
   ```bash
   make install-quality-tools
   ```
4. For each backend you want to run:
   - Navigate to the backend directory.
   - Set up environment variables (see backend-specific `README` if available).
   - Run database migrations if required.
   - Start the server.

> **Note**: See [Quality Gate Documentation](docs/QUALITY_GATE.md) for full details on quality enforcement, CI/CD integration, and troubleshooting.

#### Example: Python

```bash
cd python
python -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
# Set DATABASE_URL etc.
uvicorn app.main:app --reload
```

#### Example: Rust

```bash
cargo run --manifest-path rust/Cargo.toml
```

#### Example: Go

```bash
cd go
go mod download
go run ./cmd/server
```

#### Example: Zig

```bash
cd zig
zig build
```

---

## Testing

Each backend has its own test suite.

- **Python**: `pytest` (coverage ~81%)
- **Rust**: `cargo test` (coverage ~82%)
- **Go**: `go test ./...` (service coverage improving)
- **Zig**: `zig test main_test.zig` (63 tests, coverage tooling pending)

Run all tests with:

```bash
# From each backend directory
```

---

## Coverage Targets

We aim for ≥80% line coverage across all backends. Current status:

| Backend | Coverage |
|---------|----------|
| Python  | 81.41%   |
| Rust    | 82.59%   |
| Go      | service 81.8% (overall lower) |
| Zig     | measurement blocked – tests passing |

---

## Contributing

Follow the existing architectural patterns and ensure tests pass before submitting changes. See `docs/EVOLUTION.md` for development history and decision log.

---

## License

This project is licensed under the **GNU Lesser General Public License v3.0 (LGPL-3.0)**.

- Full license text: [`LICENSE`](./LICENSE) or [`COPYING.LESSER`](./COPYING.LESSER)
- See [`NOTICE.md`](./NOTICE.md) for attribution and additional information.

**Key points:**
- You are free to use, modify, and distribute this software.
- If you modify LGPL-licensed components, you must share those modifications under LGPL-3.0.
- You may use this library in proprietary applications (via dynamic linking) without open-sourcing your entire application.

For more details, see the license files or visit: https://www.gnu.org/licenses/lgpl-3.0.html
