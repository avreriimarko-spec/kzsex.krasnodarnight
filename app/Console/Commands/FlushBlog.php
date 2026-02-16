<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FlushBlog extends Command
{
    /**
     * Имя команды в терминале
     */
    protected $signature = 'blog:flush';

    /**
     * Описание
     */
    protected $description = 'Delete all standard blog posts and their attached images';

    public function handle()
    {
        if (!$this->confirm('ВНИМАНИЕ! Это удалит ВСЕ записи в блоге и их картинки. Вы уверены?')) {
            return;
        }

        // 1. Находим все посты блога (стандартный post_type = post)
        $this->info('Searching for blog posts...');

        $posts = get_posts([
            'post_type'   => 'post',
            'numberposts' => -1, // Все
            'post_status' => 'any',
            'fields'      => 'ids', // Берем только ID
        ]);

        if (empty($posts)) {
            $this->info('No blog posts found.');
            return;
        }

        $countPosts = count($posts);
        $this->info("Found {$countPosts} posts. Looking for attached images...");

        // 2. Находим картинки, привязанные ТОЛЬКО к этим постам
        // И только те, что имеют метку импорта (чтобы не удалить вручную загруженные логотипы и т.д.)
        $attachments = get_posts([
            'post_type'       => 'attachment',
            'numberposts'     => -1,
            'post_status'     => 'inherit',
            'post_parent__in' => $posts, // <-- Фильтр по родителям (нашим постам)
            'meta_key'        => 'import_image_uuid', // <-- Доп. проверка на импортированность
            'fields'          => 'ids',
        ]);

        // 3. Удаляем картинки
        if (!empty($attachments)) {
            $this->info("Deleting " . count($attachments) . " attached images...");
            $bar = $this->output->createProgressBar(count($attachments));
            $bar->start();

            foreach ($attachments as $attachId) {
                // true = удаление мимо корзины (сразу с диска)
                wp_delete_attachment($attachId, true);
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();
        } else {
            $this->info('No attached images found to delete.');
        }

        // 4. Удаляем сами посты
        $this->info("Deleting {$countPosts} posts...");
        $barPosts = $this->output->createProgressBar($countPosts);
        $barPosts->start();

        foreach ($posts as $postId) {
            wp_delete_post($postId, true); // true = мимо корзины
            $barPosts->advance();
        }

        $barPosts->finish();
        $this->newLine();

        // 5. Опционально: Чистка пустых рубрик и тегов
        $this->cleanupTerms();

        $this->info('Blog cleanup complete! 🧹');
    }

    /**
     * Удаляет пустые категории и теги (у которых count = 0)
     */
    private function cleanupTerms()
    {
        $this->info('Cleaning up empty categories and tags...');

        $taxonomies = ['category', 'post_tag'];

        foreach ($taxonomies as $tax) {
            $terms = get_terms([
                'taxonomy'   => $tax,
                'hide_empty' => false, // Берем даже пустые
            ]);

            foreach ($terms as $term) {
                // Если в рубрике 0 записей и это не "Uncategorized" (id 1)
                if ($term->count == 0 && $term->term_id != 1) {
                    wp_delete_term($term->term_id, $tax);
                }
            }
        }
    }
}
