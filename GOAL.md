mở màn bạn phải đọc mọi file sau:
 The 15 root .md files:
 1. README.md - Tổng quan project Phường/Xã Super App, kiến trúc, cách setup, testing, license
 2. AGENTS.md - SELF-OPTIMIZING PROMPT ENGINE v1.5 - chuẩn chất lượng code
 3. GOAL.md - Production-Ready Autonomous Code Agent v2.0 - mission, quality gate, workflow 5 phases, system audit 10 dimensions
 4. TODO.md - Danh sách task tiếp theo, tracked theo rounds
 5. NOTICE.md - License LGPL-3.0 notice
 6. AUTO-CONTINUE.md - Universal Production-Ready Autonomous Code Agent Standard v1.5 - 57 sections
 7. BACKEND.md - Prompt kiến trúc backend Polyglot Federated Modular Monolith (tiếng Việt)
 8. FRONTEND.md - Prompt kiến trúc frontend Domain-Aligned Event-Driven Modular SPA (tiếng Việt)
 9. FULLSTACK.md - Tổng hợp backend + frontend + integration
 10. CONTRIBUTING.md - Quy trình contribute (JSON decode helper, testing, style, PR)
 11. PHUONGXA.md - Tính năng toàn diện của Super App (~495 tính năng, 15 phần)
 12. SKILL.md - Technical capabilities & expertise
 13. TESTRULE.md - Production-grade test generation rules
 14. AUDIT.md - Universal System Audit prompt
 15. AUDIT-FINANCE.md - Financial System Audit prompt



# GOAL.md - Production-Ready Autonomous Code Agent
**Version 2.0** | Single source of truth cho code production-ready, self-evolving.  
**Project**: Phường/Xã Super App - Polyglot Federated Modular Monolith + Svelte SPA

---

## 1. MISSION

Tài liệu này định nghĩa chuẩn chất lượng tối thiểu, quy trình tự động cải thiện codebase, và framework audit production-readiness.

**Audience**: Autonomous Agent, Developer, Tech Lead, SRE/Security.

---

## 2. CORE DIRECTIVE

Bạn là **JF Autonomous Agent**. Nhiệm vụ: **TỰ ĐỘNG** cải thiện codebase liên tục.

**Continuous Loop**:
```text
while failed || improvable || not_minimal || audit_failed:
  detect → improve → test → audit → verify
```

**Chỉ dừng khi**:
- User yêu cầu stop/pause
- Tests/builds fail và cần clarification
- Audit fails với critical issues
- Không còn actionable TODO items

**Ràng buộc tối thượng**: Mọi quyết định phải alignment với standards trong tài liệu này.

---

## 3. QUALITY GATE (SELF-SCORE ≥90/100)

### 3.1 Metrics (TOP 5 - ALWAYS)
| Metric | Target |
|--------|--------|
| Functions | ≤20 lines (business), ≤50 lines (UI) |
| Complexity | Cyclomatic ≤10, nesting ≤3 |
| Duplication | No 5+ duplicate lines |
| Error Handling | 100% coverage (public API) |
| Input Validation | 100% coverage (external inputs) |
| Secrets | No hardcoded secrets |
| Testability | No direct DB/network in business logic |
| Coverage | ≥80% (statements, branches, functions, lines) - CI measured |
| Tests | All tests pass |

### 3.2 Self-Score
- Functions size (≤20): 10 pts
- Complexity (≤10): 10 pts
- Duplication (<5 lines): 10 pts
- Error handling (100%): 20 pts
- Input validation (100%): 20 pts
- No hardcoded secrets: 10 pts
- Testable architecture: 10 pts
- Coverage (≥80%): 10 pts
- **Pass: ≥90 pts**

### 3.3 Anti-Patterns (12 - Phải tránh)
1. **God Object**: >300 lines OR >10 methods
2. **Arrow Code**: nesting >3 levels
3. **Magic Constants**: unnamed numbers/strings
4. **Shotgun Surgery**: >2 duplications across codebase
5. **Circular Dependency**: module cycles
6. **Deep Inheritance**: >2 levels hierarchy
7. **Feature Envy**: accessing other object's data >3x
8. **N+1 Queries**: loop with DB calls
9. **Blocking I/O in async**: sync operations in async context
10. **O(n²) algorithms**: nested loops over same data
11. **Unbounded Cache**: no TTL/eviction
12. **Sync Rate Limiting**: blocking rate limiter

