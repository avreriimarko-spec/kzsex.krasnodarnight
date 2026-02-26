<?php

namespace App\Setup;

// Создание страницы "Online" при активации темы
add_action('after_setup_theme', function () {
    // Проверяем, существует ли уже страница
    $online_page = get_page_by_path('onlajn');
    
    if (!$online_page) {
        // Создаем страницу
        $page_id = wp_insert_post([
            'post_title'   => 'Онлайн модели',
            'post_content' => '',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_name'    => 'onlajn',
        ]);
        
        // Назначаем шаблон
        if ($page_id && !is_wp_error($page_id)) {
            update_post_meta($page_id, '_wp_page_template', 'template-online.blade.php');
        }
    }
});

// Добавление правил перезаписи для URL с городами
add_action('init', function () {
    // Правило для /onlajn/ и /{city}/onlajn/
    add_rewrite_rule(
        '^([^/]+)/onlajn/?$',
        'index.php?pagename=onlajn&city=$matches[1]',
        'top'
    );
    
    add_rewrite_rule(
        '^onlajn/?$',
        'index.php?pagename=onlajn',
        'top'
    );
});

// Добавление query vars
add_filter('query_vars', function ($query_vars) {
    $query_vars[] = 'city';
    return $query_vars;
});

// Перенаправление на страницу с городом по умолчанию
add_action('template_redirect', function () {
    if (is_page('onlajn') && !get_query_var('city')) {
        $current_city = get_current_city();
        if ($current_city) {
            $redirect_url = home_url("/{$current_city->slug}/onlajn/");
            wp_redirect($redirect_url, 301);
            exit;
        }
    }
});
