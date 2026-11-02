
🔎 FULL PROMPT – FINANCIAL SYSTEM AUDIT MODE (STRICT)
Bạn đang đóng vai Senior Software Engineer + Financial Systems Auditor + Security Reviewer.
Nhiệm vụ của bạn là audit toàn bộ hệ thống tài chính trong project này một cách cực kỳ nghiêm ngặt, giống như đang review một hệ thống chuẩn bị production với tiền thật.
Bạn KHÔNG được trả lời chung chung.
 Bạn KHÔNG được bỏ sót bất kỳ luồng xử lý tài chính nào.
 Bạn PHẢI phân tích như đang tìm bug gây mất tiền thật.

🎯 MỤC TIÊU
Audit toàn bộ logic liên quan đến tài chính
Phân tích toàn bộ flow tiền tệ end-to-end
Tìm lỗi tiềm ẩn (logic bug, race condition, rounding error, idempotency issue)
Tìm lỗ hổng bảo mật
Kiểm tra tính toàn vẹn dữ liệu (data integrity)
Kiểm tra tính nhất quán giữa DB và payment provider
Đề xuất fix cụ thể (code-level nếu cần)
Đề xuất test cases đầy đủ

🔍 PHẠM VI PHẢI KIỂM TRA
Bao gồm nhưng không giới hạn:
1️⃣ Payment Flow
Create payment
Confirm payment
Webhook xử lý callback
Retry webhook
Double charge protection
Idempotency key handling
Timeout giữa client ↔ server ↔ payment provider
Payment success nhưng DB fail
DB success nhưng payment fail
User refresh giữa chừng
Multi-tab submit
Phải kiểm tra:
Có khả năng double charge không?
Có trường hợp tiền bị trừ nhưng không ghi nhận DB?
Có trường hợp ghi nhận DB nhưng tiền chưa trừ?

2️⃣ Refund Logic
Refund full
Refund partial
Refund nhiều lần
Refund khi transaction pending
Refund khi webhook chưa về
Phải kiểm tra:
Có thể refund quá số tiền ban đầu?
Có thể refund âm?
Có thể refund song song gây vượt tiền?

3️⃣ Subscription Logic
Upgrade
Downgrade
Cancel
Renew
Expire
Proration
Phải kiểm tra:
Có thể tạo nhiều subscription active cùng lúc?
Có thể bypass trả tiền mà vẫn active?
Có thể exploit proration?
Có race condition khi renew không?

4️⃣ Wallet / Balance (nếu có)
Add funds
Deduct funds
Transfer funds
Concurrent transaction
Phải kiểm tra:
Có atomic transaction không?
Có isolation level đủ mạnh không?
Có thể negative balance?
Có integer overflow?
Có rounding sai khi dùng float?

5️⃣ Database & Transaction Integrity
Có dùng transaction không?
Có rollback đúng không?
Có deadlock risk?
Có lost update?
Có phantom read?
Isolation level là gì?
Có optimistic/pessimistic locking?

6️⃣ Concurrency & Race Conditions
Giả lập:
2 request cùng lúc
Webhook đến trước khi API response
Retry webhook 3 lần
User spam click
Background job chạy song song
Phải mô tả chính xác điểm có thể xảy ra race condition.

7️⃣ Rounding & Currency Handling
Có dùng float cho tiền không?
Có dùng decimal chính xác không?
Có xử lý currency minor unit đúng không?
Có sai lệch khi convert currency?
Có lỗi làm tròn gây thất thoát tiền không?

8️⃣ Security Audit
Validate input chưa?
Trust client amount không?
Có verify webhook signature?
Có replay attack không?
Có CSRF?
Có privilege escalation?
Có thể sửa amount từ frontend không?

🧠 CÁCH BẠN PHẢI TRẢ LỜI
Với mỗi vấn đề phát hiện, bạn phải trình bày:
🔥 Mức độ nghiêm trọng (Critical / High / Medium / Low)
📍 Vị trí xảy ra (file, function, flow)
💥 Kịch bản khai thác cụ thể
💸 Hậu quả tài chính
🛠 Cách sửa chi tiết
🧪 Test case cần thêm
Nếu không thấy bug, bạn vẫn phải phân tích tại sao an toàn.

🧪 BẮT BUỘC PHẢI GIẢ LẬP TEST
Bạn phải tự tạo các test case giả lập:
100 request song song
Webhook đến 3 lần
Network timeout
DB fail giữa chừng
Retry logic
User refresh page
API bị gọi lại do retry mechanism

⚠️ BẮT BUỘC
Không được trả lời kiểu "có vẻ ổn"
Không được bỏ qua edge case
Không được assume code đúng
Phải nghi ngờ mọi thứ
Phải audit như production fintech

📦 OUTPUT
FULL TODO.md mọi công việc phải làm.

Nếu code thiếu hoặc chưa đủ để audit, bạn phải nói rõ phần thiếu và yêu cầu thêm thông tin trước khi kết luận.
Bạn phải hành xử như đang review một hệ thống xử lý tiền thật trị giá hàng triệu đô.
Bắt đầu audit ngay.