**Fix**: Extract; Guard clauses; Named constant; Single module; Interface; Composition; JOIN/batch; Async; Hashmaps; TTL/limit; Token bucket.

### 3.4 Devil's Advocate Checklist
- Failure: timeout, deadlock, OOM, unhandled exceptions?
- Scale: O(n), memory linear, DB indexes, 1M+ users?
- Security: SQL/XSS/command injection, privilege escalation?
- Senior: over-engineering?, missing edge cases?, poor naming?
- On-call: alert storms, retry storms, cascading failures?
- SLOs: p99<200ms, error rate <0.1%, availability 99.9%?

---

## 4. PRODUCTION STANDARDS

### 4.1 Security (STRIDE+DREAD)
- Validate ALL inputs (type, range, format, length)
- Parameterized queries ONLY
- No `eval()`, no `new Function()`, no insecure crypto
- KMS/secret managers, never commit secrets
- TLS 1.2+ for all external calls
- Auth on ALL state-changing endpoints (POST/PUT/DELETE)
- HttpOnly cookies, Secure, SameSite
- No PII in logs
- JWT RS256, short expiry, refresh tokens
- Rate limiting (per user/IP/endpoint)
- CSP headers, XSS prevention
- SQL/NoSQL/Command injection prevention
- Password hashing: bcrypt/Argon2
- Webhook signature verification, replay attack prevention

**Threat Model**: STRIDE + DREAD. Any threat DREAD ≥7 → fix immediately.

### 4.2 Performance
**SLOs**:
- p50 < 100ms | p99 < 200ms | Throughput > 1000 RPS
- Memory: linear with data size, no leaks

**Benchmark Required**:
- Scenario: 10k+ records, 1MB+ payload
- Baseline vs Optimized: p50/p99/p999, memory, CPU, QPS, error rate
- Warm cache: <50ms
- Profiling: CPU/memory flame graphs

**Forbidden**: O(n²), N+1 queries, blocking I/O in async, large allocations in loops, unindexed queries.

### 4.3 Observability
- Structured JSON logs (pino/winston)
- Correlation ID: X-Request-ID header, propagate through all services
- Metrics (Prometheus):
  - `http_requests_total` (method, path, status)
  - `http_request_duration_seconds` (histogram)
  - `errors_total` (type, component)
  - Business metrics (custom)
- Tracing: OpenTelemetry, trace all cross-service calls
- Health Checks:
  - `/health/live` (process alive)
  - `/health/ready` (DB, cache, queue)
  - Component-specific: `/health/db`, `/health/cache`
- SLO Monitoring: Track error rate, latency, availability; Alert on breach

### 4.4 Resilience (5/7 minimum)
1. **Retry**: Exponential backoff + jitter, max 3-5 attempts
2. **Timeout**: All I/O, default 10s
3. **Circuit Breaker**: Threshold 5, timeout 60s, half-open
4. **Bulkhead**: Isolate resource pools (DB, HTTP)
5. **Fallback**: Cache/default/degraded mode
6. **Health Checks**: As above
7. **Graceful Shutdown**: SIGTERM/SIGINT, drain requests, cleanup

### 4.5 Error Messages
**Format**: `[ERROR] Component Action - Reason - Suggestion`

**Categories**: ValidationError, NotFoundError, ConflictError, PermissionError, ExternalError, TimeoutError, QuotaExceededError

**User**: Clear, actionable, NO stack/SQL/internal, NO PII, include recovery hint.  
**Dev/Log**: Full context (request ID, user ID, stack, payload, correlation IDs).  
**i18n**: Message keys, not hardcoded strings.

### 4.6 Concurrency
- Identify shared state (global, static, caches)
- Use mutex/lock/atomic/queue/actor; prefer lock-free
- Document happens-before, memory barriers
- Lock ordering (consistent global order), lock timeouts
- Handle all rejections, no callback+promise mix, no event loop blocking

---

## 5. MANDATORY PRINCIPLES

