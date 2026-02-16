<?php

namespace App\Fields;

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }
    
    acf_add_local_field_group([
        'key' => 'group_taxonomy_city_specific',
        'title' => 'Информация по Городам',
        'fields' => [
            // Вкладка с выбором города
            [
                'key' => 'field_taxonomy_city_specific_tab_main',
                'label' => 'Настройки по Городам',
                'type' => 'tab',
                'instructions' => 'Здесь можно добавить специфичную информацию для каждого города. Если заполнено, будет показываться вместо основной информации.',
            ],
            
            // Выбор города
            [
                'key' => 'field_taxonomy_city_specific_selector',
                'label' => 'Выберите город',
                'name' => 'city_selector',
                'type' => 'taxonomy',
                'taxonomy' => 'city',
                'field_type' => 'select',
                'multiple' => 0,
                'allow_null' => 1,
                'return_format' => 'object',
                'add_term' => 0,
                'wrapper' => ['width' => '50'],
                'instructions' => 'Выберите город для которого хотите добавить информацию. Оставьте пустым для использования основной информации.',
            ],
            
            // Текущий выбранный город (информационное поле)
            [
                'key' => 'field_taxonomy_city_current_info',
                'label' => 'Текущий город',
                'name' => 'current_city_info',
                'type' => 'message',
                'message' => 'Выберите город из выпадающего списка выше',
                'wrapper' => ['width' => '50'],
                'conditional_logic' => [
                    [
                        'field' => 'city_selector',
                        'operator' => '==empty',
                    ],
                ],
            ],
            
            // Вкладка: SEO для выбранного города
            [
                'key' => 'field_taxonomy_city_specific_tab_seo',
                'label' => 'SEO для выбранного города',
                'type' => 'tab',
                'conditional_logic' => [
                    [
                        'field' => 'city_selector',
                        'operator' => '!=empty',
                    ],
                ],
            ],
            [
                'key' => 'field_taxonomy_city_specific_seo_title',
                'label' => 'SEO Title для города',
                'name' => 'city_seo_title',
                'type' => 'text',
                'wrapper' => ['width' => '50'],
                'instructions' => 'SEO Title для конкретного города. Если заполнено, заменяет основной SEO Title.',
                'conditional_logic' => [
                    [
                        'field' => 'city_selector',
                        'operator' => '!=empty',
                    ],
                ],
            ],
            [
                'key' => 'field_taxonomy_city_specific_seo_description',
                'label' => 'Meta Description для города',
                'name' => 'city_seo_description',
                'type' => 'textarea',
                'rows' => 3,
                'wrapper' => ['width' => '50'],
                'instructions' => 'Meta Description для конкретного города. Если заполнено, заменяет основной Meta Description.',
                'conditional_logic' => [
                    [
                        'field' => 'city_selector',
                        'operator' => '!=empty',
                    ],
                ],
            ],
            
            // Вкладка: Контент для выбранного города
            [
                'key' => 'field_taxonomy_city_specific_tab_content',
                'label' => 'Контент для выбранного города',
                'type' => 'tab',
                'conditional_logic' => [
                    [
                        'field' => 'city_selector',
                        'operator' => '!=empty',
                    ],
                ],
            ],
            [
                'key' => 'field_taxonomy_city_specific_custom_h1',
                'label' => 'H1 Заголовок для города',
                'name' => 'city_custom_h1',
                'type' => 'text',
                'wrapper' => ['width' => '50'],
                'instructions' => 'H1 заголовок для конкретного города. Если заполнено, заменяет основной H1.',
                'conditional_logic' => [
                    [
                        'field' => 'city_selector',
                        'operator' => '!=empty',
                    ],
                ],
            ],
            [
                'key' => 'field_taxonomy_city_specific_description',
                'label' => 'Описание для города',
                'name' => 'city_description',
                'type' => 'wysiwyg',
                'media_upload' => false,
                'tabs' => 'visual',
                'toolbar' => 'basic',
                'wrapper' => ['width' => '100'],
                'instructions' => 'Описание для конкретного города. Если заполнено, заменяет основное описание.',
                'conditional_logic' => [
                    [
                        'field' => 'city_selector',
                        'operator' => '!=empty',
                    ],
                ],
            ],
            [
                'key' => 'field_taxonomy_city_specific_main_text',
                'label' => 'Основной контент для города',
                'name' => 'city_main_text',
                'type' => 'wysiwyg',
                'media_upload' => true,
                'tabs' => 'all',
                'toolbar' => 'full',
                'wrapper' => ['width' => '100'],
                'instructions' => 'Основной контент для конкретного города. Если заполнено, добавляется к основному контенту.',
                'conditional_logic' => [
                    [
                        'field' => 'city_selector',
                        'operator' => '!=empty',
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'taxonomy',
                    'operator' => '==',
                    'value' => 'all',
                ],
            ],
        ],
        'position' => 'normal',
        'menu_order' => 2,
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
    ]);
});
