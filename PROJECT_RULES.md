# Nguyên tắc dự án VinFast Hùng Vương

Tài liệu này là nguồn thống nhất cho các quyết định kiến trúc và quy ước riêng của dự án. Đọc trước khi lập kế hoạch, tạo hoặc sửa code; đồng thời tuân thủ hướng dẫn Laravel Boost trong `AGENTS.md` hoặc `CLAUDE.md` và các quy tắc theo đường dẫn nếu có.

Các quy tắc dưới đây mô tả cách triển khai đã thống nhất, không có nghĩa các chức năng đã được xây dựng. Khi người dùng thay đổi quyết định kiến trúc, cập nhật tài liệu này cùng với thay đổi liên quan. Không tự mở rộng phạm vi sản phẩm.

## 1. Mục tiêu và phạm vi

- Website giới thiệu đại lý, danh mục xe, ưu đãi và bài viết SEO; chuyển khách quan tâm thành yêu cầu tư vấn, báo giá hoặc đăng ký lái thử.
- Admin quản lý xe, ưu đãi, bài viết, nội dung website, khách hàng tiềm năng, lịch lái thử và quyền truy cập.
- Quản lý kho theo VIN, hợp đồng, đặt cọc, thanh toán và hậu mãi là phạm vi mở rộng; chỉ triển khai khi được yêu cầu.
- Bài viết SEO là chức năng chính, cần hỗ trợ quản lý nhiều bài, phân loại nội dung và liên kết với mẫu xe.

## 2. Công nghệ và quyết định còn mở

- Dùng Laravel và Eloquent theo phiên bản thực tế đang cài đặt. Tại thời điểm lập tài liệu: Laravel 13.34.0, PHPUnit 12.5.37; môi trường dự án theo hướng dẫn dùng PHP 8.5.
- Trước khi sử dụng API của package, kiểm tra lại bằng `composer show <vendor/package>` hoặc `composer show --direct`; với JavaScript, kiểm tra `package.json` và lockfile nếu có.
- Giao diện mặc định dùng Blade, Blade Components và Tailwind theo nền dự án hiện có.
- UI quản trị lấy trang chủ làm chuẩn nhận diện: nền than `#101c20`, xanh lime `#bbf451`, nền sáng `#f5f7f5` và nút bo tròn. Nội dung admin dùng nền sáng để đọc bảng và biểu mẫu.
- Typography dùng Montserrat cho tiêu đề, Be Vietnam Pro cho nội dung trên website, admin và form lái thử.
  Token và `@font-face` dùng chung tại `resources/css/typography.css`; component `site.typography` tải CSS qua Vite.
  Font WOFF2 tiếng Việt/Latin lưu tại `resources/fonts`, kèm giấy phép; dùng `font-display: swap`, không tải font bên ngoài.
- CSS admin được quản lý bằng Tailwind CSS 4: token trong `@theme`, utility và `@apply` tại `resources/css/app.css`; JavaScript admin tại `resources/js/admin.js`, build bằng Vite. Không thêm CSS admin mới vào `public/css/studio.css`.
- Trang chi tiết xe, danh sách và chi tiết bài viết phía client dùng cùng nhận diện trang chủ; Tailwind tại `resources/css/site.css` và JavaScript tại `resources/js/site.js`, build bằng Vite. Khoảng cách mũi tên select dùng chung tại `resources/css/select.css`.
- Đa ngôn ngữ chỉ áp dụng giao diện client: tiếng Việt tại URL hiện có, tiếng Anh với tiền tố `/en`.
  Dùng localization Laravel, không thêm package; ngôn ngữ theo URL và các liên kết giữ ngữ cảnh ngôn ngữ.
  Bộ chuyển VI/EN giữ trang, bộ lọc và màu xe; metadata ngôn ngữ phản ánh giao diện đang chọn.
  Nội dung xe, ưu đãi, bài viết và thông tin nhập từ admin giữ nguyên, không tự dịch dữ liệu.
- Navbar trang chủ và các trang client dùng chung component, bố cục và mục điều hướng.
  Bộ chọn ngôn ngữ là dropdown cờ Việt Nam/Anh, đặt ở ngoài cùng bên phải, kích thước gọn.
  Mobile giữ nút menu và chọn ngôn ngữ trên hàng đầu; CTA lái thử nằm trong menu khi thiếu không gian.
