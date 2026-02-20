<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Services\ImageService;
use WP_Error;

class ImportProfiles extends Command
{
    protected $signature = 'profiles:import {profiles_path} {locales_path}';
    protected $description = 'Import profiles including all extended taxonomies';
    private $imageService;

    public function __construct()
    {
        parent::__construct();
        $this->imageService = new ImageService();
    }

    // Ключ в JSON (locales) => Слаг таксономии в WordPress
    protected const LOCALE_MAP = [
        'city' => 'city',
        'services' => 'service',
        'hair_color' => 'hair_color',
        'hair_length' => 'hair_length',
        'body_type' => 'body_type',
        'ethnicity' => 'ethnicity',
        'nationality' => 'nationality',
        'languages' => 'language',
        'breast_size' => 'breast_size',
        'breast_type' => 'breast_type',
        'eye_color' => 'eye_color',
        'pubic_hair' => 'pubic_hair',
        'piercing' => 'piercing',
        'travel' => 'travel',
        'inoutcall' => 'inoutcall',
        'smoker' => 'smoker',
        'verified' => 'verified',
        'independent' => 'independent',
        'vip' => 'vip',
        'gender' => 'gender',
        'orientation' => 'orientation',
        'meeting_with' => 'meeting_with',
        'tattoo' => 'tattoo',
    ];

