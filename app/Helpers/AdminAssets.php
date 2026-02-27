<?php

namespace App\Helpers;

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    $postType = (string) ($screen->post_type ?? '');

    if ($postType === 'page') {
        wp_enqueue_script(
            'admin-city-fields',
            get_template_directory_uri() . '/app/Assets/admin-city-fields.js',
            ['jquery'],
            '1.0.0',
            true
        );

        return;
    }

    if ($postType !== 'profile') {
        return;
    }

    $scriptPath = get_template_directory() . '/app/Assets/admin-profile-location.js';
    $scriptVersion = file_exists($scriptPath) ? (string) filemtime($scriptPath) : '1.0.0';

    wp_enqueue_script(
        'admin-profile-location',
        get_template_directory_uri() . '/app/Assets/admin-profile-location.js',
        ['jquery'],
        $scriptVersion,
        true
    );

    $termCityMap = [
        'metro' => function_exists('get_location_term_city_map') ? \get_location_term_city_map('metro') : [],
        'district' => function_exists('get_location_term_city_map') ? \get_location_term_city_map('district') : [],
    ];

    wp_localize_script('admin-profile-location', 'eskortMoskvyProfileLocationAdmin', [
        'termCityMap' => $termCityMap,
        'messages' => [
            'selectCity' => 'Сначала выберите город, затем метро и районы.',
            'noTerms' => 'Для выбранного города нет доступных терминов.',
        ],
    ]);
});
