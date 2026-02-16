<?php

namespace App\Fields;

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key' => 'group_page_work',
        'title' => 'Настройки страницы "Работа"',
        'fields' => [
            // Вкладка: Преимущества
            [
                'key' => 'field_tab_work_benefits',
                'label' => 'Преимущества',
                'type' => 'tab',
            ],
            [
                'key' => 'field_work_benefits',
                'label' => 'Почему мы?',
                'name' => 'work_benefits',
                'type' => 'repeater',
                'layout' => 'row',
                'button_label' => 'Добавить преимущество',
                'sub_fields' => [
                    [
                        'key' => 'field_wb_title',
                        'label' => 'Заголовок',
                        'name' => 'title',
                        'type' => 'text',
                        'placeholder' => 'Высокий доход',
                    ],
                    [
                        'key' => 'field_wb_desc',
                        'label' => 'Описание',
                        'name' => 'description',
                        'type' => 'textarea',
                        'rows' => 2,
                    ],
                ],
            ],

            // Вкладка: Условия/Требования
            [
                'key' => 'field_tab_work_req',
                'label' => 'Условия',
                'type' => 'tab',
            ],
            [
                'key' => 'field_work_requirements',
                'label' => 'Список требований/условий',
                'name' => 'work_requirements',
                'type' => 'repeater',
                'layout' => 'table',
                'button_label' => 'Добавить пункт',
                'sub_fields' => [
                    [
                        'key' => 'field_req_text',
                        'label' => 'Текст',
                        'name' => 'text',
                        'type' => 'text',
                        'placeholder' => 'Возраст от 18 лет',
                    ],
                ],
            ],

            // Вкладка: Форма
            [
                'key' => 'field_tab_work_form',
                'label' => 'Форма',
                'type' => 'tab',
            ],
            [
                'key' => 'field_work_form_shortcode',
                'label' => 'Шорткод анкеты',
                'name' => 'work_form_shortcode',
                'type' => 'text',
                'instructions' => 'Вставьте сюда шорткод (например: [contact-form-7 id="123"])',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'page_template',
                    'operator' => '==',
                    'value' => 'template-work.blade.php',
                ],
            ],
        ],
    ]);
});
