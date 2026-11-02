---
name: rust-review
description: Review Rust security và crypto modules
license: MIT
compatibility: opencode
metadata:
  audience: developers
  scope: security
---
## Chức năng
- Review Rust modules trong rust/modules/{Module}/
- Review security-critical code (Auth, Encryption, KeyManagement)
- Áp dụng Rust best practices, memory safety
- Check borrow checker, lifetimes, error handling

## Cấu trúc module
```
modules/{Module}/src/
├── models.rs
├── repositories.rs
├── service.rs
└── lib.rs
```

## Quy tắc review
1. Validate ownership, borrowing patterns
2. Check unsafe blocks usage
3. Review cryptographic implementations
4. Validate error handling, Result/Option types
5. Security audit: key management, encryption

## Khi nào dùng
Khi cần review AuthService, Encryption, KeyManagement, CertificateAuthority.

---
## Nguyên tắc bắt buộc từ guideline.md

### 1. Đọc toàn bộ code trước khi review
- Đọc hết mọi source code, mọi dòng code, mọi file trong module
- Bao gồm: models.rs, repositories.rs, service.rs, lib.rs
- Không được bỏ sót bất kỳ file nào, bất kỳ dòng code nào

### 2. Questioning mindset
- KHÔNG tin code hiện tại là đúng
- KHÔNG giả định kiến trúc hiện tại là hợp lý
- KHÔNG bảo vệ quyết định cũ
- CHỈ dựa trên phân tích kỹ thuật có thể kiểm chứng

### 3. Nhận diện vibe code trong Rust
- Tên biến: data1, resultX, temp, handler123
- Logic trùng lặp, if/else vô nghĩa
- Hàm dài không cần thiết
- Copy-paste function/component
- Error handling qua unwrap() mọi nơi

### 4. Rust-specific checks
- **Ownership/Borrowing**: Validate ownership patterns, lifetime annotations
- **Unsafe blocks**: Check unsafe block usage, justify necessity
- **Error handling**: Review Result/Option types, proper error propagation
- **Concurrency**: Review Mutex/RwLock, channel usage, sync traits

### 5. Security checks bắt buộc (quan trọng)
- **Cryptographic implementations**: Review encryption/decryption logic
- **Key management**: Hardcode keys? Key storage patterns?
- **Authentication flows**: Token handling, session management
- **Input validation**: Deserialization, parsing, type coercion

### 6. Performance checks
- Clone patterns không cần thiết
- Unnecessary allocations
- Lock contention trong concurrent code
- Iterator vs loop performance

### 7. Refactor có kiểm soát
- Không rewrite toàn bộ nếu không cần
- Mỗi refactor phải có LÝ DO KỸ THUẬT
- Commit thường xuyên, incremental changes
- Backup code cũ trước khi xóa

### 8. Báo cáo cuối cùng
- Những phần đã được làm sạch
- Những phần vẫn còn rủi ro (đặc biệt security)
- Những giả định kỹ thuật đang tồn tại
- Cấm tô hồng, nói chung chung, che giấu technical debt

---
## 7️⃣ Debugging & Issue Resolution (QUAN TRỌNG)

### Nguyên tắc bắt buộc:
- **KHÔNG XÓA CODE** - Đó là giải pháp cuối cùng, vi phạm nguyên tắc
- **Đọc toàn bộ file trước** - Không chỉ đoạn code suspected, đọc mọi dòng, imports, dependencies
- **Bình tĩnh phân tích** - Đọc lại toàn bộ file, mọi dòng code liên quan
- **Tìm root cause** - Check braces, imports, async/sync, lifetimes, borrow checker
- **Incremental debugging** - Add debug prints, isolate sections, test hypotheses từng bước
- **Systematic approach** - Read → Understand → Isolate → Test → Verify

### Rust-specific debugging:
- Count braces manually - fix syntax errors nhanh hơn xóa code
- Check ownership/borrowing conflicts
- Review lifetime annotations
- Validate Result/Option handling

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
- Bỏ qua borrow checker errors

### Đọc lại bắt buộc:
Nếu chưa hiểu rõ code, PHẢI ĐỌC LẠI cho đến khi nắm vững hoàn toàn mọi dòng code. Vi phạm sẽ dẫn đến lỗi nghiêm trọng, degradation hệ thống.

---
## 9️⃣ Frontend & UI/UX Review
Backend cũng cần hiểu FE impact:

