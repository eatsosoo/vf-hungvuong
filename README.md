# VinFast Hùng Vương

Website giới thiệu đại lý VinFast và khu vực quản trị xe, bài viết SEO, khuyến mãi,
trang nội dung, ảnh, khách hàng, lịch lái thử, báo cáo và tài khoản.

Dự án dùng PHP 8.5, Laravel 13, Blade Components, Tailwind CSS 4 và Vite 8.
Quy ước kiến trúc nằm trong [PROJECT_RULES.md](PROJECT_RULES.md).

## Chạy dự án local

### 1. Chuẩn bị môi trường

Cần PHP 8.5, Composer 2, Node.js 22.12+ hoặc 24, npm và MySQL.
PHP cần các extension Laravel thông thường, `pdo_mysql`, `pdo_sqlite` (cho test)
và `gd` có hỗ trợ WebP (cho upload và tối ưu ảnh).

Chạy tại thư mục dự án:

```bash
composer install
npm ci
```

Nếu chưa có `.env`, sao chép mẫu và tạo khóa ứng dụng:

```bash
cp .env.example .env
php artisan key:generate --no-interaction
```

Nếu `.env` đã có khóa, giữ nguyên khóa hiện tại.

### 2. Cấu hình MySQL

Tạo database trong MySQL bằng tài khoản có quyền tạo database:

```sql
CREATE DATABASE IF NOT EXISTS vf_studio
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Cập nhật `.env` theo MySQL trên máy:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vf_studio
DB_USERNAME=root
DB_PASSWORD=
```

Sau đó chạy migration, nạp dữ liệu đại lý mẫu và build asset:

```bash
php artisan config:clear --no-interaction
php artisan migrate --no-interaction
php artisan db:seed --no-interaction
npm run build
```

Seeder tạo cấu hình, danh mục bài viết, trang nội dung và dữ liệu xe
VF 5, VF 6, VF 7, VF 8 và VF 9. Thông số ghi rõ phiên bản tham chiếu,
nguồn VinFast và ngày đối chiếu trong dữ liệu JSON. Ảnh VF 8/VF 9 lấy từ nguồn
VinFast chính thức, được lưu sẵn trong `public/assets/vinfast` và chuyển sang WebP
trên disk local khi seed; không cần kết nối internet lúc nạp dữ liệu.
VF 8 trong catalog tham chiếu bản Eco/Plus, tách rõ với VF 8 Thế hệ mới 2026.
Trong môi trường `local` hoặc `testing`, seeder thêm 20 bài viết mẫu đã xuất bản,
có nội dung Markdown, tiêu đề/mô tả SEO, từ khóa chính, phân loại và liên kết mẫu xe.
`SamplePostSeeder` được bỏ qua ở các môi trường khác, kể cả khi chạy `db:seed --force`.
Seeder không tạo tài khoản quản trị hay mật khẩu đăng nhập mặc định. Nếu chưa có
Admin/Manager đang hoạt động, bài mẫu dùng một tác giả Editor bị vô hiệu hóa,
có mật khẩu ngẫu nhiên không được công bố; hãy tạo tài khoản quản trị riêng ở bước 3.
Ảnh lưu riêng tư và được phục vụ qua route `/media/{media}`, không cần `storage:link`.

Với dự án đã có dữ liệu, chạy migration trước rồi nạp lại seed cần thiết:

```bash
php artisan migrate --no-interaction
php artisan db:seed --class=VehicleSeeder --no-interaction
php artisan db:seed --class=SamplePostSeeder --no-interaction
```

Nạp seed xe trước để bài mẫu có thể dùng ảnh đại diện và quan hệ xe đã tồn tại.
`VehicleSeeder` bổ sung những trường còn trống, giữ dữ liệu thông số đã nhập,
ảnh đã chọn và trạng thái hiển thị hiện có; giá phiên bản mới để trống để đại lý
cập nhật theo báo giá thực tế.
Chạy lại `SamplePostSeeder` không ghi đè bài có cùng slug: tiêu đề, nội dung,
trạng thái xuất bản và quan hệ đã chỉnh trong admin được giữ nguyên.
Các bài này là dữ liệu mẫu để xem và thử giao diện local, cần được biên tập theo
thông tin đại lý trước khi dùng làm nội dung xuất bản thực tế.

### 3. Tạo tài khoản quản trị

Chạy lệnh sau trong terminal tương tác:

```bash
php artisan admin:create admin@example.com --name="Quản trị viên"
```

Nhập mật khẩu và xác nhận theo lời nhắc. Mật khẩu cần ít nhất 12 ký tự,
bao gồm chữ hoa, chữ thường, số và ký hiệu. Lệnh này cần chế độ tương tác
để nhập mật khẩu ẩn; không truyền mật khẩu trong command line.

### 4. Khởi động với asset đã build

```bash
php artisan serve --host=127.0.0.1 --port=8000 --no-interaction
```