- Logo website dùng PNG nền trong suốt tại `public/assets/vinfast/logo.png`.
- Trang ưu đãi hiển thị quyền lợi, xe áp dụng, thời hạn và bộ lọc theo dòng xe; không tự suy diễn mức giảm giá.
  Phần ảnh trong card ưu đãi dùng nền trung tính, không dùng gradient xanh.
- Trang chi tiết xe có bộ chọn màu ngoại thất, đổi ảnh với hiệu ứng nhẹ và hỗ trợ giảm chuyển động.
  Danh mục màu và ảnh tham chiếu nguồn VinFast Việt Nam, lưu nguồn trong dữ liệu seed; ưu tiên ảnh xe nền trong suốt.
  Nút chọn màu hiển thị mã HEX tương ứng; xe hai màu hiển thị cả màu thân và màu nóc.
- CTA đăng ký lái thử mở drawer từ bên phải trên trang hiện tại, chọn sẵn xe khi có ngữ cảnh.
  Header gọn; hiển thị ảnh, tên và phiên bản xe vừa chọn, thu gọn phần chọn xe và cho phép đổi xe khi cần.
  Form gửi tới endpoint khách hàng hiện có và hiển thị validation/thành công ngay trong drawer.
  Component dùng chung cho trang chủ và layout client; khi JavaScript không khả dụng, dùng trang liên hệ.
- Dùng lại Blade Components cho field input/textarea, select, chọn ảnh, tiêu đề, điều hướng, bảng, phân trang, trạng thái rỗng, badge và thống kê. Field cần label, ID riêng, lỗi liên kết bằng ARIA và giữ giá trị cũ sau validation.
- Cỡ chữ trang chủ là chuẩn chung cho client và quản trị: nội dung 14px, chữ phụ/nút/ô nhập 12px,
  nhãn form 11px, chú thích 10px; tiêu đề trang 40–61px (mobile 36px), tiêu đề section 28–40px,
  tiêu đề card/nhóm form 18px. Token cỡ chữ dùng chung trong `resources/css/typography.css`,
  ánh xạ vào theme Tailwind để utility và component không dùng hai hệ cỡ chữ khác nhau.
- Typography admin phân biệt rõ tiêu đề, nội dung, nhãn và gợi ý bằng token màu và độ đậm;
  tiêu đề 600–700, nhãn 500, nội dung và giá trị nhập 400. Không để giá trị nhập kế thừa độ đậm của nhãn.
- Bảng và phân trang nằm trong cùng một khối. Danh sách bản ghi hiển thị ID, ngày tạo và ngày cập nhật;
  báo cáo tổng hợp theo ngày giữ các cột số liệu tổng hợp. Thao tác từng dòng dùng icon với nhãn ARIA,
  tooltip và vùng bấm tối thiểu 44px; giữ nguyên kiểm tra quyền và xác nhận xóa.
- Tiêu đề từng cột dữ liệu trong bảng admin hỗ trợ sắp xếp tăng/giảm bằng thao tác nhấn;
  hiển thị chiều hiện tại bằng mũi tên và `aria-sort`. Tìm kiếm và bộ lọc nằm ở thanh công cụ trên bảng.
  Sắp xếp và lọc ở database trước khi phân trang; cột và chiều sắp xếp phải nằm trong danh sách cho phép.
  Thanh phân trang luôn hiển thị số kết quả và điều hướng, kể cả khi chỉ có một trang hoặc chưa có dữ liệu;
  cho chọn 10/20/50/100 dòng, giữ bộ lọc và chiều sắp xếp khi chuyển trang,
  về trang đầu khi đổi bộ lọc, số dòng hoặc cột/chiều sắp xếp. Dùng ID làm thứ tự phụ để phân trang ổn định.
  Cột thao tác và checkbox hàng loạt không có sort; thư viện ảnh giữ phân trang 24 ảnh theo bố cục riêng.
- Form khuyến mãi nhóm thông tin, nội dung và xe áp dụng ở cột chính; thời gian, hiển thị và ảnh ở cột phụ.
  Màn hình nhỏ xếp một cột. Chọn nhiều xe bằng checkbox, giữ cả trạng thái bỏ chọn toàn bộ sau validation lỗi.
