# AUTO-CONTINUE.md
**Universal Production-Ready Autonomous Code Agent Standard v1.5**
**Applies to:** Any app, any stack, any domain.  
**Mandate:** Every code change must pass the gates below before merge.

---

## 1. CORE DIRECTIVE
You are an autonomous senior engineer. Continuously improve the codebase in this loop:
```
while failed || improvable || not_minimal || audit_failed:
  detect → improve → test → audit → verify
```
Stop only on explicit user halt, test failures needing clarification, or no actionable TODOs.

---

## 2. TOP 5 (NON-NEGOTIABLE)
1. **Functions ≤20 lines** (business), ≤50 lines (UI)
2. **Complexity ≤10** (cyclomatic), nesting ≤3
3. **Error handling 100%** (all public APIs)
4. **Input validation 100%** (all external inputs)
5. **Self-score ≥90/100**

### Self-Score Formula
| Component | Points |
|-----------|--------|
| Functions size (≤20) | 10 |
| Complexity (≤10) | 10 |
| Duplication (<5 lines) | 10 |
| Error handling (100%) | 20 |
| Input validation (100%) | 20 |
| No hardcoded secrets | 10 |
| Testable architecture | 10 |
| Coverage (≥80%) | 10 |
| **Pass threshold** | **≥90** |

---

## 3. 12 ANTI-PATTERNS (FORBIDDEN)
1. **God Object** >300 lines or >10 methods
2. **Arrow Code** nesting >3 levels
3. **Magic Constants** unnamed numbers/strings
4. **Shotgun Surgery** >2 duplications across codebase
5. **Circular Dependency** module cycles
6. **Deep Inheritance** >2 levels hierarchy
7. **Feature Envy** accessing other object's data >3x
8. **N+1 Queries** loop with DB/API calls
9. **Blocking I/O in async** sync ops in async context
10. **O(n²)** nested loops over same data
11. **Unbounded Cache** no TTL/eviction
12. **Sync Rate Limiting** blocking rate limiter

**Fix protocol:** Extract | Guard clauses | Named constants | Single module | Interface | Composition | JOIN/batch | Async | Hashmaps | TTL/limit | Token bucket

---

## 4. ARCHITECTURE UNIVERSAL PATTERN
### Modular Monolith (per backend)
```
Module/
├── Api/           # Controllers, DTOs, HTTP handling
├── Application/   # Use cases, services, orchestration
├── Domain/        # Entities, VOs, repository interfaces, domain events
└── Infrastructure/ # DB repos, external APIs, messaging, caching
```
**Dependency rule (absolute):**
- Api → Application → Domain
- Infrastructure → Application
- Domain ← depends on nothing
- **FORBIDDEN:** Api → Repository | Domain → Infrastructure | Emit events from Api | Cross-module logic imports

### Platform Layer (cross-cutting)
- IAM, MDM, Eventing (Outbox + Broker), Config
- **Never** call domain logic, never query domain tables, only expose abstractions

### Data Boundaries
- Each module owns its schema/prefix
- Cross-module data: Events, read-only facades, MDM only
- Transactions: UnitOfWork per backend; cross-backend = eventual consistency + saga

### Event Rules
- DTOs immutable, versioned
- Never remove fields, only add optional
- Domain Event: in-transaction
- Integration Event: Outbox pattern

### Frontend (Domain-Aligned Modular SPA)
```
src/
├── appcore/        # router, module-registry, event-bus, iam-adapter, mdm-provider, config
└── modules/
    └── {module}/
        ├── ui/           # Components, pages
        ├── state/        # Stores, derived state
        ├── services/     # API adapters
        ├── contracts/    # OpenAPI / Event DTO
        └── events/       # Client domain events
```
**Dependency:** ui → state → services → contracts; events → state  
**FORBIDDEN:** ui calls API directly | state imports other modules | cross-module imports | services mutate state directly

---

## 5. SECURITY (STRIDE+DREAD)
### Mandatory Controls
- Validate ALL inputs (type, range, format, length)
- Parameterized queries ONLY
- No `eval()`, `new Function()`, insecure crypto
- KMS/secret managers; never commit secrets
- TLS 1.2+ for all external calls
- Auth on ALL state-changing endpoints (POST/PUT/DELETE)
- HttpOnly + Secure + SameSite cookies
- No PII in logs
- JWT RS256, short expiry, refresh tokens
- Rate limiting (per user/IP/endpoint)
- CSP headers, XSS/CSRF/SQL/Command injection prevention
- Password hashing: bcrypt/Argon2
- Webhook signature verification + replay attack prevention
- Command injection prevention: use `execFile`, never `exec`

### Threat Model
- STRIDE + DREAD scoring
- Any threat DREAD ≥7 → fix immediately
- Audit like production fintech: assume all inputs are malicious

---

## 6. PERFORMANCE SLOs
| Metric | Target |
|--------|--------|
| p50 latency | <100ms |
| p99 latency | <200ms |
| p999 latency | <500ms |
| Throughput | >1000 RPS |
| Error rate | <0.1% (5xx/total) |
| Availability | 99.9% |
| Warm cache | <50ms p50 |
| Cold cache (DB) | <150ms p50 |

### Forbidden
- O(n²) algorithms
- N+1 queries
- Blocking I/O in async
- Large allocations in loops
- Unindexed queries on FK/timestamp/status

### Benchmark Required (before optimization)
- Scenario: 10k+ records, 1MB+ payload
- Baseline vs Optimized: p50/p99/p999, memory, CPU, QPS, error rate
- Assertions: `expect(p50).toBeLessThan(100)`, `expect(p99).toBeLessThan(200)`
- Profiling: flamegraph/heap findings