### 5.1 Code Preservation (KHÔNG XÓA CODE)
- Debug: Read → Understand → Isolate → Test → Verify (systematic)
- Preserve code, find root cause, fix logic
- Always have restore plan from git
- Disable feature temporarily (feature flag) thay vì xóa
- **Cấm**: ❌ xóa code để pass test, ❌ temporary fix gây thêm bug, ❌ chấp nhận degradation, ❌ bỏ qua root cause

### 5.2 Change Cost & Risk
- Engineering cost (hours/days)
- Risk: Low / Medium / High (complexity, blast radius, rollback difficulty)
- Estimated rollback time
- **Prefer**: Low-risk, high-impact > High-risk, aesthetic/speculative

### 5.3 Missing Code = Write More
- Nếu thiếu feature, API, logic, edge case → **VIẾT THÊM**
- KHÔNG skip vì "không yêu cầu"
- KHÔNG remove code để simplify
- KHÔNG giảm scope để pass nhanh
- **App phải ngày càng hoàn thiện**

### 5.4 Production Readiness Checklist (Mental Test ALL)
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

### 5.5 Debugging Framework (Systematic)
1. Read entire file (mọi line, imports, dependencies)
2. Understand context (structure, related logic, external calls)
3. Isolate problem (reproduction case, minimal code)
4. Test hypotheses (debug prints, unit tests)
5. Verify fix (no regression)

**Nếu vẫn fail**: Consult team, review git history, disable feature tạm thời (không xóa), plan restore.

### 5.6 Simplicity & Surgical Changes
- **Simplicity-first**: Minimum code that solves the problem. Nothing speculative.
- **Surgical**: Touch only what you must. Don't "improve" adjacent code.
- **Goal-driven**: Define success criteria BEFORE implementation.
- **No hallucination**: MUST NOT guess, infer missing behavior, invent APIs. If info missing → STOP and ask.

---

## 6. AUTONOMOUS EXECUTION ENGINE

### 6.1 Startup Routine
Khi agent khởi động:
1. Đọc `docs/AGENT_METRICS.md` → current state
2. Đọc `docs/AGENT_PROFILE.md` → known weaknesses
3. Đọc `docs/EVOLUTION.md` → trajectory
4. Schedule discovery cycle (interval: 2h)
5. Begin autonomous loop

### 6.2 Continuous Cycle Schedule
- Discovery Cycle: Mỗi 2h từ thời điểm bắt đầu
- Task Execution: Mỗi task ≤30 phút, verify ngay sau mỗi step
- Sleep: 5 phút giữa cycles nếu Task Queue rỗng
- Batch Size: Tối đa 3 tasks/cycle

### 6.3 Emergency Break Conditions
STOP agent nếu (trong 10 phút rolling window):
- Error rate >5%
- Memory usage >90%
- Disk space <10%
- External dependency outage >30 phút

**Khi STOP**:
- Ghi log vào `docs/AGENT_METRICS.md` với reason
- Rollback last batch (`git revert HEAD~n`)
- Alert (nếu remote configured)
- Chờ manual intervention

---

## 7. SYSTEMATIC WORKFLOW (5 PHASES)

```
Analyze → Clarify → Plan → Test(fail) → Implement → Refactor → Optimize → Audit → Verify
```

**Audit step MANDATORY trước Verify**. Code phải pass System Audit (10 dimensions) trước khi considered done.

### Phase 1: DISCOVERY & PROACTIVE ANALYSIS
**Priority**: Violations trước, Improvement sau.

**7.1.1 Quality Gates & Violations** (Mỗi 2h):
Run lint, type-check, test --coverage, build. Phân loại violations:
- SECURITY: secrets, injection, auth flaws, CVE
- PERFORMANCE: O(n²), N+1 queries, memory leaks, blocking I/O
- QUALITY: complexity>10, functions>20lines, dup>5
- TESTING: coverage<80%, missing tests, no error path tests
- DEBT: TODOs, FIXMEs, legacy code, deprecated APIs

Nếu có violations → skip Proactive Analysis.

**7.1.2 Proactive Analysis** (Only if NO violations):
10 steps parallel:
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

Nếu sau 10 bước vẫn không có task → tăng targets (coverage 90%, complexity 5) và repeat.

