<?php

namespace App\Fields;

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }
    
    acf_add_local_field_group([
        'key' => 'group_city_basic_fields',
        'title' => 'Информация о городе',
        'fields' => [
            // Вкладка: SEO
            [
                'key' => 'field_city_tab_seo',
                'label' => 'SEO',
                'type' => 'tab',
            ],
            [
                'key' => 'field_city_seo_title',
                'label' => 'SEO Title',
                'name' => 'seo_title',
                'type' => 'text',
                'wrapper' => ['width' => '50'],
                'instructions' => 'Заголовок для браузера. Если пусто, используется название города.',
            ],
            [
                'key' => 'field_city_seo_description',
                'label' => 'Meta Description',
                'name' => 'seo_description',
                'type' => 'textarea',
                'rows' => 3,
                'wrapper' => ['width' => '50'],
                'instructions' => 'Описание для поисковиков.',
            ],
            
            // Вкладка: Контент
            [
                'key' => 'field_city_tab_content',
                'label' => 'Контент',
                'type' => 'tab',
            ],
            [
                'key' => 'field_city_custom_h1',
                'label' => 'H1 Заголовок',
                'name' => 'custom_h1',
                'type' => 'text',
                'wrapper' => ['width' => '50'],
                'instructions' => 'Заголовок страницы (H1). Если пусто, используется название города.',
            ],
            [
                'key' => 'field_city_description',
                'label' => 'Описание города',
                'name' => 'description',
                'type' => 'wysiwyg',
                'media_upload' => false,
                'tabs' => 'visual',
                'toolbar' => 'basic',
                'wrapper' => ['width' => '100'],
                'instructions' => 'Описание города под заголовком.',
            ],
            [
                'key' => 'field_city_main_text',
                'label' => 'Основной контент',
                'name' => 'main_text',
                'type' => 'wysiwyg',
                'media_upload' => true,
                'tabs' => 'all',
                'toolbar' => 'full',
                'wrapper' => ['width' => '100'],
                'instructions' => 'Основной текст о городе. Будет обернут в тег <article>',
            ],
            
            // Вкладка: Дополнительно
            [
                'key' => 'field_city_tab_additional',
                'label' => 'Дополнительно',
                'type' => 'tab',
            ],
            [
                'key' => 'field_city_population',
                'label' => 'Население',
                'name' => 'population',
                'type' => 'number',
                'wrapper' => ['width' => '33'],
                'instructions' => 'Численность населения.',
            ],
            [
                'key' => 'field_city_coordinates',
                'label' => 'Координаты',
                'name' => 'coordinates',
                'type' => 'text',
                'wrapper' => ['width' => '33'],
                'instructions' => 'Широта, долгота (например: 51.1694, 71.4491)',
            ],
            [
                'key' => 'field_city_phone_code',
                'label' => 'Телефонный код',
                'name' => 'phone_code',
                'type' => 'text',
                'wrapper' => ['width' => '33'],
                'instructions' => 'Международный телефонный код города.',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'taxonomy',
                    'operator' => '==',
                    'value' => 'city',
                ],
            ],
        ],
        'position' => 'normal',
        'menu_order' => 0,
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
    ]);
});
