---
name: fullstack
description: Review và phát triển fullstack theo Polyglot Federated Modular Monolith + Domain-Aligned Event-Driven Modular SPA
license: MIT
compatibility: opencode
metadata:
  audience: senior-developers
  scope: fullstack
---

## Chức năng

- Review fullstack code (Frontend Svelte + Backend đa ngôn ngữ)
- Áp dụng Polyglot Federated Modular Monolith architecture
- Review domain-aligned module structure
- Validate communication patterns (HTTP + Events)
- Enforce architecture boundaries giữa FE và BE

## Khi nào dùng

Khi cần review hoặc phát triển bất kỳ phần nào của hệ thống fullstack, bao gồm:
- Frontend Svelte SPA modules
- Backend modules (PHP, Python, Go, Kotlin, Zig)
- Integration giữa FE và BE
- Platform services (IAM, MDM, Eventing, Config)

---

## Nguyên tắc bắt buộc

### 1. Đọc toàn bộ code trước khi review

- Đọc hết mọi source code liên quan
- Đọc cả config files, contracts, event definitions
- Không được bỏ sót bất kỳ file nào

### 2. Questioning mindset

- KHÔNG tin code hiện tại là đúng
- KHÔNG giả định kiến trúc hiện tại là hợp lý
- CHỈ dựa trên phân tích kỹ thuật có thể kiểm chứng

### 3. Architecture-first approach

- Luôn verify against FULLSTACK.md architecture
- Kiểm tra module boundaries
- Validate communication patterns
- Đảm bảo enforcement rules được tuân thủ

---

## Frontend Review

### Structure Validation

Frontend PHẢI theo domain-aligned structure:

```
src/
 ├── appcore/
 │    ├── router.ts
 │    ├── module-registry.ts
 │    ├── event-bus.ts
 │    ├── iam-adapter.ts
 │    ├── mdm-provider.ts
 │    ├── config.ts
 │    └── error-boundary.ts
 └── modules/
      ├── billing/
      │    ├── ui/
      │    ├── state/
      │    ├── services/
      │    ├── contracts/
      │    ├── events/
      │    └── index.ts
      ├── orders/
      └── users/
```

CẤM:
- Chia theo page/component/api/technical layer
- ui gọi thẳng API
- state gọi service module khác
- import chéo module
- services mutate state trực tiếp

### Dependency Rule

```
ui → state → services → contracts
events → state
```

### State Management

- State sở hữu bởi module
- KHÔNG có global domain store
- KHÔNG share state giữa module
- Cross-module data: event hoặc read-only facade

### Communication

- HTTP/JSON với versioning
- Retry + timeout
- Idempotency key
- Realtime: WebSocket/SSE với versioned events

---

## Backend Review

### Polyglot Modular Monolith

Mỗi backend là 1 Modular Monolith:
- Build → 1 artifact
- Runtime → 1 process
- Có App Core riêng

Stack hiện tại:
- PHP Symfony
- Python FastAPI
- Go Gin
- Kotlin Ktor
- Zig

### Module Structure (per language)

**PHP Symfony:**
```
src/AppCore
src/Modules/{Module}/{Api,Application,Domain,Infrastructure,Contracts}
```

**Python FastAPI:**
```
app/appcore
app/modules/{module}/{api,application,domain,infrastructure,contracts}
```

**Go Gin:**
```
internal/appcore
internal/modules/{module}/{api,application,domain,infra,contracts}
```

**Kotlin Ktor:**
```
modules/{module}/{api,application,domain,infra,contracts}
```

**Zig:**
```
modules/{module}/{api,application,domain,infra,contracts}.zig
```

### Module Internal Architecture

```
Module
├── Api
├── Application
├── Domain
├── Infrastructure
└── Contracts
```

**Dependency Rule:**
- Api → Application → Domain
- Infrastructure → Application
- Domain → không phụ thuộc gì

**CẤM:**
- Api → Repository
- Domain → Infrastructure
- Emit event từ Api

### Communication

**Trong backend:**
- Domain Event
- Sync qua App Core
- CẤM direct call

**Giữa backend:**
- Integration Event (broker)
- Sync chỉ khi cần: HTTP/GRPC với timeout, retry, circuit breaker, idempotency