### Phase 2: PLANNING
Auto-select highest priority từ Task Queue:
1. CRITICAL violations (security breaches, breaking tests)
2. HIGH violations (quality gate failures, performance regressions)
3. SECURITY improvements
4. PERFORMANCE improvements
5. MEDIUM violations (code smells, missing tests)
6. LOW tasks (documentation, trivial refactors)

**Micro-Plan** (≤3 steps, mỗi step ≤30 phút):
```
Step 1: [action] → verify: [cmd output]
Step 2: [action] → verify: [cmd output]
Step 3: [action] → verify: [cmd output]
```

### Phase 3: EXECUTION
Rules:
- ALWAYS write test FIRST (red-green-refactor)
- NEVER modify unrelated code
- ALWAYS match existing style
- ALWAYS update docs nếu API changes
- ALWAYS run quality gate sau mỗi batch

Success definition:
- All tests pass
- Coverage improved OR maintained ≥80%
- No new violations
- Quality gate score ≥90
- Improvement impact measurable

### Phase 4: AUDIT (MANDATORY BEFORE VERIFY)
Run System Audit (10 dimensions - Section 11). For each issue:
- Severity (CRITICAL/HIGH/MEDIUM/LOW)
- Location (flow/module/function)
- Exploit (how to trigger)
- Impact (data loss, financial, security)
- Root cause (technical deep-dive)
- Fix (code change, config)
- Test case (concrete test)
- Priority (P0/P1/P2)

**Audit Pass Criteria**: Self-score ≥90 + zero critical issues.  
Nếu fail → return to Phase 3 Optimize, do NOT proceed to Verify.

### Phase 5: REPORTING & VERIFICATION
**Metrics Log Format** (append to `docs/AGENT_METRICS.md`):
```markdown
## [YYYY-MM-DD HH:MM] Cycle N - Task: [Task Name]
- **Type**: Violation Fix / Proactive Improvement
- **Priority**: CRITICAL/HIGH/MEDIUM/LOW
- **Duration**: X minutes
- **Status**: ✅ Success / ❌ Failed
- **Test Delta**: +Y tests (total Z)
- **Coverage Delta**: Statements +A%, Branches +B%, ...
- **Performance**: p50 X→Y, p99 X→Y
- **Security**: [actions]
- **Notes**: [details]
```

**Auto-Update Docs**:
- Append metrics to `docs/AGENT_METRICS.md`
- Append weakness to `docs/AGENT_PROFILE.md`
- Append trajectory changes to `docs/EVOLUTION.md`

---

## 8. METRICS & EVOLUTION

### 8.1 Continuous Improvement Metrics
**Health Score**:
```text
Health = (coverage% × 0.3) + ((1 - avg_complexity/20) × 0.3) + (test_count/1000 × 0.2) + ((1 - duplication%) × 0.2)
```
Target: tăng ≥0.5% mỗi tuần. Nếu stagnant >3 ngày → escalate.

**Targets**:
- Evolution Rate: ≥10 improvements/tuần
- Technical Debt: TODOs/FIXMEs giảm ≥2/tuần
- Security: CVE patches within 24h, JWT RS256 enforced, rate limiting active
- Performance: p50/p99 giảm ≥5% mỗi iteration (khi có performance tasks)
- Documentation: JSDoc ≥95% cho public APIs
- Observability: Mỗi service có ≥5 custom metrics + tracing + structured logs

### 8.2 Self-Evolution Files (Auto-maintain)
- `docs/AGENT_METRICS.md`: iterations/task, failure rate, rollback, regressions, MTTR
- `docs/AGENT_PROFILE.md`: weaknesses, fragile modules, error-prone stacks
- `docs/EVOLUTION.md`: 3-6 month roadmap, refactors, debt, infrastructure evolution

**Meta-Goal**: System breaks less, fixes faster, plans further ahead, makes fewer repeated mistakes.

---

## 9. IMPROVEMENT CATALOG

Mỗi task thuộc một type (ưu tiên: R > P > S > T > D > O > C > U > M):

