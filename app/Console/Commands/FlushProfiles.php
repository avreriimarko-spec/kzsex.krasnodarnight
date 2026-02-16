<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FlushProfiles extends Command
{
    protected $signature = 'profiles:flush';
    protected $description = 'Delete all profiles and imported images';

    public function handle()
    {
        if (!$this->confirm('Вы уверены? Это удалит ВСЕ анкеты и ИХ фотографии!')) {
            return;
        }

        $this->info('Searching for profiles...');

        // 1. Сначала находим сами анкеты
        $profiles = get_posts([
            'post_type'   => 'profile',
            'numberposts' => -1,
            'fields'      => 'ids'
        ]);

        if (empty($profiles)) {
            $this->info('No profiles found.');
            return;
        }

        // 2. Находим картинки, которые привязаны ИМЕННО К ЭТИМ анкетам
        $attachments = get_posts([
            'post_type'       => 'attachment',
            'numberposts'     => -1,
            'post_status'     => 'inherit',
            'post_parent__in' => $profiles, // <--- ВАЖНОЕ УСЛОВИЕ
            'meta_key'        => 'import_image_uuid',
            'fields'          => 'ids',
        ]);

        if (!empty($attachments)) {
            $this->info("Deleting " . count($attachments) . " profile images...");
            $bar = $this->output->createProgressBar(count($attachments));
            foreach ($attachments as $id) {
                wp_delete_attachment($id, true);
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();
        }

        // 3. Удаляем сами анкеты
        $this->info("Deleting " . count($profiles) . " profiles...");
        $barPosts = $this->output->createProgressBar(count($profiles));
        foreach ($profiles as $id) {
            wp_delete_post($id, true);
            $barPosts->advance();
        }
        $barPosts->finish();
        $this->newLine();

        $this->info('Clean up complete! Blog posts are safe.');
    }
}
