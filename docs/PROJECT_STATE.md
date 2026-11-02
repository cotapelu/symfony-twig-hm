# PROJECT STATE - Phường/Xã Super App

**Snapshot:** 2026-06-29
**Architecture:** Polyglot Federated Modular Monolith (PHP, Go, Python, Kotlin, Rust, Zig) + Svelte SPA

---

## SYSTEM OVERVIEW

| Backend | Modules | Files | Tests | Coverage | Quality | Status |
|---------|---------|-------|-------|----------|---------|--------|
| PHP Symfony | 66 | 832 | 1540 Unit pass | **52.96% lines (Unit suite, modules+src, measured 2026-11-02)** — SỬA: con cũ ~22% sai; đo qua podman `php-cli-xdebug` image | ⚠️ PHPStan errors (partial) | Active |
| Go (Gin) | 37 | 13,234 | pass (109 pkgs w/ cov) | **86.63% mean per-package (2026-11-02)**; outliers `internal/health` 9.6%, `internal/middleware` 44.2% | vet clean | Active |
| Python (FastAPI) | 18 | 326 | 1514 pass / 2 skip | **96.85% (2026-11-02)** — chạy bằng system python3 (venvs trống) | - | Active |
| Kotlin (Ktor) | 33 | 206 | 59 pass | **32% instruction (jacoco, 2026-11-02)** — SỬA: con cũ 91.85% sai; chỉ `identitymanagement` có test (93%) | - | Active |
| Rust | 3 | 5+ | pass | **86.01% lines (2026-11-02)**; `src/main.rs` 0% | - | Active |
| Zig | 11 | 41 | 63/63 pass | Unknown (coverage tooling pending) | - | Active |
| Frontend (Svelte) | ? | 889 | **7654 passed / 61 skipped / 0 fail (2026-11-02, sau fix sleep test)** | **39.03% lines** (Svelte 28.37%, TS 69.94%; branches 85.39%) | svelte-check 289 errors (gate), lint 0 errors/922 warnings | Active |

---

## PHP SYMFONY DETAILS

**Location:** `php-symfony/`
**Structure:** `modules/{ModuleName}/{Controller,Entity,Repository,Service,DTO,etc}`

**Modules (66 total):**
- Standard layered: BusinessAccounts, BusinessAccount, Ecommerce, Delivery, etc.
- Mixed patterns: Some use Api/Application/Domain/Infrastructure, others use Repository/Entity/Service/Controller
- Notable: BusinessAccounts (secured), BusinessAccount (different), User, Notification, Payment, etc.

**Quality:**
- PHPStan: Many errors across codebase (especially non-secured modules)
- Testing: Unit tests: 1,540 tests, all passing (0 failures, 0 skipped). Recent: PushNotificationService refactor (468), AppointmentService tests (469), SubscriptionService tests + bug fix (470), NewsService tests (471), PollService tests (472), FeedbackService tests (473), MiniProgramService tests (474), AI Service unit tests (501), Realtime Service unit tests (502), CallAccessibility Service unit tests (503), TypingIndicator Service unit tests (504), LanguageSupport Service unit tests (505), Presence Service unit tests (506), AccessibilityReport Service unit tests (507), LiveCaption Service unit tests (508), ConnectionStability Service unit tests (509), SignLanguage Service unit tests (510), NewMessageGenerator unit tests (511), ReactionAddedGenerator unit tests (512), ParticipantRemovedGenerator unit tests (513), CallMonitorService unit tests (514), CallQualityService unit tests (515), Exploration phase (475) – dependencies too complex for quick unit tests. Integration tests excluded from default suite.
- Security: BusinessAccounts module hardened; others need audit

**Issues:**
- Coverage ~22% (improved); target ≥80%
- deptrak configured and validated; CI gate enabled (php-architecture workflow)
- PHPStan errors exist in many modules (only BusinessAccounts clean)
- 2 incomplete unit tests remain (Insurance delete methods); lower priority