    public function handle()
    {
        $profilesPath = $this->argument('profiles_path');
        $localesPath = $this->argument('locales_path');

        if (!file_exists($profilesPath) || !file_exists($localesPath)) {
            $this->error('JSON files not found!');
            return;
        }

        // --- 1. Импорт справочников (Locales) ---
        $this->info('Importing Locales...');
        $localesData = json_decode(file_get_contents($localesPath), true);

        // Берем параметры из seo.ru.params
        $seoParams = $localesData['seo']['ru']['params'] ?? [];

        // Карта: [ 'wp_taxonomy_slug' => [ 'json_key_id' => wp_term_id ] ]
        $termMap = [];

        foreach (self::LOCALE_MAP as $jsonKey => $wpTaxonomy) {
            // Пропускаем, если в locales.json нет данных для этой таксономии
            if (!isset($seoParams[$jsonKey])) continue;

            $bar = $this->output->createProgressBar(count($seoParams[$jsonKey]));
            $bar->start();

            foreach ($seoParams[$jsonKey] as $jsonId => $data) {
                    // --- Проверка города на KZ ---
                if ($jsonKey === 'city') {
                    // Если поле country существует и оно НЕ равно 'KZ' -> пропускаем
                    if (isset($data['country']) && $data['country'] !== 'KZ') {
                        $bar->advance();
                        continue;
                    }
                }
                $termName = $data['name'];
                $termSlug = $data['slug'] ?? null;

                $existing = term_exists($termName, $wpTaxonomy);

                if ($existing) {
                    $termId = is_array($existing) ? $existing['term_id'] : $existing;
                } else {
                    $inserted = wp_insert_term($termName, $wpTaxonomy, [
                        'slug' => $termSlug
                    ]);

                    if (is_wp_error($inserted)) {
                        continue;
                    }
                    $termId = $inserted['term_id'];
                }

                // Сохраняем ID для маппинга
                // jsonId может быть строкой "1" или числом 1
                $termMap[$wpTaxonomy][(string)$jsonId] = $termId;

                $bar->advance();
            }
            $bar->finish();
            $this->newLine();
        }

        // --- 2. Импорт Анкет ---
        $this->info('Importing Profiles...');
        $profilesData = json_decode(file_get_contents($profilesPath), true);
        $profiles = $profilesData['profiles'] ?? [];

        if (!function_exists('media_sideload_image')) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $bar = $this->output->createProgressBar(count($profiles));
        $bar->start();

        foreach ($profiles as $p) {
            $uuid = $p['uuid'];
            $data = $p['data'];

            $existing = get_posts([
                'post_type' => 'profile',
                'meta_key' => 'import_uuid',
                'meta_value' => $uuid,
                'post_status' => 'any',
                'numberposts' => 1,
                'fields' => 'ids'
            ]);

            $postId = $existing[0] ?? null;

            $name = $data['name']['ru'] ?? 'No Name';

            $slug = Str::slug($name) . '-' . $uuid;

            $postArgs = [
                'ID' => $postId,
                'post_title' => $name,

                'post_name' => $slug,

                'post_content' => $data['seo']['ru']['bio'] ?? '',
                'post_excerpt' => $data['seo']['ru']['description'] ?? '',
                'post_status' => 'publish',
                'post_type' => 'profile',
            ];

            if ($postId) {
                wp_update_post($postArgs);
            } else {
                $postId = wp_insert_post($postArgs);
                update_post_meta($postId, 'import_uuid', $uuid);
            }

            // --- Привязка таксономий ---
            $this->attachTerms($postId, $data, $p, $termMap);

            // --- Заполнение ACF полей (цены, контакты) ---
            $this->fillAcfFields($postId, $p);

            // --- Картинки ---
            if (!empty($p['media']['images'])) {
                $this->processImages($postId, $p['media']['images']);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Import completed!');
    }

    /**
     * Логика привязки таксономий, включая массивы и флаги
     */
    private function attachTerms($postId, $data, $rootProfile, $termMap)
    {
        // Список полей, которые в JSON лежат просто в корне data (или data.piercing)
        // И которые нужно маппить 1-в-1 или как массив ID
        $keysToMap = [
            'hair_color',
            'hair_length',
            'body_type',
            'ethnicity',
            'nationality',
            'breast_size',
            'breast_type',
            'pubic_hair',
            'piercing',      // Это массив в JSON ["1"]
            'travel',
            'inoutcall',
            'smoker',
            'verified',      // Это "0" или "1" в JSON
            'independent',   // Это "0" или "1" в JSON
            'vip',           // Это "0" или "1" в JSON
            'gender',
            'orientation',
            'meeting_with',
            'tattoo'
        ];

        foreach ($keysToMap as $jsonKey) {
            // Значение в анкете (может быть "1", "0", "22" или ["1", "2"])
            $val = $data[$jsonKey] ?? null;

            // Слаг таксономии в WP
            $wpTax = self::LOCALE_MAP[$jsonKey] ?? null;

            if (($val === null || $val === '') || !$wpTax || !isset($termMap[$wpTax])) {
                continue;
            }

            $idsToSet = [];

            if (is_array($val)) {
                // Если это массив (как piercing: ["1", "2"])
                foreach ($val as $v) {
                    if (isset($termMap[$wpTax][(string)$v])) {
                        $idsToSet[] = (int)$termMap[$wpTax][(string)$v];
                    }
                }
            } else {
                // Если это одиночное значение (строка или число)
                if (isset($termMap[$wpTax][(string)$val])) {
                    $idsToSet[] = (int)$termMap[$wpTax][(string)$val];
                }
            }

            if (!empty($idsToSet)) {
                wp_set_object_terms($postId, $idsToSet, $wpTax);
            }
        }

        // --- Специфичные поля ---

        // Языки (массив кодов 'ru', 'en')
        if (!empty($data['languages']) && isset($termMap['language'])) {
            $langIds = [];
            foreach ($data['languages'] as $langCode) {
                if (isset($termMap['language'][$langCode])) {
                    $langIds[] = (int)$termMap['language'][$langCode];
                }
            }
            wp_set_object_terms($postId, $langIds, 'language');
        }

        // Город (data.location.city)
        if (!empty($data['location']['city']) && isset($termMap['city'])) {
            $cityKey = $data['location']['city'];
            if (isset($termMap['city'][$cityKey])) {
                wp_set_object_terms($postId, (int)$termMap['city'][$cityKey], 'city');
            }
        }

        // Услуги (services: {"1": 0, "5": 0}) - берем только ключи
        if (!empty($data['services']) && isset($termMap['service'])) {
            $serviceTermIds = [];
            foreach ($data['services'] as $srvId => $val) {
                if (isset($termMap['service'][$srvId])) {
                    $serviceTermIds[] = (int)$termMap['service'][$srvId];
                }
            }
            wp_set_object_terms($postId, $serviceTermIds, 'service');
        }
    }


    private function fillAcfFields($postId, $p)
    {
        $d = $p['data'];

        update_field('age', $d['age'] ?? '', $postId);
        update_field('weight', $d['weight'] ?? '', $postId);
        update_field('height', $d['height'] ?? '', $postId);

        if (isset($d['contact'])) {
            update_field('phone', $d['contact']['mobile'] ?? '', $postId);
            update_field('whatsapp', $d['contact']['whatsapp'] ?? '', $postId);
            update_field('telegram', $d['contact']['telegram'] ?? '', $postId);
        }

        if (isset($d['seo']['ru'])) {
            $seo = $d['seo']['ru'];

            update_field('seo_title', $seo['title'] ?? '', $postId);
            update_field('seo_description', $seo['description'] ?? '', $postId);
        }

        if (isset($d['price'])) {
            $pr = $d['price'];
            update_field('price_currency', $pr['currency'] ?? 'RUB', $postId);
            $priceGroup = [
                'price_1h' => $pr['1h_in'] ?? 0,
                'price_2h' => $pr['2h_in'] ?? 0,
                'price_4h' => $pr['4h_in'] ?? 0,
                'price_night' => $pr['1n_in'] ?? 0,
                'price_day' => $pr['1d_in'] ?? 0,
                'price_1h_out' => $pr['1h'] ?? 0,
                'price_2h_out' => $pr['2h'] ?? 0,
                'price_4h_out' => $pr['4h'] ?? 0,
                'price_night_out' => $pr['1n'] ?? 0,
                'price_day_out' => $pr['1d'] ?? 0,
            ];
            update_field('price', $priceGroup, $postId);
        }

        // Отзывы
        if (!empty($p['review']['ru'])) {
            $reviews = [];
            foreach ($p['review']['ru'] as $rev) {
                $reviews[] = [
                    'author' => $rev['author'],
                    'rating' => $rev['rating'],
                    'content' => $rev['content'],
                    'date' => date('Ymd', $rev['created']),
                    'imported' => true, // Помечаем, что отзыв импортирован из JSON
                ];
            }
            update_field('reviews_list', $reviews, $postId);
        }
    }

    private function processImages($postId, $images)
    {
        if (empty($images)) return;

        $galleryIds = [];

        foreach ($images as $index => $img) {
            $uuid = $img['uuid'];
            $url = $img['url'];

            // 1. Ищем или загружаем фото
            $existingId = $this->getAttachmentByUuid($uuid);

            if ($existingId) {
                $attachId = $existingId;
            } else {
                $this->info("Processing image for post {$postId}...");
                $attachId = $this->imageService->processAndAttach($url, $postId, $uuid);
            }

            if ($attachId) {
                // ЛОГИКА ИЗМЕНЕНА ЗДЕСЬ:

                if ($index === 0) {
                    // Если это первое фото — ставим как "Главное"
                    set_post_thumbnail($postId, $attachId);
                    // И пропускаем добавление в массив галереи
                    continue;
                }

                // Остальные фото добавляем в галерею
                $galleryIds[] = $attachId;
            }
        }

        // Сохраняем в ACF только дополнительные фото (без главного)
        update_field('gallery', $galleryIds, $postId);
    }

    private function getAttachmentByUuid($uuid)
    {
        $posts = get_posts([
            'post_type'  => 'attachment',
            'meta_key'   => 'import_image_uuid',
            'meta_value' => $uuid,
            'posts_per_page' => 1,
            'fields'     => 'ids',
        ]);
        return $posts[0] ?? null;
    }

    private function sideloadImage($url, $uuid, $postId)
    {
        // Проверка дублей
        $existing = get_posts([
            'post_type' => 'attachment',
            'meta_key' => 'import_image_uuid',
            'meta_value' => $uuid,
            'posts_per_page' => 1,
            'fields' => 'ids'
        ]);

        if (!empty($existing)) {
            return $existing[0];
        }

        $id = media_sideload_image($url, $postId, null, 'id');

        if (!is_wp_error($id)) {
            update_post_meta($id, 'import_image_uuid', $uuid);
            return $id;
        }

        return null;
    }
}