---

## Fullstack Integration Review

### System Overview

```
Frontend (Svelte SPA)
       ↓ HTTP/WebSocket
API Gateway / Backend Facades
       ↓
[ PHP ] [ Python ] [ Go ] [ Kotlin ] [ Zig ]
       ↓
Platform Layer + Event Bus
(IAM, MDM, Config, Eventing)
```

### Domain Alignment

Frontend modules mirror Backend modules:
- `modules/billing/` (FE) ↔ `modules/billing/` (BE)
- Cùng bounded context
- Contract-driven communication

### Communication Patterns

**1. Request/Response (Sync):**
```
FE → API Gateway → Backend Module → Database
→ Response → FE
```

Rules:
- Versioned endpoints
- Retry + timeout
- Idempotency key

**2. Event-Driven (Async):**
```
Backend → Integration Event → Event Bus → FE
FE subscribes → State update → UI re-render
```

Rules:
- Immutable events
- Versioned events
- Add optional, never remove fields

### Contract Management

**API Contracts:**
- Backend exposes: OpenAPI/Swagger
- FE consumes: TypeScript types from OpenAPI
- Version: v1, v2, ...

**Event Contracts:**
- Backend emits: Integration Events (JSON)
- FE subscribes: Event DTOs
- Rule: Never change meaning, only add optional fields

---

## Platform Layer Review

Platform services shared across all backends và frontend:

### IAM (Identity & Access Management)
- Authentication: JWT, OAuth2
- Authorization: RBAC, permissions
- Frontend: `iam-adapter.ts`

### MDM (Master Data Management)
- Reference data shared across modules
- Frontend: `mdm-provider.ts`

### Eventing
- Outbox pattern for reliability
- Broker: Kafka/RabbitMQ/Pulsar
- Frontend: `event-bus.ts`

### Config
- Environment-based configuration
- Feature flags

**Quy tắc:**
- Không gọi domain
- Không query bảng domain
- Chỉ expose abstraction
- Không nghiệp vụ

---

## Enforcement

### Backend Enforcement

- **PHP:** deptrac (Domain≠Infra, Api≠Domain, no cross-module)
- **Python:** import-linter + pytest architecture test
- **Go:** go vet + analyzer
- **Kotlin:** ArchUnit + Gradle fail
- **Zig:** build-time dependency graph

### Frontend Enforcement

- ESLint boundary rule
- path alias per module
- no-cross-import
- lint architectural test

### CI Fail Conditions

- Import sai layer
- Import chéo module
- Domain phụ thuộc Infra
- API bypass Application
- UI imports services directly
- Module imports other module

---

## Data & Boundary

**DB = infrastructure concern**

Mỗi module:
- Schema riêng hoặc
- Prefix riêng

**CẤM:**
- Query entity module khác
- Share schema

**Cross-module data:**
- Event
- Read-only facade
- MDM

---

## Event Rules

- DTO bất biến
- Versioned
- Không remove field
- Chỉ add optional
- Domain Event: in-transaction
- Integration Event: Outbox

---

## Transaction

```
App Core
  ↓
UnitOfWork
  ↓
Module A → Module B
  ↓
Domain Events
  ↓
Commit
  ↓
Integration Events
```

**Cross-backend:** eventual consistency + saga

---

## Deployment Considerations

1. **Each Backend:**
   - Build: 1 artifact
   - Runtime: 1 process
   - Independent deployment

2. **Frontend:**
   - Single SPA build
   - Deploy independent of backends
   - Must tolerate BE drift

3. **Platform Layer:**
   - Must be backward compatible
   - Support multiple BE versions

---

## Scaling Patterns

**Horizontal Scaling (Backend):**
- Each backend scales independently
- Event bus handles message distribution
- Read models for query optimization

**Vertical Scaling (Frontend):**
- Add new domain modules
- Scale existing modules
- Lazy load modules by route

---

## Consistency Model

**Within Single Backend:**
- ACID transactions
- Domain events in same transaction

**Cross-Backend:**
- Eventual consistency
- Saga pattern for long-running transactions
- Compensation actions on failure