- Admin dùng controller, Form Request, Policy và Blade của Laravel; phân quyền bằng Enum vai trò, Gate và Policy, không thêm package admin.
- Trình soạn thảo dùng Markdown, render bằng `league/commonmark` đang có sẵn với HTML bị loại bỏ và liên kết nguy hiểm bị chặn. Hỗ trợ bảng và mục lục.
- Editor bài viết dùng Tiptap 3, được người dùng cho phép thêm thư viện, với chế độ trực quan và Markdown.
  Dữ liệu lưu vẫn là Markdown; xem trước và chấm SEO qua endpoint admin có xác thực, CSRF và rate limit.
  CSS editor dùng Tailwind tại `resources/css/editor.css`; JavaScript tại `resources/js/editor.js`.
  Bản khôi phục chỉ lưu trong sessionStorage theo người dùng và bài viết, không thay thế thao tác lưu vào database.
- Sidebar desktop cho phép thu gọn còn icon, giữ tên điều hướng cho trình đọc màn hình và lưu lựa chọn trên trình duyệt.
  Lịch lái thử hỗ trợ chế độ lịch tháng và danh sách; mobile dùng lịch theo ngày, giữ cùng bộ lọc và phạm vi phân quyền.
- Ảnh được kiểm tra và chuyển sang WebP bằng GD, lưu trên disk local riêng tư và phục vụ qua endpoint ảnh. Chưa chốt dịch vụ lưu trữ production.
- Mã màu xe hỗ trợ nhập HEX và color picker đồng bộ; màu nóc xe có thể để trống.
- Bộ chọn ảnh admin hỗ trợ tải trực tiếp hoặc modal thư viện trực quan, tìm kiếm và phân trang toàn bộ ảnh.
  Thư viện hỗ trợ tải tối đa 20 ảnh mỗi lần, xem trước và sửa alt từng ảnh (mặc định tên file), bỏ ảnh trước khi gửi.
  Giao diện kiểm tra giới hạn mỗi file và tổng lô theo cấu hình PHP hiện tại, trong giới hạn validation của ứng dụng.
  Upload nhiều ảnh được lưu đồng bộ hoặc hoàn tác cả dữ liệu và file khi lỗi.
  Thư viện và modal hỗ trợ sắp xếp tên A–Z/Z–A, ngày thêm mới/cũ, cùng lựa chọn 2/3/4/6 ảnh mỗi hàng trên desktop.
- Vai trò hiện tại: Admin quản lý toàn bộ; Manager quản lý nội dung, xe, khách và báo cáo; Sales xử lý khách được phân công; Editor viết nháp của mình, quản lý phân loại và ảnh. Chỉ Admin/Manager được xuất bản bài.
- Local dùng MySQL `vf_studio`. Kiểm thử tự động dùng SQLite in-memory, không chạy RefreshDatabase trên database local hoặc production.
- Production phải cấu hình APP_URL đúng tên miền và dùng HTTPS; APP_DEBUG bị vô hiệu hóa ở production. Session được mã hóa, cookie HttpOnly/SameSite và Secure khi production.
- Lịch xuất bản dùng scheduler; thông báo khách mới dùng database notification qua queue. Khi vận hành cần tiến trình scheduler và queue worker.
- Không thêm hoặc đổi dependency, framework frontend hay cấu trúc thư mục gốc khi chưa được người dùng đồng ý.

## 3. Kiến trúc: MVC kết hợp Action

Luồng ghi dữ liệu có nghiệp vụ: `Route → Form Request → Controller → Action → Eloquent Model`.

- **Route:** khai báo endpoint, tên route và middleware; không chứa nghiệp vụ.
- **Form Request:** validation đầu vào và kiểm tra quyền gửi yêu cầu, sử dụng Policy khi phù hợp. Không ghi dữ liệu trong validation.
- **Controller:** điều phối request, gọi Action khi cần và trả view, redirect hoặc API Resource. Không chứa luồng nghiệp vụ nhiều bước.
- **Action:** thực hiện một mục đích nghiệp vụ, ví dụ `PublishPost`, `SchedulePost`, `AssignLead`. Dùng phương thức `handle(...)` với kiểu tham số và kiểu trả về rõ ràng; không phụ thuộc HTTP request hoặc trả HTTP response.
- **Model:** quan hệ, casts, query scopes và hành vi gắn trực tiếp với dữ liệu của model. Không chứa điều phối HTTP, gửi thông báo hay luồng xử lý liên quan nhiều chức năng.
- **Policy:** quyết định quyền trên đối tượng. Giao diện chỉ phản ánh quyền; máy chủ phải kiểm tra lại khi đọc hoặc thay đổi dữ liệu.
- **Job:** thực thi nền công việc tốn thời gian hoặc cần retry. Dùng lại Action khi có cùng nghiệp vụ.
- **View / Component:** trình bày dữ liệu; không truy vấn database hoặc thực hiện nghiệp vụ.

