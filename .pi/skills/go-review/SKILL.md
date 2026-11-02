---
name: go-review
description: Review Go microservices trong Super App
license: MIT
compatibility: opencode
metadata:
  audience: developers
  scope: backend
---
## Chức năng
- Review Go modules trong go/modules/{Module}/
- Áp dụng nguyên tắc Clean Architecture
- Kiểm tra error handling, concurrency patterns
- Review REST/gRPC endpoints, repository layer

## Cấu trúc module
```
modules/{Module}/
├── internal/models/models.go
├── internal/repository/repository.go
├── internal/service/service.go
└── cmd/main.go
```

## Quy tắc review
1. Đọc hết internal/models, repository, service
2. Check context usage, error propagation
3. Validate concurrency patterns (goroutines, channels)
4. Database connection handling, migrations
5. Unit tests coverage

## Khi nào dùng
Khi cần review Go payment, banking, wallet, realtime modules.

---
## Nguyên tắc bắt buộc từ guideline.md

### 1. Đọc toàn bộ code trước khi review
- Đọc hết mọi source code, mọi dòng code, mọi file trong module
- Bao gồm: models.go, repository.go, service.go, main.go
- Không được bỏ sót bất kỳ file nào, bất kỳ dòng code nào

### 2. Questioning mindset
- KHÔNG tin code hiện tại là đúng
- KHÔNG giả định kiến trúc hiện tại là hợp lý
- KHÔNG bảo vệ quyết định cũ
- CHỈ dựa trên phân tích kỹ thuật có thể kiểm chứng

### 3. Nhận diện vibe code trong Go
- Tên biến: data1, resultX, temp, handler123
- Logic trùng lặp, if/else vô nghĩa
- Hàm dài không cần thiết
- Copy-paste function/component
- Error bị ignore với _

### 4. Go-specific checks
- **Context usage**: Check context cancellation, timeout propagation
- **Error handling**: Review error wrapping, sentinel errors
- **Concurrency**: Review goroutines, channels, sync patterns
- **Interface pollution**: Unnecessary interfaces, interface segregation

### 5. Security checks bắt buộc
- **Input validation**: Request parsing, deserialization
- **Authentication**: JWT, session management
- **SQL injection**: Parameterized queries
- **Race conditions**: Concurrent access patterns

### 6. Performance checks
- Connection pool sizing
- Memory allocations in hot paths
- GC pressure minimization
- Channel blocking patterns

### 7. Refactor có kiểm soát
- Không rewrite toàn bộ nếu không cần
- Mỗi refactor phải có LÝ DO KỸ THUẬT
- Commit thường xuyên, incremental changes
- Backup code cũ trước khi xóa

### 8. Báo cáo cuối cùng
- Những phần đã được làm sạch
- Những phần vẫn còn rủi ro (đặc biệt concurrency)
- Những giả định kỹ thuật đang tồn tại
- Cấm tô hồng, nói chung chung, che giấu technical debt

---
## 7️⃣ Debugging & Issue Resolution (QUAN TRỌNG)

### Nguyên tắc bắt buộc:
- **KHÔNG XÓA CODE** - Đó là giải pháp cuối cùng, vi phạm nguyên tắc
- **Đọc toàn bộ file trước** - Không chỉ đoạn code suspected, đọc mọi dòng, imports, dependencies
- **Bình tĩnh phân tích** - Đọc lại toàn bộ file, mọi dòng code liên quan
- **Tìm root cause** - Check imports, context, goroutines, channels
- **Incremental debugging** - Add debug prints, isolate sections, test hypotheses từng bước
- **Systematic approach** - Read → Understand → Isolate → Test → Verify

### Go-specific debugging:
- Check context propagation and cancellation
- Verify goroutine leaks (unclosed channels?)
- Review error wrapping patterns
- Check for nil pointer dereferences

### Tại sao hiệu quả:
- Đọc toàn bộ file hiểu context đầy đủ: dependencies, structure, related logic
- Không miss root cause như goroutine leaks ở vị trí khác
- **Preserve code** - tìm real root cause thay vì xóa code để "pass"

### Nếu vẫn failed:
- Consult team, review git history, pair programming
- Disable feature tạm thời thay vì xóa code
- Luôn có plan restore code từ git nếu cần

### Cấm tuyệt đối:
- Xóa code để pass test
- "Vá áo" - fix tạm thời dẫn đến nhiều bug hơn
- Chấp nhận degradation
- Ignore error với _

### Đọc lại bắt buộc:
Nếu chưa hiểu rõ code, PHẢI ĐỌC LẠI cho đến khi nắm vững hoàn toàn mọi dòng code. Vi phạm sẽ dẫn đến lỗi nghiêm trọng, degradation hệ thống.

---
## 9️⃣ Frontend & UI/UX Review
Backend cũng cần hiểu FE impact:

- **API contracts**: Response format khớp với FE consumption?
- **Error handling**: Error responses có user-friendly và đầy đủ không?
- **Real-time updates**: WebSocket/SSE responses xử lý đúng?
- **gRPC**: Protobuf definitions sync với FE?

