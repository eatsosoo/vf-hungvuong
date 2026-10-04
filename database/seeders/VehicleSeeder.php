<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->catalog() as $slug => $data) {
            $media = $this->seedImage($slug, $data['image'], 'VinFast '.$data['name'].' màu trắng');
            $defaults = [
                'name' => 'VinFast '.$data['name'],
                'segment' => 'SUV điện',
                'description' => $data['description'],
                'specifications' => $data['specifications'],
                'media_id' => $media->id,
                'brochure_url' => $data['brochure_url'] ?? null,
                'is_active' => true,
            ];
            $vehicle = Vehicle::query()->firstOrNew(['slug' => $slug]);
            if (! $vehicle->exists) {
                $vehicle->fill($defaults);
            } else {
                foreach (['segment', 'description', 'specifications', 'media_id', 'brochure_url'] as $field) {
                    if ($vehicle->{$field} === null || $vehicle->{$field} === '' || $vehicle->{$field} === []) {
                        $vehicle->{$field} = $defaults[$field];
                    }
                }
            }
            if ($vehicle->isDirty()) {
                $vehicle->save();
            }
            foreach ($data['variants'] as $variant) {
                $vehicle->variants()->firstOrCreate(['name' => $variant]);
            }
            if (in_array($slug, ['vf8', 'vf9'], true)) {
                $color = $vehicle->colors()->firstOrCreate(['name' => 'Infinity Blanc'], [
                    'media_id' => $media->id,
                ]);
                if ($color->media_id === null) {
                    $color->update(['media_id' => $media->id]);
                }
            }
        }
    }

    private function seedImage(string $slug, string $filename, string $alt): Media
    {
        $path = 'images/'.$slug.'.webp';
        $disk = Storage::disk('local');
        if (! $disk->exists($path)) {
            $source = public_path('assets/vinfast/'.$filename);
            $image = imagecreatefromstring(file_get_contents($source));
            if ($image === false) {
                throw new RuntimeException('Không thể đọc ảnh seed: '.$filename);
            }
            $width = min(1920, imagesx($image));
            $height = max(1, (int) round(imagesy($image) * $width / imagesx($image)));
            $resized = imagescale($image, $width, $height);
            if ($resized === false) {
                throw new RuntimeException('Không thể tối ưu ảnh seed: '.$filename);
            }
            imagepalettetotruecolor($resized);
            imagesavealpha($resized, true);
            ob_start();
            $encoded = imagewebp($resized, null, 82);
            $bytes = ob_get_clean();
            if (! $encoded || ! is_string($bytes)) {
                throw new RuntimeException('Không thể chuyển WebP: '.$filename);
            }
            if (! $disk->put($path, $bytes)) {
                throw new RuntimeException('Không thể lưu ảnh seed: '.$path);
            }
        }
        $bytes = $disk->get($path);
        $info = getimagesizefromstring($bytes);
        if ($info === false) {
            throw new RuntimeException('Ảnh seed đã lưu không hợp lệ: '.$path);
        }

        return Media::query()->firstOrCreate(['path' => $path], [
            'original_name' => $filename,
            'alt' => $alt,
            'width' => $info[0],
            'height' => $info[1],
            'size' => strlen($bytes),
        ]);
    }

    /**
     * @return array<string, array{
     *     name: string, description: string, image: string, image_source?: string,
     *     variants: list<string>, specifications: array<string, string>, brochure_url?: string
     * }>
     */
    private function catalog(): array
    {
        $reference = [
            'Thị trường tham chiếu' => 'Việt Nam',
            'Ngày đối chiếu nguồn' => '04/10/2026',
            'Lưu ý thông số' => 'Thông số theo phiên bản; cần đối chiếu cấu hình xe thực tế trước khi đặt mua.',
            'Lưu ý quãng đường' => 'Kết quả thử nghiệm NEDC/WLTP; quãng đường thực tế phụ thuộc điều kiện sử dụng.',
        ];

        return [
            'vf5' => [
                'name' => 'VF 5',
                'image' => 'vinfast-vf5-white.png',
                'description' => 'SUV điện 5 chỗ với kích thước gọn, phù hợp nhu cầu đi lại hằng ngày trong đô thị.',
                'variants' => ['VF 5 Plus'],
                'specifications' => [
                    'Phiên bản tham chiếu' => 'VF 5 Plus',
                    'Số chỗ' => '5',
                    'Dài × rộng × cao' => '3.967 × 1.723 × 1.579 mm',
                    'Chiều dài cơ sở' => '2.514 mm',
                    'Mô-men xoắn cực đại' => '135 Nm',
                    'Dung lượng pin khả dụng' => '37,23 kWh',
                    'Quãng đường một lần sạc (NEDC)' => '326 km',
                    'Sạc AC tối đa' => '6,6 kW',
                    'Sạc DC tối đa' => 'Khoảng 50 kW',
                    'Sạc nhanh 10–70%' => '33 phút, theo công bố của hãng',
                    'Túi khí' => '6',
                    'Màn hình cảm ứng' => '8 inch',
                    'Điều hòa' => 'Chỉnh cơ, 1 vùng',
                    'Nguồn thông số' => 'https://vinfastauto.com/vn_vi/dat-coc-xe-dien-vf5',
                ] + $reference,
            ],
            'vf6' => [
                'name' => 'VF 6',
                'image' => 'vinfast-vf6-white.png',
                'description' => 'SUV điện 5 chỗ với hai phiên bản Eco và Plus cho nhu cầu cá nhân và gia đình.',
                'variants' => ['VF 6 Eco', 'VF 6 Plus'],
                'specifications' => [
                    'Phiên bản tham chiếu' => 'VF 6 Eco / VF 6 Plus',
                    'Số chỗ' => '5',
                    'Dài × rộng × cao' => '4.241 × 1.834 × 1.580 mm',
                    'Dẫn động' => 'Cầu trước (FWD)',
                    'Mô-men xoắn cực đại' => 'Eco: 250 Nm; Plus: 310 Nm',
                    'Dung lượng pin khả dụng' => '59,6 kWh',
                    'Quãng đường một lần sạc (NEDC)' => 'Eco: 485 km; Plus: 460 km',
                    'Sạc AC tối đa' => '7,2 kW',
                    'Sạc DC tối đa' => '100 kW',
                    'Sạc nhanh 10–70%' => '25 phút, theo công bố của hãng',
                    'Túi khí' => 'Eco: 4; Plus: 7',
                    'Màn hình cảm ứng' => '12,9 inch',
                    'Nguồn thông số' => 'https://vinfastauto.com/vn_vi/dat-coc-xe-dien-vf6',
                ] + $reference,
            ],
            'vf7' => [
                'name' => 'VF 7',
                'image' => 'vinfast-vf7-white.png',
                'description' => 'SUV điện 5 chỗ; phiên bản Eco, Plus và Plus AWD có cấu hình pin và vận hành riêng.',
                'variants' => ['VF 7 Eco', 'VF 7 Plus', 'VF 7 Plus AWD'],
                'specifications' => [
                    'Phiên bản tham chiếu' => 'VF 7 Eco / VF 7 Plus / VF 7 Plus AWD',
                    'Số chỗ' => '5',
                    'Dài × rộng × cao' => '4.545 × 1.890 × 1.635,75 mm',
                    'Chiều dài cơ sở' => '2.840 mm',
                    'Mô-men xoắn cực đại' => 'Eco: 250 Nm; Plus: 310 Nm; Plus AWD: 500 Nm',
                    'Dung lượng pin khả dụng' => 'Eco: 59,6 kWh; Plus / Plus AWD: 70 kWh',
                    'Quãng đường một lần sạc (NEDC)' => 'Eco: 440 km; Plus: 500,5 km; Plus AWD: 469 km',
                    'Sạc AC tối đa' => '7,2 kW',
                    'Sạc DC tối đa' => 'Eco: 100 kW; Plus / Plus AWD: 110 kW',
                    'Túi khí' => 'Eco: 4; Plus: 7',
                    'Màn hình cảm ứng' => '12,9 inch',
                    'Điều hòa' => 'Tự động, 2 vùng',
                    'Nguồn thông số' => 'https://vinfastauto.com/vn_vi/dat-coc-xe-dien-vf7',
                ] + $reference,
            ],
            'vf8' => [
                'name' => 'VF 8',
                'image' => 'vinfast-vf8-white.webp',
                'image_source' => 'https://static-cms-prod.vinfastauto.com/statics/car/VF-8/hinh-anh-xe-VinFast-vf8-ngoai-that-mau-trang-01.webp',
                'description' => 'SUV điện 5 chỗ dành cho hành trình gia đình, với phiên bản Eco và Plus.',
                'variants' => ['VF 8 Eco', 'VF 8 Plus'],
                'specifications' => [
                    'Phiên bản tham chiếu' => 'VF 8 Eco / VF 8 Plus; không phải VF 8 Thế hệ mới 2026',
                    'Số chỗ' => '5',
                    'Dài × rộng × cao' => '4.750 × 1.934 × 1.667 mm',
                    'Chiều dài cơ sở' => '2.950 mm',
                    'Mô-men xoắn cực đại' => 'Eco: 310 Nm; Plus: 620 Nm',
                    'Dung lượng pin khả dụng' => '87,7 kWh',
                    'Quãng đường một lần sạc (NEDC)' => 'Eco: 635 km; Plus: 475 km',
                    'Sạc AC tối đa' => '6,6 kW (1 pha) / 11 kW (3 pha)',
                    'Sạc DC tối đa' => '149 kW',
                    'Túi khí' => '11',
                    'Màn hình cảm ứng' => '15,6 inch',
                    'Điều hòa' => 'Tự động, 2 vùng',
                    'Nguồn thông số' => 'https://vinfastauto.com/vn_vi/dat-coc-xe-vf8',
                ] + $reference,
            ],
            'vf9' => [
                'name' => 'VF 9',
                'image' => 'vinfast-vf9-white.webp',
                'image_source' => 'https://static-cms-prod.vinfastauto.com/statics/car/vf9/hinh-anh-vinfast-vf9-infinity-blanc-desktop-20260915.webp',
                'description' => 'SUV điện cỡ lớn cho gia đình, dẫn động hai cầu toàn thời gian và cấu hình 7 chỗ.',
                'variants' => ['VF 9 Eco', 'VF 9 Plus'],
                'brochure_url' => 'https://storage.googleapis.com/vinfast-data-01/brochure/VF%209_%20Brochure.pdf',
                'specifications' => [
                    'Phiên bản tham chiếu' => 'VF 9 Eco / VF 9 Plus, pin CATL theo brochure chính thức',
                    'Số chỗ' => 'Eco: 7; Plus: 7 hoặc tùy chọn 6 theo brochure',
                    'Dài × rộng × cao (brochure)' => '5.119 × 2.254 × 1.697 mm',
                    'Chiều dài cơ sở' => '3.149 mm',
                    'Dẫn động' => 'Hai cầu toàn thời gian (AWD)',
                    'Công suất tối đa (brochure)' => '300 kW / 402 hp',
                    'Mô-men xoắn cực đại' => '620 Nm',
                    'Dung lượng pin khả dụng' => '123 kWh',
                    'Quãng đường một lần sạc (WLTP)' => 'Eco: 626 km; Plus: 602 km',
                    'Sạc AC tối đa' => '6,6 kW (1 pha) / 11 kW (3 pha)',
                    'Sạc nhanh 10–70%' => '35 phút, theo công bố của hãng',
                    'Túi khí' => '11',
                    'Màn hình cảm ứng phía trước' => '15,6 inch',
                    'Điều hòa' => 'Tự động, 3 vùng',
                    'Nguồn thông số' => 'https://vinfastauto.com/vn_vi/dat-coc-xe-vf9',
                    'Nguồn kích thước, công suất, số chỗ' => 'https://storage.googleapis.com/vinfast-data-01/brochure/VF%209_%20Brochure.pdf',
                ] + $reference,
            ],
        ];
    }
}
