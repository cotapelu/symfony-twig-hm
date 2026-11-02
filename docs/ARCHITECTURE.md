# Architecture Documentation

## Phường/Xã Super App – Polyglot Federated Modular Monolith

This document describes the high-level architecture and design principles used across the codebase.

---

## 1. Overview

The system is a **modular monolith** composed of multiple backend services written in different languages (PHP, Go, Python, Kotlin, Rust, Zig). Each backend implements a subset of the overall domain functionality, but all adhere to consistent architectural patterns and interfaces.

The frontend is a **Svelte** single-page application that communicates with the backends via RESTful APIs (and potentially GraphQL in the future).

---

## 2. Core Principles

- **Modularity**: Features are isolated into modules with clear boundaries.
- **Layered architecture**: Each module follows Contracts → Domain → Application → Infrastructure.
- **Technology heterogeneity**: Different languages coexist; contracts are defined via language‑agnostic interfaces (typically OpenAPI spec or shared DTO definitions).
- **Testability**: High test coverage targets (≥80%) with unit and integration tests.
- **Observability**: Structured logging, Prometheus metrics, and health checks on all backends.

---

## 3. Layer Responsibilities

| Layer          | Responsibility                                                                 |
|----------------|--------------------------------------------------------------------------------|
| **Contracts**  | DTOs, request/response schemas, service interfaces.                           |
| **Domain**     | Business entities, value objects, domain services, invariants.                |
| **Application**| Use cases, orchestration of domain objects, transaction scripts.             |
| **Infrastructure** | Database repositories, external API clients, messaging, caching.          |

Modules should never depend on outer layers; Infrastructure implements interfaces defined in Domain/Contracts.

---

## 4. Cross‑Cutting Concerns

- **Config**: Environment‑based configuration loaded at startup (`config.yaml`, `.env`, or OS env vars).
- **Logging**: JSON structured logs (where supported; e.g., Rust uses `tracing`, Python uses `structlog`).
- **Metrics**: Prometheus `/metrics` endpoint exposing counters, gauges, histograms.
- **Health**: `/health` endpoint for liveness/readiness probes.
- **Auth**: JWT‑based authentication; each backend verifies tokens independently.
- **Error Handling**: Standardized error responses with appropriate HTTP status codes.

---

## 5. Module Conventions

### Naming

- Package/module names use `CamelCase` for Go, `snake_case` for Python, `PascalCase` for C‑family languages.
- Service interfaces end with `Service`; repositories end with `Repository`; handlers/controllers end with `Handler` or `Controller`.

### File Layout

Example Go module:

```
go/
 └── modules/
     └── Banking/
         ├── api/            # HTTP handlers (presentation)
         ├── internal/
         │   └── models/     # Domain entities and value objects
         ├── service/        # Application layer, business use cases
         ├── repository/     # Infrastructure, persistence
         └── sqlite.go       # SQLite implementation (for demo)
```

Similar patterns apply to other languages (adjusting for idiomatic structure).

---

## 6. Inter‑Service Communication

When backends need to collaborate, they use:

- **HTTP/REST** for synchronous calls.
- **Message queues** (e.g., RabbitMQ, Redis) for eventual consistency (to be introduced).
- **Shared database** is avoided; each backend owns its data.

---

## 7. Testing Strategy

- **Unit tests** cover domain logic and services in isolation (use mocks/fakes for dependencies).
- **Integration tests** verify repository implementations against a test database (usually SQLite in‑memory or a dedicated test schema).
- **End‑to‑end tests** exercise the full HTTP stack (few, due to complexity).

Coverage is measured per backend; goal: ≥80% line coverage.

---

## 8. Development Workflow

1. Create a feature branch.
2. Write unit tests for new logic.
3. Ensure all existing tests pass.
4. Update documentation if you change architecture or public APIs.
5. Open a pull request with a clear description.

Refer to `docs/EVOLUTION.md` for a detailed history of design decisions.

---

## 9. Observability

All backends expose:

- `GET /health` – returns `{"status":"ok"}` (or appropriate detail).
- `GET /metrics` – Prometheus text format metrics.
- Logs are written to stdout in JSON (where implemented).

Future work: OpenTelemetry distributed tracing.

---

## 10. Security Considerations

- **Input validation**: All external inputs validated using schema validators.
- **SQL injection prevention**: Parameterized queries (prepared statements) are mandatory.
- **Authentication**: JWT with RS256; tokens signed by central auth service (stub in some backends).
- **Secrets**: Never committed; use environment variables or secret managers.
- **Rate limiting**: Applied at API gateway or middleware level.

See module‑specific docs for additional security notes.

---

*This document is a living reference. Update it when architectural decisions change.*