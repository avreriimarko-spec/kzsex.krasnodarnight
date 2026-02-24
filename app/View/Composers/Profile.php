<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class Profile extends Composer
{
    protected static $views = [
        'partials.content-single-profile',
        'partials.content-profile',
        'components.profile-card',
        'components.profile-list-card',
    ];

    public function with()
    {
        $isSingle = is_singular('profile');
        $id = get_the_ID();

        // Получаем контакты из анкеты (если есть) или из глобальных настроек
        $profilePhone = get_field('phone', $id);
        $profileTg = get_field('telegram', $id);
        $profileWa = get_field('whatsapp', $id);
        
        $globalPhone = get_field('global_phone', 'option');
        $globalTg = get_field('global_tg', 'option');
        $globalWa = get_field('global_wa', 'option');
        
        return [
            'details'   => $this->details(),
            'badges'    => $this->badges(),
            'city'      => $this->getFirstTermName('city'),
            'breast'    => $this->getFirstTermName('breast_size'),

            // Данные только для одиночной анкеты
            'traits'    => $isSingle ? $this->traits() : [],
            'price'     => get_field('price', $id), // Цена нужна и в каталоге тоже
            'services'  => $isSingle ? $this->getTerms('service') : [],
            'gallery'   => $isSingle ? get_field('gallery', $id) : [],
            'reviews'   => $isSingle ? $this->getReviews($id) : [],

            // КОНТАКТЫ: берем из анкеты, если есть, иначе из глобальных
            'contacts'  => $isSingle ? [
                'phone' => $profilePhone ?: $globalPhone,
                'tg'    => $profileTg ?: $globalTg,
                'wa'    => $profileWa ?: $globalWa,
            ] : [],
        ];
    }

    public function details()
    {
        return [
            // 'age'    => get_field('age'), // чтобы не появлялся в параметрах
            'height' => get_field('height'),
            'weight' => get_field('weight'),
        ];
    }

    private function getTraitItems($taxonomy)
    {
        $terms = get_the_terms(get_the_ID(), $taxonomy);
        if (!$terms || is_wp_error($terms)) return [];

        $items = [];
        foreach ($terms as $term) {
            $name = $term->name;
            
            // Сохраняем логику преобразования размера груди (A -> 1 и т.д.)
            if ($taxonomy === 'breast_size') {
                if (preg_match('/([A-Z])$/', $name, $matches)) {
                    $letter = $matches[1];
                    $breastMap = [
                        'A' => '1', 'B' => '2', 'C' => '3', 'D' => '4', 
                        'E' => '5', 'F' => '6', 'G' => '7', 'H' => '8'
                    ];
                    $name = $breastMap[$letter] ?? $letter;
                }
            }

            $items[] = [
                'name' => $name,
                'url'  => \App\Helpers\UrlHelpers::getTermUrl($term), // Генерируем URL каталога с фильтром
            ];
        }
        return $items;
    }

    public function traits()
    {
        return array_filter([
            'Цвет волос'     => $this->getTraitItems('hair_color'),
            'Длина волос'    => $this->getTraitItems('hair_length'),
            'Грудь'          => $this->getTraitItems('breast_size'),
            'Город'          => $this->getTraitItems('city'),
            'Тип груди'      => $this->getTraitItems('breast_type'),
            'Телосложение'   => $this->getTraitItems('body_type'),
            'Национальность' => $this->getTraitItems('nationality'),
            'Глаза'          => $this->getTraitItems('eye_color'),
            'Интим. стрижка' => $this->getTraitItems('pubic_hair'),
            'Пирсинг'        => $this->getTraitItems('piercing'),
            'Курит'          => $this->getTraitItems('smoker'),
            'Выезд/Аппарт.'  => $this->getTraitItems('inoutcall'),
        ]);
    }

    public function badges()
    {
        $badges = [];
        if (has_term('vip', 'vip', get_the_ID())) $badges[] = 'VIP';
        if (has_term('verified', 'verified', get_the_ID())) $badges[] = 'Verified';
        if (has_term('independent', 'independent', get_the_ID())) $badges[] = 'Independent';
        if (strtotime(get_the_date()) > strtotime('-7 days')) $badges[] = 'New';
        return $badges;
    }

    private function getFirstTermName($taxonomy)
    {
        $terms = get_the_terms(get_the_ID(), $taxonomy);
        if (!$terms || is_wp_error($terms)) return null;
        
        $name = $terms[0]->name;
        
        // Для размера груди извлекаем только букву (A, B, C и т.д.)
        if ($taxonomy === 'breast_size') {
            // Ищем последнюю букву в названии
            if (preg_match('/([A-Z])$/', $name, $matches)) {
                $letter = $matches[1];
                // Маппинг букв в цифры
                $breastMap = [
                    'A' => '1',
                    'B' => '2', 
                    'C' => '3',
                    'D' => '4',
                    'E' => '5',
                    'F' => '6',
                    'G' => '7',
                    'H' => '8'
                ];
                return $breastMap[$letter] ?? $letter;
            }
        }
        
        return $name;
    }

    private function getTermsList($taxonomy)
    {
        $terms = get_the_terms(get_the_ID(), $taxonomy);
        if (!$terms || is_wp_error($terms)) return null;
        return implode(', ', array_column($terms, 'name'));
    }

    private function getTerms($taxonomy)
    {
        $terms = get_the_terms(get_the_ID(), $taxonomy);
        return ($terms && !is_wp_error($terms)) ? $terms : [];
    }

    private function hasTermName($taxonomy, $nameToCheck)
    {
        // Оставил для совместимости, но лучше использовать has_term() как в badges()
        $terms = get_the_terms(get_the_ID(), $taxonomy);
        if (!$terms || is_wp_error($terms)) return false;
        foreach ($terms as $term) {
            if ($term->name === $nameToCheck) return true;
        }
        return false;
    }

    private function getReviews($profileId)
    {
        $reviews = [];
        
        // Получаем отзывы как массив
        $profileReviews = get_field('reviews_list', $profileId);
        
        if ($profileReviews && is_array($profileReviews)) {
            foreach ($profileReviews as $review) {
                // Фильтруем: выводим только отзывы без поля 'imported' или с imported = false
                // Отзывы из JSON имеют поле 'imported' = true
                $isImported = isset($review['imported']) && $review['imported'] === true;
                
                if (!$isImported && is_array($review) && (!empty($review['content']) || !empty($review['author']))) {
                    $reviews[] = [
                        'author' => $review['author'] ?? 'Аноним',
                        'content' => $review['content'] ?? '',
                        'rating' => $review['rating'] ?? 0,
                        'date' => $review['date'] ?? '',
                    ];
                }
            }
        }
        
        return $reviews;
    }
}
