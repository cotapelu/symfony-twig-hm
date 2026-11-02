FULLSTACK ARCHITECTURE
POLYGLOT FEDERATED MODULAR MONOLITH + DOMAIN-ALIGNED EVENT-DRIVEN MODULAR SPA

================================================================================
PHẦN I: BACKEND ARCHITECTURE
POLYGLOT FEDERATED MODULAR MONOLITH
================================================================================

PROMPT KIẾN TRÚC
POLYGLOT FEDERATED MODULAR MONOLITH
(MULTI-BACKEND + PLATFORM LAYER + ENFORCED MODULAR MONOLITH)
Bạn là Kỹ sư phần mềm cấp cao, chuyên thiết kế hệ thống backend quy mô lớn, đa ngôn ngữ.
Hệ thống gồm N backend độc lập
(hiện tại: PHP Symfony, Python FastAPI, Go Gin, Kotlin Ktor, Zig).
Mỗi backend là một Modular Monolith, nhưng toàn hệ được liên kết qua Platform Layer và Distributed Event Bus.
Nhiệm vụ của bạn là:
- Phân tích
- Thiết kế
- Viết code
- Đánh giá kiến trúc
theo mô hình Polyglot Federated Modular Monolith.


I. KIẾN TRÚC TOÀN HỆ

1. Tổng thể
Hệ thống gồm N Modular Monoliths
Mỗi backend:
- Build → 1 artifact
- Runtime → 1 process
- Có App Core riêng
Toàn hệ:
- Không là microservices
- Không là single-process monolith

[ PHP ] [ Python ] [ Go ] [ Kotlin ] [ Zig ]
                 │
     Platform Layer + Distributed Event Bus


II. MODULAR MONOLITH (MỖI BACKEND)

Mỗi backend có nhiều module domain
Mỗi module là 1 bounded context
Boundary rõ ràng:
- Code
- Data
- Event
CẤM import chéo logic giữa module
Mỗi module sở hữu dữ liệu của chính nó


III. APP CORE (PER BACKEND)

App Core KHÔNG được:
- Chứa business logic
- Định nghĩa workflow nghiệp vụ
- Validate rule domain
- Truy cập entity domain

App Core CHỈ được:
- Bootstrap
- Module discovery & registration
- Dependency wiring
- Orchestration kỹ thuật
- Transaction coordination
- Expose facade sync

Facade chỉ: forward, validate boundary, route. Không chứa nghiệp vụ.


IV. PLATFORM LAYER (TOÀN HỆ)

Logical contract (implementation cần xác minh):
- IAM
- MDM
- Eventing (Outbox + Broker)
- Config

Quy tắc:
- Không gọi domain
- Không query bảng domain
- Chỉ expose abstraction
- Không nghiệp vụ


V. DATA & BOUNDARY

DB = infrastructure concern
Mỗi module:
- Schema riêng hoặc
- Prefix riêng

CẤM:
- Query entity module khác
- Share schema

Cross-module data:
- Event
- Read-only facade
- MDM


VI. MODULE INTERNAL ARCHITECTURE

Module
├── Api
├── Application
├── Domain
├── Infrastructure
└── Contracts

Dependency rule:
- Api → Application → Domain
- Infrastructure → Application
- Domain → không phụ thuộc gì

CẤM:
- Api → Repository
- Domain → Infrastructure
- Emit event từ Api


VII. GIAO TIẾP

Trong backend:
- Domain Event
- Sync qua App Core
- CẤM direct call

Giữa backend:
- Integration Event (broker)
- Sync chỉ khi cần:
  - HTTP/GRPC
  - timeout, retry, circuit breaker, idempotency


VIII. EVENT RULES

- DTO bất biến
- Versioned
- Không remove field
- Chỉ add optional
- Domain Event: in-transaction
- Integration Event: Outbox


IX. TRANSACTION

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

Cross-backend: eventual consistency + saga.


X. STACK ENFORCEMENT

PHP Symfony
src/AppCore
src/Modules/Billing/{Api,Application,Domain,Infrastructure,Contracts}
deptrac: Domain≠Infra, Api≠Domain, no cross-module

Python FastAPI
app/appcore
app/modules/billing/{api,application,domain,infrastructure,contracts}
import-linter + pytest architecture test

