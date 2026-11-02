---
name: vibe-cleaner
description: Phát hiện và làm sạch AI-generated "vibe code"
license: MIT
compatibility: opencode
metadata:
  audience: senior-developers
  scope: quality-assurance
---
## Chức năng
- Nhận diện code được AI sinh nhanh (vibe code)
- Phát hiện anti-patterns, logic thừa, coupling ẩn
- Clean up: dead code, redundant checks, poor naming
- Áp dụng 14 nguyên tắc từ guideline.md

## Dấu hiệu vibe code
- Tên biến: data1, resultX, temp, handler123
- Logic trùng lặp, if/else vô nghĩa
- Hàm dài không cần thiết
- Comment nhiều nhưng không giải thích được
- Copy-paste component/function

## Quy tắc clean
1. **Không tin code là đúng** - questioning mindset
2. **Phân tích domain** - xác định boundary rõ ràng
3. **Mỗi hàm = 1 ý định** - Single Responsibility
4. **Refactor incremental** - commit từng phần nhỏ
5. **Validate sau mỗi thay đổi**

## Khi nào dùng
Khi codebase có dấu hiệu rapid prototyping, demo-oriented code, hoặc cần technical debt cleanup.

---
## Nguyên tắc bắt buộc từ guideline.md

### 1. Đọc toàn bộ code trước khi clean
- Đọc hết mọi source code, mọi dòng code, mọi file
- Đọc cả file config, HTML templates, JS, CSS
- Không được bỏ sót bất kỳ file nào, bất kỳ dòng code nào

### 2. Questioning mindset (QUAN TRỌNG NHẤT)
- KHÔNG tin code hiện tại là đúng
- KHÔNG giả định kiến trúc hiện tại là hợp lý
- KHÔNG bảo vệ quyết định cũ
- CHỈ dựa trên phân tích kỹ thuật có thể kiểm chứng

### 3. Nhận diện VIBE CODE - Dấu hiệu chính
- **Tên biến mơ hồ**: data1, resultX, temp, handler123, item, obj
- **Logic trùng lặp**: Cùng logic lặp lại ở nhiều nơi
- **If/else vô nghĩa**: Check phòng thủ dư thừa, điều kiện luôn true/false
- **Hàm quá dài**: Một hàm làm quá nhiều thứ
- **Comment nhiều nhưng không giải thích được**: Code phải tự document
- **Copy-paste component/function**: Dấu hiệu của AI sinh nhanh

### 4. Phân tích kiến trúc
- Xác định domain thực sự của bài toán
- Xác định boundary: domain vs application vs infrastructure
- Chỉ ra vi phạm:
  - Single Responsibility
  - Dependency Inversion
  - Separation of Concerns

### 5. Làm sạch logic từng function
Với mỗi function, trả lời:
- Input là gì?
- Output là gì?
- Invariant là gì?
- Edge case nào bị bỏ qua?

Xóa:
- Logic trùng
- If/else vô nghĩa
- Check phòng thủ dư thừa
- Hàm chỉ tồn tại vì AI sinh ra

### 6. Chuẩn hóa naming
Đổi tên:
- Biến, hàm, class có tên mơ hồ
- Tên kiểu AI: data1, resultX, temp, handler123

Yêu cầu:
- Tên phản ánh Ý NGHĨA, không phản ánh CÁCH VIẾT
- Một hàm = một ý định

### 7. Kiểm soát rủi ro AI sinh
- **Security**: Hardcode secrets? SQL Injection? Unsafe deserialization?
- **Performance**: Loop thừa? Query N+1?
- **Correctness**: Race condition? State không đồng bộ?

Nếu không thể xác minh: PHẢI ghi rõ: [Chưa xác minh]

### 8. Refactor có kiểm soát (AN TOÀN)
- **Version Control**: Commit thường xuyên trên main branch trước khi sửa lớn
- **Incremental Changes**: Sửa từng phần nhỏ, commit ngay. Không sửa "ào ào"
- **Backup Strategy**: Không xóa code cũ vội. Stash hoặc commit backup trước
- **Risk Assessment**: Đánh giá rủi ro trước khi sửa
- **Testing After Changes**: Chạy tests ngay sau mỗi thay đổi
- **Documentation**: Ghi lại thay đổi trong commit message