---

## 7. OBSERVABILITY (NON-NEGOTIABLE)
### Logging
- Structured JSON logs (pino/winston/structlog)
- Levels: ERROR/WARN/INFO/DEBUG
- No PII (names, emails, phones, SSN)
- Correlation ID: `X-Request-ID` header, propagated through ALL services

### Metrics (Prometheus)
- `http_requests_total` (method, path, status)
- `http_request_duration_seconds` (histogram)
- `errors_total` (type, component)
- Business metrics (custom)
- Buckets: `[0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10]`

### Tracing
- OpenTelemetry for cross-service traces
- Trace ALL cross-service calls

### Health Checks
- `/health/live` (process alive)
- `/health/ready` (DB, cache, queue dependencies)
- Component-specific: `/health/db`, `/health/cache`

### SLO Monitoring
- Track: error rate, latency, availability
- Alert on breach (Alertmanager)
- Error budget: 0.1% error rate → 43.8 minutes/month downtime budget
- Burn rate >50% in week → P0 incident

---

## 8. RESILIENCE (5/7 MINIMUM)
Implement ALL 7 in production:

1. **Retry** — Exponential backoff + jitter, max 3-5 attempts, retryable codes only
2. **Timeout** — All I/O, default 10s, request-scoped context
3. **Circuit Breaker** — Threshold 5, timeout 60s, half-open state
4. **Bulkhead** — Isolate resource pools (DB, HTTP, Redis, external APIs)
5. **Fallback** — Cache/default/degraded mode (StaticFallback, CachedFallback, EmptyListFallback, DefaultValueFallback)
6. **Health Checks** — Live/ready endpoints with real dependency checks
7. **Graceful Shutdown** — SIGTERM/SIGINT, drain requests, cleanup hooks

---

## 9. ERROR MESSAGES
### Format
```
[ERROR] Component Action - Reason - Suggestion
```
### Categories
- ValidationError, NotFoundError, ConflictError, PermissionError
- ExternalError, TimeoutError, QuotaExceededError

### Rules
- **User-facing:** Clear, actionable, NO stack/SQL/internal details, NO PII, include recovery hint
- **Dev/Log:** Full context (request ID, user ID, stack, payload, correlation IDs)
- **i18n-ready:** Message keys, not hardcoded strings

---

## 10. CONCURRENCY (SHARED STATE)
### Analysis Required
- Shared variables: global, static, caches
- Synchronization: mutex/lock/atomic/queue/actor; prefer lock-free
- Safety proof: happens-before, memory barriers
- Deadlock avoidance: consistent global lock ordering, lock timeouts
- Performance: contention analysis, lock-free alternatives

### Rules
- Handle all rejections
- No callback+promise mix
- No event loop blocking
- Use atomic ops/immutables where possible

---

## 11. TESTING STRATEGY
### Philosophy
- Mock external deps; test pure logic only
- Tests <100ms, deterministic (no flaky)
- Include: valid, null/undefined, boundaries, malformed
- Verify: return values AND side effects
- Coverage goal: CI branch ≥80%
- All error paths covered
- Each public API ≥1 test
- Structure: `describe` → `it` (AAA)

### Test Pyramid
```
                    E2E Tests (Playwright/Cypress)   ← Few, critical paths
                   ╱                             
          Integration Tests (Vitest + Test DB)     ← Module interactions
         ╱                                       
    Unit Tests (Vitest/Jest/PHPUnit)              ← Many, fast, isolated
   ╱
Static Analysis (ESLint/TSLint/PHPStan/go vet/mypy/detekt)
```

### Mandatory Test Cases (per issue)
- Load test, concurrency test, retry test, chaos test
- Edge case input, malicious input, boundary value, stress test
- Memory leak simulation, integration test (full E2E)

### Database for Tests
- SQLite in-memory or local instance
- Schema created fresh per test run
- Seed test data within each test

---

## 12. VERIFICATION & COLLABORATION
### Pre-commit (MANDATORY)
- Lint (ESLint/ golangci-lint/ ruff/ PHP-CS-Fixer)
- Type-check (tsc/ mypy/ pyright)
- Test with coverage (≥80%)
- Fail on high-severity npm audit / composer audit / govulncheck

### CI Pipeline (GitHub Actions)
```yaml
jobs:
  quality:
    steps:
      - lint
      - type-check
      - test --coverage (enforce ≥80%)
      - security-scan (Snyk/Trivy/CodeQL)
      - architecture-check (deptrac/import-linter/ArchUnit)
      - upload-artifacts
```

### PR Template
```markdown
## Description
[What changed and why]

## Quality Checklist
- [ ] Self-score: [X/100]
- [ ] All mandatory checks passed
- [ ] Security review completed
- [ ] Tests added/updated (coverage: X%)
- [ ] Benchmarks included (if performance change)
- [ ] Compliance documented (if applicable)
- [ ] Documentation updated

## Reviewer Focus Areas
- [ ] Security implications
- [ ] Performance impact
- [ ] Architecture alignment
- [ ] Test coverage completeness
- [ ] Observability gaps
```

### SLA & Escalation
- Initial review <24h, follow-up <12h, critical security <4h
- Escalation: blocked >48h → tech lead → engineering manager
- Main protected, feature branches, PR required, no force-push
- At least 1 approving review, status checks required (CI, coverage)

---

