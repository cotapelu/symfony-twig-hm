---
name: migration-helper
description: Hỗ trợ migrate modules từ Elixir sang ngôn ngữ khác
license: MIT
compatibility: opencode
metadata:
  audience: developers
  scope: migration
---
## Chức năng
- Hỗ trợ migrate từ Elixir sang Go, Python, PHP, Zig
- Mapping Elixir patterns sang ngôn ngữ đích
- Preserve business logic, data models
- Review migration completeness

## Migration mapping
| Elixir Module | Target Language | Target Module |
|---------------|-----------------|---------------|
| audiostreaming | Zig | audiostreaming |
| chatbotrealtime | Python | ChatbotRealtime |
| customerservice | PHP | CustomerServiceMigrated |
| events | Go | events |
| livesupport | PHP | LiveSupport |
| locationtracking | Go | locationtracking |
| notificationsrealtime | Go | notificationsrealtime |
| realtimesync | Go | realtimesync |
| videoconference | Go | videoconference_migrated |
| wallet | Go | wallet_migrated |

## Quy tắc migration
1. **Đọc Elixir code gốc** trước khi migrate
2. **Map domain logic** sang ngôn ngữ đích
3. **Preserve API contracts** - không thay đổi interface
4. **Test end-to-end** sau migration
5. **Update modules-list.md** với trạng thái mới

## Khi nào dùng
Khi cần migrate hoặc review code đã migrated từ Elixir.

---
## Nguyên tắc bắt buộc từ guideline.md

### 1. Đọc toàn bộ code trước khi migrate
- Đọc hết mọi source code gốc (Elixir) và đích
- Đọc cả file config, test files
- Không được bỏ sót bất kỳ file nào, bất kỳ dòng code nào

### 2. Questioning mindset (quan trọng khi migrate)
- KHÔNG tin code gốc là đúng hoặc tối ưu
- KHÔNG giả định Elixir patterns áp dụng được cho ngôn ngữ đích
- KHÔNG bảo vệ logic cũ chỉ vì nó "chạy được"
- CHỈ dựa trên phân tích kỹ thuật có thể kiểm chứng

### 3. Nhận diện vibe code trong migration
- Copy-paste nguyên văn từ Elixir mà không adapt
- Giữ lại pattern không phù hợp với ngôn ngữ đích
- API contracts không được preserve đúng
- Error handling không native với ngôn ngữ đích

### 4. Migration-specific checks
- **Domain logic mapping**: Elixir processes → Go goroutines/Python async/Zig async
- **OTP patterns**: Supervision trees, gen_server → phù hợp với ngôn ngữ đích
- **Data models**: Ecto schemas → Pydantic/SQLAlchemy/TypeORM
- **API contracts**: Không thay đổi interface với consumers

### 5. Security checks bắt buộc
- **Secrets management**: Environment variables thay vì config files
- **Authentication**: Native patterns của ngôn ngữ đích
- **Input validation**: Phù hợp với framework của ngôn ngữ đích
- **Database security**: Parameterized queries

### 6. Performance checks
- **Concurrency**: Elixir's lightweight processes → native concurrency của ngôn ngữ đích
- **Database queries**: N+1 issues trong translated code
- **Memory management**: GC patterns khác nhau
- **Connection pooling**: Connection handling

### 7. Migration workflow bắt buộc
1. Đọc Elixir code gốc **trước** khi migrate
2. Map domain logic sang ngôn ngữ đích
3. Preserve API contracts - không thay đổi interface
4. Test end-to-end sau migration
5. Update modules-list.md với trạng thái mới
6. Commit thường xuyên, incremental changes

### 8. Báo cáo cuối cùng
- Những phần đã được migrate đúng
- Những phần còn rủi ro (performance, security)
- Những giả định kỹ thuật đang tồn tại
- Cấm tô hồng, nói chung chung, che giấu technical debt

---
## 7️⃣ Debugging & Issue Resolution (QUAN TRỌNG)

