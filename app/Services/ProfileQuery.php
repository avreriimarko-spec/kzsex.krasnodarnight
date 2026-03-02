<?php

namespace App\Services;

use WP_Query;
use App\Helpers\UrlHelpers;

class ProfileQuery
{
    // Статическое свойство для хранения результата в памяти
    private static $cachedQuery = null;
    private static $cachedNewBadgeProfileIds = null;
    
    private const FILTER_TAXONOMIES = [
        'service',
        'hair_color',
        'breast_size',
        'body_type',
        'ethnicity',
        'nationality',
        'eye_color',
        'hair_length',
        'breast_type',
        'intimate',
        'piercing',
        'travel',
        'smoker',
        'inoutcall',
        'what',
        'parameters',
        'metadata',
        'metro',
        'district',
        'appearance',
        'place',
    ];
    
    private const NUMERIC_FILTERS = ['age', 'height', 'weight'];

    public static function applyRequestFiltersToArgs(array $args, array $excludeTaxonomies = []): array
    {
        $exclude = array_fill_keys($excludeTaxonomies, true);

        $args['tax_query'] = self::normalizeRelationQuery($args['tax_query'] ?? []);
        $args['meta_query'] = self::normalizeRelationQuery($args['meta_query'] ?? []);

        $isDefaultCity = UrlHelpers::isDefaultCity();

        foreach (self::FILTER_TAXONOMIES as $slug) {
            if (isset($exclude[$slug])) {
                continue;
            }
            if ($slug === 'metro' && !$isDefaultCity) {
                continue;
            }
            if (!taxonomy_exists($slug)) {
                continue;
            }

            $values = array_filter((array) request()->input('f_' . $slug), static function ($value) {
                return $value !== null && $value !== '';
            });

            if (empty($values)) {
                continue;
            }

            $args['tax_query'][] = [
                'taxonomy' => $slug,
                'field'    => 'slug',
                'terms'    => array_values($values),
                'operator' => 'IN',
            ];
        }

        foreach (self::NUMERIC_FILTERS as $key) {
            $min = request()->input($key . '_min');
            $max = request()->input($key . '_max');

            if ($min !== null && $min !== '') {
                $args['meta_query'][] = ['key' => $key, 'value' => $min, 'compare' => '>=', 'type' => 'NUMERIC'];
            }
            if ($max !== null && $max !== '') {
                $args['meta_query'][] = ['key' => $key, 'value' => $max, 'compare' => '<=', 'type' => 'NUMERIC'];
            }
        }

        $pMin = request()->input('price_min');
        $pMax = request()->input('price_max');

        if ($pMin !== null && $pMin !== '') {
            $args['meta_query'][] = ['key' => 'price_price_1h', 'value' => $pMin, 'compare' => '>=', 'type' => 'NUMERIC'];
        }
        if ($pMax !== null && $pMax !== '') {
            $args['meta_query'][] = ['key' => 'price_price_1h', 'value' => $pMax, 'compare' => '<=', 'type' => 'NUMERIC'];
        }

        return $args;
    }

    private static function normalizeRelationQuery(array $query): array
    {
        if (!isset($query['relation'])) {
            $query['relation'] = 'AND';
        }

        return $query;
    }

    /**
     * Правило "новая анкета": опубликована за последние 7 дней.
     */
    public static function isProfileNew(int $profileId): bool
    {
        $post = get_post($profileId);
        if (!$post || $post->post_type !== 'profile' || $post->post_status !== 'publish') {
            return false;
        }

        $postTimestamp = (int) get_post_time('U', false, $profileId);
        if ($postTimestamp <= 0) {
            return false;
        }

        $weekAgo = current_time('timestamp') - (7 * DAY_IN_SECONDS);

        return $postTimestamp > $weekAgo;
    }

    /**
     * Профили, которые должны отображаться как "Новые" (до 7 дней с публикации).
     */
    private static function getNewBadgeProfileIds(): array
    {
        if (self::$cachedNewBadgeProfileIds !== null) {
            return self::$cachedNewBadgeProfileIds;
        }

        $weekAgoTimestamp = current_time('timestamp') - (7 * DAY_IN_SECONDS);
        $weekAgoDate = wp_date('Y-m-d H:i:s', $weekAgoTimestamp);

        $dateIds = get_posts([
            'post_type' => 'profile',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'date_query' => [
                [
                    'after' => $weekAgoDate,
                    'inclusive' => false,
                    'column' => 'post_date',
                ],
            ],
        ]);

        $ids = array_values(array_unique(array_map('intval', $dateIds)));
        self::$cachedNewBadgeProfileIds = $ids;

        return $ids;
    }