| Type | Category | Actions |
|------|----------|---------|
| R | Refactor | Split large classes/functions, extract interfaces, reduce coupling, apply SOLID |
| P | Performance | Add caching, optimize queries (JOIN/batch), lazy loading, memoization |
| S | Security | Implement RBAC, add WAF rules, encrypt sensitive fields, rotate secrets |
| T | Tests | Increase branch coverage >85%, add fuzzing, integration tests, property-based testing |
| D | Documentation | Update JSDoc, write ADRs, improve README, add architecture diagrams |
| O | Observability | Add distributed tracing, create new metrics, improve log context |
| C | Compliance | Ensure GDPR/PCI/HIPAA controls (data retention, consent, audit logs, encryption) |
| U | Upgrade | Update dependencies, migrate to new major versions, replace deprecated APIs |
| M | Modernization | Migrate to async/await, adopt TypeScript strict mode, CI/CD enhancements |

**Task Format**: `[TYPE] Module: Action (Expected Impact)`

---

## 10. TESTING & QUALITY ASSURANCE

### 10.1 Test Generation Strategy
- Mock external dependencies (DB, network, FS, queue)
- Test pure logic only (unit tests)
- Tests <100ms (fast CI), deterministic (no flaky)
- Include: valid, null/undefined, boundaries, malformed
- Verify: return values AND side effects
- Coverage goal: CI branch ≥80%
- Error paths: ALL must be covered
- Public API: Each function/method ≥1 test
- Structure: `describe` → `it` (AAA pattern)

**Test Types**:
- Unit: business logic
- Integration: service contracts
- E2E: <10% of suite (expensive)

### 10.2 Production Testing Pipeline (27 Gates)
Before push to production:
Source Hygiene → Dependency Freeze → Clean Build → Artifact Verification → Static Analysis → Type/Schema Verification → Unit Test → Coverage Gate (≥80%) → Integration Test → Contract Test → Data Migration Validation → Security Scan → Compliance Check → Performance Sanity → Stress/Edge Case → Failure Mode → Observability Validation → Config Validation → Packaging → Staging Deploy → Smoke Test → Rollback Test → Human Review → Sign-off → Git Push → CI Re-run → Production Release

> "Code lies until proven correct. Vibe code then push straight = prototype, not software engineering."

---

## 11. SYSTEM AUDIT FRAMEWORK (MANDATORY BEFORE PRODUCTION)

### 11.1 Audit Dimensions (10)
1. **Business Logic Integrity**: bypass validation, client input manipulation, edge case logic, dangerous assumptions
2. **End-to-End Flow Audit**: Client → API → Service → DB → Cache → Queue → Worker → External → Response. Find: flow ngắt giữa chừng, không rollback, silent failure, missing error handling
3. **Concurrency & Race Condition**: 2 request cùng lúc, 100 request song song, multi-tab, retry, background job song song. Xác định: lost update, double execution, dirty write, non-atomic, lock thiếu
4. **Database & Data Integrity**: Transaction? Isolation level? Deadlock? Unique constraint? Foreign key? Orphan data? Inconsistent state?
5. **Caching & Consistency**: Cache invalidation? Stale cache? Cache stampede? Cache update trước DB commit?
6. **Idempotency**: Endpoint idempotent? Retry gây double? Webhook 2 lần? Background job trùng?
7. **Failure Scenarios**: DB crash, external API timeout, worker crash, network partition, disk full, memory spike, CPU spike, queue backlog
8. **Security Audit (STRIDE+DREAD)**: Input validation, output encoding, SQL/NoSQL injection, XSS, CSRF, SSRF, broken access control, JWT verification, webhook signature, replay attack
9. **Scalability Analysis**: O(n)? N+1 query? Memory leak? Blocking I/O? Thread starvation? Event loop blocking? Benchmark: p50<100ms, p99<200ms
10. **Observability & Monitoring**: Log đầy đủ? PII trong log? Metric quan trọng? Alert khi failure? Health check? Correlation ID? Distributed tracing?

### 11.2 Mandatory Test Cases (cho mỗi vấn đề)
- Load test, concurrency test, retry test, chaos test
- Edge case input, malicious input, boundary value, stress test
- Memory leak simulation, integration test (full E2E)

### 11.3 Audit Report Template
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

### 11.4 Fix Priority Matrix
| Severity | Ease | Priority |
|----------|------|----------|
| Critical | Easy | P0 - Immediate |
| Critical | Hard | P0 - Plan |
| High | Easy | P1 - This Sprint |
| High | Hard | P1 - Next Sprint |
| Medium | - | P2 - Backlog |
| Low | - | P3 - Optional |

---

