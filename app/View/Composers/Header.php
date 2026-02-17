<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;
use App\Helpers\CityCatalog;

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
        $cities = get_terms([
            'taxonomy'   => 'city',
            'hide_empty' => false,
            'slug'       => CityCatalog::getSlugs(),
        ]);

        if (is_wp_error($cities) || !is_array($cities)) {
            return $cities;
        }

        $orderMap = array_flip(CityCatalog::getSlugs());
        usort($cities, static function ($a, $b) use ($orderMap) {
            $aOrder = $orderMap[$a->slug] ?? PHP_INT_MAX;
            $bOrder = $orderMap[$b->slug] ?? PHP_INT_MAX;
            return $aOrder <=> $bOrder;
        });

        return $cities;
    }
}