CRUD đơn giản có thể dùng trực tiếp Eloquent trong controller. Tách Action khi có chuyển trạng thái, nhiều bước, transaction hoặc logic dùng lại. Không bắt buộc tạo Action cho mọi phương thức chỉ để bọc một lệnh Eloquent.

Không mặc định thêm Repository, lớp Service tổng hợp hoặc interface cho mọi class. Chỉ thêm abstraction khi có nhu cầu cụ thể, ví dụ thay dịch vụ bên ngoài hoặc dùng chung một hợp đồng giữa nhiều implementation.

## 4. Tổ chức và đặt tên

- Giữ cấu trúc Laravel hiện có; nhóm chức năng trong thư mục tiêu chuẩn khi cần. Các đường dẫn dưới đây là quy ước cho triển khai sau này, không yêu cầu tạo thư mục trống.
- Controller admin: `app/Http/Controllers/Admin/PostController.php`; controller công khai: `app/Http/Controllers/PostController.php`.
- Form Request: `app/Http/Requests/Admin/Posts/StorePostRequest.php`.
- Action theo chức năng: `app/Actions/Posts/PublishPost.php`, `app/Actions/Leads/AssignLead.php`.
- Model, Policy, Job, Enum lần lượt nằm trong `app/Models`, `app/Policies`, `app/Jobs`, `app/Enums`.
- View admin nằm trong `resources/views/admin/{feature}`; component dùng chung trong `resources/views/components`.
- Dùng tên tiếng Anh rõ nghĩa trong code. Giao diện admin dùng tiếng Việt; client hỗ trợ tiếng Việt và tiếng Anh.
- Tên class dùng PascalCase, phương thức và biến dùng camelCase, cột database dùng snake_case. Enum case dùng TitleCase theo hướng dẫn dự án.
- Dùng tên route có ý nghĩa, ví dụ `admin.posts.index`, `posts.show`; tạo URL bằng named route.
- Không gom nghiệp vụ vào `Helper`, `Utils`, `CommonService` hoặc `BaseRepository`. Kiểm tra code hiện có trước khi thêm thành phần dùng chung.
- Không tạo tầng kế thừa hoặc component tổng quát dựa trên nhu cầu giả định. Tái sử dụng khi có trường hợp sử dụng thực tế.

## 5. Dữ liệu và nghiệp vụ

- Mọi thay đổi schema phải qua migration; không sửa database thủ công để thay migration.
- Dùng foreign key, index và unique constraint cho các quan hệ hoặc tính duy nhất cần bảo đảm. Validation không thay thế constraint database.
- Chỉ ghi các trường đã validation và được phép chỉnh sửa; không truyền toàn bộ request vào thao tác ghi model.
- Dùng transaction cho những thay đổi dữ liệu phải thành công hoặc thất bại cùng nhau.
- Dispatch công việc phụ thuộc dữ liệu đã ghi sau khi transaction commit. Job có retry phải tránh tạo hiệu ứng lặp ngoài ý muốn.
- Chuyển trạng thái phải có điều kiện rõ ràng và được kiểm tra ở máy chủ. Dùng Enum khi tập trạng thái đã xác định.
- Phân trang danh sách, eager load quan hệ cần dùng, tránh N+1. Không tải toàn bộ bảng để lọc trong PHP.
- Khi cần cache, xác định khóa, thời hạn và cách vô hiệu hóa khi dữ liệu thay đổi.
- Chọn cách xóa hoặc lưu trữ theo từng loại dữ liệu; không mặc định soft delete cho mọi bảng.

## 6. Bài viết và SEO