### Nguyên tắc bắt buộc:
- **KHÔNG XÓA CODE** - Đó là giải pháp cuối cùng, vi phạm nguyên tắc
- **Đọc toàn bộ cả gốc VÀ đích trước** - Không chỉ đoạn code suspected
- **Bình tĩnh phân tích** - Đọc lại toàn bộ code Elixir và target language
- **Tìm root cause** - So sánh patterns, check domain logic mapping
- **Incremental debugging** - Test từng feature, isolate issues
- **Systematic approach** - Read → Compare → Understand → Migrate → Test → Verify

### Migration-specific debugging:
- Elixir patterns không map trực tiếp sang target
- OTP supervision → cần cơ chế equivalent trong target
- Pattern matching → cần replacement patterns
- Process-based concurrency → cần native concurrency của target

### Tại sao hiệu quả:
- Đọc cả gốc và đích hiểu mapping đầy đủ
- Không miss domain logic khi migrate
- **Preserve functionality** - không vì pass test mà xóa code gốc logic

### Nếu vẫn failed:
- Consult team, review cả Elixir và target code
- Có thể cần re-think migration approach
- Luôn có plan restore từ git nếu cần

### Cấm tuyệt đối:
- Xóa code Elixir gốc mà không verify migration đầy đủ
- "Vá áo" - fix tạm thời dẫn đến nhiều bug hơn
- Chấp nhận feature loss
- Thay đổi API contracts mà không cần thiết

### Đọc lại bắt buộc:
Nếu chưa hiểu rõ cả Elixir gốc VÀ code target, PHẢI ĐỌC LẠI cho đến khi nắm vững hoàn toàn. Vi phạm sẽ dẫn đến migration không đầy đủ, mất functionality.

---
## 9️⃣ Frontend & UI/UX Review
Sau migration, verify FE impact:

- **API contracts**: Response format khớp với FE consumption?
- **Error handling**: Error responses tương thích với FE?
- **Real-time features**: WebSocket/SSE vẫn hoạt động?
- **Breaking changes**: Any API changes affect FE?

---
## 10️⃣ End-to-End Flow Review
Sau migration, verify complete flows:

- **Elixir → Target**: All features preserved?
- **API compatibility**: Endpoints same as before?
- **Data consistency**: Database migrations correct?
- **Error propagation**: Errors handle same way?
- **Edge cases**: Timeout, concurrency handling same?

---
## 11️⃣ Review & Validation Toàn diện
Sau migration, verify everything:

- **Integration Review**: Check all service interactions
- **E2E Mental Walkthrough**: Full user flows still work
- **Performance Review**: Target language performance vs Elixir
- **Feature Parity**: All features from Elixir preserved?
- **Security Review**: Security posture maintained?

---
## 12️⃣ Nâng cấp Liên tục & Evolution
Sau migration, continue improving:

- **Root cause analysis**: Issues found during migration
- **Language optimization**: Use native patterns of target language
- **Performance tuning**: Optimize for target language strengths
- **Error handling**: Better patterns in target language
- **API evolution**: Improve while maintaining compatibility

---
## 13️⃣ Evolution Metrics
Sau mỗi migration, đo lường:

**Migration Quality:**
- % code adapted vs copied
- API compatibility maintained
- Feature parity achieved

**Performance:**
- vs Elixir baseline
- Target language advantages utilized

**Security:**
- Vulnerabilities addressed
- New patterns better security

**Process:**
- Time per migration
- Issues found and fixed

---
## 🎯 Production Readiness Enforcer (BẮT BUỘC)

### Nguyên tắc vàng:
- **KHÔNG VIẾT TEST CODE** - Tưởng tượng và kiểm tra trong đầu
- **KHÔNG CHECK BẰNG TOOL** - Tưởng tượng mọi tình huống, mọi luồng
- **NẾU THIẾU THÌ VIẾT THÊM** - Không bỏ bớt, không skip
- **APP NGÀY CÀNG HOÀN THIỆN** - Mỗi lần review = thêm code, thêm tính năng

### 1️⃣ Mental Testing - Test trong tưởng tượng
Thay vì viết test code, tưởng tượng và verify tất cả:

**Elixir → Target Testing:**
- Feature parity: All features from Elixir preserved?
- Domain logic: Business rules mapped đúng?
- Data flow: Input → Process → Output đúng?
- Error handling: Errors handle same way?

**API Compatibility Testing:**
- Endpoints: Same as Elixir version?
- Request/response: Format compatible?
- Auth: Same authentication flow?
- Error codes: Same error responses?

**Concurrency Testing:**
- Process model: Equivalent behavior?
- Message passing: Same semantics?
- Supervision: Restart strategy same?
- State management: Same state handling?

### 2️⃣ Mental Checking - Check trong tưởng tượng
Thay vì dùng linter/tool, tưởng tượng và verify:

**Code Quality:**
- Naming: Clear and meaningful?
- Structure: Organized by domain?
- Duplication: No unnecessary copy-paste?
- Native patterns: Using target language idioms?

**Migration-Specific:**
- OTP patterns: Mapped correctly?
- Pattern matching: Replaced appropriately?
- Data types: Equivalent representations?
- Error handling: Native patterns?

**Security:**
- Secrets management: Environment variables?
- Authentication: Native implementation?
- Input validation: Framework patterns?
- Database security: Parameterized queries?

**Performance:**
- Concurrency: Native performance equivalent?
- Memory: GC patterns considered?
- Database: Query optimization?
- Connection pooling: Proper handling?

### 3️⃣ Scenario Coverage - Mọi tình huống
Tưởng tượng và cover TẤT CẢ scenarios:

**User Scenarios:**
- Happy path: User flow still works
- Error path: Error handling same behavior
- Edge cases: Boundary conditions preserved?
- Recovery: Retry, undo mechanisms work?
- Parallel: Concurrent access same?

**Business Scenarios:**
- Normal operation: Same as Elixir
- Peak load: Performance comparable?
- Failure: Error behavior identical?
- Recovery: Data consistency maintained?
- Compliance: Audit trails preserved?

**Technical Scenarios:**
- Network issues: Timeout handling same?
- Database: Connection fail handling?
- External services: Third-party integration?
- Data: Migration, large data handling?
- OTP-specific: Supervision restart same?

### 4️⃣ Flow Coverage - Mọi luồng (BẮT BUỘC)
Kiểm tra CẢ HAI CHIỀU, không được thiếu:

**🔽 Top-Down (UI → Migrated Backend → DB):**
```
User Click
  → UI Validation
    → API Request
      → Migrated Handler
        → Migrated Service
          → Migrated Repository
            → Database
              → Response
                → UI Update
```

**🔼 Bottom-Up (DB → Migrated Backend → UI):**
```
Data Changed
  → Migrated Entity
    → Migrated Event
      → Migrated Notification
        → Real-time Channel
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
- ❌ Copy-paste Elixir code without adapting

### 6️⃣ Industry Standards - Chuẩn công nghiệp
Verify code đạt chuẩn production:

**API Design:**
- RESTful conventions (target language)
- Versioning
- Documentation (OpenAPI)
- Rate limiting, pagination, filtering

**Code Quality:**
- SOLID principles (target language)
- Clean Architecture
- Native design patterns
- Error handling patterns

**Security:**
- OWASP guidelines
- Authentication (native)
- Input validation (native)
- Secrets management

**Operations:**
- Monitoring (metrics, logs)
- Alerting (thresholds, escalation)
- Health checks
- Performance benchmarking

### 7️⃣ Production Readiness Checklist
Trước khi kết thúc review, verify:

- [ ] Feature parity with Elixir achieved
- [ ] All Elixir patterns mapped correctly
- [ ] API contracts preserved
- [ ] Tất cả flows (UI→DB và DB→UI) verified
- [ ] Tất cả edge cases được cover
- [ ] Tất cả error paths được handle
- [ ] Security vulnerabilities none
- [ ] Performance acceptable
- [ ] Code đạt chuẩn industry (target language)
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
✅ Feature Parity: [x/y] Elixir features preserved
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
- ❌ Copy-paste Elixir mà không adapt