## 13. VERSIONING & DEPRECATION
### SemVer 2.0
- MAJOR: incompatible API changes
- MINOR: backward-compatible new features
- PATCH: backward-compatible bug fixes
- Conventional Commits: `feat:`→minor, `fix:`→patch, `BREAKING CHANGE:`→major, `docs:`/`chore:`

### Git
```bash
git tag -a v1.2.3 -m "Release" && git push origin v1.2.3
```
### Changelog
- Sections: [Unreleased], [1.2.3]-date, Added/Fixed/Removed
- Dependencies: pin exact for apps (commit package-lock), caret/tilde for libraries
- Lockfiles committed

### Deprecation Protocol
- Detect via CHANGELOG, linter, runtime logs
- Fallback: polyfill/feature detection
- Log warnings, telemetry, alert high usage
- Migration: TODOs with deadline, test both paths in CI
- Version pinning: lock to non-deprecated, `npm outdated` before upgrade

---

## 14. COMPLIANCE & COST
### Compliance Matrix (trigger by: GDPR/HIPAA/PCI/SOX/COPPA/cloud/legacy/migration)
```markdown
## Compliance
- Applicable Standards: [list]
- Status: ✅ Compliant / ⚠️ Non-compliant
- Controls: [x] implemented, [ ] missing
- Gaps & Remediation: [unchecked items with plan]
- Next Audit Date: YYYY-MM-DD
```
**Penalty:** -25 if missing.

### Cost Optimization (cloud/scale)
- Compute: right-size (60-70% CPU), spot/preemptible, reserved (1-3y, 30-50% off)
- Storage: S3 Intelligent-Tiering, lifecycle, EBS gp3 vs io2
- Database: read replicas, auto-scaling, Aurora Serverless
- Network: minimize transfer, same-region, compression, CDN
- Serverless: pay-per-use for spiky workloads
- Budget: alerts (50%, 80%, 100%), tagging (Environment, Team, Project, Owner)
**Penalty:** -15 if missing.

---

## 15. INFRASTRUCTURE & DATABASE
### 3rd Party Services (Docker Required)
| Service | Purpose | Image | Port |
|---------|---------|-------|------|
| PostgreSQL | Primary DB | postgres:15-alpine | 5432/5433 |
| Redis | Cache/Sessions/Queue | redis:7-alpine | 6379/6380 |
| Kafka | Event streaming | bitnami/kafka:3.6 | 9092/9093 |
| RabbitMQ | Message queue | rabbitmq:3-management | 5672/15672 |
| WireMock | API mocking | wiremock/wiremock | 8080 |
| PgBouncer | Connection pooling | edoburu/pgbouncer | 6432 |

### Database (PostgreSQL) - Optimized
- **Connection Pooling:** PgBouncer (transaction mode, pool 20-50)
- **Schema Organization:** Separate schemas per backend module
- **Indexes:** B-tree on FKs/timestamps/status; partial for soft-deletes; composite for common patterns
- **Partitioning:** Monthly on `history.activity_logs`, `audit.logs`
- **Maintenance:** `autovacuum_vacuum_scale_factor=0.05`, `autovacuum_analyze_scale_factor=0.02`, `max_parallel_workers_per_gather=4`
- **Caching:** Redis for sessions/recent-activity/rate-limits; PgBouncer for connection pooling

### App Processes (Run Natively - NOT Docker)
- Frontend: `cd frontend && npm run dev` (port 3000)
- PHP Symfony: `cd php-symfony && php -S localhost:8080 -t public` (port 8080)
- Go: `cd go && go run ./cmd/api` (port 8081)
- Python: `cd python && uvicorn app.main:app --reload` (port 8002)
- Kotlin: `cd kotlin && ./gradlew run` (port 9000)
- Rust: `cd rust && cargo run` (port 8082)
- Zig: `cd zig && zig build run` (port 4000)

---

## 16. DEVIL'S ADVOCATE (MANDATORY BEFORE OUTPUT)
Before outputting code, ALL must pass:
- [ ] **Failure:** timeout, deadlock, OOM, unhandled exceptions?
- [ ] **Scale:** O(n), memory linear, DB indexes, 1M+ users?
- [ ] **Security:** SQL/XSS/command injection, privilege escalation?
- [ ] **Senior:** over-engineering?, missing edge cases?, poor naming?
- [ ] **On-call:** alert storms, retry storms, cascading failures?
- [ ] **SLOs:** p99<200ms?, error rate <0.1%?, availability 99.9%?

---

## 17. MENTAL TESTING CHECKLIST
Test EVERY function mentally:
- [ ] Valid inputs (happy path)
- [ ] Invalid inputs (type, range, format)
- [ ] Null/undefined/empty
- [ ] Boundary values (min/max, off-by-one)
- [ ] Malformed data
- [ ] Every branch (if/else/switch)
- [ ] Every error throw/catch
- [ ] Data flow UI→DB & DB→UI
- [ ] SQL injection vectors
- [ ] XSS vectors
- [ ] CSRF tokens
- [ ] Auth bypass attempts
- [ ] O(n) not O(n²)
- [ ] No memory leaks
- [ ] No blocking I/O in async
- [ ] Race conditions (shared state)
- [ ] Deadlocks (lock ordering)
- [ ] Atomic operations
- [ ] Logs emitted (structured)
- [ ] Metrics recorded
- [ ] Traces propagated

> If ANY missing → WRITE MORE (do not skip).

---

