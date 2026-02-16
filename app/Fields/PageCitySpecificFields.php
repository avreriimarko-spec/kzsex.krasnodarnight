<?php

namespace App\Fields;

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }
    
    acf_add_local_field_group([
        'key' => 'group_page_city_specific',
        'title' => 'Информация по Городам',
        'fields' => [
            // Вкладка с выбором города
            [
                'key' => 'field_page_city_specific_tab_main',
                'label' => 'Настройки по Городам',
                'type' => 'tab',
                'instructions' => 'Здесь можно добавить специфичную информацию для каждого города. Если заполнено, будет показываться вместо основной информации.',
            ],
            
            // Repeater для городов
            [
                'key' => 'field_page_city_specific_repeater',
                'label' => 'Города',
                'name' => 'city_pages_seo',
                'type' => 'repeater',
                'layout' => 'row',
                'button_label' => 'Добавить город',
                'collapsed' => 'field_page_city_selector', // Сворачивать по выбору города
                'sub_fields' => [
                    // Заголовок с именем города (аккордеон)
                    [
                        'key' => 'field_page_city_header',
                        'label' => 'Город',
                        'name' => '',
                        'type' => 'message',
                        'message' => 'Настройки для города',
                        'wrapper' => ['class' => 'city-header'],
                    ],
                    
                    // Выбор города
                    [
                        'key' => 'field_page_city_selector',
                        'label' => 'Выберите город',
                        'name' => 'city',
                        'type' => 'taxonomy',
                        'taxonomy' => 'city',
                        'field_type' => 'select',
                        'multiple' => 0,
                        'allow_null' => 0,
                        'return_format' => 'object',
                        'add_term' => 0,
                        'wrapper' => ['width' => '100'],
                        'instructions' => 'Выберите город для которого хотите добавить информацию.',
                    ],
                    
                    // Вкладка: SEO для города
                    [
                        'key' => 'field_page_city_tab_seo',
                        'label' => 'SEO',
                        'type' => 'tab',
                    ],
                    [
                        'key' => 'field_page_city_seo_title',
                        'label' => 'SEO Title для города',
                        'name' => 'seo_title',
                        'type' => 'text',
                        'wrapper' => ['width' => '50'],
                        'instructions' => 'SEO Title для конкретного города. Если заполнено, заменяет основной SEO Title.',
                    ],
                    [
                        'key' => 'field_page_city_seo_description',
                        'label' => 'Meta Description для города',
                        'name' => 'meta_description',
                        'type' => 'textarea',
                        'rows' => 3,
                        'wrapper' => ['width' => '50'],
                        'instructions' => 'Meta Description для конкретного города. Если заполнено, заменяет основной Meta Description.',
                    ],
                    
                    // Вкладка: Контент для города
                    [
                        'key' => 'field_page_city_tab_content',
                        'label' => 'Контент',
                        'type' => 'tab',
                    ],
                    [
                        'key' => 'field_page_city_custom_h1',
                        'label' => 'H1 Заголовок для города',
                        'name' => 'h1',
                        'type' => 'text',
                        'wrapper' => ['width' => '50'],
                        'instructions' => 'H1 заголовок для конкретного города. Если заполнено, заменяет основной H1.',
                    ],
                    [
                        'key' => 'field_page_city_description',
                        'label' => 'Описание для города',
                        'name' => 'description',
                        'type' => 'wysiwyg',
                        'media_upload' => false,
                        'tabs' => 'visual',
                        'toolbar' => 'basic',
                        'wrapper' => ['width' => '50'],
                        'instructions' => 'Описание для конкретного города. Если заполнено, заменяет основное описание.',
                    ],
                    [
                        'key' => 'field_page_city_main_text',
                        'label' => 'Основной контент для города',
                        'name' => 'main_text',
                        'type' => 'wysiwyg',
                        'media_upload' => true,
                        'tabs' => 'all',
                        'toolbar' => 'full',
                        'wrapper' => ['width' => '100'],
                        'instructions' => 'Основной контент для конкретного города. Если заполнено, добавляется к основному контенту.',
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'page',
                ],
            ],
        ],
        'position' => 'normal',
        'menu_order' => 3,
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
    ]);
});
