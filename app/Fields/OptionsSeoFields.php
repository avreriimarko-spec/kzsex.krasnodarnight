<?php

namespace App\Fields;

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    if (function_exists('acf_add_options_page')) {
        acf_add_options_page([
            'page_title' => 'Настройки Темы',
            'menu_title' => 'Настройки Темы',
            'menu_slug'  => 'theme-general-settings',
            'capability' => 'edit_posts',
            'redirect'   => false,
        ]);
    }

    acf_add_local_field_group([
        'key' => 'group_schema_global',
        'title' => 'Глобальные настройки сайта',
        'fields' => [
            // --- ВКЛАДКА: КОНТАКТЫ И МЕССЕНДЖЕРЫ ---
            [
                'key' => 'field_tab_messengers',
                'label' => 'Контакты (Global)',
                'type' => 'tab',
            ],
            // Email (оставляем, пригодится)
            [
                'key' => 'field_schema_email',
                'label' => 'Email',
                'name' => 'schema_email',
                'type' => 'email',
            ],

            // 1. WhatsApp
            [
                'key' => 'field_global_wa',
                'label' => 'Глобальный WhatsApp',
                'name' => 'global_wa',
                'type' => 'text',
                'placeholder' => '79991234567',
                'instructions' => 'Только цифры. Выводится на странице Контакты.',
            ],
            [
                'key' => 'field_override_wa_globally',
                'label' => 'Подменять WhatsApp в анкетах?',
                'name' => 'override_wa_globally',
                'type' => 'true_false',
                'ui' => 1,
                'ui_on_text' => 'Да',
                'ui_off_text' => 'Нет',
            ],

            // 2. Telegram
            [
                'key' => 'field_global_tg',
                'label' => 'Глобальный Telegram',
                'name' => 'global_tg',
                'type' => 'text',
                'placeholder' => 'username',
                'prepend' => '@',
                'instructions' => 'Выводится на странице Контакты.',
            ],
            [
                'key' => 'field_override_tg_globally',
                'label' => 'Подменять Telegram в анкетах?',
                'name' => 'override_tg_globally',
                'type' => 'true_false',
                'ui' => 1,
                'ui_on_text' => 'Да',
                'ui_off_text' => 'Нет',
            ],

            // 3. Телефон
            [
                'key' => 'field_global_phone',
                'label' => 'Глобальный Номер Телефона',
                'name' => 'global_phone',
                'type' => 'text',
                'placeholder' => '+79991234567',
                'instructions' => 'Выводится на странице Контакты и в шапке/футере.',
            ],
            [
                'key' => 'field_override_phone_globally',
                'label' => 'Подменять Номер в анкетах?',
                'name' => 'override_phone_globally',
                'type' => 'true_false',
                'ui' => 1,
                'ui_on_text' => 'Да',
                'ui_off_text' => 'Нет',
            ],

            // --- ВКЛАДКА: ОРГАНИЗАЦИЯ (Для SEO/Schema) ---
            [
                'key' => 'field_schema_tab_org',
                'label' => 'Организация / SEO',
                'type' => 'tab',
            ],
            [
                'key' => 'field_schema_org_name',
                'label' => 'Название Организации',
                'name' => 'schema_org_name',
                'type' => 'text',
                'default_value' => get_bloginfo('name'),
            ],
            [
                'key' => 'field_schema_legal_name',
                'label' => 'Юридическое название',
                'name' => 'schema_legal_name',
                'type' => 'text',
            ],
            [
                'key' => 'field_schema_phone',
                'label' => 'Телефон для LD-JSON',
                'name' => 'schema_phone',
                'type' => 'text',
                'placeholder' => '+79991234567',
                'instructions' => 'Используется в структурированных данных (Schema.org LD-JSON)',
            ],
            [
                'key' => 'field_schema_logo',
                'label' => 'Логотип (URL)',
                'name' => 'schema_logo',
                'type' => 'image',
                'return_format' => 'url',
            ],
            [
                'key' => 'field_schema_address',
                'label' => 'Адрес (Одной строкой)',
                'name' => 'schema_address',
                'type' => 'text',
            ],
            [
                'key' => 'field_schema_geo_lat',
                'label' => 'Широта (Latitude)',
                'name' => 'schema_geo_lat',
                'type' => 'text',
                'wrapper' => ['width' => '50'],
            ],
            [
                'key' => 'field_schema_geo_lng',
                'label' => 'Долгота (Longitude)',
                'name' => 'schema_geo_lng',
                'type' => 'text',
                'wrapper' => ['width' => '50'],
            ],
            [
                'key' => 'field_schema_price_range',
                'label' => 'Диапазон цен',
                'name' => 'schema_price_range',
                'type' => 'text',
                'default_value' => '$$$',
            ],
            [
                'key' => 'field_schema_image',
                'label' => 'Фото офиса (Schema)',
                'name' => 'schema_image',
                'type' => 'image',
                'return_format' => 'url',
            ],

            // --- ВКЛАДКА: МАРКЕТИНГ ---
            [
                'key' => 'field_tab_marketing',
                'label' => 'Маркетинг',
                'type' => 'tab',
            ],
            [
                'key' => 'field_tg_popup_enable',
                'label' => 'Включить Telegram Popup?',
                'name' => 'tg_popup_enable',
                'type' => 'true_false',
                'ui' => 1,
            ],
            [
                'key' => 'field_tg_popup_link',
                'label' => 'Ссылка на канал',
                'name' => 'tg_popup_link',
                'type' => 'url',
                'conditional_logic' => [
                    [['field' => 'field_tg_popup_enable', 'operator' => '==', 'value' => '1']]
                ],
            ],

            // --- ВКЛАДКА: TELEGRAM BOT ---
            [
                'key' => 'field_tab_tg_bot',
                'label' => 'Telegram Бот (Заявки)',
                'type' => 'tab',
            ],
            [
                'key' => 'field_tg_bot_token',
                'label' => 'Bot Token',
                'name' => 'tg_bot_token',
                'type' => 'text',
            ],
            [
                'key' => 'field_tg_chat_id',
                'label' => 'Chat ID',
                'name' => 'tg_chat_id',
                'type' => 'text',
            ],
            // --- Маски ---
            [
                'key' => 'field_tab_phone_masks',
                'label' => 'Маски телефонов',
                'type' => 'tab',
            ],
            [
                'key' => 'field_phone_masks_settings',
                'label' => 'Настройка стран и масок',
                'name' => 'phone_masks_settings',
                'type' => 'repeater',
                'button_label' => 'Добавить страну',
                'layout' => 'table',
                'sub_fields' => [
                    [
                        'key' => 'field_mask_code',
                        'label' => 'Код (value)',
                        'name' => 'code',
                        'type' => 'text',
                        'placeholder' => 'ru',
                        'wrapper' => ['width' => '15'],
                    ],
                    [
                        'key' => 'field_mask_label',
                        'label' => 'Название в списке',
                        'name' => 'label',
                        'type' => 'text',
                        'placeholder' => 'RU 🇷🇺',
                        'wrapper' => ['width' => '25'],
                    ],
                    [
                        'key' => 'field_mask_pattern',
                        'label' => 'Маска (Alpine)',
                        'name' => 'pattern',
                        'type' => 'text',
                        'placeholder' => '+7 (999) 999-99-99',
                        'instructions' => '9 - цифра, a - буква, * - любой символ',
                        'wrapper' => ['width' => '40'],
                    ],
                    [
                        'key' => 'field_mask_default',
                        'label' => 'По умолч.',
                        'name' => 'is_default',
                        'type' => 'true_false',
                        'ui' => 1,
                        'wrapper' => ['width' => '20'],
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'theme-general-settings',
                ],
            ],
        ],
    ]);
});
