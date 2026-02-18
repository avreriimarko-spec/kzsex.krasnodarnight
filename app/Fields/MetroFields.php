<?php

namespace App\Fields;

use Log1x\AcfComposer\Field;
use StoutLogic\AcfBuilder\FieldsBuilder;

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    // Создаем группу полей для Метро
    $metro = new FieldsBuilder('metro_settings', [
        'title' => 'Настройки станции',
        'style' => 'seamless', 
    ]);

    // Указываем: показывать эти поля, когда редактируем Таксономию "Метро"
    $metro->setLocation('taxonomy', '==', 'metro');

    // Добавляем поле "Город" (тот самый штамп)
    $metro
        ->addTaxonomy('related_city', [ 
            'label' => 'Город',
            'instructions' => 'К какому городу относится эта станция?',
            'taxonomy' => 'city', // Берем список городов
            'field_type' => 'select', // Выпадающий список
            'allow_null' => 0,
            'add_term' => 0, // Не даем создавать города отсюда, только выбирать
            'save_terms' => 0, // Сохраняем как мета-данные
            'return_format' => 'id', // В базе храним ID города
        ]);

    acf_add_local_field_group($metro->build());
});