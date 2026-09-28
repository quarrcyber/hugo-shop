# Hugo Shop

Hugo Shop là website thương mại điện tử trong môi trường local. Bản hiện tại là mốc sạch: Laravel 12 phục vụ storefront, REST API và admin; Slim 4 xử lý thanh toán giả lập; một dịch vụ nhỏ mô phỏng vận chuyển.

## Chạy nhanh

Yêu cầu duy nhất là Docker Desktop có Docker Compose v2.

```bash
cp .env.example .env
docker compose up --build
```

Mở `http://127.0.0.1:8080`. Lần khởi động đầu sẽ chạy migration và seed dữ liệu mẫu. Mailpit được proxy tại `http://127.0.0.1:8080/mailpit/`.

Tài khoản mẫu dùng chung mật khẩu `Password123!`:

```text
customer@hugo-shop.local
staff@hugo-shop.local
admin@hugo-shop.local
```

Thẻ test dùng hạn `12/30`, CVV `123`:

```text
4242424242424242  thành công
4000000000000002  bị từ chối
4000000000003220  cần xác thực bổ sung
```

Không nhập thẻ thật. Shop không lưu PAN hoặc CVV; trang tài khoản chỉ quản lý token, loại thẻ, bốn số cuối, hạn thẻ và thẻ mặc định.

Các lệnh thường dùng:

```bash
make up
make test
make seed
make reset
make down
```

## Cấu trúc

```text
services/shop             Laravel 12, Blade, Tailwind, Alpine
services/payment          Slim 4 payment service và mock gateway
services/mock-shipping    API vận chuyển giả lập
docker                    PHP và Nginx images
docs                      Kiến trúc, ERD, luồng thanh toán, OpenAPI
```

Chỉ Nginx publish cổng ra máy host. MySQL, Redis, RustFS, Mailpit, Payment Service và Mock Shipping chỉ giao tiếp qua các network nội bộ. Xem chi tiết trong `docs/architecture.md`.

## Kiểm tra

```bash
docker compose exec shop-php-fpm php artisan test
docker compose exec shop-php-fpm ./vendor/bin/phpstan analyse
docker compose exec shop-php-fpm composer audit
```

Kiểm tra frontend và dependency JavaScript từ `services/shop`:

```bash
npm ci
npm run build
npm run lint:php
npm run lint:design
npm audit --audit-level=moderate
```

Khi có server local đang chạy, `npm run qa:responsive -- http://127.0.0.1:8080` sẽ chụp và đo trang ở 320, 375, 414, 768 và 1280 px bằng Chrome headless. Chi tiết thanh toán và webhook nằm trong `docs/payment-flow.md`; hợp đồng REST nằm trong `docs/openapi.yaml`.
