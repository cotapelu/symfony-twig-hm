---
name: zig-review
description: Review Zig performance và low-level modules
license: MIT
compatibility: opencode
metadata:
  audience: developers
  scope: infrastructure
---
## Chức năng
- Review Zig modules trong zig/modules/{Module}/
- Review performance-critical code (Media, Storage, Protocols)
- Check memory management, low-level optimizations
- Validate network protocols, data compression

## Cấu trúc module
```
modules/{Module}/
└── {module_name}.zig
```

## Quy tắc review
1. Validate memory allocation patterns
2. Check error handling, safety mechanisms
3. Review protocol implementations
4. Performance benchmarking considerations
5. Resource management (files, network, audio streams)

## Khi nào dùng
Khi cần review MediaProcessing, CloudStorage, NetworkProtocol, AudioStreaming.

---
## Nguyên tắc bắt buộc từ guideline.md

### 1. Đọc toàn bộ code trước khi review
- Đọc hết mọi source code, mọi dòng code, mọi file trong module
- Bao gồm: *.zig files, build.zig
- Không được bỏ sót bất kỳ file nào, bất kỳ dòng code nào

### 2. Questioning mindset
- KHÔNG tin code hiện tại là đúng
- KHÔNG giả định kiến trúc hiện tại là hợp lý
- KHÔNG bảo vệ quyết định cũ
- CHỈ dựa trên phân tích kỹ thuật có thể kiểm chứng

### 3. Nhận diện vibe code trong Zig
- Tên biến: data1, resultX, temp, handler123
- Logic trùng lặp, if/else vô nghĩa
- Hàm dài không cần thiết
- Copy-paste function/component
- Unnecessary anytype usage

### 4. Zig-specific checks
- **Memory allocation**: Validate allocator patterns, arena usage
- **Error handling**: Review error unions, error traces
- **Safety mechanisms**: Validate unreachable, unreachable_trace
- **Resource management**: File handles, network connections, audio streams

### 5. Performance checks bắt buộc
- **Memory allocation patterns**: Unnecessary allocations in hot paths
- **Protocol implementations**: Buffer management, zero-copy patterns
- **Concurrency safety**: Async/await patterns, promise handling
- **Benchmarking**: Performance critical sections identified

### 6. Security checks
- **Buffer handling**: Overflow checks, slice bounds
- **Network security**: Protocol validation, input sanitization
- **Resource cleanup**: Memory leaks, file descriptor leaks

### 7. Refactor có kiểm soát
- Không rewrite toàn bộ nếu không cần
- Mỗi refactor phải có LÝ DO KỸ THUẬT
- Commit thường xuyên, incremental changes
- Backup code cũ trước khi xóa

### 8. Báo cáo cuối cùng
- Những phần đã được làm sạch
- Những phần vẫn còn rủi ro (đặc biệt memory safety)
- Những giả định kỹ thuật đang tồn tại
- Cấm tô hồng, nói chung chung, che giấu technical debt

---
## 7️⃣ Debugging & Issue Resolution (QUAN TRỌNG)

### Nguyên tắc bắt buộc:
- **KHÔNG XÓA CODE** - Đó là giải pháp cuối cùng, vi phạm nguyên tắc
- **Đọc toàn bộ file trước** - Không chỉ đoạn code suspected, đọc mọi dòng, imports, dependencies
- **Bình tĩnh phân tích** - Đọc lại toàn bộ file, mọi dòng code liên quan
- **Tìm root cause** - Check memory allocation, error unions, safety mechanisms
- **Incremental debugging** - Add debug prints, isolate sections, test hypotheses từng bước
- **Systematic approach** - Read → Understand → Isolate → Test → Verify

### Zig-specific debugging:
- Count braces manually - fix syntax errors nhanh hơn xóa code
- Check allocator patterns
- Review error union handling
- Verify pointer and slice bounds

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
- **Binary protocols**: Protocol buffers/ custom binary formats?
- **Streaming**: Media streaming responses xử lý đúng?

---
## 10️⃣ End-to-End Flow Review
Kiểm tra luồng từ User đến Backend:

- **Protocol handling**: Binary protocol parsing đúng?
- **Memory safety**: No buffer overflow, proper bounds checking?
- **Resource cleanup**: File handles, network connections released?
- **Error propagation**: Error handling through the stack đúng?
- **Edge cases**: Memory allocation failures, protocol edge cases

---
## 11️⃣ Review & Validation Toàn diện
Thay vì automated tests:

- **Integration Review**: Mentally check protocol implementations
- **E2E Mental Walkthrough**: Tưởng tượng full data flows
- **Memory Safety Review**: Allocation patterns, bounds checking
- **Resource Management**: File handles, network connections, audio streams
- **Security Review**: Buffer overflow, protocol validation, input sanitization

---
## 12️⃣ Nâng cấp Liên tục & Evolution
Mỗi lần review, không chỉ fix mà phải:

- **Root cause analysis**: Tại sao bug xảy ra? Prevent recurrence
- **Memory optimization**: Better allocator usage, arena patterns
- **Protocol optimization**: Zero-copy patterns, buffer management
- **Error handling**: Better error unions, error traces
- **Resource management**: Proper cleanup, no leaks

---
## 13️⃣ Evolution Metrics
Sau mỗi evolution round, đo lường:

**Code Quality:**
- Memory allocation patterns improved
- Error handling optimized
- Safety mechanisms strengthened

**Performance:**
- Allocations in hot paths reduced
- Buffer management optimized
- Protocol efficiency improved

**Security:**
- Buffer overflow risks eliminated
- Protocol validation improved

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
- Logic: Mọi nhánh if/switch được cover?
- Edge cases: Corner cases, boundary values, error cases
- Error paths: Error union handling, error traces

**Memory/Resource Testing:**
- Allocations: Correct allocator used?
- Pointers: Valid, non-null?
- Slices: Bounds checking?
- Resources: File handles, network connections released?

**Protocol/Data Testing:**
- Protocol parsing: Binary format correct?
- Serialization: Encode/decode correct?
- Compression: Data integrity?
- Streaming: Buffer management?

### 2️⃣ Mental Checking - Check trong tưởng tượng
Thay vì dùng linter/tool, tưởng tượng và verify:

**Code Quality:**
- Naming: Tên biến/hàm/struct rõ nghĩa?
- Structure: Code organization hợp lý?
- Duplication: Logic trùng lặp?
- Complexity: Hàm quá dài/phức tạp?

**Zig-Specific:**
- Memory allocation: Allocator patterns
- Error handling: Error unions, error traces
- Safety: Unreachable, unreachable_trace
- Resource management: File handles, connections

**Security:**
- Buffer handling: Overflow checks
- Protocol validation: Input sanitization
- Memory safety: No use-after-free
- Resource cleanup: No leaks

**Performance:**
- Allocations in hot paths
- Buffer management
- Zero-copy patterns
- Protocol efficiency

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
- Data: Large data, streaming, compression
- Memory: Allocation failures, OOM
- Protocol: Malformed data, edge cases
- Zig-specific: Comptime errors, async/await

### 4️⃣ Flow Coverage - Mọi luồng (BẮT BUỘC)
Kiểm tra CẢ HAI CHIỀU, không được thiếu:

**🔽 Top-Down (UI → Backend → DB):**
```
User Action
  → Data Input
    → Validation
      → Protocol Encoding
        → Memory Allocation
          → Network Send
            → Server Processing
              → Response
                → UI Update
```

**🔼 Bottom-Up (DB → Backend → UI):**
```
Data Changed
  → Event Trigger
    → Memory Allocation
      → Protocol Decoding
        → State Update
          → Real-time Push
            → UI Update
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

**Protocol Design:**
- Binary protocol conventions
- Versioning
- Documentation
- Error codes

**Code Quality:**
- Memory safety
- Error handling patterns
- Resource management
- Performance optimization

**Security:**
- Buffer overflow prevention
- Input validation
- Protocol security
- Memory safety

**Operations:**
- Monitoring (metrics, logs)
- Resource tracking
- Health checks
- Performance benchmarking

### 7️⃣ Production Readiness Checklist
Trước khi kết thúc review, verify:

- [ ] Tất cả functions có mental tests passed
- [ ] Memory allocation patterns correct
- [ ] Error handling correct
- [ ] Resource cleanup correct
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
