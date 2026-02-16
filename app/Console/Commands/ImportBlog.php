<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ImageService;
use Illuminate\Support\Str;

class ImportBlog extends Command
{
    protected $signature = 'blog:import {file}';
    protected $description = 'Import blog posts from JSON to standard WP Posts';

    private $imageService;

    public function __construct()
    {
        parent::__construct();
        $this->imageService = new ImageService();
    }

    public function handle()
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File not found: $file");
            return;
        }

        $json = json_decode(file_get_contents($file), true);

        if (!$json || !isset($json['result'])) {
            $this->error("Invalid JSON format");
            return;
        }

        $this->info("Importing blog posts...");

        $posts = $json['result'];
        $bar = $this->output->createProgressBar(count($posts));
        $bar->start();

        foreach ($posts as $externalId => $item) {
            $this->processPost($item);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Import completed successfully!");
    }

    private function processPost($data)
    {
        // 1. Подготовка данных
        $title = $data['title'];
        $content = $data['content'];
        $contentImagesIds = [];

        // Обработка картинок в контенте
        if (preg_match_all('/<img[^>]+src="([^">]+)"/', $content, $matches)) {
            $imageUrls = array_unique($matches[1]);
            foreach ($imageUrls as $url) {
                $uuid = md5($url);
                $attachId = $this->getAttachmentByUuid($uuid);

                if (!$attachId) {
                    // Используем 0 как parent, потом обновим
                    $attachId = $this->imageService->processAndAttach($url, 0, $uuid);
                }

                if ($attachId) {
                    $localUrl = wp_get_attachment_url($attachId);
                    $content = str_replace($url, $localUrl, $content);
                    $contentImagesIds[] = $attachId;
                }
            }
        }

        // Транслитерация заголовка
        // Сначала переводим в латиницу, потом делаем slug
        $slug = Str::slug($this->rus2translit($title), '-');

        // Fallback если вдруг что-то пошло не так
        if (empty($slug)) {
            $slug = sanitize_title($title);
        }

        $existing = get_page_by_path($slug, OBJECT, 'post');

        $postData = [
            'ID'           => $existing ? $existing->ID : 0,
            'post_title'   => $title,
            'post_name'    => $slug,
            'post_content' => $content,
            'post_status'  => 'publish',
            'post_type'    => 'post',
            'post_date'    => date('Y-m-d H:i:s', $data['created']),
            'post_modified' => date('Y-m-d H:i:s', $data['modified']),
        ];

        if ($postData['ID']) {
            $postId = wp_update_post($postData);
        } else {
            $postId = wp_insert_post($postData);
        }

        if (is_wp_error($postId)) {
            $this->error("Error: " . $title);
            return;
        }

        // Привязка картинок
        if (!empty($contentImagesIds)) {
            foreach ($contentImagesIds as $attId) {
                wp_update_post(['ID' => $attId, 'post_parent' => $postId]);
            }
        }

        // 3. Категории
        if (!empty($data['category'])) {
            $catName = $data['category'];

            // Транслитерация слага категории
            $catSlug = Str::slug($this->rus2translit($catName), '-');

            $term = term_exists($catName, 'category');

            if (!$term) {
                $term = wp_insert_term($catName, 'category', [
                    'slug' => $catSlug
                ]);
            }

            if (!is_wp_error($term)) {
                $termId = is_array($term) ? $term['term_id'] : $term;
                wp_set_post_categories($postId, [$termId]);
            }
        }

        // 4. Метки / Теги
        $tagIds = []; // Обязательно инициализируем массив

        if (!empty($data['keywords'])) {
            $tags = explode(',', $data['keywords']);

            foreach ($tags as $rawTag) {
                // 1. Убираем пробелы и возможные невидимые символы
                $tagName = trim(preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $rawTag));

                // 2. СТРОГИЙ ФИЛЬТР:
                // Проверяем регуляркой: есть ли в строке хотя бы одна буква (русская или английская)?
                // Если букв нет (значит там цифры, символы или пустота) -> ПРОПУСКАЕМ.
                if (!preg_match('/[\p{L}]/u', $tagName)) {
                    continue;
                }

                // Транслитерация
                $tagSlug = Str::slug($this->rus2translit($tagName), '-');

                // Дополнительная защита: если после слага получились только цифры
                if (is_numeric($tagSlug)) {
                    continue;
                }

                $term = term_exists($tagName, 'post_tag');

                if (!$term) {
                    $term = wp_insert_term($tagName, 'post_tag', [
                        'slug' => $tagSlug
                    ]);
                }

                if (!is_wp_error($term)) {
                    $tagIds[] = is_array($term) ? $term['term_id'] : $term;
                }
            }
        }

        if (!empty($tagIds)) {
            // map intval гарантирует, что мы передаем именно числа (ID)
            wp_set_object_terms($postId, array_map('intval', $tagIds), 'post_tag');
        } else {
            // Если тегов нет, очищаем
            wp_set_object_terms($postId, [], 'post_tag');
        }

        // 5. SEO
        if (!empty($data['description'])) {
            update_field('seo_description', $data['description'], $postId);
            wp_update_post(['ID' => $postId, 'post_excerpt' => $data['description']]);
        }
        update_field('seo_title', $title, $postId);

        // 6. Изображение
        if (!empty($data['image'])) {
            $url = $data['image'];
            $uuid = md5($url);
            $attachId = $this->getAttachmentByUuid($uuid);

            if (!$attachId) {
                $attachId = $this->imageService->processAndAttach($url, $postId, $uuid);
            }

            if ($attachId) {
                set_post_thumbnail($postId, $attachId);
            }
        }
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

    /**
     * Транслитерация Кириллица -> Латиница
     */
    private function rus2translit($string)
    {
        $converter = [
            'а' => 'a',
            'б' => 'b',
            'в' => 'v',
            'г' => 'g',
            'д' => 'd',
            'е' => 'e',
            'ё' => 'e',
            'ж' => 'zh',
            'з' => 'z',
            'и' => 'i',
            'й' => 'y',
            'к' => 'k',
            'л' => 'l',
            'м' => 'm',
            'н' => 'n',
            'о' => 'o',
            'п' => 'p',
            'р' => 'r',
            'с' => 's',
            'т' => 't',
            'у' => 'u',
            'ф' => 'f',
            'х' => 'h',
            'ц' => 'c',
            'ч' => 'ch',
            'ш' => 'sh',
            'щ' => 'sch',
            'ь' => '',
            'ы' => 'y',
            'ъ' => '',
            'э' => 'e',
            'ю' => 'yu',
            'я' => 'ya',

            'А' => 'A',
            'Б' => 'B',
            'В' => 'V',
            'Г' => 'G',
            'Д' => 'D',
            'Е' => 'E',
            'Ё' => 'E',
            'Ж' => 'Zh',
            'З' => 'Z',
            'И' => 'I',
            'Й' => 'Y',
            'К' => 'K',
            'Л' => 'L',
            'М' => 'M',
            'Н' => 'N',
            'О' => 'O',
            'П' => 'P',
            'Р' => 'R',
            'С' => 'S',
            'Т' => 'T',
            'У' => 'U',
            'Ф' => 'F',
            'Х' => 'H',
            'Ц' => 'C',
            'Ч' => 'Ch',
            'Ш' => 'Sh',
            'Щ' => 'Sch',
            'Ь' => '',
            'Ы' => 'Y',
            'Ъ' => '',
            'Э' => 'E',
            'Ю' => 'Yu',
            'Я' => 'Ya',
        ];
        return strtr($string, $converter);
    }
}
