---
name: symfony-review
description: Review PHP Symfony modules theo Super App guideline
license: MIT
compatibility: opencode
metadata:
  audience: developers
  scope: backend
---
## Chức năng
- Review PHP Symfony code trong php-symfony/modules/{Module}/
- Áp dụng 14 nguyên tắc từ guideline.md
- Phát hiện vibe code, logic thừa, security issues
- Kiểm tra REST API standards, Entity/Repository/Service/Controller pattern

## Quy tắc review
1. Đọc hết mọi file trong module
2. Check Single Responsibility, Dependency Inversion
3. Validate tên theo convention: Entity, Repository, Service, Controller
4. Security check: SQL injection, hardcode secrets
5. Performance: N+1 queries, loop thừa

## Cấu trúc module
```
modules/{Module}/
├── Entity/{Entity}.php
├── Repository/{Entity}Repository.php
├── Service/{Module}Service.php
└── Controller/{Module}Controller.php
```

## Khi nào dùng
Khi cần review hoặc refactor bất kỳ Symfony module nào trong project.

---
## Nguyên tắc bắt buộc từ guideline.md

### 1. Đọc toàn bộ code trước khi review
- Đọc hết mọi source code, mọi dòng code, mọi file trong module
- Đọc cả file config, HTML templates, JS, CSS nếu có
- Không được bỏ sót bất kỳ file nào, bất kỳ dòng code nào

### 2. Questioning mindset
- KHÔNG tin code hiện tại là đúng
- KHÔNG giả định kiến trúc hiện tại là hợp lý
- KHÔNG bảo vệ quyết định cũ
- CHỈ dựa trên phân tích kỹ thuật có thể kiểm chứng

### 3. Nhận diện vibe code trong PHP
- Tên biến: $data1, $resultX, $temp, $handler123
- Logic trùng lặp, if/else vô nghĩa
- Hàm dài không cần thiết
- Copy-paste function/component
- Repository/Service không tách biệt rõ ràng

### 4. Kiểm tra kiến trúc
- Single Responsibility: Entity chỉ chứa data, Repository chỉ query, Service chỉ business logic
- Dependency Inversion: Controller phụ thuộc interface, không phụ thuộc concrete class
- Separation of Concerns: Tách biệt domain, application, infrastructure

### 5. Security checks bắt buộc
- Hardcode secrets, API keys, passwords?
- SQL Injection qua QueryBuilder/DQL?
- Unsafe deserialization?
- CSRF protection cho POST/PUT/DELETE?

### 6. Performance checks bắt buộc
- N+1 queries trong Repository?
- Loop thừa trong Service?
- Thiếu indexes/entity relationships?
- Fetch join chưa được sử dụng?

### 7. Refactor có kiểm soát
- Không rewrite toàn bộ nếu không cần
- Mỗi refactor phải có LÝ DO KỸ THUẬT
- Mỗi thay đổi phải: dễ đọc hơn, dễ test hơn, ít rủi ro hơn
- Commit thường xuyên, incremental changes

### 8. Validation sau mỗi thay đổi
- Chạy tests ngay sau mỗi thay đổi
- Nếu break, revert và thử cách khác
- Backup code cũ trước khi xóa

### 9. Báo cáo cuối cùng
- Những phần đã được làm sạch
- Những phần vẫn còn rủi ro
- Những giả định kỹ thuật đang tồn tại
- Cấm tô hồng, nói chung chung, che giấu technical debt

---
## 7️⃣ Debugging & Issue Resolution (QUAN TRỌNG)

### Nguyên tắc bắt buộc:
- **KHÔNG XÓA CODE** - Đó là giải pháp cuối cùng, vi phạm nguyên tắc
- **Đọc toàn bộ file trước** - Không chỉ đoạn code suspected, đọc mọi dòng, imports, dependencies
- **Bình tĩnh phân tích** - Đọc lại toàn bộ file, mọi dòng code liên quan
- **Tìm root cause** - Check braces, imports, async/sync, lifetimes
- **Incremental debugging** - Add debug prints, isolate sections, test hypotheses từng bước
- **Systematic approach** - Read → Understand → Isolate → Test → Verify

### Tại sao hiệu quả:
- Đọc toàn bộ file hiểu context đầy đủ: dependencies, structure, related logic
- Không miss root cause như unmatched braces ở vị trí khác
- **Preserve code** - tìm real root cause thay vì xóa code để "pass"

### Nếu vẫn failed:
- Consult team, review git history, pair programming
- Disable feature tạm thời thay vì xóa code
- Luôn có plan restore code từ git nếu cần

### Cấm tuyệt đối:
- Xóa code để pass test
- "Vá áo" - fix tạm thời dẫn đến nhiều bug hơn
- Chấp nhận degradation

### Đọc lại bắt buộc:
Nếu chưa hiểu rõ code, PHẢI ĐỌC LẠI cho đến khi nắm vững hoàn toàn mọi dòng code. Vi phạm sẽ dẫn đến lỗi nghiêm trọng, degradation hệ thống.

---
## 9️⃣ Frontend & UI/UX Review
Backend cũng cần hiểu FE impact:

- **API contracts**: Response format khớp với FE consumption?
- **Error handling**: Error responses có user-friendly và đầy đủ không?
- **Loading states**: API có trả về proper response cho loading indicators?
- **Validation**: Backend validation có sync với frontend validation?

---
## 10️⃣ End-to-End Flow Review
Kiểm tra luồng từ User đến Backend:

- **API Endpoint**: Endpoint tồn tại? HTTP method đúng? Auth required?
- **Request/Response**: Data binding đúng? Null handling?
- **Error propagation**: Error từ DB → Service → Controller → Client đúng?
- **Data consistency**: Race conditions, concurrent edits?
- **Edge cases**: Network timeout, server errors, validation failures

---
## 11️⃣ Review & Validation Toàn diện
Thay vì automated tests:

- **Integration Review**: Mentally check API calls và service interactions
- **E2E Mental Walkthrough**: Tưởng tượng full user flows
- **Performance Review**: Mentally assess query performance, memory usage
- **Security Review**: Mentally check vulnerabilities, input validation
- **Regression Check**: Review lại code cũ khi sửa, đảm bảo không break logic

---
## 12️⃣ Nâng cấp Liên tục & Evolution
Mỗi lần review, không chỉ fix mà phải:

- **Root cause analysis**: Tại sao bug xảy ra? Prevent recurrence
- **Code quality**: Reduce complexity, improve readability, eliminate duplicates
- **Architecture evolution**: Refactor toward better patterns
- **Technical debt**: Fix "vá áo" issues trước khi loang ra
- **Feature enhancement**: Thêm missing features nếu phát hiện
- **API evolution**: Better endpoints, error handling, versioning

---
## 13️⃣ Evolution Metrics
Sau mỗi evolution round, đo lường:

**Code Quality:**
- Complexity reduction (lines, cyclomatic complexity)
- % duplicate code removed
- Technical debt count

**Security:**
- % security issues fixed
- Compliance status (GDPR/SOC2)

**Performance:**
- Database query performance improvements
- API response time improvements

**Review Cycles:**
- Pre-evolution baseline metrics
- Post-evolution improvement quantification

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
- Logic: Mọi nhánh if/else/switch được cover?
- Edge cases: Corner cases, boundary values, null handling
- Error paths: Exception handling, error messages

**Service Layer Testing:**
- Business logic: Mọi rule được validate?
- Dependencies: Mocked services hoạt động đúng?
- Transactions: Commit/rollback đúng?
- Concurrency: Race conditions?

**Controller/API Testing:**
- HTTP methods: GET/POST/PUT/DELETE đúng?
- Status codes: 200/400/401/403/404/500 đúng?
- Request validation: Input format, required fields
- Response format: JSON structure, pagination, sorting

**Repository/Database Testing:**
- Query correctness: SQL/DQL đúng?
- Relationships: Joins, eager/lazy loading
- Performance: Query optimization, indexes
- Transactions: Data consistency

### 2️⃣ Mental Checking - Check trong tưởng tượng
Thay vì dùng linter/tool, tưởng tượng và verify:

**Code Quality:**
- Naming: Tên biến/hàm/class rõ nghĩa?
- Structure: Code organization hợp lý?
- Duplication: Logic trùng lặp?
- Complexity: Hàm quá dài/phức tạp?

**Security:**
- Authentication: Ai có quyền gọi API này?
- Authorization: RBAC/permissions đúng?
- Input validation: SQL injection, XSS, CSRF?
- Secrets: Hardcoded keys/passwords?

**Performance:**
- N+1 queries: Có vấn đề gì?
- Caching: Cache strategy hợp lý?
- Memory: Leaks, unnecessary allocations?
- Latency: Response time chấp nhận được?

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

### 4️⃣ Flow Coverage - Mọi luồng (BẮT BUỘC)
Kiểm tra CẢ HAI CHIỀU, không được thiếu:

**🔽 Top-Down (UI → Backend → DB):**
```
User Click
  → Form Validation
    → API Request
      → Controller
        → Service
          → Repository
            → Database
              → Response
                → UI Update
                  → User Feedback
```

Từng bước verify:
- User click được? Button exist?
- Form validate đúng? Required fields?
- API gọi được? Endpoint đúng?
- Controller nhận request? Parameters đúng?
- Service xử lý? Business logic đúng?
- Repository query? SQL đúng?
- Database trả về? Data đúng?
- Response format? JSON structure đúng?
- UI update? State change đúng?
- User thấy feedback? Loading/error/success?

**🔼 Bottom-Up (DB → Backend → UI):**
```
Data Changed (DB)
  → Entity Update
    → Repository event
      → Service notification
        → WebSocket/Polling
          → UI real-time update
            → User notification
```

Từng bước verify:
- Data change được detect?
- Entity map đúng?
- Repository emit event?
- Service nhận notification?
- Real-time channel hoạt động?
- UI re-render đúng?
- User nhận notification?

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

**Ví dụ:**
- API thiếu error response → Thêm Error DTO
- UI thiếu loading state → Thêm loading component
- Validation thiếu case → Thêm validation rule
- Repository thiếu method → Thêm method
- Service thiếu business rule → Thêm logic

### 6️⃣ Industry Standards - Chuẩn công nghiệp
Verify code đạt chuẩn production:

**API Design:**
- RESTful conventions (URL, HTTP methods, status codes)
- Versioning (v1, v2)
- Documentation (OpenAPI/Swagger)
- Rate limiting, pagination, filtering

**Code Quality:**
- SOLID principles
- Clean Architecture
- Design patterns phù hợp
- Error handling patterns

**Security:**
- OWASP guidelines
- Authentication (JWT, OAuth2)
- Encryption (TLS, at-rest)
- Audit logging

**Operations:**
- Monitoring (metrics, logs)
- Alerting (thresholds, escalation)
- Health checks
- Graceful degradation

**Documentation:**
- API docs updated
- README complete
- Comments meaningful
- Architecture diagrams

### 7️⃣ Production Readiness Checklist
Trước khi kết thúc review, verify:

- [ ] Tất cả functions có mental tests passed
- [ ] Tất cả APIs có contract verified
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
