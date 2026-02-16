<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class PageAbout extends Composer
{
    protected static $views = [
        'template-about',
    ];

    public function with()
    {
        return [
            'stats' => get_field('about_stats'),
            'features' => get_field('about_features'),
        ];
    }
}
