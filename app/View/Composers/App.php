<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;
use App\Services\SchemaGenerator; // Импорт нашего сервиса

class App extends Composer
{
    protected static $views = [
        '*', // Глобально для всех
    ];

    public function with()
    {
        return [
            'siteName' => $this->siteName(),
            'schemaJson' => $this->getSchemaJson(),
            'telegram' => [
                'enabled' => get_field('tg_popup_enable', 'option'),
                'link'    => get_field('tg_popup_link', 'option'),
            ],
            'contacts' => [
                'telegram' => get_field('global_tg', 'option'),
                'whatsapp' => get_field('global_wa', 'option'),
                'phone'    => get_field('global_phone', 'option'),
            ],
        ];
    }

    public function siteName()
    {
        return get_bloginfo('name', 'display');
    }

    // Вызываем наш сервис
    public function getSchemaJson()
    {
        return (new SchemaGenerator())->render();
    }
}
