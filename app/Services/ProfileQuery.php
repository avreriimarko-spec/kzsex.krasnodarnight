<?php

namespace App\Services;

use WP_Query;

class ProfileQuery
{
    // Статическое свойство для хранения результата в памяти
    private static $cachedQuery = null;

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

        $taxonomies = [
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
            'smoker'
        ];

        foreach ($taxonomies as $slug) {
            if ($values = request()->input('f_' . $slug)) {
                $args['tax_query'][] = [
                    'taxonomy' => $slug,
                    'field'    => 'slug',
                    'terms'    => $values,
                    'operator' => 'IN',
                ];
            }
        }

        // 2. Числовые фильтры
        $numericFilters = ['age', 'height', 'weight'];
        foreach ($numericFilters as $key) {
            if ($min = request()->input($key . '_min')) {
                $args['meta_query'][] = ['key' => $key, 'value' => $min, 'compare' => '>=', 'type' => 'NUMERIC'];
            }
            if ($max = request()->input($key . '_max')) {
                $args['meta_query'][] = ['key' => $key, 'value' => $max, 'compare' => '<=', 'type' => 'NUMERIC'];
            }
        }

        // Цена
        if ($pMin = request()->input('price_min')) {
            $args['meta_query'][] = ['key' => 'price_price_1h', 'value' => $pMin, 'compare' => '>=', 'type' => 'NUMERIC'];
        }
        if ($pMax = request()->input('price_max')) {
            $args['meta_query'][] = ['key' => 'price_price_1h', 'value' => $pMax, 'compare' => '<=', 'type' => 'NUMERIC'];
        }

        // 3. Логика шаблонов (Проверяем текущий шаблон страницы)
        $current_template = get_page_template_slug();
        
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
        
        // Проверяем специальные страницы через query_var или включение template-profiles
        $is_template_profiles = is_page_template('template-profiles.blade.php') 
                               || is_page_template('template-independent.blade.php')
                               || is_page_template('template-vip.blade.php')
                               || is_page_template('template-incall.blade.php')
                               || is_page_template('template-outcall.blade.php')
                               || get_query_var('special_page');
        
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

        if (is_page_template('template-independent.blade.php') || get_query_var('special_page') === 'independent') {
            $args['tax_query'][] = ['taxonomy' => 'independent', 'field' => 'slug', 'terms' => ['independent'], 'operator' => 'IN'];
        }

        if (is_page_template('template-vip.blade.php') || get_query_var('special_page') === 'vip') {
            $args['tax_query'][] = ['taxonomy' => 'vip', 'field' => 'slug', 'terms' => ['vip'], 'operator' => 'IN'];
        }

        // Главная страница - только анкеты Алматы
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
                    'terms'    => ['almaty'],
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
