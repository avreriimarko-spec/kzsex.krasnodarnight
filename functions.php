<?php

use Roots\Acorn\Application;

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| our theme. We will simply require it into the script here so that we
| don't have to worry about manually loading any of our classes later on.
|
*/

if (! file_exists($composer = __DIR__ . '/vendor/autoload.php')) {
    wp_die(__('Error locating autoloader. Please run <code>composer install</code>.', 'sage'));
}

require $composer;

/*
|--------------------------------------------------------------------------
| Register The Bootloader
|--------------------------------------------------------------------------
|
| The first thing we will do is schedule a new Acorn application container
| to boot when WordPress is finished loading the theme. The application
| serves as the "glue" for all the components of Laravel and is
| the IoC container for the system binding all of the various parts.
|
*/

Application::configure()
    ->withProviders([
        App\Providers\ThemeServiceProvider::class,
        App\Providers\ContentServiceProvider::class,
        App\Providers\OptimizationServiceProvider::class,
        App\Providers\FormServiceProvider::class,
    ])
    ->boot();

/*
|--------------------------------------------------------------------------
| Register Sage Theme Files
|--------------------------------------------------------------------------
|
| Out of the box, Sage ships with categorically named theme files
| containing common functionality and setup to be bootstrapped with your
| theme. Simply add (or remove) files from the array below to change what
| is registered alongside Sage.
|
*/

collect(['setup', 'filters'])
    ->each(function ($file) {
        if (! locate_template($file = "app/{$file}.php", true, true)) {
            wp_die(
                /* translators: %s is replaced with the relative file path */
                sprintf(__('Error locating <code>%s</code> for inclusion.', 'sage'), $file)
            );
        }
    });

// Подключаем helper-функции для URL
if (file_exists(__DIR__ . '/app/Helpers/functions.php')) {
    require_once __DIR__ . '/app/Helpers/functions.php';
}

// Подключаем admin assets
if (file_exists(__DIR__ . '/app/Helpers/AdminAssets.php')) {
    require_once __DIR__ . '/app/Helpers/AdminAssets.php';
}

// Подключаем city ajax
if (file_exists(__DIR__ . '/app/Helpers/CityAjax.php')) {
    require_once __DIR__ . '/app/Helpers/CityAjax.php';
}

// Подключаем страницу отзывов в админке
if (file_exists(__DIR__ . '/app/Helpers/AdminReviewsPage.php')) {
    require_once __DIR__ . '/app/Helpers/AdminReviewsPage.php';
    
    add_action('admin_menu', function() {
        add_menu_page(
            'Все отзывы',
            'Отзывы',
            'manage_options',
            'all-reviews',
            'App\Helpers\AdminReviewsPage::renderReviewsPage',
            'dashicons-star-filled',
            30
        );
    });
    
    add_action('admin_init', function() {
        // Обработка действий, если нужно
    });
}

// AJAX обработчик для удаления отзывов
add_action('wp_ajax_delete_review', function() {
    check_ajax_referer('delete_review_nonce', '_ajax_nonce');
    
    $profile_id = intval($_POST['profile_id']);
    $review_index = intval($_POST['review_index']);
    $review_status = sanitize_text_field($_POST['review_status'] ?? 'published');
    
    if (!$profile_id || $review_index < 0) {
        wp_send_json_error('Неверные параметры');
    }
    
    // Выбираем правильное поле в зависимости от статуса
    $field_name = $review_status === 'pending' ? 'pending_reviews' : 'reviews_list';
    $reviews = get_field($field_name, $profile_id);
    
    if ($reviews && is_array($reviews) && isset($reviews[$review_index])) {
        // Удаляем отзыв из массива
        array_splice($reviews, $review_index, 1);
        
        // Обновляем поле
        if (update_field($field_name, $reviews, $profile_id)) {
            wp_send_json_success('Отзыв удален');
        } else {
            wp_send_json_error('Ошибка при обновлении поля');
        }
    } else {
        wp_send_json_error('Отзыв не найден. Индекс: ' . $review_index . ', Всего отзывов: ' . count($reviews));
    }
});

