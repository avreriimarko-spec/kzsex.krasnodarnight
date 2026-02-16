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
 * Пример: term_url($service) -> /astana/service/sex-toys/
 */
if (!function_exists('term_url')) {
    function term_url($term, $city = null): string
    {
        return UrlHelpers::getTermUrl($term, $city);
    }
}

/**
 * 8. Получить SEO данные таксономии с учетом города
 * Пример: get_taxonomy_seo('service', 'sex-toys', 'astana')
 */
if (!function_exists('get_taxonomy_seo')) {
    function get_taxonomy_seo(string $taxonomy, string $term_slug, string $city_slug): array
    {
        return UrlHelpers::getTaxonomySeoData($taxonomy, $term_slug, $city_slug);
    }
}

/**
 * 9. Получить SEO данные страницы с учетом города
 * Пример: get_page_seo(get_the_ID(), 'astana')
 */
if (!function_exists('get_page_seo')) {
    function get_page_seo(int $page_id, string $city_slug): array
    {
        return UrlHelpers::getPageSeoData($page_id, $city_slug);
    }
}

/**
 * 10. Получить URL с префиксом текущего города
 * Если на главной странице (/), добавляет префикс Алматы
 * Пример: city_prefixed_url('/vip/') -> /almaty/vip/ (с главной), /astana/vip/ (из Астаны)
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
        
        // Для Алматы (дефолтный город) не добавляем префикс вообще
        if ($current_city && $current_city->slug === 'almaty') {
            // Если путь пустой или только /, возвращаем корень
            if (empty($path) || $path === '/') {
                return home_url('/');
            }
            // Для остальных путей просто добавляем путь
            $path = ltrim($path, '/');
            return home_url("/{$path}");
        }
        
        // Для остальных городов добавляем префикс если:
        // 1. Мы на главной странице (добавляем Алматы)
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