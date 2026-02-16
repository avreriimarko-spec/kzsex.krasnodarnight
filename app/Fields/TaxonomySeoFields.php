<?php

namespace App\Fields;

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }
    
    acf_add_local_field_group([
        'key' => 'group_taxonomy_seo_extended',
        'title' => 'SEO и Контент (Расширенные)',
        'fields' => [
            // Вкладка: Мета-теги
            [
                'key' => 'field_taxonomy_tab_seo_meta',
                'label' => 'Meta Tags',
                'type' => 'tab',
            ],
            [
                'key' => 'field_taxonomy_seo_title',
                'label' => 'SEO Title (Browser Tab)',
                'name' => 'seo_title',
                'type' => 'text',
                'instructions' => 'Если пусто, используется стандартный заголовок WP.',
            ],
            [
                'key' => 'field_taxonomy_seo_description',
                'label' => 'Meta Description',
                'name' => 'seo_description',
                'type' => 'textarea',
                'rows' => 3,
            ],

            // Вкладка: Контент на странице
            [
                'key' => 'field_taxonomy_tab_seo_content',
                'label' => 'Контент',
                'type' => 'tab',
            ],
            [
                'key' => 'field_taxonomy_custom_h1',
                'label' => 'H1 Заголовок',
                'name' => 'custom_h1',
                'type' => 'text',
                'instructions' => 'Переопределяет название страницы.',
            ],
            [
                'key' => 'field_taxonomy_description',
                'label' => 'Описание под H1',
                'name' => 'description',
                'type' => 'wysiwyg',
                'media_upload' => false,
                'tabs' => 'visual',
                'toolbar' => 'basic',
                'instructions' => 'Описание страницы под заголовком.',
            ],
            [
                'key' => 'field_taxonomy_main_seo_text',
                'label' => 'Основной текст (SEO)',
                'name' => 'main_seo_text',
                'type' => 'wysiwyg',
                'media_upload' => true,
                'tabs' => 'all',
                'toolbar' => 'full',
                'instructions' => 'Будет обернут в тег <article>. Поддерживает HTML.',
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
        'menu_order' => 0,
    ]);
});
