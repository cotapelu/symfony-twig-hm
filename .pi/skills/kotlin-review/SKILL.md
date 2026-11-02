---
name: kotlin-review
description: Review Kotlin booking và travel modules
license: MIT
compatibility: opencode
metadata:
  audience: developers
  scope: backend-mobile
---
## Chức năng
- Review Kotlin modules trong kotlin/modules/{Module}/
- Áp dụng Clean Architecture, Repository pattern
- Review booking flows, API Gateway integration
- Check coroutines, suspend functions usage

## Cấu trúc module
```
modules/{Module}/src/main/kotlin/com/superapp/
├── models/{Module}Models.kt
├── repository/{Module}Repository.kt
├── service/{Module}Service.kt
└── controller/{Module}Controller.kt
```

## Quy tắc review
1. Validate data classes, sealed classes
2. Check null safety, type inference
3. Review coroutine scope, async operations
4. API endpoint conventions
5. Database migrations (PostgreSQL/MySQL)

## Khi nào dùng
Khi cần review booking, travel, hotel, restaurant modules.

---
## Nguyên tắc bắt buộc từ guideline.md

### 1. Đọc toàn bộ code trước khi review
- Đọc hết mọi source code, mọi dòng code, mọi file trong module
- Bao gồm: Models.kt, Repository.kt, Service.kt, Controller.kt
- Không được bỏ sót bất kỳ file nào, bất kỳ dòng code nào

### 2. Questioning mindset
- KHÔNG tin code hiện tại là đúng
- KHÔNG giả định kiến trúc hiện tại là hợp lý
- KHÔNG bảo vệ quyết định cũ
- CHỈ dựa trên phân tích kỹ thuật có thể kiểm chứng

### 3. Nhận diện vibe code trong Kotlin
- Tên biến: data1, resultX, temp, handler123
- Logic trùng lặp, if/else vô nghĩa
- Hàm dài không cần thiết
- Copy-paste function/component
- Null safety bị bypass với !! hoặc ?:

### 4. Kotlin-specific checks
- **Data classes**: Validate proper data class usage, copy patterns
- **Null safety**: Review !! usage, lateinit, optional types
- **Coroutines**: Review coroutine scope, async operations, job management
- **Sealed classes**: Proper state management patterns

### 5. Security checks bắt buộc
- **Input validation**: Request parsing, deserialization
- **Authentication**: JWT, session management
- **SQL injection**: Parameterized queries
- **API security**: Rate limiting, input sanitization

### 6. Performance checks
- Database query optimization (N+1)
- Coroutine cancellation handling
- Memory leaks trong Android/background operations
- Collection operations in hot paths

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
- **Tìm root cause** - Check coroutines, null safety, sealed classes
- **Incremental debugging** - Add debug prints, isolate sections, test hypotheses từng bước
- **Systematic approach** - Read → Understand → Isolate → Test → Verify

### Kotlin-specific debugging:
- Check coroutine job cancellation
- Review null safety patterns (!! usage)
- Verify sealed class state handling
- Check collection operations

### Tại sao hiệu quả:
- Đọc toàn bộ file hiểu context đầy đủ: dependencies, structure, related logic
- Không miss root cause như coroutine leaks ở vị trí khác
- **Preserve code** - tìm real root cause thay vì xóa code để "pass"

### Nếu vẫn failed:
- Consult team, review git history, pair programming
- Disable feature tạm thời thay vì xóa code
- Luôn có plan restore code từ git nếu cần

### Cấm tuyệt đối:
- Xóa code để pass test
- "Vá áo" - fix tạm thời dẫn đến nhiều bug hơn
- Chấp nhận degradation
- Sử dụng !! thay vì fix null safety đúng cách

### Đọc lại bắt buộc:
Nếu chưa hiểu rõ code, PHẢI ĐỌC LẠI cho đến khi nắm vững hoàn toàn mọi dòng code. Vi phạm sẽ dẫn đến lỗi nghiêm trọng, degradation hệ thống.

---
## 9️⃣ Frontend & UI/UX Review
Backend cũng cần hiểu FE impact (mobile/web):

- **API contracts**: Response format khớp với mobile consumption?
- **Error handling**: Error responses có user-friendly và đầy đủ không?
- **State management**: State flows đúng cho mobile patterns?
- **Serialization**: Kotlinx.serialization đúng cho FE?