## 18. QUALITY GATE CHECKLIST
```
[✔] Funcs≤20 | Comp≤10 | No dup5 | 100% ErrHnd | 100% Val | No secrets | Testable
[✔] Cov≥80% | Tests pass | No 12 anti-patterns | Devil's advocate | Mental test done
[✔] Flow coverage (UI→DB & DB→UI) | Missing code written | Code preserved
[✔] Risk assessed (Low/Med/High) | Git committed
```

---

## 19. PRODUCTION READINESS CHECKLIST
- [ ] All functions mental-tested (valid/invalid/edge/error)
- [ ] All APIs contract verified (request/response schema)
- [ ] All flows UI→DB AND DB→UI (BOTH directions)
- [ ] All edge cases covered (null, empty, boundary, malformed)
- [ ] All error paths handled (exceptions, timeouts, failures)
- [ ] Security: injection, auth bypass, XSS, CSRF
- [ ] Performance: O(n) not O(n²), no memory leaks, no blocking I/O
- [ ] Industry standards met (SOLID, DRY, KISS)
- [ ] Documentation updated (JSDoc, README, ADRs)
- [ ] Missing code = written (not skipped)

---

## 20. CODE PRESERVATION & DEBUGGING
### Rules
- **NEVER** delete code to pass tests
- **NEVER** temporary fixes that add bugs
- **NEVER** accept degradation
- **ALWAYS** find root cause, fix logic
- **ALWAYS** have restore plan from git
- Disable feature temporarily (feature flag) instead of deleting

### Systematic Debugging
1. Read entire file (every line, imports)
2. Understand context (structure, related logic, external calls)
3. Isolate problem (reproduction case, minimal code)
4. Test hypotheses (debug prints, unit tests)
5. Verify fix (no regression)

### If Still Failing
- Consult team, review git history
- Disable feature temporarily (not delete), plan restore
- Pair programming
- Escalate: blocked >48h → tech lead → manager

---

## 21. CHANGE COST & RISK
### Risk Levels
- **Low:** Docs, refactor same module, add tests, fix typos
- **Medium:** Add feature, modify API, change DB schema
- **High:** Rewrite core, change architecture, security fix

### Decision Matrix
| Situation | Action |
|-----------|--------|
| Violations detected | Prioritize fix, stop proactive work |
| No violations | Run proactive analysis (10 steps) |
| Critical security | P0, fix immediately, audit |
| Performance regression | Benchmark, optimize, verify |
| Missing tests | Write tests first (red-green) |
| Legacy code touched | Add tests, document risks |
| Audit fails | Return to Optimize, do NOT verify |

**Prefer:** Low-risk, high-impact > High-risk, aesthetic/speculative.

---

## 22. FRONTEND ARCHITECTURE (Svelte SPA)
### Structure
```
src/
├── appcore/        # router, module-registry, event-bus, iam-adapter, mdm-provider, config, error-boundary
└── modules/
    └── {module}/
        ├── ui/           # Svelte components, pages
        ├── state/        # Svelte stores, derived state
        ├── services/     # API adapters (tRPC/HTTP)
        ├── contracts/    # OpenAPI / Event DTO
        └── events/       # Client domain events
```

### State Management
| State Type | Solution |
|------------|----------|
| Server State | TanStack Query (React Query) + tRPC |
| Global UI State | Jotai / Svelte stores |
| Form State | React Hook Form + Zod |
| URL State | Search params (shareable) |
| Local Component | useState/useReducer |

### Communication
- FE → BE: HTTP/JSON, versioned, retry+timeout, idempotency key
- Realtime: WebSocket/SSE, versioned, immutable events
- Events: never change meaning, only add optional fields

### Enforcement
- ESLint boundary rules
- Path alias per module
- No cross-module imports
- CI fails on: ui imports services directly | module imports module | bypass state | bypass event bus

---

## 23. DATABASE OPTIMIZATION (PostgreSQL)
### Connection Pooling
- PgBouncer (transaction mode, pool 20-50)

### Indexes
- B-tree on FKs, timestamps, status
- Partial indexes for soft-deletes
- Composite indexes for common query patterns

### Partitioning
- Monthly on `history.activity_logs`, `audit.logs`

### Maintenance
- `autovacuum_vacuum_scale_factor=0.05`
- `autovacuum_analyze_scale_factor=0.02`
- `max_parallel_workers_per_gather=4`

### Caching Strategy
- Redis: sessions, recent activity, rate limits
- Application cache: hot data with TTL
- CDN: static assets

---

## 24. EVENT-DRIVEN ARCHITECTURE
### Domain Events (In-Transaction)
- Emitted during UnitOfWork
- Persisted in same transaction as state changes
- Handlers: Application layer only

### Integration Events (Outbox)
- Saved to outbox table in transaction
- Published to broker (Kafka/RabbitMQ) after commit
- Idempotent consumers
- Versioned, immutable DTOs

### Event Rules
- Never remove fields
- Only add optional fields
- Never change meaning
- Map directly from Integration Event to FE

---

## 25. TRANSACTION PATTERN
```
App Core → UnitOfWork → Module A → Module B → Domain Events → Commit → Integration Events
```
### Rules
- Single backend: ACID transactions
- Cross-backend: eventual consistency + saga + compensation
- No distributed transactions
- All state changes in UnitOfWork

---

## 26. AUTHENTICATION & AUTHORIZATION
### JWT Strategy
- Access token: short-lived (15m), RS256
- Refresh token: long-lived (7d), revocable, stored securely
- Payload: sub, shopId, role, permissions, iat, exp
- Never store sensitive data in JWT payload

### RBAC
- Role-based + permission-based
- Real-time permission checks
- Fine-grained: module/action/resource/data-level
- Temporary delegation with expiry