- Bài viết hỗ trợ trạng thái nháp, hẹn giờ và đã xuất bản. Chỉ nội dung đã xuất bản và đến thời điểm cho phép mới xuất hiện trên website công khai, sitemap và danh sách liên quan.
- Trang xem trước nội dung chưa xuất bản phải được bảo vệ và có chỉ dẫn `noindex`; không để lộ qua endpoint công khai.
- Dùng múi giờ cấu hình của ứng dụng cho nhập và hiển thị lịch đăng; so sánh thời gian nhất quán. Scheduler phải gọi lại nghiệp vụ xuất bản đã kiểm tra điều kiện, kể cả khi chạy trễ hoặc chạy lại.
- Slug phải duy nhất trong phạm vi URL bài viết. Khi đổi URL đã xuất bản, lưu chuyển hướng 301 từ URL cũ; ngăn vòng lặp, chuỗi chuyển hướng và xung đột với URL hiện tại.
- Tách tiêu đề bài, tiêu đề SEO, mô tả SEO, canonical và ảnh chia sẻ thành dữ liệu rõ ràng. Có giá trị mặc định hợp lý khi trường tùy chọn chưa được nhập.
- URL canonical và chuyển hướng phải được validation; không cho phép tạo URL tùy ý dẫn tới chuyển hướng không kiểm soát.
- Metadata, sitemap và structured data phải phản ánh đúng nội dung hiển thị. Không đưa dữ liệu giả vào schema để đạt điểm SEO.
- Bài viết có thể liên kết danh mục, thẻ và mẫu xe. Dùng quan hệ dữ liệu để tạo liên kết và CTA, tránh hardcode URL trong template.
- Nội dung từ trình soạn thảo phải được lọc HTML theo danh sách cho phép ở máy chủ trước khi render. Chỉ render HTML không escape khi nội dung đã được xử lý an toàn; escape các trường văn bản còn lại.
- Ảnh hỗ trợ alt, kích thước phù hợp và tối ưu dung lượng. Không bắt buộc nhồi từ khóa hoặc đạt một độ dài bài cố định.
- Lưu `focus_keyword` để xác định chủ đề chính. Checklist SEO 100 điểm là gợi ý biên tập nội bộ,
  kiểm tra tiêu đề, mô tả, slug, chủ đề, bố cục, alt, liên kết, độ sâu và tóm tắt; không phải điểm của Google.
  Điểm không chặn xuất bản; nội dung vẫn cần được kiểm tra sự chính xác và giá trị cho người đọc.
- Seed thông số và ảnh dùng nguồn VinFast, ghi rõ phiên bản và nguồn trong dữ liệu. Chạy lại phải giữ chỉnh sửa thủ công.
  Bộ 20 bài mẫu chỉ chạy ở local/testing; không tạo tài khoản quản trị mặc định hoặc mật khẩu đăng nhập công bố.
- Danh sách admin cần phân trang, tìm kiếm và lọc trạng thái/danh mục/tác giả. Thao tác hàng loạt phải kiểm tra quyền trên từng đối tượng.

## 7. Bảo mật và vận hành admin

- Khu vực admin cần xác thực và phân quyền; áp dụng nguyên tắc chỉ cấp quyền cần thiết. Vai trò cụ thể sẽ được chốt khi triển khai phân quyền.
- Kiểm tra quyền cả với thao tác thông thường, xuất dữ liệu, upload, xem trước và thao tác hàng loạt.
- Upload phải kiểm tra loại file, kích thước và quyền; dùng tên/path do hệ thống kiểm soát. Tài liệu khách hàng cần lưu riêng tư và chỉ tải qua kiểm tra quyền.
- Không ghi mật khẩu, token, nội dung `.env` hoặc dữ liệu cá nhân không cần thiết vào log. Không commit secret.
- Biểu mẫu tư vấn, báo giá và lái thử cần validation và rate limit phù hợp. Giữ CSRF cho request web thay đổi dữ liệu.
- Nhật ký quản trị ghi người thực hiện, hành động và đối tượng cho thay đổi quan trọng; không sao chép dữ liệu nhạy cảm vào nhật ký.
- Không thêm API công khai khi chưa có nhu cầu. Khi cần API, tuân thủ quy ước versioning và API Resource của dự án.

## 8. Giao diện và chất lượng code

### Độ dài dòng và cách xuống dòng

