<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class Services extends Composer
{
    protected static $views = [
        'template-services',
    ];

    public function with()
    {
        return [
            'filter_data' => $this->getFilterData(),
        ];
    }

    private function getFilterData()
    {
        $data = [];
        
        // Те же таксономии, что и в ProfilesCatalog
        $taxonomies = [
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
        ];

        foreach ($taxonomies as $slug => $label) {
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
