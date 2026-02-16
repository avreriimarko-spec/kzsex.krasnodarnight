<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class PageContact extends Composer
{
    protected static $views = [
        'template-contact',
    ];

    public function with()
    {
        return [
            'contacts' => [
                'email' => get_field('schema_email', 'option'),
                'tg'    => get_field('global_tg', 'option'),
                'wa'    => get_field('global_wa', 'option'),
                'whatsapp' => get_field('global_wa', 'option'), // Добавляем для совместимости
                'telegram' => get_field('global_tg', 'option'), // Добавляем для совместимости
                'phone' => get_field('global_phone', 'option'),
                'hours' => get_field('contact_hours') ?: '10:00 - 22:00',
            ],
            'form_shortcode' => get_field('contact_shortcode'),
        ];
    }
}
