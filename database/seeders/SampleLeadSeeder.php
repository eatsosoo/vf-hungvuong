<?php

namespace Database\Seeders;

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SampleLeadSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Khách hàng và lịch lái thử mẫu chỉ được tạo trong môi trường local hoặc testing.');

            return;
        }

        $vehicles = Vehicle::query()->where('is_active', true)->with('variants')->orderBy('id')->get();
        if ($vehicles->isEmpty()) {
            $this->command?->warn('Cần có ít nhất một mẫu xe đang hiển thị trước khi tạo yêu cầu mẫu.');

            return;
        }

        $staff = User::query()->where('is_active', true)->whereIn('role', ['admin', 'manager', 'sales'])
            ->orderBy('id')->get();
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();
        $names = [
            'Nguyễn Minh Anh', 'Trần Hoàng Nam', 'Lê Thu Hà', 'Phạm Quốc Bảo', 'Hoàng Ngọc Linh', 'Vũ Đức Huy',
            'Đặng Thanh Mai', 'Bùi Gia Khánh', 'Đỗ Hải Yến', 'Ngô Tuấn Kiệt', 'Dương Bảo Ngọc', 'Lý Thành Đạt',
            'Nguyễn Thảo Vy', 'Trần Quang Minh', 'Lê Phương Thảo', 'Phạm Nhật Duy', 'Hoàng Mỹ Duyên', 'Vũ Anh Khoa',
            'Đặng Kim Chi', 'Bùi Trung Hiếu', 'Đỗ Minh Châu', 'Ngô Đức Thành', 'Dương Ngọc Ánh', 'Lý Gia Hân',
        ];
        $counts = DB::transaction(function () use ($vehicles, $staff, $today, $names): array {
            $counts = ['test_drive' => 0, 'consultation' => 0, 'quote' => 0];
            for ($index = 0; $index < 48; $index++) {
                $source = sprintf('demo:lead:%03d', $index + 1);
                if (Lead::query()->where('source', $source)->exists()) {
                    continue;
                }

                $type = $index < 24 ? 'test_drive' : ($index < 36 ? 'consultation' : 'quote');
                $states = LeadStatus::forType($type);
                $status = $states[$index % count($states)];
                $vehicle = $vehicles[$index % $vehicles->count()];
                $variant = $vehicle->variants->isEmpty() ? null
                    : $vehicle->variants[$index % $vehicle->variants->count()];
                $assignee = $status === LeadStatus::New || $staff->isEmpty() ? null : $staff[$index % $staff->count()];
                $slot = null;
                if ($type === 'test_drive' && ! in_array($index, [20, 21], true)) {
                    $offset = match ($status) {
                        LeadStatus::Completed => -1 - intdiv($index, 5),
                        LeadStatus::Cancelled => intdiv($index, 5) - 2,
                        default => intdiv($index, 2),
                    };
                    $slot = $today->addDays($offset)->setTime(9 + ($index % 4) * 2, $index % 2 ? 30 : 0);
                }
                $registeredDay = $slot && $slot->lessThan($today) ? $slot : $today;
                $createdAt = $registeredDay->subDays(2 + $index % 14)->setTime(8, 15);
                $message = match ($type) {
                    'test_drive' => 'Muốn trải nghiệm không gian, vận hành và tính năng hỗ trợ lái cùng gia đình.',
                    'quote' => 'Cần báo giá, thông tin phiên bản và phương án thanh toán để tham khảo.',
                    default => 'Cần tư vấn lựa chọn xe, điểm sạc và chi phí sử dụng theo nhu cầu đi lại.',
                };
                $lead = new Lead([
                    'type' => $type,
                    'name' => '[Mẫu] '.$names[$index % count($names)],
                    'phone' => sprintf('000000%04d', $index + 1),
                    'email' => sprintf('khach-mau-%03d@example.test', $index + 1),
                    'message' => '[Dữ liệu mẫu] '.$message,
                    'source' => $source,
                    'vehicle_id' => $vehicle->id,
                    'vehicle_variant_id' => $variant?->id,
                    'assigned_to' => $assignee?->id,
                    'status' => $status,
                    'preferred_at' => $slot,
                    'appointment_at' => in_array($status, [LeadStatus::Confirmed, LeadStatus::Completed], true)
                        ? $slot : null,
                    'location' => $type === 'test_drive' ? 'Showroom VinFast Hùng Vương — lịch mẫu' : null,
                    'quote_amount' => $type === 'quote' && $status !== LeadStatus::New
                        ? 450000000 + ($index % 12) * 50000000 : null,
                    'quote_details' => $type === 'quote'
                        ? '[Dữ liệu mẫu] Giá minh họa để xem giao diện; không phải báo giá bán xe chính thức.' : null,
                    'consented_at' => $createdAt,
                ]);
                $lead->created_at = $createdAt;
                $lead->updated_at = $status === LeadStatus::New ? $createdAt : $createdAt->addDay();
                $lead->save();
                if ($assignee) {
                    $lead->notes()->create([
                        'user_id' => $assignee->id,
                        'body' => '[Dữ liệu mẫu] '.match ($status) {
                            LeadStatus::Confirmed => 'Đã xác nhận thời gian và địa điểm lái thử.',
                            LeadStatus::Completed => 'Đã hoàn tất buổi lái thử, chờ trao đổi nhu cầu tiếp theo.',
                            LeadStatus::Cancelled => 'Khách đề nghị hủy yêu cầu mẫu.',
                            LeadStatus::Won => 'Đã ghi nhận kết quả thành công trong tình huống minh họa.',
                            LeadStatus::Lost => 'Khách tạm dừng kế hoạch mua xe trong tình huống minh họa.',
                            default => 'Đã trao đổi nhu cầu, chuẩn bị thông tin tư vấn tiếp theo.',
                        },
                    ]);
                }
                $counts[$type]++;
            }

            return $counts;
        });

        $this->command?->info(sprintf(
            'Đã tạo %d lịch lái thử, %d yêu cầu tư vấn, %d yêu cầu báo giá mẫu; giữ nguyên dữ liệu đã có.',
            $counts['test_drive'], $counts['consultation'], $counts['quote'],
        ));
    }
}
