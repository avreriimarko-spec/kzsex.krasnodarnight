<?php

namespace App\Fields;

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key' => 'group_page_contact',
        'title' => 'Настройки Контактов',
        'fields' => [
            // Вкладка: Данные
            [
                'key' => 'field_tab_contact_info',
                'label' => 'Контакты',
                'type' => 'tab',
            ],
            [
                'key' => 'field_contact_email',
                'label' => 'Email',
                'name' => 'contact_email',
                'type' => 'email',
            ],
            [
                'key' => 'field_contact_tg',
                'label' => 'Telegram (username)',
                'name' => 'contact_tg',
                'type' => 'text',
                'prepend' => '@',
            ],
            [
                'key' => 'field_contact_wa',
                'label' => 'WhatsApp (номер)',
                'name' => 'contact_wa',
                'type' => 'text',
                'instructions' => 'Без плюса и скобок, только цифры (напр. 79991234567)',
            ],
            [
                'key' => 'field_contact_hours',
                'label' => 'Время работы',
                'name' => 'contact_hours',
                'type' => 'text',
                'default_value' => 'Ежедневно: 10:00 - 22:00',
            ],

            // Вкладка: Форма
            [
                'key' => 'field_tab_contact_form',
                'label' => 'Форма',
                'type' => 'tab',
            ],
            [
                'key' => 'field_contact_shortcode',
                'label' => 'Шорткод формы',
                'name' => 'contact_shortcode',
                'type' => 'text',
                'instructions' => 'Вставьте сюда шорткод (например: [contact-form-7 id="123"])',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'page_template',
                    'operator' => '==',
                    'value' => 'template-contact.blade.php',
                ],
            ],
        ],
    ]);
});
