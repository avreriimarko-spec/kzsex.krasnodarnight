<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;
use App\Services\ProfileQuery;

class ProfilesCatalog extends Composer
{
    protected static $views = [
        'template-profiles',
        'template-verified',
        'template-elite',
        'template-cheap',
        'template-incall',
        'template-outcall',
        'template-independent',
        'template-vip',
    ];

    const FILTER_TAXONOMIES = [
        'service'       => 'Услуги',
        'hair_color'    => 'Цвет волос',
        'breast_size'   => 'Размер груди',
        'body_type'     => 'Телосложение',
        'ethnicity'     => 'Типаж',
        'nationality'   => 'Национальность',
        'eye_color'     => 'Цвет глаз',
        'hair_length'   => 'Длина волос',
        'breast_type'   => 'Тип груди',
        'intimate'      => 'Интимная стрижка',
        'piercing'      => 'Пирсинг',
        'travel'        => 'Путешествия',
        'smoker'        => 'Курение',
        'inoutcall'     => 'У себя / Выезд',
        'what'          => 'Что',
        'parameters'    => 'Параметры',
        'metadata'      => 'Метаданные',
        'metro'         => 'Метро',
        'district'      => 'Районы'
    ];

    public function with()
    {
        return [
            'profiles_query' => ProfileQuery::get(),
            'filter_data'    => $this->getFilterData(),
        ];
    }

    private function getFilterData()
    {
        $data = [];
        foreach (self::FILTER_TAXONOMIES as $slug => $label) {
            $terms = get_terms([
                'taxonomy'   => $slug,
                'hide_empty' => true,
            ]);

            if (!is_wp_error($terms) && !empty($terms)) {
                $data[$slug] = [
                    'label' => $label,
                    'terms' => $terms,
                ];
            }
        }
        return $data;
    }
}