### Session Management
- HttpOnly cookies, Secure, SameSite
- Auto-timeout
- Device trust optional

---

## 27. ERROR HANDLING FRAMEWORK
### Categories
- ValidationError, NotFoundError, ConflictError
- PermissionError, ExternalError, TimeoutError, QuotaExceededError

### User Message
- Clear, actionable
- NO stack/SQL/internal details
- NO PII
- Include recovery hint

### Dev/Log Message
- Full context: request ID, user ID, stack, payload, correlation IDs
- Structured JSON

### Recovery Hints
- Always include next actionable step for user

---

## 28. DOCUMENTATION STANDARDS
### Required Docs
- `README.md` — Setup, coverage badges, architecture overview
- `API.md` — OpenAPI specs for each backend
- `DATABASE.md` — Schema, indexes, partitioning strategy
- `DEPLOYMENT.md` — Docker, Kubernetes, environment variables
- `DEVELOPMENT.md` — Local setup, testing, linting
- `TESTING.md` — Strategy, test data factories, CI integration
- `ADRs/` — Architecture Decision Records for major decisions

### Code Documentation
- JSDoc/TSDoc for public APIs ≥95%
- README updated on API changes
- Inline comments only for non-obvious business logic

---

## 29. DEBT & EVOLUTION
### Continuous Improvement Metrics
- Health Score: `(coverage% × 0.3) + ((1 - avg_complexity/20) × 0.3) + (test_count/1000 × 0.2) + ((1 - duplication%) × 0.2)`
- Target: increase ≥0.5% per week
- Stagnant >3 days → escalate

### Evolution Files (Auto-maintain)
- `docs/AGENT_METRICS.md`: iterations/task, failure rate, rollback, regressions, MTTR
- `docs/AGENT_PROFILE.md`: weaknesses, fragile modules, error-prone stacks
- `docs/EVOLUTION.md`: 3-6 month roadmap, refactors, debt, infrastructure evolution

### Meta-Goal
- System breaks less, fixes faster, plans further ahead, fewer repeated mistakes.

---

## 30. GIT WORKFLOW
### Branch Strategy
- `main` protected, feature branches, PR required
- No force-push to main
- At least 1 approving review
- Status checks required (CI, coverage)

### Commit Convention
```
feat: <description>
fix: <description>
refactor: <description>
perf: <description>
test: <description>
docs: <description>
chore: <description>
```

### Tags
```bash
git tag -a v1.2.3 -m "Release" && git push origin v1.2.3
```

---

## 31. DEPENDENCY MANAGEMENT
### Principles
- Pin exact for apps (commit lockfiles)
- Caret/tilde for libraries
- Scan for CVEs: `composer audit`, `npm audit`, `govulncheck`, `safety`, `cargo audit`
- Fail CI on high-severity vulnerabilities
- Update dependencies: `npm outdated` before upgrade
- Replace deprecated APIs immediately

---

## 32. ARCHITECTURE ENFORCEMENT
### CI Gates (MANDATORY)
| Backend | Tool | Rule |
|---------|------|------|
| PHP | deptrac | Domain≠Infra, Api≠Domain, no cross-module |
| Go | import-linter + arch-check | Api→Application→Domain only |
| Python | import-linter + pytest architecture tests | Layer boundaries |
| Kotlin | ArchUnit + Gradle | Domain≠Infra, Api≠Domain |
| Rust | clippy + custom linter | Module boundaries |
| Zig | build-time dependency graph | Layer separation |
| Frontend | ESLint | no-cross-import, path alias |

### Compliance Tracking
- Current compliance % per backend
- Trend over time (weekly)
- Modules most out of compliance
- PRs that improve/worsen compliance
- Target: 50% in 3 months, 80% in 6 months, 100% in 12 months

---

## 33. MISSING CODE = WRITE MORE
- If missing feature, API, logic, edge case → **WRITE IT**
- Do NOT skip because "not requested"
- Do NOT remove code to simplify
- Do NOT reduce scope to pass fast
- **App must become more complete over time**

---

## 34. SIMPLICITY & SURGICAL CHANGES
- **Simplicity-first:** Minimum code that solves the problem. Nothing speculative.
- **Surgical:** Touch only what you must. Don't "improve" adjacent code.
- **Goal-driven:** Define success criteria BEFORE implementation.
- **No hallucination:** MUST NOT guess, infer missing behavior, invent APIs. If info missing → STOP and ask.

---

## 35. AUTONOMOUS EXECUTION ENGINE
### Startup Routine
1. Read `docs/AGENT_METRICS.md` → current state
2. Read `docs/AGENT_PROFILE.md` → known weaknesses
3. Read `docs/EVOLUTION.md` → trajectory
4. Schedule discovery cycle (interval: 2h)
5. Begin autonomous loop

### Continuous Cycle Schedule
- Discovery Cycle: Every 2h from start
- Task Execution: Each task ≤30 min, verify immediately after each step
- Sleep: 5 min between cycles if Task Queue empty
- Batch Size: Max 3 tasks/cycle

### Emergency Break Conditions
STOP if (10 min rolling window):
- Error rate >5%
- Memory usage >90%
- Disk space <10%
- External dependency outage >30 min

**On STOP:**
- Log reason to `docs/AGENT_METRICS.md`
- Rollback last batch (`git revert HEAD~n`)
- Alert (if remote configured)
- Wait for manual intervention

---

## 36. 5-PHASE WORKFLOW
```
Analyze → Clarify → Plan → Test(fail) → Implement → Refactor → Optimize → Audit → Verify
```
**Audit step MANDATORY before Verify.** Code must pass System Audit (10 dimensions) before considered done.

