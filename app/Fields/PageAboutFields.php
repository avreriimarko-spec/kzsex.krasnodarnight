<?php

namespace App\Fields;

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key' => 'group_page_about',
        'title' => 'Блоки "О нас"',
        'fields' => [
            // Вкладка Статистика
            [
                'key' => 'field_tab_stats',
                'label' => 'Статистика',
                'type' => 'tab',
            ],
            [
                'key' => 'field_about_stats',
                'label' => 'Цифры (Статистика)',
                'name' => 'about_stats',
                'type' => 'repeater',
                'button_label' => 'Добавить цифру',
                'layout' => 'table',
                'sub_fields' => [
                    [
                        'key' => 'field_stat_number',
                        'label' => 'Число',
                        'name' => 'number',
                        'type' => 'text',
                        'placeholder' => '5+',
                    ],
                    [
                        'key' => 'field_stat_label',
                        'label' => 'Подпись',
                        'name' => 'label',
                        'type' => 'text',
                        'placeholder' => 'Лет работы',
                    ],
                ],
            ],

            // Вкладка Преимущества
            [
                'key' => 'field_tab_features',
                'label' => 'Преимущества',
                'type' => 'tab',
            ],
            [
                'key' => 'field_about_features',
                'label' => 'Список преимуществ',
                'name' => 'about_features',
                'type' => 'repeater',
                'layout' => 'row',
                'button_label' => 'Добавить преимущество',
                'sub_fields' => [
                    [
                        'key' => 'field_feat_title',
                        'label' => 'Заголовок',
                        'name' => 'title',
                        'type' => 'text',
                    ],
                    [
                        'key' => 'field_feat_desc',
                        'label' => 'Описание',
                        'name' => 'description',
                        'type' => 'textarea',
                        'rows' => 2,
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'page_template',
                    'operator' => '==',
                    'value' => 'template-about.blade.php',
                ],
            ],
        ],
    ]);
});
