<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class Forms extends Composer
{
    /**
     * Подключаем данные ТОЛЬКО к компонентам форм
     */
    protected static $views = [
        'components.work-form',
        'components.contact-form',
    ];

    public function with()
    {
        return [
            'phoneSettings' => $this->getPhoneSettings(),
        ];
    }

    private function getPhoneSettings()
    {
        // Получаем данные из ACF Options
        $rows = get_field('phone_masks_settings', 'option');

        $masksObj = [];
        $list = [];
        $default = 'ru';

        if ($rows) {
            foreach ($rows as $row) {
                // Формируем объект масок для Alpine: {'ru': '+7...', 'us': '+1...'}
                $masksObj[$row['code']] = $row['pattern'];

                // Формируем список для селекта
                $list[] = [
                    'code' => $row['code'],
                    'label' => $row['label']
                ];

                if ($row['is_default']) {
                    $default = $row['code'];
                }
            }
        } else {
            // Фолбэк, если в админке пусто
            $masksObj = ['ru' => '+7 (999) 999-99-99', 'other' => '999999999999999'];
            $list = [['code' => 'ru', 'label' => 'RU'], ['code' => 'other', 'label' => '🌍']];
        }

        return [
            'masks' => $masksObj,
            'list' => $list,
            'default' => $default
        ];
    }
}