**Integration Test Scaffolding:**
- Created `docker-compose.test.yml` (PostgreSQL 15 on port 5433) and `.env.test`.
- Enhanced `DatabaseIntegrationTestCase` with auto-schema creation and table truncation.
- Added `QueueIntegrationTest` (now 9 tests) – expanded coverage to include cancel, lookup by number/phone, call+start, skip.
- Added `FeedbackIntegrationTest` (5 tests) – also passing.
- Added `AppointmentIntegrationTest` (now 16 tests) – expanded coverage to include start, complete, no-show, today filtering, statistics, and assignOfficer; fixed production bug in assignOfficer.
- Added `InsuranceIntegrationTest` (20 tests) – covers policy and claim management, search, statistics, expiring policies; fixed production bugs in InsuranceService (date portability, statistics method) and Claim entity.
- Added `Government/CitizenIntegrationTest` (4 tests) – covers citizen creation, update, and retrieval.
- Added `Government/WardIntegrationDbTest` (4 tests) – covers ward CRUD, population/area updates.
- Added `PollIntegrationDbTest` (3 tests) – covers poll creation, voting, and retrieval.
- Added `Maps/PlaceIntegrationDbTest` (4 tests) – covers CRUD operations for places.
- Added `FeatureFlags/FeatureFlagIntegrationDbTest` (4 tests) – covers feature flag CRUD and enable/disable.
- Added `News/NewsIntegrationDbTest` (4 tests) – covers news article CRUD operations.
- Status: ✅ Pass (73/73 integration tests).

---

## GO BACKEND DETAILS

**Location:** `go/`
**Structure:** `go/modules/{ModuleName}/Api,Application,Domain,Infrastructure,Contracts` (some may vary)

**Modules (38):**
1. Banking
2. BillsPayment
3. Courier
4. CreditLending
5. Crypto
6. CryptoService
7. DeliveryService
8. Escrow
9. Fraud
10. FraudDetection
11. Inventory
12. KafkaEvents
13. Land
14. LandConstruction
15. Location
16. Marketplace
17. Order
18. QRPayment
19. SampleModule
20. WalletService
21. authservice
22. certificateauthority
23. citizen
24. common
25. coreservices
26. events
27. integration
28. keymanagement
29. livestreaming
30. location (duplicate?)
31. locationtracking
32. marketplace (duplicate?)
33. notifications
34. notificationsrealtime
35. optimized
36. realtimesync
37. testing
38. videoconference

**Observations:**
- Some duplicate names (location, marketplace) - may be intentional (different purposes)
- Need to audit each for architecture compliance (Api→Application→Domain)
- Tests exist? Need to check
- Coverage: Need to run `go test ./... -cover`

---

## PYTHON BACKEND DETAILS

**Location:** `python/`
**Structure:** `python/app/modules/{ModuleName}/Api,Application,Domain,Infrastructure,Contracts` (expected)

**Modules (18):**
- Need to list from `python/modules/` directory

**Observations:**
- FastAPI likely
- Need type checking (mypy)
- Need tests with pytest-cov for coverage
- Potential circular import issues

---

## KOTLIN BACKEND DETAILS

**Location:** `kotlin/`
**Structure:** `kotlin/modules/{ModuleName}/Api,Application,Domain,Infrastructure,Contracts` (expected)

**Modules (33):**
- Need to list from `kotlin/modules/`

**Observations:**
- Ktor framework likely
- Need ArchUnit tests for architecture
- Need JaCoCo for coverage
- Need ktlint for code style

---

## RUST BACKEND

**Location:** `rust/modules/`
**Modules:** 3 only (Audit, Access, Security)
**Files:** 5 total

**Observations:**
- Severely underdeveloped. Missing core domain modules (Banking, Payments, etc.)
- Likely just POC or placeholder
- **CRITICAL**: Need major expansion to match other backends
- Tests likely minimal or none
- Coverage likely 0%

---

## ZIG BACKEND

**Location:** `zig/modules/`
**Modules:** 11 (SampleModule, cloudstorage, cryptoservice, contentmoderation,audiostreaming, datacompression, mediaprocessing, identity, ...)
**Files:** 39 total (~3.5 files/module)

**Observations:**
- Zig ecosystem immature, limited libraries
- Each module likely very sparse
- Need comprehensive test suite
- Need custom solutions for logging, metrics, etc.
- **CRITICAL**: Needs substantial development

---

## FRONTEND (Svelte)

**Location:** `frontend/`
**Structure:** `src/appcore/`, `src/modules/{module}/ui,state,services,contracts,events/`
**Files:** 888 TS/JS/Svelte