**Frontend:**
- Optimistic UI updates
- Eventual sync with backend state
- Graceful degradation on event lag

---

## Security Checks

### Frontend
- Authentication tokens secure
- No sensitive data in client
- XSS prevention
- CSRF protection

### Backend
- Input validation
- SQL injection prevention
- Authentication/Authorization
- Secrets management

### Fullstack
- API contracts validate both sides
- Event schemas validated
- IAM integration verified

---

## Performance Checks

### Frontend
- Lazy loading modules
- Bundle size optimized
- API calls minimized

### Backend
- Database queries optimized (no N+1)
- Connection pooling
- Caching strategy

### Fullstack
- Event latency acceptable
- HTTP timeout properly configured
- Circuit breaker working

---

## Debugging & Issue Resolution

### Nguyên tắc:

- **Đọc toàn bộ file trước** - Không chỉ đoạn code suspected
- **Bình tĩnh phân tích** - Đọc lại toàn bộ file liên quan
- **Tìm root cause** - Check imports, dependencies, communication
- **Incremental debugging** - Test từng phần nhỏ
- **Systematic approach** - Read → Understand → Isolate → Test → Verify

### Cross-stack debugging:

- Trace request từ FE → API Gateway → Backend
- Trace event flow từ Backend → Event Bus → FE
- Verify contract compatibility
- Check version mismatches

---

## Review Output Format

Khi kết thúc review, báo cáo:

```
Fullstack Architecture Review:

✅ Frontend Structure:
   - Domain-aligned modules: [OK/MISSING]
   - Dependency flow ui→state→services→contracts: [OK/MISSING]
   - No cross-module imports: [OK/MISSING]

✅ Backend Structure:
   - Module isolation: [OK/MISSING]
   - Clean architecture (Api→Application→Domain→Infra): [OK/MISSING]
   - No cross-module imports: [OK/MISSING]

✅ Integration:
   - API contracts validated: [OK/MISSING]
   - Event contracts validated: [OK/MISSING]
   - Communication patterns correct: [OK/MISSING]

✅ Platform Services:
   - IAM integration: [OK/MISSING]
   - MDM usage: [OK/MISSING]
   - Eventing: [OK/MISSING]

✅ Enforcement:
   - CI checks passed: [OK/MISSING]
   - No architectural violations: [OK/MISSING]

⚠️ Issues Found: [liệt kê nếu có]
📝 Recommendations: [nếu cần]
```

---

## Validation Checklist

Trước khi kết thúc review, verify:

- [ ] Frontend domain-aligned structure
- [ ] Backend module isolation
- [ ] Clean architecture layers
- [ ] Communication patterns (HTTP + Events)
- [ ] Contract versioning
- [ ] Platform services integration
- [ ] CI enforcement passed
- [ ] No cross-module imports
- [ ] Security checks passed
- [ ] Performance acceptable

---

## Mâu thuẫn với Other Skills

Skill này là **source of truth** cho fullstack architecture.

Nếu có mâu thuẫn với các skill khác (rust-review, symfony-review, etc.):
- **Frontend structure:** LUÔN follow domain-aligned (KHÔNG follow Atomic Design từ skill files)
- **Module layers:** LUÔN follow Api→Application→Domain→Infra (KHÔNG follow skill-specific structures)
- **Testing:** VIẾT TEST CODE thực tế (KHÔNG theo rule "KHÔNG VIẾT TEST CODE")
- **Tool checking:** DÙNG TOOL thực tế (KHÔNG theo rule "KHÔNG CHECK BẰNG TOOL")

---

## Lưu ý quan trọng

1. **Frontend KHÔNG mirror kiến trúc backend**, nhưng phải phản chiếu ranh giới domain, contract và event

2. **Frontend là consumer, không phải nơi chứa nghiệp vụ**

3. **Mỗi backend độc lập** nhưng được liên kết qua Platform Layer và Distributed Event Bus

4. **Events là primary communication** giữa modules và giữa backends

5. **Contract versioning** là chìa khóa để FE chịu được BE drift

---

## Tham khảo

- FULLSTACK.md - Architecture document chính
- BACKEND.md - Backend architecture
- FRONTEND.md - Frontend architecture