---
## 10️⃣ End-to-End Flow Review
Kiểm tra luồng từ User đến Backend:

- **API Endpoint**: Endpoint tồn tại? HTTP method đúng? Auth required?
- **Coroutines**: Cancellation, timeout hoạt động đúng?
- **Error propagation**: Exception handling through coroutines đúng?
- **Database**: N+1 issues, transaction management đúng?
- **Edge cases**: Coroutine leaks, concurrent access, resource cleanup

---
## 11️⃣ Review & Validation Toàn diện
Thay vì automated tests:

- **Integration Review**: Mentally check API calls và coroutine flows
- **E2E Mental Walkthrough**: Tưởng tượng full booking/travel flows
- **Coroutine Review**: Scope management, job cancellation, exception handling
- **Database Review**: Query optimization, transaction management
- **Security Review**: Input validation, auth flows, injection prevention

---
## 12️⃣ Nâng cấp Liên tục & Evolution
Mỗi lần review, không chỉ fix mà phải:

- **Root cause analysis**: Tại sao bug xảy ra? Prevent recurrence
- **Coroutine optimization**: Better scope management, job handling
- **Data flow**: Improve data class usage, sealed class patterns
- **Error handling**: Better exception patterns in coroutines
- **API evolution**: Better endpoints for booking flows

---
## 13️⃣ Evolution Metrics
Sau mỗi evolution round, đo lường:

**Code Quality:**
- Null safety improved
- Coroutine patterns optimized
- Data class usage improved

**Performance:**
- Database query N+1 eliminated
- Coroutine cancellation proper
- Memory leaks in Android fixed

**Security:**
- Input validation improved
- Auth flows verified

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
- Input: Các loại dữ liệu đầu vào (valid, invalid, null, empty, boundary)
- Output: Kết quả trả về đúng?
- Logic: Mọi nhánh if/when được cover?
- Edge cases: Corner cases, boundary values, null handling
- Error paths: Exception handling, error messages

**Coroutine/Async Testing:**
- Coroutine scope: Job management đúng?
- Cancellation: propagate đúng?
- Exception handling: CoroutineExceptionHandler?
- Concurrency: Race conditions?

**Data/Repository Testing:**
- Query execution: SQL đúng?
- Transaction: Commit/rollback đúng?
- Entity mapping: DTO ↔ Entity conversion đúng?
- Pagination: Page calculation đúng?

### 2️⃣ Mental Checking - Check trong tưởng tượng
Thay vì dùng linter/tool, tưởng tượng và verify:

**Code Quality:**
- Naming: Tên biến/hàm/class rõ nghĩa?
- Structure: Code organization hợp lý?
- Duplication: Logic trùng lặp?
- Null safety: !! usage minimal?

**Kotlin-Specific:**
- Data classes: Proper usage, copy patterns
- Null safety: Review !! usage
- Coroutines: Scope, async operations
- Sealed classes: State management

**Security:**
- Input validation: Request parsing
- Authentication: JWT, session management
- SQL injection: Parameterized queries
- API security: Rate limiting

**Performance:**
- Database query optimization (N+1)
- Coroutine cancellation handling
- Memory leaks trong background
- Collection operations

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
- Kotlin-specific: Coroutine leaks, null pointer, sealed state

### 4️⃣ Flow Coverage - Mọi luồng (BẮT BUỘC)
Kiểm tra CẢ HAI CHIỀU, không được thiếu:

**🔽 Top-Down (UI → Backend → DB):**
```
User Click
  → Form Validation
    → API Request
      → Controller
        → Service (Coroutine)
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
    → Event Emission
      → Flow/Channel
        → Service Consumer
          → WebSocket/StateFlow
            → UI Reactive Update
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
- Versioning (v1, v2)
- Documentation (OpenAPI)
- Rate limiting, pagination, filtering

**Code Quality:**
- SOLID principles
- Clean Architecture
- Null safety patterns
- Coroutine patterns

**Security:**
- OWASP guidelines
- Authentication (JWT, OAuth2)
- Input validation
- SQL injection prevention

**Operations:**
- Monitoring (metrics, logs)
- Alerting (thresholds, escalation)
- Health checks
- Graceful degradation

### 7️⃣ Production Readiness Checklist
Trước khi kết thúc review, verify:

- [ ] Tất cả functions có mental tests passed
- [ ] Null safety correct
- [ ] Coroutine patterns correct
- [ ] Sealed class state management correct
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