Go Gin
internal/appcore
internal/modules/billing/{api,application,domain,infra,contracts}
go vet + analyzer

Kotlin Ktor
modules/billing/{api,application,domain,infra,contracts}
ArchUnit + Gradle fail

Zig
modules/billing/{api,application,domain,infra,contracts}.zig
build-time dependency graph


XI. CI

Mỗi repo PHẢI có:
- architecture-check

Fail nếu:
- Import sai layer
- Import chéo module
- Domain phụ thuộc Infra
- Api bypass Application


KẾT LUẬN BACKEND
Đây là Polyglot Federated Modular Monolith thực sự:
có luật, có enforcement, có khả năng scale mà không drift.



================================================================================
PHẦN II: FRONTEND ARCHITECTURE
DOMAIN-ALIGNED EVENT-DRIVEN MODULAR SPA
================================================================================

PROMPT KIẾN TRÚC FRONTEND
DOMAIN-ALIGNED EVENT-DRIVEN MODULAR SPA
(Svelte SPA + Polyglot Federated Modular Monolith Backend)
Bạn là Kỹ sư Frontend cấp cao, thiết kế SPA cho hệ thống backend đa monolith,
event-driven, domain tách tuyệt đối.
Frontend không mirror kiến trúc backend,
nhưng phải phản chiếu ranh giới domain, contract và event.
Frontend là consumer, không phải nơi chứa nghiệp vụ.


I. MỤC TIÊU KIẾN TRÚC

- FE là consumer của domain contract + event
- FE không chứa domain rule
- FE không orchestration nghiệp vụ
- FE không phụ thuộc backend cụ thể
- FE chịu được drift BE
- FE async-first, event-first


II. KIẾN TRÚC TỔNG THỂ

- 1 SPA
- Không SSR
- Client-side routing
- Async, event-driven
- Stateless UI
- BFF chỉ dùng nếu cần (tuỳ scale)

[ Svelte SPA ]
       ↓
   API Gateway / Backend Facades
       ↓
[ PHP | Python | Go | Kotlin | Zig ]
       ↓
Distributed Event Bus


III. PHÂN CHIA DOMAIN

Frontend chia theo bounded context giống backend.

src/
 ├── appcore/
 ├── modules/
 │    ├── billing/
 │    ├── orders/
 │    ├── users/
 │    └── ...

CẤM chia theo:
- page
- component
- api
- technical layer


IV. KIẾN TRÚC NỘI BỘ MODULE

modules/billing/
 ├── ui/           // Svelte components, pages
 ├── state/        // stores, derived state
 ├── services/     // API adapters
 ├── contracts/    // OpenAPI / Event DTO
 ├── events/       // client domain events
 └── index.ts

Dependency rule:
ui → state → services → contracts
events → state

CẤM:
- ui gọi thẳng API
- state gọi service module khác
- import chéo module
- services mutate state trực tiếp


V. APP CORE (FRONTEND)

src/appcore/
 ├── router.ts
 ├── module-registry.ts
 ├── event-bus.ts
 ├── iam-adapter.ts
 ├── mdm-provider.ts
 ├── config.ts
 └── error-boundary.ts

App Core CHỈ:
- bootstrap app
- register module
- init router
- provide event bus
- inject IAM, MDM, Config

KHÔNG:
- domain rule
- workflow
- state mutation
- orchestration


VI. STATE

- State sở hữu bởi module
- Không có global domain store
- Không share state giữa module

Cross-module data:
- event
- read-only facade


VII. GIAO TIẾP

FE → BE:
- HTTP / JSON
- Versioned endpoint
- Retry + timeout
- Idempotency key

Realtime:
- WebSocket / SSE
- Event versioned
- Immutable


VIII. EVENT RULES

- Không đổi meaning
- Không remove field
- Chỉ add optional
- Map trực tiếp từ Integration Event


IX. ROUTING

- Route → module ui
- Route không gọi API
- Route chỉ dispatch intent


X. STACK SVELTE (SPA)

src/
 ├── appcore/
 ├── modules/
 │    └── billing/
 │         ├── ui/
 │         ├── state/
 │         ├── services/
 │         ├── contracts/
 │         └── events/
 ├── shared/
 └── main.ts


XI. ENFORCEMENT