// AJAX обработчик для отправки отзыва
add_action('wp_ajax_submit_review', function() {
    check_ajax_referer('submit_review_nonce', '_ajax_nonce');
    
    $profile_id = intval($_POST['profile_id']);
    $author = sanitize_text_field($_POST['author']);
    $rating = floatval($_POST['rating']);
    $content = sanitize_textarea_field($_POST['content']);
    
    // Валидация
    if (!$profile_id || empty($author) || empty($content) || $rating < 1 || $rating > 5) {
        wp_send_json_error('Пожалуйста, заполните все поля корректно');
    }
    
    // Получаем существующие отзывы на модерации
    $pending_reviews = get_field('pending_reviews', $profile_id);
    if (!is_array($pending_reviews)) {
        $pending_reviews = [];
    }
    
    // Добавляем новый отзыв на модерацию
    $new_review = [
        'author' => $author,
        'rating' => $rating,
        'content' => $content,
        'date' => date('Ymd'),
        'imported' => false, // Помечаем, что отзыв добавлен вручную
        'status' => 'pending', // Статус модерации
        'created_at' => current_time('mysql')
    ];
    
    $pending_reviews[] = $new_review;
    
    // Обновляем поле с отзывами на модерации
    $result = update_field('pending_reviews', $pending_reviews, $profile_id);
    
    if ($result) {
        // Отладочная информация
        error_log('Отзыв успешно добавлен на модерацию. Profile ID: ' . $profile_id . ', Всего отзывов на модерации: ' . count($pending_reviews));
        wp_send_json_success('Отзыв успешно отправлен и будет опубликован после модерации');
    } else {
        error_log('Ошибка при сохранении отзыва на модерацию. Profile ID: ' . $profile_id);
        wp_send_json_error('Ошибка при сохранении отзыва');
    }
});

// AJAX обработчик для неавторизованных пользователей
add_action('wp_ajax_nopriv_submit_review', function() {
    check_ajax_referer('submit_review_nonce', '_ajax_nonce');
    
    $profile_id = intval($_POST['profile_id']);
    $author = sanitize_text_field($_POST['author']);
    $rating = floatval($_POST['rating']);
    $content = sanitize_textarea_field($_POST['content']);
    
    // Валидация
    if (!$profile_id || empty($author) || empty($content) || $rating < 1 || $rating > 5) {
        wp_send_json_error('Пожалуйста, заполните все поля корректно');
    }
    
    // Получаем существующие отзывы на модерации
    $pending_reviews = get_field('pending_reviews', $profile_id);
    if (!is_array($pending_reviews)) {
        $pending_reviews = [];
    }
    
    // Добавляем новый отзыв на модерацию
    $new_review = [
        'author' => $author,
        'rating' => $rating,
        'content' => $content,
        'date' => date('Ymd'),
        'imported' => false, // Помечаем, что отзыв добавлен вручную
        'status' => 'pending', // Статус модерации
        'created_at' => current_time('mysql')
    ];
    
    $pending_reviews[] = $new_review;
    
    // Обновляем поле с отзывами на модерации
    if (update_field('pending_reviews', $pending_reviews, $profile_id)) {
        wp_send_json_success('Отзыв успешно отправлен и будет опубликован после модерации');
    } else {
        wp_send_json_error('Ошибка при сохранении отзыва');
    }
});

