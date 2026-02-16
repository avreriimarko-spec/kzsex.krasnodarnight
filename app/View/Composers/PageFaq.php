<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class PageFaq extends Composer
{
    protected static $views = [
        'template-faq',
    ];

    public function with()
    {
        return [
            'faq_items' => get_field('faq_list'),
        ];
    }
}