- **API contracts**: Response format khớp với FE consumption?
- **Error handling**: Error responses có user-friendly và đầy đủ không?
- **Serialization**: Rust structs serialize đúng sang JSON cho FE?
- **Performance**: API response time ảnh hưởng UX?

---
## 10️⃣ End-to-End Flow Review
Kiểm tra luồng từ User đến Backend:

- **API Endpoint**: Endpoint tồn tại? HTTP method đúng? Auth required?
- **Serialization**: JSON deserialize/serialize đúng? Null handling?
- **Error propagation**: Error từ DB → Service → Client đúng?
- **Concurrency safety**: Race conditions trong concurrent requests?
- **Edge cases**: Network timeout, panic recovery, resource cleanup

---
## 11️⃣ Review & Validation Toàn diện
Thay vì automated tests:

- **Integration Review**: Mentally check API calls và service interactions
- **E2E Mental Walkthrough**: Tưởng tượng full user flows
- **Memory Safety Review**: Mentally check ownership, borrowing, lifetimes
- **Concurrency Review**: Mentally check Mutex, channels, sync patterns
- **Security Review**: Mentally check crypto implementations, key management

---
## 12️⃣ Nâng cấp Liên tục & Evolution
Mỗi lần review, không chỉ fix mà phải:

- **Root cause analysis**: Tại sao bug xảy ra? Prevent recurrence
- **Memory safety**: Improve ownership patterns, reduce clones
- **Concurrency optimization**: Better lock granularity, channel usage
- **Error handling**: Better error types, propagation patterns
- **API evolution**: Better endpoints, error handling, versioning

---
## 13️⃣ Evolution Metrics
Sau mỗi evolution round, đo lường:

**Code Quality:**
- Clone patterns eliminated
- Unsafe blocks justified/reduced
- Lifetime annotations added

**Security:**
- Vulnerabilities addressed (%)
- Crypto implementations verified

**Performance:**
- Memory allocations reduced
- Lock contention minimized

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
- Logic: Mọi nhánh match/if được cover?
- Edge cases: Corner cases, overflow, lifetime issues
- Error paths: Result/Option handling, panic recovery

**Ownership/Concurrency Testing:**
- Ownership: Borrowing rules đúng?
- Lifetimes: All references valid?
- Concurrency: Race conditions?
- Sync: Mutex/RwLock usage đúng?

**Service/API Testing:**
- HTTP handling: Request/response đúng?
- Serialization: JSON serialize/deserialize đúng?
- Error propagation: Errors handle đúng?
- Auth: Token validation đúng?

### 2️⃣ Mental Checking - Check trong tưởng tượng
Thay vì dùng linter/tool, tưởng tượng và verify:

**Code Quality:**
- Naming: Tên biến/hàm/struct rõ nghĩa?
- Structure: Code organization hợp lý?
- Duplication: Logic trùng lặp?
- Complexity: Hàm quá dài/phức tạp?

**Rust-Specific:**
- Unsafe blocks: Justified?
- Clone patterns: Necessary?
- Lifetime annotations: Correct?
- Error handling: Result/Option patterns?

**Security:**
- Cryptography: Implementation đúng?
- Key management: Secrets safe?
- Input validation: Deserialization safe?
- Authentication: Token handling đúng?

**Performance:**
- Allocations: Unnecessary?
- Clones: Can be references?
- Locking: Contention issues?
- Compilation: Release mode?

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
- Rust-specific: Borrow checker failures, lifetime issues

### 4️⃣ Flow Coverage - Mọi luồng (BẮT BUỘC)
Kiểm tra CẢ HAI CHIỀU, không được thiếu:

**🔽 Top-Down (UI → Backend → DB):**
```
User Click
  → Form Validation
    → API Request
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
    → Event/Channel
      → Service Notification
        → WebSocket/Polling
          → UI Update
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
- RESTful conventions (URL, HTTP methods, status codes)
- Versioning (v1, v2)
- Documentation (OpenAPI/Swagger)
- Rate limiting, pagination, filtering

**Code Quality:**
- SOLID principles (Rust equivalent)
- Clean Architecture
- Design patterns phù hợp
- Error handling patterns (Result/Option)

**Security:**
- OWASP guidelines
- Cryptography best practices
- Key management
- Authentication (JWT, OAuth2)

**Operations:**
- Monitoring (metrics, logs)
- Alerting (thresholds, escalation)
- Health checks
- Graceful degradation

### 7️⃣ Production Readiness Checklist
Trước khi kết thúc review, verify:

- [ ] Tất cả functions có mental tests passed
- [ ] Ownership/lifetime correct
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