**Observations:**
- Needs audit: Are modules aligned with backend bounded contexts?
- Need to ensure no cross-module imports
- Need TypeScript strict mode
- Unit tests stable (1522 passing), component tests enabled; E2E tests pending
- Coverage: **38.57% line** (accurate, measured 2026-08-11 from a green run). NOTE: earlier "~8%"/"~30.1%" figures were artifacts of a broken `(empty-report)` lcov. True breakdown: TS ~69.66%, Svelte ~27.84%; `lib/components` ~91%, `modules/*/ui` ~3.79%, `src/routes/**/+page.svelte` 0% (~59.8k lines → target for E2E). Test suite green: 7569 passed / 61 skipped / 0 failed.
- Coverage measurement fixed: vitest config now includes .svelte files and excludes test files
- Added locationStore (30), callStore (12), chatOfficialStore (15), ttthStore (11), citizenServicesStore (10), voicemailStore (17), landConstructionStore (7), mapStore (7), householdStore (7), bookingsStore (12), aiStore (25), businessStore (30), chatbotStore (19), procedureStore (32), servicesStore (17), settingsStore (16), smartincidentsStore (29), filesStore (10), dashboardStore (5) unit tests with comprehensive error handling and reset methods; increased coverage.

---

## SHARED COMPONENTS

**Events:** `Events/Domain Events/`, `Events/Integration/`
**Migrations:** `migrations/` (SQL migration scripts)
**Tests:** `tests/` (top-level integration, e2e, performance, security tests)
**Platform Layer:** Unknown location (should be cross-backend shared)

---

## INFRASTRUCTURE & TOOLING

**Build & Test:**
- Makefile with targets for all backends
- Dockerfiles for each backend
- Stabilized frontend test environment: setupTests.js provides mutable window.navigator, window.location, localStorage, sessionStorage; fixed environment-related test failures.
- Coverage scripts (PHP, Go, Python, Kotlin, Frontend)
- Pre-commit hooks? (check .pre-commit-config.yaml)

**Quality Gates:**
- PHP: PHPStan (configured), deptrac (binary installed, config needs work)
- Go: go vet, staticcheck? (need to add)
- Python: mypy, pylint? (need to add)
- Kotlin: detekt? (need to add)
- Frontend: ESLint configured

**CI/CD:**
- GitHub Actions present (check .github/workflows/)
- Need to ensure all quality gates run on PR

**Observability:**
- Logging: Monolog (PHP), structured logs needed for others
- Metrics: None standardized
- Tracing: None standardized
- Health checks: Partial

**Security:**
- No unified security scanning
- Need: composer audit, npm audit, govulncheck, safety, OWASP Dependency Check

---

## IMMEDIATE PRIORITIES

1. **Coverage**: Increase PHP coverage from ~22% to ≥80% (short-term target: 35%); add missing unit tests for remaining modules (RideHailing, Common services, Notifications integration); then extend coverage measurement to Go/Python/Kotlin/Frontend
2. **Architecture enforcement**: ✅ Deptrak configured and CI enabled; import-linter integrated for Go, Python, Kotlin; monitoring violations
3. **Security hardening**: BusinessAccounts secured; audit other high-risk modules (User, Ecommerce, Payments) for IDOR/SQLi/XSS
4. **Backend audits**: Assess Go (38), Python (18), Kotlin (33) modules for boundary compliance
5. **Expand critical backends**: Rust (3 modules → full domain), Zig (11 modules → complete implementations)
6. **Observability**: Standardize structured logging (JSON) + metrics (Prometheus) across all backends
7. **Documentation**: Generate OpenAPI specs, create architecture decision records (ADRs), update README with coverage badge

---

## RISK ASSESSMENT

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Architecture drift | High | High | Enforce deptrac/import-linter, CI gates |
| Security vulnerabilities | High | High | Full audit, add tests, implement IAM properly |
| Incomplete Rust/Zig backends | Certain | High | Allocate dedicated dev time |
| Coverage <80% | Likely | Medium | Fix infrastructure, add tests |
| Integration failures | Medium | High | Add contract tests, integration tests |
| Performance bottlenecks | Medium | Medium | Add load testing, profiling |

---

## EVOLUTION TRACKING

See `docs/EVOLUTION.md` for iteration history.
See `docs/AGENT_METRICS.md` for success rates, MTTR.
See `docs/AGENT_PROFILE.md` for weaknesses and remediation.

---

*Last updated: 2026-06-29*  
*Maintained by evolution system*