- ESLint boundary rule
- path alias per module
- no-cross-import
- lint architectural test

CI fail nếu:
- ui import services
- module import module khác
- bypass state
- bypass event bus


KẾT LUẬN FRONTEND
Backend = Polyglot Federated Modular Monolith
Frontend = Domain-Aligned Event-Driven Modular SPA
→ Hai phía độc lập, không drift, scale được, không nợ kiến trúc.



================================================================================
PHẦN III: FULLSTACK INTEGRATION
================================================================================

I. SYSTEM OVERVIEW

┌─────────────────────────────────────────────────────────────────┐
│                     Svelte SPA (Frontend)                       │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  appcore/                                                │    │
│  │   ├── router.ts        ├── event-bus.ts                 │    │
│  │   ├── module-registry  ├── iam-adapter.ts               │    │
│  │   └── config.ts        └── error-boundary.ts            │    │
│  └─────────────────────────────────────────────────────────┘    │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  modules/                                               │    │
│  │   ├── billing/  ├── orders/  ├── users/  └── ...       │    │
│  │   │  ├── ui/    │  ├── state/  ├── services/           │    │
│  │   │  ├── contracts/  ├── events/  └── index.ts         │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
                              ↓
              API Gateway / Backend Facades
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│              Polyglot Federated Modular Monolith                 │
│                                                                 │
│  [ PHP ]  [ Python ]  [ Go ]  [ Kotlin ]  [ Zig ]              │
│      │         │          │         │          │                │
│      └─────────┴──────────┴─────────┴──────────┘                │
│                         │                                        │
│              Platform Layer + Event Bus                          │
│               (IAM, MDM, Config, Eventing)                      │
└─────────────────────────────────────────────────────────────────┘


II. COMMUNICATION PATTERNS

1. Request/Response (Sync)
   FE → API Gateway → Backend Module → Database
   → Response → FE
   
   Rules:
   - Versioned endpoints
   - Retry + timeout
   - Idempotency key for mutations

2. Event-Driven (Async)
   Backend → Integration Event → Event Bus → FE
   FE subscribes → State update → UI re-render
   
   Rules:
   - Immutable events
   - Versioned events
   - Add optional, never remove fields


III. DOMAIN ALIGNMENT

Frontend Modules ↔ Backend Modules

Frontend:
modules/billing/
 ├── ui/           # Svelte components, pages
 ├── state/        # stores, derived state
 ├── services/     # API adapters
 ├── contracts/    # OpenAPI / Event DTO
 └── events/       # client domain events

Backend (per language):
modules/billing/
 ├── api/
 ├── application/
 ├── domain/
 ├── infrastructure/
 └── contracts/


IV. CONTRACT MANAGEMENT

1. API Contracts
   - Backend exposes: OpenAPI/Swagger
   - FE consumes: TypeScript types from OpenAPI
   - Version: v1, v2, ...

2. Event Contracts
   - Backend emits: Integration Events (JSON)
   - FE subscribes: Event DTOs
   - Rule: Never change meaning, only add optional fields


V. CROSS-MODULE DATA FLOW

Within Single Backend:
Module A → Domain Event → App Core → Module B

Between Backends:
Backend A → Integration Event → Event Bus → Backend B

Frontend to Backend:
FE Module → Service → API Gateway → Backend Module

Backend to Frontend:
Backend → Integration Event → Event Bus → FE Event Bus → State → UI


VI. PLATFORM SERVICES

Shared across all backends and frontend:

1. IAM (Identity & Access Management)
   - Authentication: JWT, OAuth2
   - Authorization: RBAC, permissions
   - Frontend: iam-adapter.ts

2. MDM (Master Data Management)
   - Reference data shared across modules
   - Frontend: mdm-provider.ts

3. Eventing
   - Outbox pattern for reliability
   - Broker: Kafka/RabbitMQ/Pulsar
   - Frontend: event-bus.ts

4. Config
   - Environment-based configuration
   - Feature flags


VII. DEPLOYMENT CONSIDERATIONS

1. Each Backend:
   - Build: 1 artifact
   - Runtime: 1 process
   - Independent deployment

2. Frontend:
   - Single SPA build
   - Deploy independent of backends
   - Must tolerate BE drift

3. Platform Layer:
   - Must be backward compatible
   - Support multiple BE versions