---
## 10️⃣ End-to-End Flow Review
Kiểm tra luồng từ User đến Backend:

- **API Endpoint**: Endpoint tồn tại? HTTP method đúng? Auth required?
- **Context propagation**: Context cancellation/timeout đúng?
- **Concurrency safety**: Race conditions trong concurrent requests?
- **Error propagation**: Error wrapping và propagation đúng?
- **Edge cases**: Context deadline, goroutine leaks, resource cleanup

---
## 11️⃣ Review & Validation Toàn diện
Thay vì automated tests:

- **Integration Review**: Mentally check API calls và service interactions
- **E2E Mental Walkthrough**: Tưởng tượng full user flows
- **Concurrency Review**: Goroutines, channels, Mutex patterns
- **Context Review**: Cancellation propagation, timeout handling
- **Security Review**: Input validation, auth flows, race conditions

---
## 12️⃣ Nâng cấp Liên tục & Evolution
Mỗi lần review, không chỉ fix mà phải:

- **Root cause analysis**: Tại sao bug xảy ra? Prevent recurrence
- **Concurrency optimization**: Better goroutine management, channel sizing
- **Error handling**: Better error wrapping, sentinel errors
- **Context usage**: Better cancellation propagation
- **API evolution**: Better endpoints, error handling, versioning

---
## 13️⃣ Evolution Metrics
Sau mỗi evolution round, đo lường:

**Code Quality:**
- Error handling patterns improved
- Context usage optimized
- Interface segregation

**Concurrency:**
- Goroutine leaks eliminated
- Channel blocking minimized
- Lock contention reduced

**Performance:**
- GC pressure minimized
- Memory allocations reduced
- Connection pool optimized

**Review Cycles:**
- Pre-evolution baseline
- Post-evolution improvement

---
## 🎯 Production Readiness Enforcer (BẮT BUỘC)

### Nguyên tắc vàng:
- **KHÔNG VIẾT TEST CODE** - Tưởng tượng và kiểm tra trong đầu
- **KHÔNG CHECK BẰNG TOOL** - Tưởng tượng mọi tình huống, mọi luồng
- **NẾU THIẾU THÌ VIẾT THÊM** - Không bỏ bớt, không skip
- **APP NGÀY CÀNG HOÀN THIỆN** - Mỗi lần review = thêm code, thêm tính năng

### 1️⃣ Mental Testing - Test trong tưởng tượng
Thay vì viết test code, tưởng tượng và verify tất cả:

**Function/Method Testing:**
- Input: Các loại dữ liệu đầu vào (valid, invalid, nil, empty, boundary)
- Output: Kết quả trả về đúng?
- Logic: Mọi nhánh if/switch được cover?
- Edge cases: Corner cases, boundary values, nil handling
- Error paths: Error handling, error wrapping

**Concurrency Testing:**
- Goroutines: Created và cleaned up đúng?
- Channels: Send/receive blocking đúng?
- Mutex: Lock/unlock balanced?
- Context: Cancellation propagation đúng?

**Service/API Testing:**
- HTTP handling: Request/response đúng?
- Error wrapping: Sentinel errors đúng?
- Middleware: Auth, logging, tracing đúng?
- Database: Connection, query, transaction đúng?

### 2️⃣ Mental Checking - Check trong tưởng tượng
Thay vì dùng linter/tool, tưởng tượng và verify:

**Code Quality:**
- Naming: Tên biến/hàm/struct rõ nghĩa?
- Structure: Code organization hợp lý?
- Duplication: Logic trùng lặp?
- Interface pollution: Unnecessary interfaces?

**Go-Specific:**
- Context usage: Cancellation, timeout
- Error handling: Wrapping, sentinel errors
- Concurrency: Goroutines, channels, sync
- Memory: Allocations, GC pressure

**Security:**
- Input validation: Request parsing
- Authentication: JWT, session management
- SQL injection: Parameterized queries
- Race conditions: Concurrent access

**Performance:**
- Connection pool sizing
- Memory allocations in hot paths
- GC pressure minimization
- Channel blocking patterns

### 3️⃣ Scenario Coverage - Mọi tình huống
Tưởng tượng và cover TẤT CẢ scenarios:

**User Scenarios:**
- Happy path: User làm đúng flow
- Error path: User nhập sai, click nhầm
- Edge cases: User làm điều không expected
- Recovery: User muốn undo, retry
- Parallel: User mở nhiều tabs, nhiều requests

**Business Scenarios:**
- Normal operation: Ngày thường
- Peak load: Giờ cao điểm
- Failure: Service down, network fail
- Recovery: Failover, data restoration
- Compliance: Audit trails, logging

**Technical Scenarios:**
- Network issues: Timeout, disconnect
- Database: Connection fail, deadlock
- External services: Third-party API down
- Data: Large data, migration, backup
- Go-specific: Goroutine leaks, context deadline, nil pointer

### 4️⃣ Flow Coverage - Mọi luồng (BẮT BUỘC)
Kiểm tra CẢ HAI CHIỀU, không được thiếu:

**🔽 Top-Down (UI → Backend → DB):**
```
User Click
  → Form Validation
    → HTTP Request
      → Middleware (Auth, Logging)
        → Handler
          → Service
            → Repository
              → Database
                → Response
                  → UI Update
                    → User Feedback
```

**🔼 Bottom-Up (DB → Backend → UI):**
```
Data Changed
  → Entity Update
    → Event Channel
      → Service Consumer
        → WebSocket/SSE
          → UI Real-time Update
            → User Notification
```

### 5️⃣ Missing Code = VIẾT THÊM
**QUAN TRỌNG NHẤT:**
- NẾU PHÁT HIỆN THIẾU → VIẾT THÊM CODE
- NẾU THẤY CHƯA HOÀN CHỈNH → IMPLEMENT TIẾP
- NẾU MISSING FEATURE → THÊM VÀO
- NẾU API CHƯA ĐỦ → EXPAND API

**KHÔNG ĐƯỢC:**
- ❌ Skip vì "không yêu cầu"
- ❌ Remove code vì "không cần"
- ❌ Simplify bằng cách bỏ tính năng
- ❌ Pass nhanh bằng cách giảm scope

### 6️⃣ Industry Standards - Chuẩn công nghiệp
Verify code đạt chuẩn production:

**API Design:**
- RESTful conventions
- gRPC options
- Versioning (v1, v2)
- Documentation (OpenAPI)
- Rate limiting, pagination, filtering

**Code Quality:**
- SOLID principles (Go style)
- Clean Architecture
- Error handling patterns
- Interface segregation

**Security:**
- OWASP guidelines
- Authentication (JWT, OAuth2)
- Input validation
- Race condition prevention

**Operations:**
- Monitoring (metrics, logs)
- Alerting (thresholds, escalation)
- Health checks (liveness, readiness)
- Graceful degradation

### 7️⃣ Production Readiness Checklist
Trước khi kết thúc review, verify:

- [ ] Tất cả functions có mental tests passed
- [ ] Context usage correct
- [ ] Concurrency patterns correct
- [ ] Error handling correct
- [ ] Tất cả flows (UI→DB và DB→UI) verified
- [ ] Tất cả edge cases được cover
- [ ] Tất cả error paths được handle
- [ ] Security vulnerabilities none
- [ ] Performance acceptable
- [ ] Code đạt chuẩn industry
- [ ] Documentation updated
- [ ] Missing code = VIẾT THÊM (không bỏ bớt)

### 8️⃣ Output Format
Khi kết thúc, báo cáo:

```
Production Readiness Report:
✅ Mental Tests: [số] cases passed
✅ Flow Coverage: UI→DB: [x], DB→UI: [y]  
✅ Scenarios: [số] scenarios covered
✅ Edge Cases: [số] cases handled
✅ Missing Code: [số] functions/components added
✅ Industry Standards: [x/y] criteria met
⚠️  Still Missing: [liệt kê nếu có]
📝 Recommendations: [nếu cần]
```

**Cấm:**
- ❌ Nói "không cần test" mà không mental test
- ❌ Bỏ sót flow direction (chỉ check 1 chiều)
- ❌ Skip edge case
- ❌ Remove code thay vì thêm code
- ❌ Nói "đã đủ" khi thực tế chưa đủ

---
## 14️⃣ Frontend Architecture (Atomic Design)

### Atomic Component Library:
- **Atoms**: Button, Input, Select, Checkbox, Radio, Label, Icon, Badge, Chip...
- **Molecules**: FormGroup, Card, Modal, Alert, Pagination, SearchBar, Dropdown...
- **Organisms**: Header, Sidebar, DataTable, FilterPanel, ActionPanel, InfoCard...
- **Templates**: PageLayout, AuthLayout, DashboardLayout, ModalLayout...
- **Pages**: Sử dụng Templates + Components (KHÔNG viết lại UI elements)

### Rules:
1. **Pages KHÔNG viết lại UI elements** - Dùng component library
2. **Components bậc cao có thể dùng tất cả bậc thấp**
3. **UI/UX đồng nhất** qua shared components
4. **Mỗi feature** = feature folder + shared components

### Frontend Structure:
```
frontend/
├── components/
│   ├── atoms/           # Basic UI (Button, Input, Icon...)
│   ├── molecules/       # Combinations (FormGroup, Card, Modal...)
│   └── organisms/       # Complex (Header, Sidebar, DataTable...)
├── features/            # Feature folders
│   ├── auth/
│   ├── dashboard/
│   ├── users/
│   └── ...
├── templates/           # Page layouts
└── pages/               # Page definitions (dùng components)
```

### Feature-Based Organization:
- Frontend organized by **features** (không phải layers)
- Mỗi feature sử dụng **component library** (không tự viết UI)
- Mỗi feature khớp với **backend module**
- 1 FE chung cho **multiple backend apps** (modular monoliths)

### Frontend-Backend Mapping:
- Frontend feature → Backend module
- API calls map từ feature → tương ứng backend module
- Multiple backend apps share same FE

### Validation:
- [ ] Pages sử dụng component library (không inline UI)
- [ ] UI elements đồng nhất qua components
- [ ] Components sử dụng đúng atomic hierarchy
- [ ] Features organized correctly
