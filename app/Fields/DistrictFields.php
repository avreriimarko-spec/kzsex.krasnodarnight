<?php

namespace App\Fields;

use Log1x\AcfComposer\Field;
use StoutLogic\AcfBuilder\FieldsBuilder;

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    // Создаем группу полей для Районов
    $district = new FieldsBuilder('district_settings', [
        'title' => 'Настройки района',
        'style' => 'seamless', 
    ]);

    // Указываем: показывать эти поля, когда редактируем Таксономию "Районы"
    $district->setLocation('taxonomy', '==', 'district');

    // Добавляем поле "Город" (тот самый штамп)
    $district
        ->addTaxonomy('related_city', [ 
            'label' => 'Город',
            'instructions' => 'К какому городу относится этот район?',
            'taxonomy' => 'city', // Берем список городов
            'field_type' => 'select', // Выпадающий список
            'allow_null' => 0,
            'add_term' => 0, // Не даем создавать города отсюда, только выбирать
            'save_terms' => 0, // Сохраняем как мета-данные
            'return_format' => 'id', // В базе храним ID города
        ]);

    acf_add_local_field_group($district->build());
});