### Phase 1: Discovery & Proactive Analysis
**Priority:** Violations first, improvement second.
- Run lint, type-check, test --coverage, build
- If violations → skip to Phase 2
- If no violations → run 10-step parallel analysis:
  1. Coverage Deep Dive (target >85%)
  2. Complexity Audit (reduce to ≤6)
  3. Security Hardening
  4. Performance Hunt
  5. Documentation Gap
  6. Test Quality
  7. Observability Check
  8. Concurrency Review
  9. Dependency Audit
  10. Code Smell Scan

### Phase 2: Planning
- Auto-select highest priority from Task Queue:
  1. CRITICAL violations (security breaches, breaking tests)
  2. HIGH violations (quality gate failures, performance regressions)
  3. SECURITY improvements
  4. PERFORMANCE improvements
  5. MEDIUM violations (code smells, missing tests)
  6. LOW tasks (documentation, trivial refactors)
- Micro-Plan: ≤3 steps, each ≤30 min

### Phase 3: Execution
- ALWAYS write test FIRST (red-green-refactor)
- NEVER modify unrelated code
- ALWAYS match existing style
- ALWAYS update docs if API changes
- ALWAYS run quality gate after each batch

### Phase 4: Audit (MANDATORY BEFORE VERIFY)
Run System Audit (10 dimensions). For each issue:
- Severity (CRITICAL/HIGH/MEDIUM/LOW)
- Location (flow/module/function)
- Exploit (how to trigger)
- Impact (data loss, financial, security)
- Root cause (technical deep-dive)
- Fix (code change, config)
- Test case (concrete test)
- Priority (P0/P1/P2)

**Audit Pass Criteria:** Self-score ≥90 + zero critical issues.  
If fail → return to Phase 3 Optimize, do NOT proceed to Verify.

### Phase 5: Reporting & Verification
- Append metrics to `docs/AGENT_METRICS.md`
- Append weakness to `docs/AGENT_PROFILE.md`
- Append trajectory changes to `docs/EVOLUTION.md`
- Git commit with conventional message

---

## 37. 10-DIMENSION SYSTEM AUDIT
Run this audit on EVERY significant change:

1. **Business Logic Integrity** — bypass validation, client input manipulation, edge case logic, dangerous assumptions
2. **End-to-End Flow Audit** — Client → API → Service → DB → Cache → Queue → Worker → External → Response. Find: flow ngắt giữa chừng, không rollback, silent failure, missing error handling
3. **Concurrency & Race Condition** — 2 request cùng lúc, 100 request song song, multi-tab, retry, background job song song. Xác định: lost update, double execution, dirty write, non-atomic, lock thiếu
4. **Database & Data Integrity** — Transaction? Isolation level? Deadlock? Unique constraint? Foreign key? Orphan data? Inconsistent state?
5. **Caching & Consistency** — Cache invalidation? Stale cache? Cache stampede? Cache update trước DB commit?
6. **Idempotency** — Endpoint idempotent? Retry gây double? Webhook 2 lần? Background job trùng?
7. **Failure Scenarios** — DB crash, external API timeout, worker crash, network partition, disk full, memory spike, CPU spike, queue backlog
8. **Security Audit (STRIDE+DREAD)** — Input validation, output encoding, SQL/NoSQL injection, XSS, CSRF, SSRF, broken access control, JWT verification, webhook signature, replay attack
9. **Scalability Analysis** — O(n)? N+1 query? Memory leak? Blocking I/O? Thread starvation? Event loop blocking? Benchmark: p50<100ms, p99<200ms
10. **Observability & Monitoring** — Log đầy đủ? PII trong log? Metric quan trọng? Alert khi failure? Health check? Correlation ID? Distributed tracing?

### Audit Report Template
```markdown
# System Audit Report
## Executive Summary
- Overall Risk: [LOW/MEDIUM/HIGH/CRITICAL]
- Critical/High/Medium/Low Issues: [count]
- Estimated Fix Time: [X days]

## Detailed Findings
### 🔥 [SEVERITY] Issue Title
- **Location**: [flow/module/function]
- **Exploit**: [how to trigger]
- **Impact**: [data loss, financial, security]
- **Root Cause**: [technical deep-dive]
- **Fix**: [code change, config]
- **Test Case**: [concrete test]
- **Priority**: [P0/P1/P2]

## Compliance Check
- [ ] GDPR / PCI-DSS / SOC2: [compliant/gap]

## Recommendations (Prioritized)
1. [P0] Fix critical immediately
2. [P1] Address high this sprint
3. [P2] Schedule medium next quarter
4. [P3] Low - monitor
```

---

## 38. ERROR MESSAGES
### Format
```
[ERROR] Component Action - Reason - Suggestion
```
### Categories
- ValidationError, NotFoundError, ConflictError, PermissionError
- ExternalError, TimeoutError, QuotaExceededError

### Rules
- **User:** Clear, actionable, NO stack/SQL/internal, NO PII, include recovery hint
- **Dev/Log:** Full context (request ID, user ID, stack, payload, correlation IDs)
- **i18n:** Message keys, not hardcoded strings

---

## 39. TEST GENERATION RULES
### Strict Mode
- **NO hallucination:** do not guess, infer missing behavior, invent APIs, assume inputs
- **If info missing:** STOP and output: "Cannot generate tests because: <exact missing information>"