// AJAX обработчик для одобрения отзыва
add_action('wp_ajax_approve_review', function() {
    check_ajax_referer('moderate_review_nonce', '_ajax_nonce');
    
    $profile_id = intval($_POST['profile_id']);
    $review_index = intval($_POST['review_index']);
    
    if (!$profile_id || $review_index < 0) {
        wp_send_json_error('Неверные параметры');
    }
    
    // Получаем отзывы на модерации
    $pending_reviews = get_field('pending_reviews', $profile_id);
    
    if ($pending_reviews && is_array($pending_reviews) && isset($pending_reviews[$review_index])) {
        $review = $pending_reviews[$review_index];
        
        // Получаем существующие опубликованные отзывы
        $published_reviews = get_field('reviews_list', $profile_id);
        if (!is_array($published_reviews)) {
            $published_reviews = [];
        }
        
        // Добавляем отзыв в опубликованные
        $review['status'] = 'approved';
        $review['approved_at'] = current_time('mysql');
        $published_reviews[] = $review;
        
        // Удаляем отзыв из модерации
        array_splice($pending_reviews, $review_index, 1);
        
        // Обновляем оба поля
        update_field('reviews_list', $published_reviews, $profile_id);
        update_field('pending_reviews', $pending_reviews, $profile_id);
        
        wp_send_json_success('Отзыв одобрен и опубликован');
    } else {
        wp_send_json_error('Отзыв не найден');
    }
});

// AJAX обработчик для отклонения отзыва
add_action('wp_ajax_reject_review', function() {
    check_ajax_referer('moderate_review_nonce', '_ajax_nonce');
    
    $profile_id = intval($_POST['profile_id']);
    $review_index = intval($_POST['review_index']);
    
    if (!$profile_id || $review_index < 0) {
        wp_send_json_error('Неверные параметры');
    }
    
    $pending_reviews = get_field('pending_reviews', $profile_id);
    
    if ($pending_reviews && is_array($pending_reviews) && isset($pending_reviews[$review_index])) {
        // Удаляем отзыв из модерации
        array_splice($pending_reviews, $review_index, 1);
        
        // Обновляем поле
        update_field('pending_reviews', $pending_reviews, $profile_id);
        
        wp_send_json_success('Отзыв отклонен');
    } else {
        wp_send_json_error('Отзыв не найден');
    }
});

// AJAX обработчик для обновления отзыва на модерации
add_action('wp_ajax_update_pending_review', function() {
    check_ajax_referer('update_pending_review_nonce', '_ajax_nonce');
    
    $profile_id = intval($_POST['profile_id']);
    $review_index = intval($_POST['review_index']);
    $author = sanitize_text_field($_POST['author']);
    $rating = floatval($_POST['rating']);
    $content = sanitize_textarea_field($_POST['content']);
    
    if (!$profile_id || $review_index < 0 || empty($author) || empty($content) || $rating < 1 || $rating > 5) {
        wp_send_json_error('Неверные параметры');
    }
    
    $pending_reviews = get_field('pending_reviews', $profile_id);
    
    if ($pending_reviews && is_array($pending_reviews) && isset($pending_reviews[$review_index])) {
        // Обновляем данные отзыва
        $pending_reviews[$review_index]['author'] = $author;
        $pending_reviews[$review_index]['rating'] = $rating;
        $pending_reviews[$review_index]['content'] = $content;
        
        // Обновляем поле
        if (update_field('pending_reviews', $pending_reviews, $profile_id)) {
            wp_send_json_success('Отзыв успешно обновлен');
        } else {
            wp_send_json_error('Ошибка при обновлении отзыва');
        }
    } else {
        wp_send_json_error('Отзыв не найден');
    }
});

