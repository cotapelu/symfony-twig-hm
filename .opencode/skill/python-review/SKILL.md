---
name: python-review
description: Review Python AI/ML và data processing modules
license: MIT
compatibility: opencode
metadata:
  audience: developers
  scope: backend-ai
---
## Chức năng
- Review Python modules trong python/modules/{Module}/
- Review AI/ML code (Recommendations, Chatbot, Analytics)
- Áp dụng Clean Architecture, type hints
- Check async/await patterns, database migrations

## Cấu trúc module
```
modules/{Module}/
├── models/schemas.py
├── repository/repositories.py
├── service/service.py
└── api/main.py
```

## Quy tắc review
1. Validate Pydantic schemas, type hints
2. Check async database operations
3. Review AI model integration, data flow
4. Security: input validation, API authentication
5. Performance: caching, query optimization

## Khi nào dùng
Khi cần review Chatbot, Analytics, Recommendations, Telemedicine modules.

---
## Nguyên tắc bắt buộc từ guideline.md

### 1. Đọc toàn bộ code trước khi review
- Đọc hết mọi source code, mọi dòng code, mọi file trong module
- Bao gồm: schemas.py, repositories.py, service.py, main.py
- Không được bỏ sót bất kỳ file nào, bất kỳ dòng code nào

### 2. Questioning mindset
- KHÔNG tin code hiện tại là đúng
- KHÔNG giả định kiến trúc hiện tại là hợp lý
- KHÔNG bảo vệ quyết định cũ
- CHỈ dựa trên phân tích kỹ thuật có thể kiểm chứng

### 3. Nhận diện vibe code trong Python
- Tên biến: data1, resultX, temp, handler123
- Logic trùng lặp, if/else vô nghĩa
- Hàm dài không cần thiết
- Copy-paste function/component
- Thiếu type hints

### 4. Python-specific checks
- **Type hints**: Validate Pydantic schemas, type annotations
- **Async/await**: Check async database operations, connection pooling
- **AI/ML integration**: Review model loading, inference, data flow
- **Error handling**: Review try/except patterns, exception propagation

### 5. Security checks bắt buộc
- **Input validation**: Pydantic validation, deserialization
- **API authentication**: JWT, OAuth flows
- **Model security**: Prompt injection, data exposure
- **Secrets management**: Environment variables, API keys

### 6. Performance checks
- Database query optimization
- Model inference caching
- Async connection pooling
- Memory leaks trong long-running processes

### 7. Refactor có kiểm soát
- Không rewrite toàn bộ nếu không cần
- Mỗi refactor phải có LÝ DO KỸ THUẬT
- Commit thường xuyên, incremental changes
- Backup code cũ trước khi xóa

### 8. Báo cáo cuối cùng
- Những phần đã được làm sạch
- Những phần vẫn còn rủi ro (đặc biệt AI/ML security)
- Những giả định kỹ thuật đang tồn tại
- Cấm tô hồng, nói chung chung, che giấu technical debt

---
## 7️⃣ Debugging & Issue Resolution (QUAN TRỌNG)

### Nguyên tắc bắt buộc:
- **KHÔNG XÓA CODE** - Đó là giải pháp cuối cùng, vi phạm nguyên tắc
- **Đọc toàn bộ file trước** - Không chỉ đoạn code suspected, đọc mọi dòng, imports, dependencies
- **Bình tĩnh phân tích** - Đọc lại toàn bộ file, mọi dòng code liên quan
- **Tìm root cause** - Check imports, async/await, type hints, Pydantic models
- **Incremental debugging** - Add debug prints, isolate sections, test hypotheses từng bước
- **Systematic approach** - Read → Understand → Isolate → Test → Verify

### Python-specific debugging:
- Check indentation (Python specific!)
- Verify type hints consistency
- Review async/await patterns
- Check Pydantic validation logic

### Tại sao hiệu quả:
- Đọc toàn bộ file hiểu context đầy đủ: dependencies, structure, related logic
- Không miss root cause như indentation errors ở vị trí khác
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
- **AI/ML responses**: Model outputs hiển thị đúng cho users?
- **Streaming**: SSE/WebSocket responses xử lý đúng?

---
## 10️⃣ End-to-End Flow Review
Kiểm tra luồng từ User đến Backend:

- **API Endpoint**: Endpoint tồn tại? HTTP method đúng? Auth required?
- **Data validation**: Pydantic validation hoạt động đúng?
- **Model inference**: Input → Model → Output flow đúng?
- **Error propagation**: Error từ ML pipeline → API Client đúng?
- **Edge cases**: Model errors, GPU OOM, timeout handling

---
## 11️⃣ Review & Validation Toàn diện
Thay vì automated tests:

- **Integration Review**: Mentally check API calls và model integration
- **E2E Mental Walkthrough**: Tưởng tượng full user flows với AI
- **ML Performance Review**: Inference time, memory usage, batch processing
- **Data Flow Review**: Input → Preprocess → Model → Postprocess → Output
- **Security Review**: Prompt injection, data exposure, model theft

---
## 12️⃣ Nâng cấp Liên tục & Evolution
Mỗi lần review, không chỉ fix mà phải:

- **Root cause analysis**: Tại sao bug xảy ra? Prevent recurrence
- **Model optimization**: Better inference, caching, batch processing
- **Data pipeline**: Improve preprocessing, postprocessing
- **Error handling**: Better ML-specific error handling
- **API evolution**: Better endpoints for AI features

---
## 13️⃣ Evolution Metrics
Sau mỗi evolution round, đo lường:

**Code Quality:**
- Type hints coverage
- Async patterns optimized
- Error handling improved

**AI/ML Performance:**
- Inference time reduced
- Memory usage optimized
- Batch processing efficiency

**Security:**
- Prompt injection vulnerabilities addressed
- Data exposure risks mitigated

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
- Logic: Mọi nhánh if/else được cover?
- Edge cases: Corner cases, boundary values, None handling
- Error paths: Exception handling, error messages

**Async/ML Testing:**
- Async operations: Connection pool đúng?
- Model inference: Input → Model → Output đúng?
- Data pipeline: Preprocess → Model → Postprocess đúng?
- Performance: Inference time acceptable?

**API/Database Testing:**
- HTTP handling: Request/response đúng?
- Pydantic validation: Schema validation đúng?
- Database: Query execution đúng?
- Error propagation: Error handling đúng?

### 2️⃣ Mental Checking - Check trong tưởng tượng
Thay vì dùng linter/tool, tưởng tượng và verify:

**Code Quality:**
- Naming: Tên biến/hàm/class rõ nghĩa?
- Structure: Code organization hợp lý?
- Duplication: Logic trùng lặp?
- Type hints: Có đầy đủ không?

**Python-Specific:**
- Type hints: Validate Pydantic schemas
- Async/await: Check connection pooling
- ML integration: Model loading, inference
- Error handling: Try/except patterns

**Security:**
- Input validation: Pydantic validation
- API authentication: JWT, OAuth flows
- Model security: Prompt injection
- Secrets: Environment variables

**Performance:**
- Database query optimization
- Model inference caching
- Async connection pooling
- Memory leaks trong long-running processes

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
- ML-specific: Model errors, GPU OOM, timeout

### 4️⃣ Flow Coverage - Mọi luồng (BẮT BUỘC)
Kiểm tra CẢ HAI CHIỀU, không được thiếu:

**🔽 Top-Down (UI → Backend → DB):**
```
User Click
  → Form Validation (Pydantic)
    → API Request
      → Handler
        → Service (Async)
          → Model Inference
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
      → Service Notification
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
- Versioning (v1, v2)
- Documentation (OpenAPI/Swagger)
- Rate limiting, pagination, filtering

**Code Quality:**
- SOLID principles
- Clean Architecture
- Type hints everywhere
- Error handling patterns

**Security:**
- OWASP guidelines
- Authentication (JWT, OAuth2)
- Input validation (Pydantic)
- Model security (Prompt injection)

**Operations:**
- Monitoring (metrics, logs)
- Alerting (thresholds, escalation)
- Health checks
- ML-specific: Model versioning, A/B testing

### 7️⃣ Production Readiness Checklist
Trước khi kết thúc review, verify:

- [ ] Tất cả functions có mental tests passed
- [ ] Type hints coverage 100%
- [ ] Async patterns correct
- [ ] Model integration correct
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