## 12. ARCHITECTURE & DESIGN

### 12.1 Backend Architecture
**Pattern**: Clean Architecture / Modular Monolith
```
Module/
├── Api/           # Controllers, DTOs, HTTP handling
├── Application/   # Services, Use Cases, business logic orchestration
├── Domain/        # Entities, ValueObjects, Repository interfaces, Domain Events
└── Infrastructure/ # Database repos, external API clients, file storage
```

**Dependency Rule**:
- Api → Application → Domain
- Infrastructure → Application
- Domain ← không phụ thuộc gì

**CẤM**:
- Api → Repository
- Domain → Infrastructure
- Emit event từ Api

**Platform Layer** (toàn hệ): IAM, MDM, Eventing (Outbox + Broker), Config. Không gọi domain, không query bảng domain.

**Cross-Backend**: Integration Event (broker), Saga pattern, eventual consistency.

### 12.2 Frontend Architecture
**Pattern**: Domain-Aligned Event-Driven Modular SPA (Svelte)
```
src/
├── appcore/        # router, module-registry, event-bus, iam-adapter, mdm-provider, config
└── modules/
    └── {module}/
        ├── ui/           # Svelte components, pages
        ├── state/        # stores, derived state
        ├── services/     # API adapters
        ├── contracts/    # OpenAPI / Event DTO
        └── events/       # client domain events
```

**Dependency Rule**: ui → state → services → contracts; events → state

**CẤM**:
- ui gọi thẳng API
- state gọi service module khác
- import chéo module
- services mutate state trực tiếp

**Enforcement**: ESLint boundary rule, path alias per module, no-cross-import, CI fail nếu violation.

### 12.3 Event Rules
- DTO bất biến, Versioned
- Không remove field, chỉ add optional
- Domain Event: in-transaction
- Integration Event: Outbox

### 12.4 Transaction
```
App Core → UnitOfWork → Module A → Module B → Domain Events → Commit → Integration Events
```
Cross-backend: eventual consistency + saga.

---

## 13. COMPLIANCE, COST & LEGACY

### 13.1 Compliance Matrix
Triggered by keywords: GDPR, HIPAA, PCI, SOX, COPPA, "cloud", "AWS", "legacy", "monolith", "migration".

Require COMPLIANCE section in project docs:
```markdown
## Compliance
- Applicable Standards: [list]
- Status: ✅ Compliant / ⚠️ Non-compliant
- Controls: [x] implemented, [ ] missing
- Gaps & Remediation: [unchecked items with plan]
- Next Audit Date: YYYY-MM-DD
```

### 13.2 Cost Optimization (Cloud)
- Compute: right-size (60-70% CPU), spot/preemptible, reserved (1-3y, 30-50% off)
- Storage: S3 Intelligent-Tiering, lifecycle, EBS gp3 vs io2
- Database: read replicas, auto-scaling, Aurora Serverless
- Network: minimize transfer, same-region, compression, CDN
- Serverless: pay-per-use for spiky workloads
- Budget: alerts (50%, 80%, 100%), tagging (Environment, Team, Project, Owner)

### 13.3 Legacy System Integration
- Strangler Fig: identify bounded context, build parallel isolated module, gradually route traffic
- Legacy DB migration: dual writes, data validation, read-replica sync lag <1s, blue-green cutover
- API versioning: always version from start (/api/v1/), support previous version, deprecation headers
- Technical debt: debt ratio = legacy LOC / total; >30% → recommend refactor sprint

---

## 14. VERIFICATION & COLLABORATION

### 14.1 Pre-commit
- Lint (ESLint/ golangci-lint/ ruff)
- Type-check (tsc/ mypy/ pyright)
- Test with coverage (≥80%)
- Fail on high-severity npm audit

### 14.2 CI Pipeline (GitHub Actions)
```yaml
jobs:
  quality:
    steps:
      - lint
      - type-check
      - test --coverage (enforce ≥80%)
      - security-scan (Snyk/Trivy/CodeQL)
      - upload-artifacts
```

### 14.3 PR Template
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

### 14.4 SLA & Branch Strategy
- Initial review <24h, follow-up <12h, critical security <4h
- Escalation: blocked >48h → tech lead → engineering manager
- Main protected, feature branches, PR required, no force-push
- At least 1 approving review, status checks required (CI, coverage)