// AJAX обработчик для массового удаления отзывов
add_action('wp_ajax_delete_all_reviews', function() {
    check_ajax_referer('delete_all_reviews_nonce', '_ajax_nonce');
    
    $status = sanitize_text_field($_POST['status']);
    $deleted_count = 0;
    
    // Получаем все профили
    $profiles = get_posts([
        'post_type' => 'profile',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ]);
    
    foreach ($profiles as $profile_id) {
        if ($status === 'pending' || $status === 'all') {
            // Удаляем отзывы на модерации
            $pending_reviews = get_field('pending_reviews', $profile_id);
            if ($pending_reviews && is_array($pending_reviews)) {
                $deleted_count += count($pending_reviews);
                update_field('pending_reviews', [], $profile_id);
            }
        }
        
        if ($status === 'published' || $status === 'all') {
            // Удаляем опубликованные отзывы
            $published_reviews = get_field('reviews_list', $profile_id);
            if ($published_reviews && is_array($published_reviews)) {
                // Фильтруем только импортированные отзывы (оставляем их)
                $imported_reviews = array_filter($published_reviews, function($review) {
                    return isset($review['imported']) && $review['imported'] === true;
                });
                
                $deleted_manual = count($published_reviews) - count($imported_reviews);
                $deleted_count += $deleted_manual;
                
                update_field('reviews_list', array_values($imported_reviews), $profile_id);
            }
        }
    }
    
    if ($deleted_count > 0) {
        $status_text = $status === 'pending' ? 'на модерации' : ($status === 'published' ? 'опубликованных' : 'всех');
        wp_send_json_success("Успешно удалено {$deleted_count} отзывов {$status_text}. Импортированные отзывы сохранены.");
    } else {
        wp_send_json_error('Отзывы для удаления не найдены');
    }
});

// Обработчик формы добавления анкеты
add_action('wp_ajax_submit_profile_application', 'handle_profile_application');
add_action('wp_ajax_nopriv_submit_profile_application', 'handle_profile_application');

