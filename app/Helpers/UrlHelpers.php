<?php

namespace App\Helpers;

use WP_Term;
use WP_Post;
use WP_Error;

class UrlHelpers
{
    // Слаг города, который работает от корня сайта
    // ОБЯЗАТЕЛЬНО проверьте, что такой слаг есть в базе
    public const DEFAULT_CITY_SLUG = 'almaty'; 

    /**
     * Получить URL главной страницы города
     * Пример: / (для дефолтного), /astana/ (для остальных)
     */
    public static function getCityUrl($city): string
    {
        $slug = ($city instanceof WP_Term) ? $city->slug : $city;

        // Для дефолтного города возвращаем корневой URL
        if ($slug === self::DEFAULT_CITY_SLUG) {
            return home_url('/');
        }

        // Для остальных городов добавляем префикс
        return home_url("/{$slug}/");
    }
    
    /**
     * Получить URL профиля с учетом города
     * Пример: /almaty/profile/anna/ (для Алматы), /astana/profile/anna/ (для других городов)
     */
    public static function getProfileUrl($profile, $city = null): string
    {
        if (is_object($profile)) {
            $profile_slug = $profile->post_name;
            $profile_id = $profile->ID;
        } else {
            // Если передан ID, получаем пост
            $profile_id = $profile;
            $post = get_post($profile_id);
            $profile_slug = $post ? $post->post_name : '';
        }
        
        // 1. Если город не передан, сначала пытаемся определить из привязанных таксономий поста
        if (!$city && $profile_id) {
            $terms = get_the_terms($profile_id, 'city');
            if (!empty($terms) && !is_wp_error($terms)) {
                $city = $terms[0]; // Используем первый город анкеты
            }
        }
        
        // 2. Если все еще нет города, используем текущий город из URL
        if (!$city) {
            $city = self::getCurrentCity();
        }

        $city_slug = ($city instanceof WP_Term) ? $city->slug : $city;
        
        // Если город не найден (совсем), ставим дефолтный
        if (!$city_slug) {
            $city_slug = self::DEFAULT_CITY_SLUG;
        }

        // 3. Формируем ссылку с учетом города
        // Для Алматы всегда добавляем префикс almaty
        if ($city_slug === self::DEFAULT_CITY_SLUG) {
            // Для Алматы всегда выводим almaty/profile/slug
            return home_url("/{$city_slug}/profile/{$profile_slug}/");
        } else {
            // Для остальных городов всегда добавляем префикс города
            return home_url("/{$city_slug}/profile/{$profile_slug}/");
        }
    }
    
    /**
     * Получить URL специальной страницы (карта, отзывы, каталог)
     * Пример: /map/ (дефолт), /astana/map/
     */
    public static function getSpecialPageUrl($page_slug, $city = null): string
    {
        if (!$city) {
            $city = self::getCurrentCity();
        }

        $city_slug = ($city instanceof WP_Term) ? $city->slug : $city;

        // Страницы, которые всегда должны быть с вложением города
        $with_city_pages = ['vip', 'cheap', 'deshevye', 'independent', 'online'];
        
        // Страницы, которые всегда должны быть без города
        $without_city_pages = ['map', 'reviews', 'catalog'];

        if (in_array($page_slug, $with_city_pages)) {
            // Всегда добавляем префикс города
            return home_url("/{$city_slug}/{$page_slug}/");
        } elseif (in_array($page_slug, $without_city_pages)) {
            // Всегда без города
            return home_url("/{$page_slug}/");
        } else {
            // Для остальных страниц для дефолтного города (Алматы) не добавляем префикс
            if ($city_slug === self::DEFAULT_CITY_SLUG) {
                return home_url("/{$page_slug}/");
            } else {
                // Для остальных городов добавляем префикс города
                return home_url("/{$city_slug}/{$page_slug}/");
            }
        }
    }
    
    /**
     * Получить URL обычной WP страницы с префиксом города (если нужно)
     */
    public static function getPageUrl($page_slug, $city = null): string
    {
        return self::getSpecialPageUrl($page_slug, $city);
    }
    