### 14.5 Versioning (SemVer 2.0)
- MAJOR: incompatible API changes
- MINOR: backward-compatible new features
- PATCH: backward-compatible bug fixes
- Conventional Commits: `feat:`, `fix:`, `docs:`, `chore:`, `refactor:`, `perf:`, `test:`
- Git tagging: `git tag -a v1.2.3 -m "Release" && git push origin v1.2.3`
- Changelog: [Unreleased], [1.2.3]-date, Added/Fixed/Removed

---

## 15. OPERATIONAL ARTIFACTS (Auto-maintain)

### 15.1 AGENT_METRICS.md
```markdown
## [YYYY-MM-DD HH:MM] Cycle N - Task: [Task Name]
- **Type**: [ ]
- **Priority**: [ ]
- **Duration**: X min
- **Status**: ✅ Success / ❌ Failed
- **Test Delta**: +Y tests (total Z)
- **Coverage Delta**: Statements +A%, Branches +B%, ...
- **Performance**: p50 X→Y, p99 X→Y
- **Security**: [actions]
- **Notes**: [details]
```

### 15.2 AGENT_PROFILE.md
```markdown
# Agent Weakness Profile
## Fragile Modules
- [module]: [reason], [failure rate]%
## Error-Prone Stacks
- [technology]: [common mistakes]
## High-Cost Changes
- [area]: [reason], [avg hours]
## Recurring Debt
- [type]: [count], [trend]
```

### 15.3 EVOLUTION.md
```markdown
# Evolution Roadmap (3-6 months)
## Current Trajectory
- Health Score trend: [ ]
## Upcoming Refactors
- [Module]: [reason], [estimated effort]
## Infrastructure Evolution
- [Plan]: [rationale, timeline]
## Debt Reduction Plan
- [Debt type]: [target reduction]
```

---

## 16. QUICK REFERENCE

### 16.1 Mental Testing Checklist
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

> If ANY missing → VIẾT THÊM (do not skip).

### 16.2 Quality Gate Checklist
```
[✔] Funcs≤20 | Comp≤10 | No dup5 | 100% ErrHnd | 100% Val | No secrets | Testable
[✔] Cov≥80% | Tests pass | No 12 anti-patterns | Devil's advocate | Mental test done
[✔] Flow coverage (UI→DB & DB→UI) | Missing code written | Code preserved
[✔] Risk assessed (Low/Med/High) | Git committed
```

### 16.3 Common Commands
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

### 16.4 Decision Matrix
| Situation | Action |
|-----------|--------|
| Violations detected | Prioritize fix, stop proactive work |
| No violations | Run proactive analysis (10 steps) |
| Critical security | P0, fix immediately, audit |
| Performance regression | Benchmark, optimize, verify |
| Missing tests | Write tests first (red-green) |
| Legacy code touched | Add tests, document risks |
| Audit fails | Return to Optimize, do NOT verify |

---

## 17. SKILLS REFERENCE
| Skill | Use Case |
|-------|----------|
| `analyze-code` | Code analysis |
| `code-review` | Cleanup, quality |
| `security-audit` | Security review |
| `performance-optimize` | Performance tuning |
| `refactor` | Refactoring |
| `generate-tests` | Test generation |
| `backend-db-pattern` | Database design (4 Steps) |
| `iam-platform-layer` | Auth/Authorization |
| `go-architect` | Go services |
| `python-architect` | Python apps |
| `rust-architect` | Rust systems |

Use each skill when appropriate.

---

## 18. PROJECT PROFILE AUTO-DETECT
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
| Team: Large org | "org", "multiple teams" | Full process (PR templates, CODEOWNERS, SLA) |

**Default**: Medium risk, Medium size, Cloud, Small team → Full Tier 1 + Tier 2.

---

## 19. CHANGE LOG
- **v2.0** (2026-08-10): Tối ưu từ v1.0 (~700→~300 dòng). Gộp sections trùng lặp, giữ nguyên toàn bộ rules quan trọng, lessons learned từ iterations 596-621, insights từ 27 docs files.
- **v1.0** (2025-06-30): Initial merge of AGENTS.md v2.1 + AUTO-CONTINUE.md v2.2.

---

END OF GOAL.md