- Trang chủ: [http://localhost:8000](http://localhost:8000)
- Danh sách xe: [http://localhost:8000/xe](http://localhost:8000/xe)
- Bài viết: [http://localhost:8000/bai-viet](http://localhost:8000/bai-viet)
- Đăng nhập: [http://localhost:8000/dang-nhap](http://localhost:8000/dang-nhap)
- Quản trị: [http://localhost:8000/admin](http://localhost:8000/admin)

Mở hai terminal riêng nếu cần kiểm tra thông báo khách mới hoặc lịch xuất bản:

```bash
php artisan queue:work --tries=3 --no-interaction
php artisan schedule:work --no-interaction
```

Scheduler xuất bản bài viết đến lịch; queue xử lý thông báo khách mới.
Dùng `Ctrl+C` để dừng từng tiến trình. Chạy lại `npm run build` sau khi sửa CSS/JavaScript.

### Phát triển với Vite

Dự án cũng có lệnh chạy đồng thời Laravel server, Vite, queue, log và scheduler:

```bash
composer run dev
```

Hoặc mở Vite trong terminal riêng bên cạnh các tiến trình ở trên:

```bash
npm run dev
```

Chính sách CSP hiện tại chỉ cho phép asset cùng origin. Nếu trình duyệt chặn asset/HMR
từ Vite, dừng Vite và dùng chế độ asset đã build ở bước 4.
Nếu Vite đã dừng bất thường nhưng còn `public/hot`, xóa file đó để Laravel
đọc asset từ `public/build/manifest.json`.

## Component và CSS quản trị

Admin và trang đăng nhập dùng asset Vite từ `resources/css/app.css` và
`resources/js/admin.js`. Màu thương hiệu và font được khai báo bằng `@theme`;
các kiểu dùng chung được quản lý bằng utility Tailwind và `@apply`.
Phong cách theo trang chủ: nền than đậm, xanh lime, nền nội dung sáng, Arial và nút bo tròn.

Các component dùng lại nằm trong `resources/views/components`:

- `field`, `select`, `field-wrapper`: label, gợi ý, giá trị cũ và lỗi validation liên kết với field.
- `admin.media-picker`: chọn lại ảnh bằng ID và danh sách gợi ý.
- `admin.page-heading`, `admin.nav-link`, `admin.icon`: tiêu đề và điều hướng.
- `admin.table`, `admin.pagination`, `admin.empty-state`: danh sách và trạng thái rỗng.
- `admin.badge`, `admin.stat-card`: trạng thái và số liệu.
- `admin.post-editor`, `admin.seo-assessment`: trình soạn bài và checklist SEO.
- `admin.calendar-event`: lịch hẹn có trạng thái và liên kết chi tiết.
- `feedback`: thông báo thành công và tổng hợp lỗi.

Ví dụ:

```blade
<x-field name="name" label="Tên mẫu xe" :value="$vehicle->name" required />
<x-field name="description" label="Giới thiệu" type="textarea" :rows="4" />
<x-select name="is_active" label="Hiển thị">
    <option value="0">Ẩn</option>
    <option value="1" @selected(old('is_active', $vehicle->is_active))>Bật</option>
</x-select>
```

Với nhiều form trên cùng trang dùng chung tên field, truyền `id` riêng cho mỗi field.
Các trang client dùng `resources/css/site.css` và `resources/js/site.js`,
chia sẻ token thương hiệu và field với admin. JavaScript client tái sử dụng
xử lý biểu mẫu và video trong `public/js/studio.js`.
Trang chủ giữ stylesheet hiện có; mũi tên select dùng chung từ `resources/css/select.css`.
Logo nền trong suốt nằm tại `public/assets/vinfast/logo.png`.

Các component `site.breadcrumbs`, `site.cta`, `site.pagination`, `site.empty-state`,
`post-card` và `vehicle-card` hỗ trợ giao diện client responsive.
Bài viết xuất bản trong admin sẽ xuất hiện ở `/bai-viet` và `/bai-viet/{slug}`.
Metadata, canonical và structured data được render ở layout; xem trước có `noindex`.

Editor dùng Tiptap 3, chỉ tải asset tại trang viết bài. Có định dạng đoạn/H2–H4,
in đậm/nghiêng/gạch ngang, danh sách, trích dẫn, mã, bảng, liên kết, ảnh và video YouTube.
Có chế độ Markdown, xem trước qua máy chủ, toàn màn hình và hoàn tác/làm lại.
Ảnh có thể chọn từ thư viện hoặc tải lên trực tiếp; file được kiểm tra và tối ưu WebP.
Nội dung được lưu trong DB khi nhấn **Lưu bài viết**. Bản khôi phục trong sessionStorage
chỉ tồn tại trong phiên trình duyệt, theo người dùng và bài; không thay thế việc lưu bài.

Checklist SEO hiển thị điểm 0–100 và hướng dẫn từng tiêu chí. Đây là tiêu chuẩn
biên tập nội bộ, không phải điểm hay cam kết thứ hạng của Google. `focus_keyword`
được lưu cùng bài viết; cần chạy migration khi cập nhật dự án đã có dữ liệu.

Nút bên cạnh breadcrumb thu gọn sidebar desktop còn icon và ghi nhớ lựa chọn.
Trang **Lịch lái thử** mặc định dùng lịch tháng, chuyển sang **Danh sách** khi cần.
Các bộ lọc và quyền xem lịch được giữ giữa hai chế độ; mobile hiển thị lịch theo ngày.

## Kiểm thử

Test dùng SQLite in-memory theo `phpunit.xml`, độc lập với database MySQL local.

```bash
php artisan test --compact tests/Feature/AdminInterfaceTest.php
php artisan test --compact tests/Feature/AdminWorkflowTest.php
php artisan test --compact tests/Feature/ClientPageSeoTest.php
php artisan test --compact
npm run build
```

Khi sửa PHP, định dạng bằng Laravel Pint. Thư mục này cần nằm trong Git checkout để dùng `--dirty`:

```bash
vendor/bin/pint --dirty --format agent
```

Nếu chưa có Git checkout, chạy Pint với đường dẫn file PHP đã sửa.