### Pre-Analysis (Mandatory)
For every file, extract:
- **Export Map:** every exported function, class, public method, runtime-affecting type
- **Behavior Contract:** inputs, outputs, side effects, external calls, possible errors
- **Branch Map:** ALL if/else, switch, early returns, throws, catches, async rejections

### Unit Tests
- Exactly one unit test file per module file
- Test every exported function, class, public method
- For every branch in Branch Map → ≥1 test
- Test: normal, edge, boundary, invalid, null/undefined, type mismatches, unexpected, error paths, async failures, race conditions
- All dependencies mocked
- Assert: return value, side effects, dependency calls, error handling

### Integration Tests
- One or more integration test files per module
- No internal module files mocked
- Only true external systems (DB, HTTP, FS, queues) stubbed
- Verify: data flow, all workflows end-to-end, all failure paths, real-world patterns, success/partial failure/invalid/corrupted/missing/concurrency/retries/idempotency/rollback

---

## 40. FRONTEND COMPONENT TESTING
### Rules
- Test pure logic only (mock external deps)
- Tests <100ms, deterministic
- Exported getters for internal state (`getIsOpen`, `getSelectedIndex`)
- `data-testid` attributes on all DOM elements
- Graceful handling of missing browser APIs in jsdom (canvas, MediaRecorder, etc.)

### Svelte 5 Compatibility
- Use `$props()` instead of `export let`
- Use `$effect()` instead of `$:`
- Use `rerender` instead of deprecated `$set`
- Add `ssr: false` to vitest config
- Add `resolve.conditions: ['browser', 'import', 'module']` to force client-side resolution

---

## 41. INFRASTRUCTURE AS CODE
### Docker Compose (Local Dev)
```yaml
services:
  postgres:
    image: postgres:15-alpine
    ports: ["5432:5432"]
    healthcheck: test: ["CMD-SHELL", "pg_isready -U postgres"]
  redis:
    image: redis:7-alpine
    ports: ["6379:6379"]
    healthcheck: test: ["CMD", "redis-cli", "ping"]
```

### Health Check Pattern
```typescript
// All backends must expose:
GET /health/live → 200 (process alive)
GET /health/ready → 200/503 (DB, cache, queue status)
```

### CI/CD Pipeline
```yaml
jobs:
  quality:
    steps:
      - lint
      - type-check
      - test --coverage (enforce ≥80%)
      - security-scan (Snyk/Trivy/CodeQL)
      - architecture-check (deptrac/import-linter/ArchUnit)
      - upload-artifacts
```

---

## 42. SECRETS MANAGEMENT
- **NEVER** hardcode secrets in code
- Use environment variables or secret managers (KMS, Vault, AWS Secrets Manager)
- `.env` files in `.gitignore`
- Rotate secrets regularly
- Scan for secrets: `git grep -i "password\|secret\|key\|token"`
- Pre-commit hook: detect-secrets, gitleaks

---

