<?php

use App\Helpers\UrlHelpers;

/**
 * 1. Получить URL города (Главная страница города)
 */
if (!function_exists('city_url')) {
    function city_url($city): string
    {
        return UrlHelpers::getCityUrl($city);
    }
}

/**
 * 2. Получить URL профиля с учетом города
 */
if (!function_exists('profile_url')) {
    function profile_url($profile, $city = null): string
    {
        return UrlHelpers::getProfileUrl($profile, $city);
    }
}

/**
 * 3. Получить URL специальной страницы (map, reviews...) с учетом города
 */
if (!function_exists('special_page_url')) {
    function special_page_url($special_page, $city = null): string
    {
        return UrlHelpers::getSpecialPageUrl($special_page, $city);
    }
}

/**
 * 4. Получить URL обычной страницы (fallback)
 */
if (!function_exists('page_url')) {
    function page_url($page_slug, $city = null): string
    {
        return UrlHelpers::getPageUrl($page_slug, $city);
    }
}

/**
 * 5. Получить объект текущего города (WP_Term)
 */
if (!function_exists('get_current_city')) {
    function get_current_city(): ?WP_Term
    {
        return UrlHelpers::getCurrentCity();
    }
}

/**
 * 6. Получить массив SEO данных из ACF для текущей страницы города
 * Пример: get_city_seo('home') -> вернет ['h1' => '...', 'meta_title' => '...']
 */
if (!function_exists('get_city_seo')) {
    function get_city_seo(string $page_key): array
    {
        return UrlHelpers::getCitySeoData($page_key);
    }
}

/**
 * 7. Получить URL термина (услуги, категории) с учетом города
 * Пример: term_url($service) -> /balashiha/uslugi/sex-toys/
 */
if (!function_exists('term_url')) {
    function term_url($term, $city = null): string
    {
        return UrlHelpers::getTermUrl($term, $city);
    }
}

/**
 * 8. Получить SEO данные таксономии с учетом города
 * Пример: get_taxonomy_seo('service', 'sex-toys', 'balashiha')
 */
if (!function_exists('get_taxonomy_seo')) {
    function get_taxonomy_seo(string $taxonomy, string $term_slug, string $city_slug): array
    {
        return UrlHelpers::getTaxonomySeoData($taxonomy, $term_slug, $city_slug);
    }
}

/**
 * 9. Получить SEO данные страницы с учетом города
 * Пример: get_page_seo(get_the_ID(), 'balashiha')
 */
if (!function_exists('get_page_seo')) {
    function get_page_seo(int $page_id, string $city_slug): array
    {
        return UrlHelpers::getPageSeoData($page_id, $city_slug);
    }
}

/**
 * 10. Получить URL с префиксом текущего города
 * Если на главной странице (/), добавляет префикс Москва
 * Пример: city_prefixed_url('/vip/') -> /moskva/vip/ (с главной), /balashiha/vip/ (из Балашихи)
 */
if (!function_exists('city_prefixed_url')) {
    function city_prefixed_url($path = ''): string
    {
        $current_city = get_current_city();
        $current_url = $_SERVER['REQUEST_URI'] ?? '';
        
        // Проверяем, находимся ли мы на главной странице
        $is_main_page = ($current_url === '/' || $current_url === '');
        
        // Проверяем, содержит ли текущий URL город
        $has_city_in_url = false;
        if ($current_city && strpos($current_url, '/' . $current_city->slug . '/') === 0) {
            $has_city_in_url = true;
        }
        
        // Для дефолтного города не добавляем префикс вообще
        if ($current_city && $current_city->slug === UrlHelpers::DEFAULT_CITY_SLUG) {
            // Если путь пустой или только /, возвращаем корень
            if (empty($path) || $path === '/') {
                return home_url('/');
            }
            // Для остальных путей просто добавляем путь
            $path = ltrim($path, '/');
            return home_url("/{$path}");
        }
        
        // Для остальных городов добавляем префикс если:
        // 1. Мы на главной странице (добавляем Москву)
        // 2. Город уже есть в текущем URL
        // 3. Если путь не начинается с / (корневой путь)
        if ($current_city && ($is_main_page || $has_city_in_url || $path !== '/')) {
            // Если путь начинается с /, убираем его для избежания двойного слэша
            $path = ltrim($path, '/');
            
            // Если путь пустой или только /, возвращаем URL города
            if (empty($path) || $path === '') {
                return home_url("/{$current_city->slug}/");
            }
            
            return home_url("/{$current_city->slug}/{$path}");
        }
        
        return home_url($path);
    }
}

/**
 * 11. Локационные таксономии (метро/районы) и их подписи
 */
if (!function_exists('location_taxonomies')) {
    function location_taxonomies(): array
    {
        return [
            'metro' => 'Метро',
            'district' => 'Районы',
        ];
    }
}

/**
 * 12. Получить термины метро/районов (плоский список)
 */
if (!function_exists('get_location_terms')) {
    function get_location_terms(string $location_taxonomy): array
    {
        if (!array_key_exists($location_taxonomy, location_taxonomies())) {
            return [];
        }

        $terms = get_terms([
            'taxonomy' => $location_taxonomy,
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC',
        ]);

        if (is_wp_error($terms) || empty($terms)) {
            return [];
        }

        return $terms;
    }
}

/**
 * 13. Получить city_id из ACF поля related_city у термина метро/района
 */
if (!function_exists('get_related_city_id_for_location_term')) {
    function get_related_city_id_for_location_term(string $location_taxonomy, $location_term): int
    {
        if (!function_exists('get_field') || !isset($location_term->term_id)) {
            return 0;
        }

        $related_city = get_field('related_city', $location_taxonomy . '_' . $location_term->term_id);

        if (is_object($related_city) && isset($related_city->term_id)) {
            return (int) $related_city->term_id;
        }

        if (is_array($related_city) && isset($related_city['term_id'])) {
            return (int) $related_city['term_id'];
        }

        return (int) $related_city;
    }
}

/**
 * 14. Получить термины метро/районов, сгруппированные по ID города
 */
if (!function_exists('get_location_terms_by_city')) {
    function get_location_terms_by_city(string $location_taxonomy): array
    {
        $terms_by_city = [];
        $location_terms = get_location_terms($location_taxonomy);

        foreach ($location_terms as $location_term) {
            $related_city_id = get_related_city_id_for_location_term($location_taxonomy, $location_term);

            if ($related_city_id <= 0) {
                continue;
            }

            if (!isset($terms_by_city[$related_city_id])) {
                $terms_by_city[$related_city_id] = [];
            }

            $terms_by_city[$related_city_id][] = $location_term;
        }

        return $terms_by_city;
    }
}