    /**
     * Получить URL термина (услуги, категории) с учетом города
     * Пример: /astana/service/sex-toys/, /almaty/service/relax/
     */
    public static function getTermUrl($term, $city = null): string
    {
        if (!$city) {
            $city = self::getCurrentCity();
        }

        $city_slug = ($city instanceof WP_Term) ? $city->slug : $city;
        $term_slug = is_object($term) ? $term->slug : $term;
        $taxonomy = is_object($term) ? $term->taxonomy : 'service';

        // Всегда добавляем город в URL
        return home_url("/{$city_slug}/{$taxonomy}/{$term_slug}/");
    }
    
    /**
     * Получить SEO данные таксономии с учетом города
     * Пример: getTaxonomySeoData('service', 'sex-toys', 'astana')
     */
    public static function getTaxonomySeoData(string $taxonomy, string $term_slug, string $city_slug): array
    {
        // Ищем пост с полями для конкретного города
        $args = [
            'post_type' => 'taxonomy',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'tax_query' => [
                'relation' => 'AND',
                [
                    'taxonomy' => $taxonomy,
                    'field' => 'slug',
                    'terms' => $term_slug,
                ],
                [
                    'taxonomy' => 'city',
                    'field' => 'slug',
                    'terms' => $city_slug,
                ],
            ],
        ];
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) {
            $post_id = $query->posts[0]->ID;
            
            return [
                'seo_title' => get_field('seo_title', $post_id),
                'seo_description' => get_field('seo_description', $post_id),
                'custom_h1' => get_field('custom_h1', $post_id),
                'description' => get_field('description', $post_id),
                'main_text' => get_field('main_text', $post_id),
            ];
        }
        
        // Если нет данных для города, возвращаем пустой массив
        return [];
    }
    
    /**
     * Получить SEO данные страницы с учетом города
     * Пример: getPageSeoData($page_id, 'astana')
     */
    public static function getPageSeoData(int $page_id, string $city_slug): array
    {
        // Получаем ACF поля для страницы с учетом города
        // Ищем в repeater поля для конкретного города
        if (function_exists('get_field')) {
            $city_term = get_term_by('slug', $city_slug, 'city');
            if ($city_term) {
                // Ищем в repeater "city_pages_seo" для этого города
                $city_pages = get_field('city_pages_seo', 'city_' . $city_term->term_id);
                
                if (is_array($city_pages)) {
                    foreach ($city_pages as $page_data) {
                        // Ищем соответствие по page_key или по slug страницы
                        $page = get_post($page_id);
                        $page_slug = $page ? $page->post_name : '';
                        
                        if (isset($page_data['page_key']) && $page_data['page_key'] === $page_slug) {
                            return [
                                'seo_title' => $page_data['meta_title'] ?? '',
                                'seo_description' => $page_data['meta_description'] ?? '',
                                'custom_h1' => $page_data['h1'] ?? '',
                                'description' => $page_data['intro_text'] ?? '',
                                'main_text' => $page_data['seo_text'] ?? '',
                            ];
                        }
                    }
                }
            }
        }
        
        // Если нет данных для города, возвращаем пустой массив
        return [];
    }
    
    /**
     * Определяет текущий город на основе URL (query_var)
     * Если города в URL нет -> возвращает объект дефолтного города.
     */
    public static function getCurrentCity(): ?WP_Term
    {
        // Переменная 'city' устанавливается в Rewrite Rules (см. ContentServiceProvider)
        $city_slug = get_query_var('city');
        
        if (empty($city_slug)) {
            $city_slug = self::DEFAULT_CITY_SLUG;
        }
        
        $term = get_term_by('slug', $city_slug, 'city');
        
        return ($term instanceof WP_Term) ? $term : null;
    }

    /**
     * Вспомогательный метод: находимся ли мы в дефолтном городе
     */
    public static function isDefaultCity(): bool
    {
        $city = self::getCurrentCity();
        return $city && $city->slug === self::DEFAULT_CITY_SLUG;
    }

    /**
     * Получить данные SEO (ACF Repeater) для конкретной "виртуальной" страницы города
     * @param string $page_key Ключ страницы: 'home', 'map', 'reviews' и т.д.
     */
    public static function getCitySeoData(string $page_key): array
    {
        $city = self::getCurrentCity();
        if (!$city) return [];

        // Получаем поля термина "city_{ID}"
        // Используем get_field если ACF подключен, иначе пустой массив
        if (!function_exists('get_field')) return [];

        $rows = get_field('city_pages_seo', 'city_' . $city->term_id);

        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (isset($row['page_key']) && $row['page_key'] === $page_key) {
                    return $row;
                }
            }
        }

        return [];
    }
}