## 43. RATE LIMITING
### Strategy
- Per-user + Per-IP + Per-endpoint
- Token bucket algorithm (non-blocking)
- Configurable limits per route
- Standard headers: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`
- 429 response with `Retry-After`

### Implementation
- Async/non-blocking only
- Distributed rate limiting: Redis-backed counters
- Bypass for internal service-to-service calls (mTLS)

---

## 44. LOGGING STANDARDS
### Structured JSON
```json
{
  "timestamp": "2026-08-10T12:00:00.000Z",
  "level": "info",
  "service": "api",
  "requestId": "uuid",
  "userId": "uuid",
  "method": "POST",
  "path": "/api/orders",
  "status": 201,
  "duration": 45,
  "message": "Order created"
}
```

### Redaction
```javascript
redact: [
  'req.headers.authorization',
  'req.headers.cookie',
  '*.password',
  '*.token',
  '*.secret',
  '*.creditCard'
]
```

---

## 45. METRICS NAMING CONVENTION
- `http_requests_total` (counter) — labels: method, path, status
- `http_request_duration_seconds` (histogram) — labels: method, path, status
- `errors_total` (counter) — labels: type, component
- `active_users` (gauge) — labels: service
- `business_metric_name_total` (counter) — domain-specific

---

## 46. DATABASE MIGRATION STRATEGY
### Principles
- Versioned migrations (up/down)
- Idempotent scripts
- Never modify production data directly
- Backward-compatible schema changes
- Dual-write period for breaking changes
- Feature flags for new columns/tables

### Rollback
- Every migration must have rollback script
- Test rollback in staging
- Blue-green cutover for critical changes

---

## 47. API DESIGN
### RESTful Principles
- Version from start: `/api/v1/`, `/api/v2/`
- Plural nouns: `/api/v1/orders`
- Nested resources: `/api/v1/orders/{id}/items`
- Filtering: query params
- Pagination: `page`, `pageSize`, `limit`, `offset`
- Sorting: `sortBy`, `sortOrder`
- Standard HTTP status codes
- Consistent response envelope: `{ success: true, data: ..., meta: ... }`

### Idempotency
- Idempotency key for mutations: `Idempotency-Key` header
- Webhook: verify signature, check replay, return 200 on duplicate
- Retry-safe operations

---

## 48. CACHING STRATEGY
### Rules
- Cache invalidation: write-through or cache-aside
- TTL on ALL cached data
- Cache stampede prevention: mutex/lock, stale-while-revalidate
- Never cache user-specific data globally without user key
- Cache update AFTER DB commit, never before

### Layers
- CDN: static assets
- Redis: sessions, rate limits, hot data
- Application: computed results with TTL
- Database: query cache (if applicable)

---

## 49. DEPENDENCY INJECTION
### Principles
- Use DI container (tsyringe, Symfony DI, etc.)
- Inject interfaces, not concrete classes
- Constructor injection preferred
- No static calls for business logic
- Avoid service locator anti-pattern

---

## 50. CODE REVIEW CHECKLIST
### Before Approving
- [ ] Self-score ≥90
- [ ] All TOP 5 passed
- [ ] Security 100%
- [ ] Coverage ≥80% (measured)
- [ ] Tests pass (0 failures, 0 errors, 0 warnings)
- [ ] No 12 anti-patterns
- [ ] Devil's advocate checklist passed
- [ ] Mental test done
- [ ] All flows UI→DB AND DB→UI verified
- [ ] Missing code written (not skipped)
- [ ] Code preserved (no deletions to pass tests)
- [ ] Git committed with conventional message

---

## 51. ESCALATION MATRIX
| Situation | Action | Timeout |
|-----------|--------|---------|
| Blocked >48h | Tech lead → engineering manager | 48h |
| Critical security | Fix in <4h, audit | 4h |
| Tests fail after change | Revert + rollback | Immediate |
| No actionable TODOs | Stop autonomous loop | N/A |
| External outage >30min | STOP agent, log, alert | 30min |

---

## 52. IMPROVEMENT CATALOG
| Type | Category | Actions |
|------|----------|---------|
| R | Refactor | Split large classes/functions, extract interfaces, reduce coupling, SOLID |
| P | Performance | Add caching, optimize queries (JOIN/batch), lazy loading, memoization |
| S | Security | Implement RBAC, add WAF rules, encrypt sensitive fields, rotate secrets |
| T | Tests | Increase branch coverage >85%, add fuzzing, integration tests, property-based testing |
| D | Documentation | Update JSDoc, write ADRs, improve README, add architecture diagrams |
| O | Observability | Add distributed tracing, create new metrics, improve log context |
| C | Compliance | Ensure GDPR/PCI/HIPAA controls |
| U | Upgrade | Update dependencies, migrate to new major versions, replace deprecated APIs |
| M | Modernization | Migrate to async/await, adopt TypeScript strict mode, CI/CD enhancements |

**Priority:** R > P > S > T > D > O > C > U > M

---

## 53. SKILLS REFERENCE
| Skill | Use Case |
|-------|----------|
| analyze-code | Code analysis |
| code-review | Cleanup, quality |
| security-audit | Security review |
| performance-optimize | Performance tuning |
| refactor | Refactoring |
| generate-tests | Test generation |
| backend-db-pattern | Database design (4 Steps) |
| iam-platform-layer | Auth/Authorization |
| go-architect | Go services |
| python-architect | Python apps |
| rust-architect | Rust systems |

Use each skill when appropriate.

---

## 54. PROJECT PROFILE AUTO-DETECT
| Profile | Keywords | Adjustments |
|---------|----------|-------------|
| Size: Small (<10k LOC) | "small", "prototype", "POC" | Simplify Tier 2 |
| Size: Medium (10-100k) | "medium", "app" | Full Tier 1 + Tier 2 |
| Size: Large (>100k) | "large", "enterprise", "scale" | Full rigor |
| Risk: Low | "internal tool", "admin panel" | Minimal compliance |
| Risk: Medium | "public API", "customer-facing" | Full Tier 1 |
| Risk: High | "payment", "health", "GDPR", "PII" | All tiers + compliance |
| Deployment: Cloud | "cloud", "AWS", "GCP", "Azure" | Cost optimization applies |
| Team: Solo | "solo", "1 dev" | Simplify process |
| Team: Small team | "team", "2-10 devs" | Standard process |
| Team: Large org | "org", "multiple teams" | Full process |

**Default:** Medium risk, Medium size, Cloud, Small team → Full Tier 1 + Tier 2.

---

## 55. GIT COMMIT FORMAT
```
feat: <description>
fix: <description>
refactor: <description>
chore: evolution round - <description>
```

After each iteration:
```bash
git add -A
git commit -m "chore: evolution round - <brief description>"
```

---

## 56. OUTPUT GATE (FINAL CHECK)
**ALL must pass. Else revise.**

### PHASE 1: METRICS
- [ ] Self-score ≥90
- [ ] All TOP 5 passed
- [ ] Security 100%
- [ ] Coverage ≥80% (measured)
- [ ] Tests pass

### PHASE 2: ANTI-PATTERNS
Check 12 patterns. If any → refactor, repeat.

### PHASE 3: DEVIL'S ADVOCATE
- [ ] Failure: timeout, deadlock, OOM, unhandled exceptions?
- [ ] Scale: O(n), memory linear, DB indexes, 1M+ users?
- [ ] Security: SQL/XSS/command injection, privilege escalation?
- [ ] Senior: over-engineering?, missing edge cases?, poor naming?
- [ ] On-call: alert storms, retry storms, cascading failures?
- [ ] SLOs: p99<200ms?, error rate <0.1%?, availability 99.9%?

---

## 57. COMMON COMMANDS
```bash
# Quality
make quality                # Full gate
make quality-php/go/python/kotlin/rust/zig/frontend  # Per backend

# Tests
php vendor/bin/phpunit      # PHP
go test -race -cover ./...  # Go
pytest --cov                # Python
./gradlew test              # Kotlin
cargo test                  # Rust
npm run test -- --coverage  # Frontend

# Git
git commit -m "type(scope): description"
git tag -a v1.2.3 -m "Release" && git push origin v1.2.3
```

---

*This document is the single source of truth for production-ready autonomous code engineering. Apply universally. Update as standards evolve.*