- Giới hạn dòng code là **120 ký tự**, tính cả phần thụt lề, áp dụng cho PHP, Blade/HTML, JavaScript và CSS. Xuống dòng sớm khi giúp code dễ đọc; không cần chờ đến đủ 120 ký tự.
- PHP: tách chuỗi gọi phương thức dài thành mỗi lời gọi một dòng. Khi danh sách tham số hoặc phần tử mảng dài, đặt mỗi tham số hoặc phần tử trên một dòng, theo định dạng Laravel Pint.
- Blade/HTML: với thẻ có nhiều thuộc tính hoặc vượt giới hạn, đặt mỗi thuộc tính trên một dòng và căn thụt lề nhất quán. Không ngắt nội dung theo cách làm thay đổi khoảng trắng hiển thị hoặc ý nghĩa của template.
- JavaScript/CSS: tách biểu thức, danh sách tham số và giá trị dài tại vị trí hợp lý; mỗi khai báo CSS trên một dòng riêng. Tuân thủ formatter hiện có của dự án nếu được cấu hình.
- URL, chuỗi không thể tách an toàn và giá trị mà việc ngắt dòng làm thay đổi ý nghĩa được phép vượt 120 ký tự. Không sửa file sinh tự động hoặc lockfile chỉ để đáp ứng giới hạn này.
- Laravel Pint là chuẩn định dạng PHP nhưng không bảo đảm mọi dòng dưới 120 ký tự; cần kiểm tra độ dài dòng khi review. Không tự cài thêm formatter hoặc linter.
- Quy tắc 120 ký tự áp dụng cho code; văn bản Markdown được xuống dòng theo đoạn và cấu trúc nội dung để dễ đọc.

### Giao diện và khả năng bảo trì

- Tái sử dụng component cho trường nhập, validation error, bảng, phân trang, trạng thái và xác nhận thao tác khi có nhu cầu dùng chung.
- Giao diện cần trạng thái rỗng, lỗi, thành công và phản hồi khi xử lý; label rõ ràng, dùng được bằng bàn phím và trên màn hình nhỏ.
- Không đặt bí mật hoặc logic phân quyền chỉ ở JavaScript. Giữ code JavaScript trong hệ thống asset hiện có, tránh rải script lặp lại trong view.
- Mỗi thay đổi tập trung vào một mục đích. Không refactor phần không liên quan nếu chưa cần cho yêu cầu hiện tại.
- Không nuốt exception bằng catch rỗng hoặc trả thành công khi xử lý thất bại. Thông báo cho người dùng cần dễ hiểu, chi tiết chẩn đoán nằm trong log phù hợp.

## 9. Kiểm thử và điều kiện hoàn thành

- Ưu tiên feature test kiểm tra hành vi thật: validation, phân quyền, thay đổi dữ liệu và kết quả HTTP. Unit test dành cho logic độc lập khi hữu ích.
- Dùng factory, cô lập dữ liệu test và fake dịch vụ bên ngoài; không gọi dịch vụ thật hoặc thay đổi dữ liệu production trong test.
- Với bài viết, kiểm tra các ranh giới quan trọng khi triển khai: nháp không công khai, lịch đăng, quyền xuất bản, slug trùng và chuyển hướng URL cũ.
- Với khách hàng và admin, kiểm tra truy cập trái quyền, dữ liệu không hợp lệ và quyền thực hiện thao tác hàng loạt khi có chức năng đó.
- Đọc skill `testing-best-practices` khi làm việc với test nếu có; nếu không tìm thấy, báo rõ và áp dụng quy tắc PHPUnit hiện có.
- Chạy test hẹp nhất bao phủ thay đổi: `php artisan test --compact <path>` hoặc filter phù hợp. Chạy lại test sau mỗi lần sửa test.
- Khi sửa PHP, chạy `vendor/bin/pint --dirty --format agent`. Khi sửa frontend, chạy `npm run build` và kiểm tra giao diện liên quan khi có môi trường chạy.
- Chỉ coi công việc hoàn thành khi nghiệp vụ, validation, quyền truy cập và kiểm tra liên quan đã được xử lý. Báo rõ những kiểm tra chưa chạy hoặc giới hạn còn lại.
- Không tạo tài liệu mới ngoài yêu cầu. Giữ các quyết định kiến trúc chung tại tài liệu này để tránh nhiều bản quy tắc mâu thuẫn.
