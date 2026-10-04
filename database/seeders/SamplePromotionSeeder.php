<?php

namespace Database\Seeders;

use App\Models\Promotion;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SamplePromotionSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Ưu đãi mẫu chỉ được tạo trong môi trường local hoặc testing.');

            return;
        }

        $vehicles = Vehicle::query()->active()->with('media')->get()->keyBy('slug');
        $created = DB::transaction(function () use ($vehicles): int {
            $created = 0;

            foreach ($this->samples() as $sample) {
                if (Promotion::query()->where('slug', $sample['slug'])->exists()) {
                    continue;
                }

                $applicableVehicles = collect($sample['vehicles'])
                    ->map(fn (string $slug): ?Vehicle => $vehicles->get($slug))
                    ->filter();

                if ($applicableVehicles->isEmpty()) {
                    $this->command?->warn('Bỏ qua ưu đãi chưa có xe áp dụng: '.$sample['title']);

                    continue;
                }

                $coverId = $applicableVehicles
                    ->map(fn (Vehicle $vehicle): ?int => $vehicle->media?->id)
                    ->filter()->first();
                $body = "> Nội dung ưu đãi mẫu phục vụ bản demo, không phải chính sách bán hàng chính thức.\n\n".
                    $sample['body']."\n\n## Cách nhận tư vấn\n\n".
                    'Chọn xe phù hợp và gửi yêu cầu tư vấn để được liên hệ về phiên bản, '.
                    "thời gian nhận xe và các điều kiện áp dụng.\n\n".
                    '[Nhận tư vấn ưu đãi]('.route('contact', ['type' => 'consultation'], false).')';

                $promotion = Promotion::query()->create([
                    'title' => $sample['title'],
                    'slug' => $sample['slug'],
                    'body' => $body,
                    'media_id' => $coverId,
                    'starts_at' => now()->startOfMonth(),
                    'ends_at' => now()->addDays($sample['validity_days'])->endOfDay(),
                    'is_active' => true,
                ]);
                $promotion->vehicles()->sync($applicableVehicles->pluck('id')->all());
                $created++;
            }

            return $created;
        });

        $this->command?->info('Đã tạo '.$created.' ưu đãi mẫu; giữ nguyên các ưu đãi đã tồn tại.');
    }

    /**
     * @return list<array{slug: string, title: string, body: string, vehicles: list<string>, validity_days: int}>
     */
    private function samples(): array
    {
        return [
            [
                'slug' => 'goi-qua-tang-khoi-dau-cung-vf5',
                'title' => 'Gói quà tặng khởi đầu cùng VF 5',
                'vehicles' => ['vf5'],
                'validity_days' => 30,
                'body' => "## Khởi đầu hành trình thuần điện\n\n".
                    "Gói ưu đãi minh họa cho khách hàng chọn VF 5 làm chiếc xe điện đầu tiên.\n\n".
                    "### Quyền lợi trong gói mẫu\n\n".
                    "- Bộ thảm sàn và phụ kiện chăm sóc xe.\n".
                    "- Hướng dẫn sử dụng xe, ứng dụng và tìm trạm sạc.\n".
                    '- Hỗ trợ lên lịch lái thử và bàn giao theo thời gian mong muốn.',
            ],
            [
                'slug' => 'vf6-dong-hanh-cung-gia-dinh-tre',
                'title' => 'VF 6 đồng hành cùng gia đình trẻ',
                'vehicles' => ['vf6'],
                'validity_days' => 45,
                'body' => "## Thêm an tâm cho những chuyến đi gia đình\n\n".
                    "Gói mẫu dành cho khách hàng quan tâm VF 6 Eco hoặc VF 6 Plus.\n\n".
                    "### Quyền lợi trong gói mẫu\n\n".
                    "- Gói hỗ trợ bảo hiểm thân vỏ năm đầu.\n".
                    "- Bộ phụ kiện bảo vệ nội thất.\n".
                    '- Tư vấn lựa chọn phiên bản và chi phí sử dụng theo nhu cầu gia đình.',
            ],
            [
                'slug' => 'doi-xe-cu-len-vf7',
                'title' => 'Đổi xe cũ, khởi hành cùng VF 7',
                'vehicles' => ['vf7'],
                'validity_days' => 60,
                'body' => "## Một bước chuyển cho hành trình mới\n\n".
                    "Chương trình mẫu cho khách hàng cân nhắc đổi xe đang sử dụng sang VF 7.\n\n".
                    "### Quyền lợi trong gói mẫu\n\n".
                    "- Hỗ trợ kiểm tra và định giá xe hiện tại.\n".
                    "- Tư vấn các phiên bản VF 7 theo nhu cầu vận hành.\n".
                    "- Gói quà tặng phụ kiện khi hoàn tất bàn giao.\n\n".
                    'Phương án đổi xe được trao đổi sau khi kiểm tra tình trạng xe thực tế.',
            ],
            [
                'slug' => 'vf8-san-sang-cho-hanh-trinh-xa',
                'title' => 'VF 8 sẵn sàng cho hành trình xa',
                'vehicles' => ['vf8'],
                'validity_days' => 60,
                'body' => "## Chuẩn bị cho chuyến đi tiếp theo\n\n".
                    "Gói ưu đãi mẫu dành cho khách hàng lựa chọn VF 8 cho nhu cầu đi lại và du lịch.\n\n".
                    "### Quyền lợi trong gói mẫu\n\n".
                    "- Bộ phụ kiện bảo vệ khoang hành lý.\n".
                    "- Hướng dẫn lập lộ trình và điểm dừng sạc.\n".
                    '- Buổi trải nghiệm các tính năng hỗ trợ lái khi nhận xe.',
            ],
            [
                'slug' => 'vf9-trai-nghiem-cho-ca-gia-dinh',
                'title' => 'VF 9: trải nghiệm dành cho cả gia đình',
                'vehicles' => ['vf9'],
                'validity_days' => 90,
                'body' => "## Không gian cho những người bạn yêu thương\n\n".
                    "Gói mẫu cho gia đình muốn trải nghiệm VF 9 trước khi lựa chọn cấu hình phù hợp.\n\n".
                    "### Quyền lợi trong gói mẫu\n\n".
                    "- Lịch lái thử riêng cho các thành viên trong gia đình.\n".
                    "- Gói phụ kiện chăm sóc và bảo vệ nội thất.\n".
                    '- Tư vấn bố trí chỗ ngồi, khoang hành lý và nhu cầu di chuyển.',
            ],
            [
                'slug' => 'xe-dien-do-thi-vf5-vf6',
                'title' => 'Ưu đãi xe điện đô thị VF 5 và VF 6',
                'vehicles' => ['vf5', 'vf6'],
                'validity_days' => 30,
                'body' => "## Nhẹ nhàng hơn trên mỗi cung đường phố\n\n".
                    "Gói mẫu giúp khách hàng so sánh VF 5 và VF 6 cho nhu cầu đi lại hằng ngày.\n\n".
                    "### Quyền lợi trong gói mẫu\n\n".
                    "- Trải nghiệm hai mẫu xe trong cùng một buổi hẹn.\n".
                    "- Bộ quà tặng chăm sóc xe khi nhận bàn giao.\n".
                    '- Tư vấn điểm sạc phù hợp với nơi ở và nơi làm việc.',
            ],
            [
                'slug' => 'cuoi-tuan-lai-thu-nhan-qua',
                'title' => 'Cuối tuần lái thử, nhận quà trải nghiệm',
                'vehicles' => ['vf7', 'vf5', 'vf6', 'vf8', 'vf9'],
                'validity_days' => 45,
                'body' => "## Hẹn gặp bạn tại VinFast Hùng Vương\n\n".
                    "Chương trình mẫu cho khách hàng đăng ký trải nghiệm xe vào cuối tuần.\n\n".
                    "### Quyền lợi trong gói mẫu\n\n".
                    "- Chọn mẫu xe và khung giờ trải nghiệm phù hợp.\n".
                    "- Quà lưu niệm sau buổi lái thử.\n".
                    "- Tư vấn trực tiếp về phiên bản và quy trình nhận xe.\n\n".
                    'Lịch hẹn và khả năng đáp ứng xe được xác nhận khi nhân viên liên hệ.',
            ],
            [
                'slug' => 'goi-tu-van-sac-tai-nha',
                'title' => 'Gói đồng hành: tư vấn sạc tại nhà',
                'vehicles' => ['vf8', 'vf5', 'vf6', 'vf7', 'vf9'],
                'validity_days' => 90,
                'body' => "## Chủ động kế hoạch sạc hằng ngày\n\n".
                    "Gói mẫu dành cho khách hàng muốn tìm hiểu phương án sạc phù hợp tại nơi ở.\n\n".
                    "### Quyền lợi trong gói mẫu\n\n".
                    "- Tư vấn nhu cầu và thời gian sạc dự kiến.\n".
                    "- Hỗ trợ đặt lịch khảo sát điều kiện lắp đặt.\n".
                    "- Hướng dẫn sử dụng điểm sạc công cộng khi cần.\n\n".
                    'Thiết bị, chi phí và phương án lắp đặt được tư vấn theo điều kiện khảo sát thực tế.',
            ],
        ];
    }
}
