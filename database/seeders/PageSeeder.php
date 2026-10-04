<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            'gioi-thieu' => ['title' => 'VinFast Hùng Vương',
                'body' => "## Đồng hành cùng hành trình thuần điện\n\nWebsite giúp bạn tìm ".
                    'hiểu các dòng xe VinFast, xem bài viết và liên hệ chuyên '.
                    "viên.\n\n## Tư vấn theo nhu cầu\n\nGửi yêu cầu báo giá hoặc đăng ".
                    'ký lái thử. Đại lý sẽ liên hệ để xác nhận thông tin, phiên bản '.
                    'và lịch phù hợp.'],
            'faq' => ['title' => 'Câu hỏi thường gặp',
                'body' => "## Làm thế nào để đăng ký lái thử?\n\nChọn mẫu xe và thời gian ".
                    'mong muốn tại trang Liên hệ. Lịch chỉ được xác nhận sau khi nhân '.
                    "viên liên hệ với bạn.\n\n## Giá trên website có phải giá cuối ".
                    "cùng?\n\nGiá hiển thị là giá tham khảo. Hãy gửi yêu cầu báo giá ".
                    "để xác nhận phiên bản, ưu đãi và các chi phí liên quan.\n\n## ".
                    "Tôi có cần tài khoản để gửi yêu cầu?\n\nKhông. Bạn chỉ cần cung ".
                    'cấp thông tin liên hệ và đồng ý cho đại lý xử lý yêu cầu.'],
            'bao-mat' => ['title' => 'Thông tin về xử lý dữ liệu cá nhân',
                'body' => "## Dữ liệu bạn cung cấp\n\nKhi gửi biểu mẫu, website tiếp nhận ".
                    'họ tên, số điện thoại, email nếu có, xe quan tâm, nội dung yêu '.
                    "cầu và thời gian lái thử mong muốn.\n\n## Mục đích sử ".
                    "dụng\n\nThông tin được dùng để liên hệ tư vấn, báo giá, xác nhận ".
                    'lịch lái thử và theo dõi xử lý yêu cầu. Nhân viên truy cập theo '.
                    "quyền và phạm vi được phân công.\n\n## Bảo vệ thông ".
                    "tin\n\nWebsite dùng phiên làm việc để xử lý biểu mẫu. Thông tin ".
                    'liên hệ và ghi chú được mã hóa khi lưu; khu vực quản trị cần '.
                    "đăng nhập.\n\n## Liên hệ về dữ liệu\n\nĐể đề nghị kiểm tra, sửa ".
                    'hoặc xóa thông tin đã gửi, vui lòng liên hệ đại lý qua trang '.
                    'Liên hệ. Không gửi mật khẩu, thông tin thanh toán hoặc giấy tờ '.
                    'cá nhân trong biểu mẫu tư vấn.'],
            'dieu-khoan' => ['title' => 'Thông tin sử dụng website',
                'body' => "## Thông tin tham khảo\n\nNội dung website hỗ trợ tìm hiểu xe và ".
                    'liên hệ đại lý. Thông số, giá và ưu đãi cần được xác nhận theo '.
                    "phiên bản và thời điểm tư vấn.\n\n## Yêu cầu tư vấn và lịch ".
                    "hẹn\n\nGửi biểu mẫu không tạo đơn mua xe, giao dịch thanh toán ".
                    'hoặc lịch hẹn đã xác nhận. Nhân viên sẽ liên hệ để thống nhất '.
                    "thông tin.\n\n## Sử dụng biểu mẫu\n\nVui lòng cung cấp thông tin ".
                    'liên hệ đúng và chỉ gửi nội dung liên quan đến yêu cầu của bạn.'],
        ];
        foreach ($pages as $slug => $data) {
            $page = Page::query()->firstOrCreate(['slug' => $slug], [...$data, 'is_active' => true]);
            if ($page->body === 'Nội dung đang được đại lý hoàn thiện.' && ! $page->is_active) {
                $page->update([...$data, 'is_active' => true]);
            }
        }
    }
}