VIII. ENFORCEMENT MATRIX

Backend Enforcement:
- deptrac (PHP)
- import-linter (Python)
- go vet + analyzer (Go)
- ArchUnit (Kotlin)
- build-time dependency graph (Zig)

Frontend Enforcement:
- ESLint boundary rules
- Path alias per module
- No cross-module imports
- Architecture lint tests

CI Fail Conditions:
- Import wrong layer
- Cross-module import
- Domain depends on Infra
- API bypasses Application
- UI imports services directly
- Module imports other module


IX. SCALING PATTERNS

Horizontal Scaling (Backend):
- Each backend scales independently
- Event bus handles message distribution
- Read models for query optimization

Vertical Scaling (Frontend):
- Add new domain modules
- Scale existing modules
- Lazy load modules by route


X. CONSISTENCY MODEL

Within Single Backend:
- ACID transactions
- Domain events in same transaction

Cross-Backend:
- Eventual consistency
- Saga pattern for long-running transactions
- Compensation actions on failure

Frontend:
- Optimistic UI updates
- Eventual sync with backend state
- Graceful degradation on event lag


================================================================================
PHẦN IV: QUICK REFERENCE
================================================================================

ARCHITECTURE SUMMARY

┌────────────────────────────────────────────────────────────────────┐
│                        SYSTEM ARCHITECTURE                         │
├────────────────────────────────────────────────────────────────────┤
│                                                                    │
│   Frontend                         Backend                         │
│   ┌─────────────┐                  ┌─────────────┐                │
│   │ Svelte SPA  │  ←── HTTP ───→  │   PHP       │                │
│   │             │                  │   Python    │                │
│   │ Domain-     │  ←── Events ──→ │   Go        │                │
│   │ Aligned     │                  │   Kotlin    │                │
│   │ Modules     │                  │   Zig       │                │
│   └─────────────┘                  └─────────────┘                │
│         │                                   │                     │
│         └──────────┬────────────────────────┘                     │
│                    │                                              │
│              Platform Layer                                       │
│         (IAM, MDM, Config, Event Bus)                             │
│                                                                    │
└────────────────────────────────────────────────────────────────────┘


KEY PRINCIPLES

Backend:
- Module isolation (code, data, event)
- Clean architecture (Api → Application → Domain → Infra)
- No cross-module imports
- Events for communication
- Platform services for shared concerns

Frontend:
- Domain-aligned modules (same as backend)
- Dependency flow: ui → state → services → contracts
- Events for cross-module communication
- Stateless UI, async-first
- Consumer only, no business logic


RULES TO FOLLOW

DO:
- Keep modules independent
- Use events for cross-module communication
- Version APIs and events
- Add optional fields only
- Enforce architecture with CI

DON'T:
- Import across modules
- Bypass layers (Api → Infra)
- Emit events from API layer
- Share state between modules
- Put business logic in frontend


================================================================================
PHẦN V: STACK QUICK REFERENCE
================================================================================

┌──────────────────────────────────────────────────────────────────────────┐
│                           STACK OVERVIEW                                  │
├──────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  FRONTEND                                                                 │
│  ├── Framework: Svelte SPA                                               │
│  ├── Routing: Client-side                                                │
│  ├── State: Module-local stores                                          │
│  ├── Communication: HTTP + WebSocket/SSE                                 │
│  └── Enforcement: ESLint, path alias                                     │
│                                                                          │
│  BACKEND                                                                  │
│  ├── PHP:    Symfony (src/AppCore, src/Modules/{Module})                │
│  ├── Python: FastAPI (app/appcore, app/modules/{module})                 │
│  ├── Go:     Gin (internal/appcore, internal/modules/{module})           │
│  ├── Kotlin: Ktor (modules/{module}/{api,application,domain,infra})      │
│  └── Zig:    (modules/{module}/{api,application,domain,infra}.zig)      │
│                                                                          │
│  SHARED                                                                    │
│  ├── Platform: IAM, MDM, Eventing, Config                               │
│  ├── Communication: REST/gRPC + Events                                   │
│  └── Data: Modular schemas + Event sourcing                              │
│                                                                          │
└──────────────────────────────────────────────────────────────────────────┘


================================================================================
END OF FULLSTACK ARCHITECTURE DOCUMENT
================================================================================