function handle_profile_application() {
    // Отладочная информация
    error_log('Profile application received: ' . print_r($_POST, true));
    
    // Проверка nonce - используем правильный nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'wp_rest') && !wp_verify_nonce($_POST['_wpnonce'], 'profile_form_nonce')) {
        error_log('Nonce verification failed');
        wp_send_json_error('Ошибка безопасности');
    }

    // Валидация полей
    $name = sanitize_text_field($_POST['name'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $contact_method = sanitize_text_field($_POST['contact_method'] ?? '');
    $username = sanitize_text_field($_POST['username'] ?? '');
    $work_time_from = sanitize_text_field($_POST['work_time_from'] ?? '');
    $work_time_to = sanitize_text_field($_POST['work_time_to'] ?? '');
    $is_24_7 = isset($_POST['is_24_7']) ? 1 : 0;
    $additional_info = sanitize_textarea_field($_POST['additional_info'] ?? '');

    // Обязательные поля
    if (empty($name) || empty($phone) || empty($contact_method)) {
        error_log('Validation failed: missing required fields');
        wp_send_json_error('Заполните все обязательные поля');
    }

    // Если выбран Telegram, обязательно нужно имя пользователя
    if ($contact_method === 'telegram' && empty($username)) {
        error_log('Validation failed: missing username for Telegram');
        wp_send_json_error('Укажите имя пользователя в Telegram');
    }

    // Если не круглосуточно, проверяем время работы
    if (!$is_24_7 && (empty($work_time_from) || empty($work_time_to))) {
        error_log('Validation failed: missing work time');
        wp_send_json_error('Укажите время работы');
    }

    // Формируем сообщение для Telegram
    $message = "<b>📝 Новая заявка на добавление анкеты</b>\n\n";
    $message .= "<b>👤 Имя:</b> {$name}\n";
    $message .= "<b>📱 Телефон:</b> {$phone}\n";
    $message .= "<b>📞 Способ связи:</b> " . get_contact_method_label($contact_method) . "\n";
    
    if ($contact_method === 'telegram') {
        $message .= "<b>💬 Telegram:</b> @{$username}\n";
    }
    
    if ($is_24_7) {
        $message .= "<b>⏰ Время работы:</b> Круглосуточно\n";
    } else {
        $message .= "<b>⏰ Время работы:</b> с {$work_time_from} до {$work_time_to}\n";
    }
    
    if (!empty($additional_info)) {
        $message .= "<b>📝 Доп. информация:</b> {$additional_info}\n";
    }

    $message .= "\n<b>🌐 Источник:</b> " . home_url('/dobavit-anketu/');
    $message .= "\n<b>📅 Дата:</b> " . date('d.m.Y H:i');

    error_log('Sending to Telegram: ' . $message);

    // Отправляем в Telegram
    $telegram_sent = send_to_telegram($message);

    if ($telegram_sent) {
        error_log('Telegram sent successfully');
        wp_send_json_success('Ваша заявка успешно отправлена!');
    } else {
        error_log('Telegram sending failed');
        wp_send_json_error('Произошла ошибка при отправке. Попробуйте позже.');
    }
}

function get_contact_method_label($method) {
    $labels = [
        'telegram' => 'Telegram',
        'phone' => 'Телефон',
        'whatsapp' => 'WhatsApp'
    ];
    return $labels[$method] ?? $method;
}

function send_to_telegram($message) {
    // Используем TelegramService как на странице контактов
    if (class_exists('\App\Services\TelegramService')) {
        $telegram = new \App\Services\TelegramService();
        
        if (!$telegram->isConfigured()) {
            error_log('Telegram not configured: ' . $telegram->getConfigError());
            return false;
        }
        
        error_log('Using TelegramService to send message');
        $result = $telegram->sendApplication($message);
        error_log('TelegramService result: ' . ($result ? 'success' : 'failed'));
        return $result;
    }
    
    // Fallback если класс не найден
    error_log('TelegramService class not found, checking fields...');
    
    // Пробуем старый метод с правильными полями
    $bot_token = get_field('tg_bot_token', 'option');
    $chat_id = get_field('tg_chat_id', 'option');
    
    if (!$bot_token || !$chat_id) {
        error_log('Telegram fields not found: token=' . ($bot_token ? 'set' : 'not set') . ', chat_id=' . ($chat_id ? 'set' : 'not set'));
        return false;
    }

    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];

    $response = wp_remote_post($url, [
        'body' => $data,
        'timeout' => 30,
    ]);

    if (is_wp_error($response)) {
        error_log('Telegram request error: ' . $response->get_error_message());
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    $success = isset($data['ok']) && $data['ok'] === true;
    error_log('Fallback telegram result: ' . ($success ? 'success' : 'failed'));
    
    return $success;
}

// Обработка XML sitemap запросов
add_action('init', function() {
    // Если запрос к page.sitemap.xml
    if (strpos($_SERVER['REQUEST_URI'], '/page.sitemap.xml') !== false) {
        // Полное отключение вывода ошибок
        error_reporting(0);
        ini_set('display_errors', 0);
        ini_set('log_errors', 0);
        
        // Буферизация вывода
        ob_start();
        
        // Устанавливаем заголовки
        header('Content-Type: application/xml; charset=utf-8');
        
        // Выводим XML
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        
        // Главная страница
        echo '<url>';
        echo '<loc>' . home_url('/') . '</loc>';
        echo '<lastmod>' . date('Y-m-d') . '</lastmod>';
        echo '<changefreq>daily</changefreq>';
        echo '<priority>1.0</priority>';
        echo '</url>';
        
        // Получаем все опубликованные страницы
        $pages = get_pages([
            'sort_column' => 'menu_order, post_title',
            'sort_order' => 'ASC',
            'post_status' => 'publish'
        ]);
        
        foreach($pages as $page) {
            // Пропускаем главную страницу если она установлена как домашняя
            if ($page->ID == get_option('page_on_front')) continue;
            
            $modified = get_the_modified_date('Y-m-d', $page->ID);
            $url = get_permalink($page->ID);
            
            echo '<url>';
            echo '<loc>' . htmlspecialchars($url) . '</loc>';
            echo '<lastmod>' . $modified . '</lastmod>';
            echo '<changefreq>monthly</changefreq>';
            echo '<priority>0.6</priority>';
            echo '</url>';
        }
        
        echo '</urlset>';
        
        // Очищаем буфер и завершаем выполнение
        ob_end_flush();
        exit;
    }
});
