<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Str;

class ImageService
{
    protected $manager;
    protected $watermarkPath;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
        $this->watermarkPath = get_theme_file_path('resources/images/watermark.png');
    }

    public function processAndAttach(string $url, int $postId, string $uuid): ?int
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) return null;

        $imageContent = @file_get_contents($url);
        if (!$imageContent) return null;

        return $this->processAndAttachFromContent($imageContent, $postId, $uuid);
    }

    public function processAndAttachFromPath(string $path, int $postId, string $uuid): ?int
    {
        if (!is_readable($path)) return null;

        $imageContent = @file_get_contents($path);
        if (!$imageContent) return null;

        return $this->processAndAttachFromContent($imageContent, $postId, $uuid);
    }

    private function processAndAttachFromContent(string $imageContent, int $postId, string $uuid): ?int
    {
        try {
            // 1. Создаем основное изображение
            $image = $this->manager->read($imageContent);

            // 2. Отражение по горизонтали (зеркало)
            // (flop - горизонтально, flip - вертикально/вверх тормашками)
            // Обычно для уникализации нужно именно flop
            $image->flop();

            // 3. Паттерн ватермарки
            if (file_exists($this->watermarkPath)) {
                $this->applyWatermarkPattern($image);
            }

            // 4. WebP + Чистка метаданных + Качество 75
            $encoded = $image->toWebp(75);

            // 5. Сохранение
            $uploadDir = wp_upload_dir();
            $filename = Str::slug(get_the_title($postId) . '-' . Str::random(6)) . '.webp';
            $filePath = $uploadDir['path'] . '/' . $filename;

            $encoded->save($filePath);

            // 6. Регистрация в WP
            $attachment = [
                'post_mime_type' => 'image/webp',
                'post_title'     => get_the_title($postId),
                'post_content'   => '',
                'post_status'    => 'inherit',
                'guid'           => $uploadDir['url'] . '/' . $filename,
            ];

            $attachId = wp_insert_attachment($attachment, $filePath, $postId);

            if (!is_wp_error($attachId)) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
                $attachData = wp_generate_attachment_metadata($attachId, $filePath);
                wp_update_attachment_metadata($attachId, $attachData);
                update_post_meta($attachId, 'import_image_uuid', $uuid);

                return $attachId;
            }
        } catch (\Exception $e) {
            error_log("Image Service Error: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Логика замощения ватермаркой
     */
    private function applyWatermarkPattern($image)
    {
        // 1. Читаем ватермарку
        $watermark = $this->manager->read($this->watermarkPath);

        // 2. Масштабируем
        // Делаем её чуть больше (20%), чтобы было заметнее
        // Tuning to match desired preview (smaller + lighter + wider spacing)
        $watermark->scale(width: intval($image->width() * 0.14));

        // 3. Поворачиваем на -45 градусов
        // ВАЖНО: Второй аргумент 'rgba(0, 0, 0, 0)' задает полностью ПРОЗРАЧНЫЙ фон для углов
        $watermark->rotate(-45, 'rgba(0, 0, 0, 0)');

        // Размеры ватермарки после поворота
        $wmW = $watermark->width();
        $wmH = $watermark->height();

        // Размеры холста
        $imgW = $image->width();
        $imgH = $image->height();

        // Шаг сетки (расстояние между центрами логотипов)
        // Делаем шаг побольше, чтобы они не налипали друг на друга
        $gapX = $wmW * 1.6;
        $gapY = $wmH * 1.6;

        // 4. Проходим циклом и накладываем
        // Начинаем с отрицательного отступа (-$wmW), чтобы заполнить края
        for ($y = -$wmH; $y < $imgH; $y += $gapY) {

            // Сдвигаем каждый второй ряд для "шахматного" порядка
            $rowIndex = floor($y / $gapY);
            $rowOffsetX = ($rowIndex % 2 !== 0) ? ($gapX / 2) : 0;

            for ($x = -$wmW; $x < $imgW; $x += $gapX) {

                // Вычисляем координаты
                $posX = intval($x + $rowOffsetX);
                $posY = intval($y);

                // Накладываем
                // opacity не поддерживается в place() для GD в v3 простым способом,
                // поэтому ватермарка должна быть полупрозрачной сама по себе (PNG).
                $image->place(
                    element: $watermark,
                    position: 'top-left',
                    offset_x: $posX,
                    offset_y: $posY,
                    opacity: 12
                );
            }
        }
    }
}
