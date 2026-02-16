<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class PageWork extends Composer
{
    protected static $views = [
        'template-work',
    ];

    public function with()
    {
        return [
            'benefits' => get_field('work_benefits'),
            'requirements' => get_field('work_requirements'),
            'form_shortcode' => get_field('work_form_shortcode'),
        ];
    }
}
