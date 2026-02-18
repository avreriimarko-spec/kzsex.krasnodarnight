<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportMetro extends Command
{
    // Команда принимает два параметра: файл и код города (slug)
    // Пример запуска: wp acorn import:metro almaty.xml almaty
    protected $signature = 'import:metro {file : Путь к XML файлу} {city_slug : Slug города (например: moskva или almaty)}';

    protected $description = 'Импорт станций метро и привязка их к городу';

    public function handle()
    {
        $filePath = $this->argument('file');
        $citySlug = $this->argument('city_slug');

        // 1. Проверки
        if (!file_exists($filePath)) {
            $this->error("Файл не найден: {$filePath}");
            return;
        }

        // Находим город в базе по его коду (slug)
        $cityTerm = get_term_by('slug', $citySlug, 'city');
        if (!$cityTerm) {
            $this->error("Город с кодом '{$citySlug}' не найден! Сначала создайте его в админке.");
            return;
        }

        $this->info("Импорт станций для города: {$cityTerm->name}...");

        // 2. Читаем XML
        $xml = simplexml_load_file($filePath, 'SimpleXMLElement', LIBXML_NOCDATA);
        $namespaces = $xml->getNamespaces(true);
        $wpNamespace = $namespaces['wp'] ?? null;

        $count = 0;

        foreach ($xml->channel->item as $item) {
            $wp = $item->children($wpNamespace);
            
            // Нас интересуют только записи типа metro
            if ((string)$wp->post_type === 'metro') {
                $title = (string)$item->title; // Название: "Абая"
                $slug = (string)$wp->post_name; // Код: "abaya"

                // 3. Создаем станцию (терм)
                if (!term_exists($slug, 'metro')) {
                    $inserted = wp_insert_term($title, 'metro', ['slug' => $slug]);
                    if (is_wp_error($inserted)) {
                        $this->error("Ошибка создания {$title}: " . $inserted->get_error_message());
                        continue;
                    }
                    $termId = $inserted['term_id'];
                    $this->line("Создана: {$title}");
                } else {
                    $existing = get_term_by('slug', $slug, 'metro');
                    $termId = $existing->term_id;
                    $this->line("Обновлена: {$title}");
                }

                // 4. ГЛАВНОЕ: Ставим "штамп" (привязываем к городу)
                // Используем ACF функцию update_field
                // Синтаксис для таксономии: 'metro_15' (где 15 - ID станции)
                if (function_exists('update_field')) {
                    // Полю 'related_city' присваиваем ID города
                    update_field('related_city', $cityTerm->term_id, 'metro_' . $termId);
                }
                
                $count++;
            }
        }

        $this->info("Успешно! Обработано станций: {$count}. Все привязаны к городу {$cityTerm->name}.");
    }
}