    /**
     * Получить запрос (Выполняется 1 раз за загрузку страницы)
     */
    public static function get(): WP_Query
    {
        // Отключаем кэширование для пагинации и фильтров
        // if (self::$cachedQuery !== null) {
        //     return self::$cachedQuery;
        // }

        $paged = get_query_var('paged') ?: get_query_var('page') ?: 1;

        $args = [
            'post_type'      => 'profile',
            'post_status'    => 'publish',
            'posts_per_page' => 48, // Количество анкет на странице (как в шаблоне)
            'paged'          => $paged,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'no_found_rows'  => false, // Включить подсчет общего количества
            'tax_query'      => ['relation' => 'AND'],
            'meta_query'     => ['relation' => 'AND'],
        ];

        $args = self::applyRequestFiltersToArgs($args);

        // Добавляем фильтрацию по текущему городу для всех страниц кроме главной
        $current_city = null;
        
        // Сначала пробуем получить из query_vars напрямую
        global $wp;
        if (isset($wp->query_vars['city']) && !empty($wp->query_vars['city'])) {
            $city_slug = $wp->query_vars['city'];
            $current_city = get_term_by('slug', $city_slug, 'city');
        }
        
        // Если не нашли, пробуем через get_current_city()
        if (!$current_city) {
            $current_city = get_current_city();
        }
        
        // Дополнительная проверка - если все еще нет города, пробуем GET параметр
        if (!$current_city && isset($_GET['city'])) {
            $city_slug = sanitize_text_field($_GET['city']);
            $current_city = get_term_by('slug', $city_slug, 'city');
        }

        // Всегда добавляем фильтр города если он определен
        if ($current_city) {
            $args['tax_query'][] = [
                'taxonomy' => 'city',
                'field'    => 'slug',
                'terms'    => $current_city->slug,
                'operator' => 'IN', // IN работает правильно для таксономий
            ];
        }

        if (is_page_template('template-incall.blade.php') || get_query_var('special_page') === 'incall') {
            $args['tax_query'][] = ['taxonomy' => 'inoutcall', 'field' => 'slug', 'terms' => ['incall', 'incall-and-outcall'], 'operator' => 'IN'];
        }

        if (is_page_template('template-outcall.blade.php') || get_query_var('special_page') === 'outcall') {
            $args['tax_query'][] = ['taxonomy' => 'inoutcall', 'field' => 'slug', 'terms' => ['outcall', 'incall-and-outcall'], 'operator' => 'IN'];
        }
        
        $isNewPage = is_page_template('template-new.blade.php')
            || get_query_var('special_page') === 'novye'
            || get_query_var('pagename') === 'novye';

        if ($isNewPage) {
            $newBadgeIds = self::getNewBadgeProfileIds();
            $args['post__in'] = !empty($newBadgeIds) ? $newBadgeIds : [0];
        }

        if (is_page_template('template-vip.blade.php') || get_query_var('special_page') === 'vip') {
            $args['tax_query'][] = ['taxonomy' => 'vip', 'field' => 'slug', 'terms' => ['vip'], 'operator' => 'IN'];
        }

        if (is_page_template('template-verified.blade.php') || get_query_var('special_page') === 'provereno') {
            $args['tax_query'][] = ['taxonomy' => 'verified', 'field' => 'slug', 'terms' => ['verified'], 'operator' => 'IN'];
        }

        if (is_page_template('template-cheap.blade.php') || get_query_var('special_page') === 'deshyovye') {
            // Дешевые анкеты: ограничиваем верхнюю границу цены за 1 час
            $args['meta_query'][] = ['key' => 'price_price_1h', 'value' => 15000, 'compare' => '<=', 'type' => 'NUMERIC'];
        }

        // Главная страница - только анкеты Москвы
        $front_page_id = get_option('page_on_front');
        $is_front_page = (get_option('show_on_front') === 'page' && $front_page_id && is_front_page());
        
        if ($is_front_page) {
            // Дополнительная проверка шаблона для главной страницы
            $template = get_post_meta($front_page_id, '_wp_page_template', true);
            $is_front_template_profiles = ($template === 'template-profiles.blade.php');
            
            if ($is_front_template_profiles) {
                $args['tax_query'][] = [
                    'taxonomy' => 'city',
                    'field'    => 'slug',
                    'terms'    => [\App\Helpers\UrlHelpers::DEFAULT_CITY_SLUG],
                    'operator' => 'IN', // IN работает правильно для таксономий
                ];
            }
        }

        // Создаем и возвращаем запрос без кэширования
        $query = new WP_Query($args);
        
        // Дополнительная фильтрация для исправления проблем с таксономией
        if ($current_city) {
            $filtered_posts = [];
            foreach ($query->posts as $post) {
                $cities = get_the_terms($post->ID, 'city');
                if ($cities && !is_wp_error($cities)) {
                    foreach ($cities as $city) {
                        if ($city->slug === $current_city->slug) {
                            $filtered_posts[] = $post;
                            break;
                        }
                    }
                }
            }
            
            // Заменяем массив постов отфильтрованным
            $query->posts = $filtered_posts;
            $query->post_count = count($filtered_posts);
            
            // НЕ заменяем found_posts - оно должно показывать общее количество
            // $query->found_posts = count($filtered_posts);
            
            // Сбрасываем указатель текущего поста
            $query->current_post = -1;
            $query->in_the_loop = false;
        }
        
        return $query;
    }
}