### 9. Trình bày refactor
- Trước refactor: vấn đề gì
- Sau refactor: giải quyết ra sao
- Safety Check: Đã test? Có backup? Có thể revert?

### 10. Kết luận cuối
Báo cáo rõ:
- Những phần đã được làm sạch
- Những phần vẫn còn rủi ro
- Những giả định kỹ thuật đang tồn tại
- Những điểm cần xác nhận thêm từ người viết code gốc

**CẤM:**
- Cấm tô hồng (phóng đại thành tựu)
- Cấm nói chung chung (phải cụ thể, có ví dụ)
- Cấm che giấu technical debt (phải báo cáo rõ)

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
## 11️⃣ Frontend & UI/UX Review
Clean cả FE impact:

- **Component quality**: Copy-paste components? Hooks dư thừa?
- **State management**: Context vs Redux, props drilling?
- **Data flow**: API response khớp với component consumption?
- **Naming & Structure**: Tên component, prop, state rõ nghĩa?

---
## 12️⃣ End-to-End Flow Review
Kiểm tra luồng hoàn chỉnh:

- **User Action**: Click → Form submit → API call → Response → UI update
- **Error propagation**: Error handling đầy đủ ở mọi layer?
- **Race conditions**: Concurrent requests xử lý đúng?
- **Loading states**: User feedback tốt ở mọi trạng thái?

---
## 13️⃣ Review & Validation Toàn diện
Thay vì automated tests:

- **Integration Review**: Mentally check API calls và component interactions
- **E2E Mental Walkthrough**: Tưởng tượng full user flows
- **Performance Review**: Mentally assess bundle size, load times
- **Accessibility Review**: ARIA, keyboard nav, screen readers
- **Security Review**: XSS, data exposure, injection vulnerabilities

---
## 14️⃣ Evolution Metrics
Sau mỗi clean round, đo lường:

**Code Quality:**
- Lines of code removed
- % duplicate code eliminated
- Complexity reduction

**Feature Completion:**
- % features implemented vs planned
- Missing gaps identified

**UX Improvements:**
- Accessibility score
- Error states coverage
- User feedback quality

**Security Hardening:**
- Vulnerabilities addressed
- Attack surface reduction

**Process:**
- Time efficiency (hours per round)
- Maintenance ease improvement

---
## 🎯 Production Readiness Enforcer (BẮT BUỘC)

### Nguyên tắc vàng:
- **KHÔNG VIẾT TEST CODE** - Tưởng tượng và kiểm tra trong đầu
- **KHÔNG CHECK BẰNG TOOL** - Tưởng tượng mọi tình huống, mọi luồng
- **NẾU THIẾU THÌ VIẾT THÊM** - Không bỏ bớt, không skip
- **APP NGÀY CÀNG HOÀN THIỆN** - Mỗi lần review = thêm code, thêm tính năng

### 1️⃣ Mental Testing - Test trong tưởng tượng
Thay vì viết test code, tưởng tượng và verify tất cả:

**Component/Function Testing:**
- Input: Các loại dữ liệu đầu vào (valid, invalid, null, empty, boundary)
- Output: Kết quả trả về đúng?
- Logic: Mọi nhánh if/else/switch được cover?
- Edge cases: Corner cases, boundary values, null handling
- Error paths: Exception handling, error messages

**Service/Logic Testing:**
- Business logic: Mọi rule được validate?
- Dependencies: Mocked services hoạt động đúng?
- State management: State transitions đúng?
- Concurrency: Race conditions?

**API/Data Testing:**
- HTTP methods: GET/POST/PUT/DELETE đúng?
- Status codes: 200/400/401/403/404/500 đúng?
- Data flow: Input → Process → Output đúng?
- Persistence: Data saved/retrieved correctly?

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
User Action
  → UI Component
    → State Change
      → API Call
        → Backend Processing
          → Database
            → Response
              → UI Update
                → User Feedback
```

**🔼 Bottom-Up (DB → Backend → UI):**
```
Data Changed
  → Event Emission
    → State Update
      → Real-time Sync
        → UI Re-render
          → User Notification
```

Từng bước verify cả hai chiều.

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
