# Luồng thanh toán và vận chuyển

```mermaid
sequenceDiagram
    participant B as Browser
    participant S as Laravel Shop
    participant P as Payment Service
    participant G as Mock Gateway
    participant H as Mock Shipping
    participant D as MySQL

    B->>S: Gửi checkout + thẻ test
    S->>P: POST /tokenize
    P->>G: Kiểm tra kịch bản thẻ test
    G-->>P: token + loại + 4 số cuối
    P-->>S: token
    Note over S: PAN và CVV không được ghi database hoặc log
    S->>H: POST /quote
    H-->>S: phí + dịch vụ
    S->>D: Transaction, khóa tồn kho, tạo order/payment
    S->>P: POST /charge + Idempotency-Key
    P->>G: Charge token
    G-->>P: succeeded / failed / requires_action
    P-->>S: kết quả
    P-->>S: webhook có X-Hugo-Signature
    alt Thành công
        S->>D: order=paid, payment=succeeded
    else Thất bại
        S->>D: order=cancelled, hoàn tồn kho
    end
    S-->>B: Kết quả đơn hàng
```

## Thẻ test

| Số thẻ | Kịch bản |
| --- | --- |
| `4242424242424242` | Thành công |
| `4000000000000002` | Bị từ chối |
| `4000000000003220` | Cần xác thực bổ sung |

Dùng hạn `12/30` và CVV `123`. Đây chỉ là dữ liệu giả của mock gateway. Không nhập thẻ thật.

Khách có thể token hóa thêm nhiều thẻ test ở trang tài khoản, chọn một thẻ mặc định hoặc xóa thẻ. Mọi thao tác đều kiểm tra chủ sở hữu. PAN và CVV chỉ tồn tại trong request tới Payment Service; model `CreditCard` chỉ giữ token, loại thẻ, bốn số cuối và hạn thẻ, đồng thời ẩn token khỏi JSON.

Mọi charge bắt buộc có `Idempotency-Key`. Payment Service cache kết quả theo khóa để một retry không tạo giao dịch thứ hai. Webhook ký `HMAC-SHA256(raw_body, PAYMENT_WEBHOOK_SECRET)` và Shop so sánh bằng `hash_equals`.
