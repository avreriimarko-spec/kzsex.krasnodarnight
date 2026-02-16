<?php

namespace App\Fields;

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key' => 'group_profile_details',
        'title' => 'Данные анкеты',
        'fields' => [
            // Вкладка: Основное
            [
                'key' => 'field_tab_main',
                'label' => 'Основное',
                'type' => 'tab',
            ],
            [
                'key' => 'field_age',
                'label' => 'Возраст',
                'name' => 'age',
                'type' => 'number',
            ],
            [
                'key' => 'field_weight',
                'label' => 'Вес (кг)',
                'name' => 'weight',
                'type' => 'number',
            ],
            [
                'key' => 'field_height',
                'label' => 'Рост (см)',
                'name' => 'height',
                'type' => 'number',
            ],
            [
                'key' => 'field_phone',
                'label' => 'Телефон',
                'name' => 'phone',
                'type' => 'text',
            ],
            [
                'key' => 'field_whatsapp',
                'label' => 'WhatsApp',
                'name' => 'whatsapp',
                'type' => 'text',
            ],
            [
                'key' => 'field_telegram',
                'label' => 'Telegram',
                'name' => 'telegram',
                'type' => 'text',
            ],
            [
                'key' => 'field_online',
                'label' => 'Онлайн (в сети)',
                'name' => 'online',
                'type' => 'true_false',
                'default_value' => 0,
                'ui' => 1,
                'instructions' => 'Отметьте, если модель сейчас онлайн и доступна для общения',
            ],

            // Вкладка: Медиа (ГАЛЕРЕЯ) - ТО, ЧТО МЫ ДОБАВЛЯЕМ
            [
                'key' => 'field_tab_media',
                'label' => 'Медиа',
                'type' => 'tab',
            ],
            [
                'key' => 'field_gallery',
                'label' => 'Галерея фото',
                'name' => 'gallery', // Важно: это имя используется в импорте
                'type' => 'gallery',
                'instructions' => 'Загрузите сюда дополнительные фото',
                'return_format' => 'array', // Важно для шаблона
                'preview_size' => 'medium',
                'library' => 'all',
            ],

            // Вкладка: Цены
            [
                'key' => 'field_tab_pricing',
                'label' => 'Цены',
                'type' => 'tab',
            ],
            [
                'key' => 'field_group_price',
                'label' => 'Прайс-лист',
                'name' => 'price',
                'type' => 'group',
                'layout' => 'block',
                'sub_fields' => [
                    [
                        'key' => 'field_currency',
                        'label' => 'Валюта',
                        'name' => 'currency',
                        'type' => 'text',
                        'default_value' => 'KZT',
                    ],
                    [
                        'key' => 'field_price_1h',
                        'label' => '1 Час (Аппарты)',
                        'name' => 'price_1h',
                        'type' => 'number',
                    ],
                    [
                        'key' => 'field_price_2h',
                        'label' => '2 Часа (Аппарты)',
                        'name' => 'price_2h',
                        'type' => 'number',
                    ],
                    [
                        'key' => 'field_price_night',
                        'label' => 'Ночь (Аппарты)',
                        'name' => 'price_night',
                        'type' => 'number',
                    ],
                    [
                        'key' => 'field_price_1h_out',
                        'label' => '1 Час (Выезд)',
                        'name' => 'price_1h_out',
                        'type' => 'number',
                    ],
                    [
                        'key' => 'field_price_2h_out',
                        'label' => '2 Часа (Выезд)',
                        'name' => 'price_2h_out',
                        'type' => 'number',
                    ],
                    [
                        'key' => 'field_price_4h',
                        'label' => '4 Часа (Аппарты)',
                        'name' => 'price_4h',
                        'type' => 'number',
                    ],
                    [
                        'key' => 'field_price_day',
                        'label' => 'Сутки (Аппарты)',
                        'name' => 'price_day',
                        'type' => 'number',
                    ],
                    [
                        'key' => 'field_price_4h_out',
                        'label' => '4 Часа (Выезд)',
                        'name' => 'price_4h_out',
                        'type' => 'number',
                    ],
                    [
                        'key' => 'field_price_day_out',
                        'label' => 'Сутки (Выезд)',
                        'name' => 'price_day_out',
                        'type' => 'number',
                    ],
                    [
                        'key' => 'field_price_night_out',
                        'label' => 'Ночь (Выезд)',
                        'name' => 'price_night_out',
                        'type' => 'number',
                    ],
                ],
            ],

            // Вкладка: Отзывы
            [
                'key' => 'field_tab_reviews',
                'label' => 'Отзывы',
                'type' => 'tab',
            ],
            [
                'key' => 'field_reviews_list',
                'label' => 'Список отзывов',
                'name' => 'reviews_list',
                'type' => 'repeater',
                'layout' => 'row',
                'sub_fields' => [
                    [
                        'key' => 'field_review_author',
                        'label' => 'Автор',
                        'name' => 'author',
                        'type' => 'text',
                    ],
                    [
                        'key' => 'field_review_rating',
                        'label' => 'Рейтинг',
                        'name' => 'rating',
                        'type' => 'number',
                    ],
                    [
                        'key' => 'field_review_content',
                        'label' => 'Текст',
                        'name' => 'content',
                        'type' => 'textarea',
                    ],
                    [
                        'key' => 'field_review_date',
                        'label' => 'Дата',
                        'name' => 'date',
                        'type' => 'date_picker',
                        'return_format' => 'Ymd',
                    ],
                ],
            ],
            [
                'key' => 'field_tab_profile_seo',
                'label' => 'SEO',
                'type' => 'tab',
            ],
            [
                'key' => 'field_profile_seo_title',
                'label' => 'SEO Title (Browser Tab)',
                'name' => 'seo_title',
                'type' => 'text',
            ],
            [
                'key' => 'field_profile_seo_desc',
                'label' => 'Meta Description',
                'name' => 'seo_description',
                'type' => 'textarea',
                'rows' => 3,
            ],
            // Вкладка: Системное
            [
                'key' => 'field_tab_system',
                'label' => 'Системное',
                'type' => 'tab',
            ],
            [
                'key' => 'field_import_uuid',
                'label' => 'UUID импорта',
                'name' => 'import_uuid',
                'type' => 'text',
                'readonly' => 1,
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'profile',
                ],
            ],
        ],
    ]);
});
