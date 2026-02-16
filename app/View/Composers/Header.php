<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class Header extends Composer
{
    protected static $views = [
        'sections.header',
    ];

    public function with()
    {
        return [
            'cities' => $this->getCities(),
        ];
    }

    private function getCities()
    {
        return get_terms([
            'taxonomy'   => 'city',
            'hide_empty' => true,
        ]);
    }
}
