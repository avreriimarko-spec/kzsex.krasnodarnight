<?php

namespace App\Helpers;

use WP_Term;

class CityCatalog
{
    public const DEFAULT_CITY_SLUG = 'moskva';

    public const CITIES = [
        ['name' => 'Москва', 'slug' => 'moskva', 'population' => 13274285],
        ['name' => 'Балашиха', 'slug' => 'balashiha', 'population' => 530311],
        ['name' => 'Подольск', 'slug' => 'podolsk', 'population' => 312911],
        ['name' => 'Мытищи', 'slug' => 'mytishchi', 'population' => 275313],
        ['name' => 'Химки', 'slug' => 'himki', 'population' => 256684],
        ['name' => 'Люберцы', 'slug' => 'lyubertsy', 'population' => 236339],
        ['name' => 'Королёв', 'slug' => 'korolyov', 'population' => 226007],
        ['name' => 'Красногорск', 'slug' => 'krasnogorsk', 'population' => 193127],
        ['name' => 'Одинцово', 'slug' => 'odincovo', 'population' => 187301],
        ['name' => 'Домодедово', 'slug' => 'domodedovo', 'population' => 158046],
        ['name' => 'Электросталь', 'slug' => 'ehlektrostal', 'population' => 141778],
        ['name' => 'Щёлково', 'slug' => 'shchyolkovo', 'population' => 135918],
        ['name' => 'Серпухов', 'slug' => 'serpuhov', 'population' => 133756],
        ['name' => 'Коломна', 'slug' => 'kolomna', 'population' => 132247],
    ];

    public static function getSlugs(): array
    {
        return array_column(self::CITIES, 'slug');
    }

    public static function getDefaultCityName(): string
    {
        foreach (self::CITIES as $city) {
            if (($city['slug'] ?? '') === self::DEFAULT_CITY_SLUG) {
                return (string) ($city['name'] ?? 'Москва');
            }
        }

        return 'Москва';
    }

    public static function syncTerms(): void
    {
        if (!function_exists('get_option') || !taxonomy_exists('city')) {
            return;
        }

        $catalogHash = md5(wp_json_encode(self::CITIES));
        $storedHash = get_option('eskort-moskvy_city_catalog_hash');
        $needsSync = ($storedHash !== $catalogHash);

        if ($needsSync) {
            foreach (self::CITIES as $city) {
                $name = (string) ($city['name'] ?? '');
                $slug = (string) ($city['slug'] ?? '');
                $population = (int) ($city['population'] ?? 0);

                if ($name === '' || $slug === '') {
                    continue;
                }

                $term = get_term_by('slug', $slug, 'city');
                $termId = null;

                if ($term instanceof WP_Term) {
                    $termId = $term->term_id;
                    if ($term->name !== $name) {
                        wp_update_term($termId, 'city', ['name' => $name]);
                    }
                } else {
                    $inserted = wp_insert_term($name, 'city', ['slug' => $slug]);
                    if (!is_wp_error($inserted)) {
                        $termId = (int) ($inserted['term_id'] ?? 0);
                    }
                }

                if ($termId && function_exists('update_term_meta')) {
                    update_term_meta($termId, 'city_population', $population);
                }
            }
        }

        self::removeObsoleteTerms();

        if ($needsSync) {
            update_option('eskort-moskvy_city_catalog_hash', $catalogHash, false);
        }
    }

    private static function removeObsoleteTerms(): void
    {
        if (!function_exists('get_terms') || !function_exists('wp_delete_term')) {
            return;
        }

        $allowedSlugs = array_fill_keys(self::getSlugs(), true);
        $allCityTerms = get_terms([
            'taxonomy' => 'city',
            'hide_empty' => false,
        ]);

        if (is_wp_error($allCityTerms) || !is_array($allCityTerms)) {
            return;
        }

        foreach ($allCityTerms as $term) {
            if (!$term instanceof WP_Term) {
                continue;
            }

            if (isset($allowedSlugs[$term->slug])) {
                continue;
            }

            wp_delete_term($term->term_id, 'city');
        }
    }
}
