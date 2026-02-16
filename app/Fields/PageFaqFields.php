<?php

namespace App\Fields;

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key' => 'group_page_faq',
        'title' => 'Настройки FAQ',
        'fields' => [
            [
                'key' => 'field_faq_list',
                'label' => 'Список вопросов',
                'name' => 'faq_list',
                'type' => 'repeater',
                'layout' => 'row',
                'button_label' => 'Добавить вопрос',
                'sub_fields' => [
                    [
                        'key' => 'field_faq_question',
                        'label' => 'Вопрос',
                        'name' => 'question',
                        'type' => 'text',
                    ],
                    [
                        'key' => 'field_faq_answer',
                        'label' => 'Ответ',
                        'name' => 'answer',
                        'type' => 'wysiwyg', // Визуальный редактор, чтобы можно было вставить ссылки или списки
                        'media_upload' => false,
                        'toolbar' => 'basic',
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'page_template', // Показываем только если выбран шаблон FAQ
                    'operator' => '==',
                    'value' => 'template-faq.blade.php',
                ],
            ],
        ],
    ]);
});
