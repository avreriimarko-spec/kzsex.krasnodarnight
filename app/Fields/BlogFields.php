<?php

namespace App\Fields;

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key' => 'group_template_blog_page',
        'title' => 'Блог: заголовок и описание',
        'fields' => [
            [
                'key' => 'field_blog_page_h1',
                'label' => 'H1',
                'name' => 'h1',
                'type' => 'text',
                'instructions' => 'Если пусто, используется заголовок страницы.',
            ],
            [
                'key' => 'field_blog_page_lead',
                'label' => 'Текст под H1',
                'name' => 'p',
                'type' => 'textarea',
                'rows' => 3,
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'page_template',
                    'operator' => '==',
                    'value' => 'template-blog.blade.php',
                ],
            ],
        ],
        'position' => 'acf_after_title',
    ]);

    acf_add_local_field_group([
        'key' => 'group_blog_article_fields',
        'title' => 'Блог: поля статьи',
        'fields' => [
            [
                'key' => 'field_blog_h1_statiya',
                'label' => 'H1 статьи',
                'name' => 'h1_statiya',
                'type' => 'text',
                'instructions' => 'Если пусто, используется обычный заголовок записи.',
            ],
            [
                'key' => 'field_blog_p_statiya',
                'label' => 'Короткое описание',
                'name' => 'p_statiya',
                'type' => 'textarea',
                'rows' => 3,
            ],
            [
                'key' => 'field_blog_seo_statiya',
                'label' => 'SEO описание',
                'name' => 'seo_statiya',
                'type' => 'textarea',
                'rows' => 3,
            ],
            [
                'key' => 'field_blog_photo_statiya',
                'label' => 'Фото статьи',
                'name' => 'photo_statiya',
                'type' => 'image',
                'return_format' => 'array',
                'preview_size' => 'medium',
                'library' => 'all',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'blog',
                ],
            ],
        ],
        'position' => 'acf_after_title',
    ]);
});
