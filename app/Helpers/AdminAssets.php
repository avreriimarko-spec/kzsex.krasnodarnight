<?php

namespace App\Helpers;

add_action('admin_enqueue_scripts', function ($hook) {
    // Подключаем скрипт только для редактирования страниц
    if ($hook === 'post.php' || $hook === 'post-new.php') {
        // Получаем тип поста
        global $post;
        $post_type = isset($post) ? $post->post_type : '';
        
        // Подключаем только для страниц
        if ($post_type === 'page') {
            wp_enqueue_script(
                'admin-city-fields',
                get_template_directory_uri() . '/app/Assets/admin-city-fields.js',
                ['jquery'],
                '1.0.0',
                true
            );
        }
    }
});
