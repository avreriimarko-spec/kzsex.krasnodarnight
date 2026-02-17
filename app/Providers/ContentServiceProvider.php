<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Helpers\CityCatalog;

class ContentServiceProvider extends ServiceProvider
{
    /**
     * Список таксономий
     */
    protected const TAXONOMIES = [
        'city' => ['slug' => 'city', 'name' => 'Города', 'post_type' => ['profile']],
        'services' => ['slug' => 'service', 'name' => 'Услуги', 'post_type' => ['profile']],
        'hair_color' => ['slug' => 'hair_color', 'name' => 'Цвет волос', 'post_type' => ['profile']],
        'hair_length' => ['slug' => 'hair_length', 'name' => 'Длина волос', 'post_type' => ['profile']],
        'body_type' => ['slug' => 'body_type', 'name' => 'Телосложение', 'post_type' => ['profile']],
        'ethnicity' => ['slug' => 'ethnicity', 'name' => 'Этнос', 'post_type' => ['profile']],
        'nationality' => ['slug' => 'nationality', 'name' => 'Национальность', 'post_type' => ['profile']],
        'languages' => ['slug' => 'language', 'name' => 'Языки', 'post_type' => ['profile']],
        'breast_size' => ['slug' => 'breast_size', 'name' => 'Размер груди', 'post_type' => ['profile']],
        'breast_type' => ['slug' => 'breast_type', 'name' => 'Тип груди', 'post_type' => ['profile']],
        'pubic_hair' => ['slug' => 'pubic_hair', 'name' => 'Интимная стрижка', 'post_type' => ['profile']],
        'piercing' => ['slug' => 'piercing', 'name' => 'Пирсинг', 'post_type' => ['profile']],
        'travel' => ['slug' => 'travel', 'name' => 'Путешествия', 'post_type' => ['profile']],
        'inoutcall' => ['slug' => 'inoutcall', 'name' => 'У Себя / Выезд', 'post_type' => ['profile']],
        'smoker' => ['slug' => 'smoker', 'name' => 'Курит', 'post_type' => ['profile']],
        'verified' => ['slug' => 'verified', 'name' => 'Проверенная', 'post_type' => ['profile']],
        'independent' => ['slug' => 'independent', 'name' => 'Индивидуалка', 'post_type' => ['profile']],
        'vip' => ['slug' => 'vip', 'name' => 'Вип', 'post_type' => ['profile']],
        'gender' => ['slug' => 'gender', 'name' => 'Пол', 'post_type' => ['profile']],
        'orientation' => ['slug' => 'orientation', 'name' => 'Ориентация', 'post_type' => ['profile']],
        'meeting_with' => ['slug' => 'meeting_with', 'name' => 'Встречается с', 'post_type' => ['profile']],
        'tattoo' => ['slug' => 'tattoo', 'name' => 'Тату', 'post_type' => ['profile']],
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerPostTypes();
        $this->registerTaxonomies();
        CityCatalog::syncTerms();
        $this->addCustomRewriteRules();
        $this->registerAcfCityFields(); // Регистрация полей ACF
        $this->addTemplateFilter(); // Фильтр для шаблонов таксономий
        $this->addProfileLinkFilter(); // Фильтр для ссылок профилей
    }

    protected function registerPostTypes(): void
    {
        register_post_type('profile', [
            'labels' => [
                'name' => 'Анкеты',
                'singular_name' => 'Анкета',
                'menu_name' => 'Анкеты',
                'add_new' => 'Добавить анкету',
            ],
            'public' => true,
            'has_archive' => false, // Отключаем архив
            'rewrite' => false, // Отключаем стандартные rewrite rules
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
            'menu_icon' => 'dashicons-heart',
            'show_in_rest' => true,
        ]);
    }

    protected function registerTaxonomies(): void
    {
        foreach (self::TAXONOMIES as $key => $config) {
            $slug = $config['slug'];

            // Отключаем стандартный rewrite для city и vip, чтобы избежать конфликтов
            $rewrite = ($key === 'city' || $key === 'vip') ? false : ['slug' => $slug, 'with_front' => false];

            register_taxonomy($config['slug'], $config['post_type'], [
                'labels' => [
                    'name' => $config['name'],
                    'singular_name' => $config['name'],
                ],
                'public' => true,
                'hierarchical' => true,
                'show_in_rest' => true,
                'show_admin_column' => true,
                'rewrite' => $rewrite,
            ]);
        }
    }

    /**
     * Основная логика маршрутизации для городов
     */
    protected function addCustomRewriteRules(): void
    {
        // 1. Добавляем переменные запроса, чтобы WP их понимал
        add_filter('query_vars', function ($vars) {
            $vars[] = 'city';
            $vars[] = 'special_page'; // map, reviews, vip и т.д.
            return $vars;
        });

        add_action('init', function () {
            $citySlugs = CityCatalog::getSlugs();
            $escapedCitySlugs = array_map(static function ($slug) {
                return preg_quote((string) $slug, '/');
            }, $citySlugs);
            $excludedCitiesPattern = !empty($escapedCitySlugs)
                ? '(?:' . implode('|', $escapedCitySlugs) . ')(?:/|$)'
                : 'a^';

            // --- ПРАВИЛА ДЛЯ ПАГИНАЦИИ СТРАНИЦ БЕЗ ГОРОДА ---

            // Пагинация обычных страниц (исключая известные города): /individualki/page/2/, /vip/page/3/
            // Список городов берем из CityCatalog.
            add_rewrite_rule(
                '^(?!' . $excludedCitiesPattern . ')([^/]+)/page/([0-9]{1,})/?$',
                'index.php?pagename=$matches[1]&paged=$matches[2]',
                'top'
            );

            // --- ПРАВИЛА ДЛЯ ВСЕХ ГОРОДОВ (с префиксом) ---

            // Пагинация городов: /moskva/page/2/, /balashiha/page/3/
            add_rewrite_rule(
                '^([^/]+)/page/([0-9]{1,})/?$',
                'index.php?city=$matches[1]&paged=$matches[2]',
                'top'
            );

            // Пагинация страниц внутри городов: /podolsk/prostitutki-na-vyezd/page/2/, /moskva/vip/page/3/
            add_rewrite_rule(
                '^([^/]+)/([^/]+)/page/([0-9]{1,})/?$',
                'index.php?city=$matches[1]&pagename=$matches[2]&paged=$matches[3]',
                'top'
            );

            // Профиль в городе: /balashiha/profile/anna-123/, /moskva/profile/anna-123/
            add_rewrite_rule(
                '^([^/]+)/profile/([^/]+)/?$',
                'index.php?city=$matches[1]&post_type=profile&name=$matches[2]',
                'top'
            );

            // Услуги в городе: /balashiha/service/sex-toys/, /moskva/service/relax/
            add_rewrite_rule(
                '^([^/]+)/([^/]+)/([^/]+)/?$',
                'index.php?city=$matches[1]&taxonomy=$matches[2]&term=$matches[3]',
                'top'
            );

            // Пагинация таксономий в городе: /moskva/service/group-sex/page/2/, /balashiha/hair_color/blond/page/3/
            add_rewrite_rule(
                '^([^/]+)/([^/]+)/([^/]+)/page/([0-9]{1,})/?$',
                'index.php?city=$matches[1]&taxonomy=$matches[2]&term=$matches[3]&paged=$matches[4]',
                'top'
            );

            // Спец. страницы в городе (включая локализованные алиасы)
            add_rewrite_rule(
                '^([^/]+)/(map|reviews|catalog|vip|individualki|cheap|deshevye)/?$',
                'index.php?city=$matches[1]&pagename=$matches[2]',
                'top'
            );
            // Локальные / синонимы спец‑страниц
            $city_special_pages = [
                'prostitutki-na-vyezd' => 'outcall',
                'prostitutki-priem'    => 'incall',
                'individualki'         => 'independent',
                'deshevye'             => 'cheap',
                'otzyvy'               => 'reviews',
                'online'               => 'online',
            ];

            foreach ($city_special_pages as $slug => $key) {
                $slug_regex = preg_quote($slug, '/');
                add_rewrite_rule(
                    '^([^/]+)/' . $slug_regex . '/?$',
                    'index.php?city=$matches[1]&pagename=' . $slug,
                    'top'
                );
                // Поддержка пагинации для этих страниц: /city/slug/page/2/
                add_rewrite_rule(
                    '^([^/]+)/' . $slug_regex . '/page/([0-9]{1,})/?$',
                    'index.php?city=$matches[1]&pagename=' . $slug . '&paged=$matches[2]',
                    'top'
                );
            }

            // Спец. страницы без города (только для Москвы): /map/, /reviews/
            add_rewrite_rule(
                '^(map|reviews)/?$',
                'index.php?special_page=$matches[1]',
                'top'
            );
            
            // Отзывы - используем pagename чтобы работала страница WordPress
            add_rewrite_rule(
                '^otzyvy/?$',
                'index.php?pagename=otzyvy',
                'top'
            );

            // Страницы с городом (кроме спец. страниц и page): /balashiha/independent/, /moskva/vip/
            add_rewrite_rule(
                '^([^/]+)/((?!page|profiles)[^/]+)/?$',
                'index.php?city=$matches[1]&pagename=$matches[2]',
                'top'
            );

            // Главная страница города: /balashiha/, /moskva/
            add_rewrite_rule(
                '^([^/]+)/?$',
                'index.php?city=$matches[1]',
                'top'
            );
        });

        // Редирект VIP и Independent без города на версию с городом
        add_action('template_redirect', function () {
            if (is_page_template('template-vip.blade.php') && !get_query_var('city')) {
                $current_city = get_current_city();
                if ($current_city) {
                    wp_redirect(home_url("/{$current_city->slug}/vip/"), 301);
                    exit;
                }
            }

            if (is_page_template('template-cheap.blade.php') && !get_query_var('city')) {
                $current_city = get_current_city();
                if ($current_city) {
                    $target_slug = get_post_field('post_name', get_queried_object_id());
                    if (!$target_slug) {
                        $target_slug = get_page_by_path('cheap') ? 'cheap' : 'deshevye';
                    }

                    wp_redirect(home_url("/{$current_city->slug}/{$target_slug}/"), 301);
                    exit;
                }
            }

            if (is_page_template('template-independent.blade.php') && !get_query_var('city')) {
                $current_city = get_current_city();
                if ($current_city) {
                    wp_redirect(home_url("/{$current_city->slug}/independent/"), 301);
                    exit;
                }
            }
        });

        // Фильтр request: Проверка, существует ли город
        // Если кто-то введет /not-a-city/, правило '^([^/]+)/?$' сработает,
        // но мы должны проверить, есть ли такой город. Если нет — отдаем управление WP (ищем страницу).
        add_filter('request', function ($query_vars) {
            $allowedCitySlugs = CityCatalog::getSlugs();

            if (isset($query_vars['city'])) {
                $requestedCitySlug = (string) $query_vars['city'];
                $cityTerm = get_term_by('slug', $requestedCitySlug, 'city');

                // Если термин города есть в БД, но его нет в CityCatalog — считаем URL устаревшим.
                if ($cityTerm && !in_array($requestedCitySlug, $allowedCitySlugs, true)) {
                    $query_vars['error'] = 404;
                    unset($query_vars['city'], $query_vars['special_page']);
                    return $query_vars;
                }
            }

            // Блокируем прямые запросы к профилям без города
            if (isset($query_vars['post_type']) && $query_vars['post_type'] === 'profile' && !isset($query_vars['city'])) {
                // Это запрос к профилю без города - отдаем 404
                $query_vars['error'] = 404;
                return $query_vars;
            }

            // Блокируем запросы к профилям с неправильным городом
            if (isset($query_vars['post_type']) && $query_vars['post_type'] === 'profile' && isset($query_vars['city'])) {
                // Получаем профиль по name или post_name
                $profile_slug = $query_vars['name'] ?? '';
                if ($profile_slug) {
                    $profile = get_page_by_path($profile_slug, OBJECT, 'profile');
                    if ($profile) {
                        // Проверим города анкеты
                        $profile_cities = get_the_terms($profile->ID, 'city');
                        if ($profile_cities && !is_wp_error($profile_cities)) {
                            $city_matches = false;
                            foreach ($profile_cities as $city) {
                                if ($city->slug === $query_vars['city']) {
                                    $city_matches = true;
                                    break;
                                }
                            }

                            // Если город не совпадает - отдаем 404
                            if (!$city_matches) {
                                $query_vars['error'] = 404;
                                return $query_vars;
                            }
                        }
                    }
                }
            }

            if (isset($query_vars['city'])) {
                // Проверяем, является ли второй сегмент специальной страницей
                if (isset($query_vars['special_page'])) {
                    // Это special_page запрос, проверяем что город существует
                    $city_term = get_term_by('slug', $query_vars['city'], 'city');
                    if (!$city_term) {
                        // Город не существует, удаляем city и special_page
                        unset($query_vars['city']);
                        unset($query_vars['special_page']);
                    }
                }
                // Если это taxonomy запрос (услуги, категории), проверяем город
                elseif (isset($query_vars['taxonomy']) && isset($query_vars['term'])) {
                    $city_term = get_term_by('slug', $query_vars['city'], 'city');
                    if (!$city_term) {
                        // Это не город, возможно это обычная таксономия
                        unset($query_vars['city']);
                    }
                }
                // Если это главная страница города
                elseif (!isset($query_vars['post_type']) && !isset($query_vars['taxonomy'])) {
                    $city_term = get_term_by('slug', $query_vars['city'], 'city');
                    if (!$city_term) {
                        // Это не город. Возможно, это страница "Контакты" (/contacts)
                        // Удаляем city, переносим в pagename
                        $potential_page = $query_vars['city'];
                        unset($query_vars['city']);
                        $query_vars['pagename'] = $potential_page;
                    }
                }
            }
            return $query_vars;
        });
    }

    /**
     * Фильтр для выбора шаблона
     */
    protected function addTemplateFilter(): void
    {
        add_filter('template_include', function ($template) {
            // Проверяем, есть ли город и special_page (ПРИОРИТЕТ 1)
            $city = get_query_var('city');
            $special_page = get_query_var('special_page');

            if ($city && $special_page) {
                $special_page = get_query_var('special_page');

                // Ищем шаблон для специальной страницы
                $special_templates = [
                    'vip' => 'views/template-vip.blade.php',
                    'cheap' => 'views/template-cheap.blade.php',
                    'deshevye' => 'views/template-cheap.blade.php',
                    'independent' => 'views/template-independent.blade.php',
                    'individualki' => 'views/template-independent.blade.php',
                    'outcall' => 'views/template-outcall.blade.php',
                    'incall' => 'views/template-incall.blade.php',
                    'online' => 'views/template-online.blade.php',
                    'prostitutki-na-vyezd' => 'views/template-outcall.blade.php',
                    'prostitutki-priem' => 'views/template-incall.blade.php',
                ];

                if (isset($special_templates[$special_page])) {
                    $template_name = $special_templates[$special_page];

                    // Пробуем прямой путь к файлу
                    $direct_path = get_template_directory() . '/resources/' . $template_name;

                    if (file_exists($direct_path)) {
                        return $direct_path;
                    }
                }
            }
            // Проверяем special_page без города (ПРИОРИТЕТ 2)
            elseif (!$city && $special_page) {
                $special_templates = [
                    'reviews' => 'views/template-reviews.blade.php',
                ];

                if (isset($special_templates[$special_page])) {
                    $template_name = $special_templates[$special_page];
                    
                    // Для Sage/Acorn нужно использовать фильтр который правильно обрабатывает Blade
                    add_filter('sage/template', function($templates) use ($template_name) {
                        array_unshift($templates, str_replace('views/', '', $template_name));
                        return $templates;
                    });
                    
                    // Возвращаем стандартный шаблон чтобы Sage обработал наш
                    return get_template_directory() . '/index.php';
                }
            }

            // Проверяем, есть ли город и таксономия в запросе
            if (get_query_var('city') && get_query_var('taxonomy')) {
                $city_template = locate_template('taxonomy-with-city.blade.php');
                if ($city_template) {
                    return $city_template;
                }
            }

            // Проверяем, есть ли город и это страница
            if (get_query_var('city') && is_page()) {
                // Не перезаписываем кастомные шаблоны страниц
                $assigned_template = get_page_template_slug(get_queried_object_id());
                if (empty($assigned_template) || $assigned_template === 'default') {
                    $page_template = locate_template('page-with-city.blade.php');
                    if ($page_template) {
                        return $page_template;
                    }
                }
            }

            return $template;
        }, 999);
    }

    protected function addProfileLinkFilter(): void
    {
        // Фильтр для ссылок профилей - всегда используем нашу функцию profile_url()
        add_filter('post_type_link', function ($post_link, $post) {
            if ($post->post_type === 'profile') {
                // Используем нашу функцию profile_url() которая учитывает город
                return profile_url($post->ID);
            }
            return $post_link;
        }, 10, 2);
    }

    /**
     * Программная регистрация полей ACF для таксономии City
     */
    protected function registerAcfCityFields(): void
    {
        // Проверяем, активен ли ACF
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        add_action('acf/init', function () {
            acf_add_local_field_group([
                'key' => 'group_city_seo_settings',
                'title' => 'Настройки страниц города (SEO)',
                'fields' => [
                    [
                        'key' => 'field_city_pages_repeater',
                        'label' => 'Страницы',
                        'name' => 'city_pages_seo',
                        'type' => 'repeater',
                        'layout' => 'row',
                        'button_label' => 'Добавить страницу',
                        'sub_fields' => [
                            [
                                'key' => 'field_city_page_select',
                                'label' => 'Тип страницы',
                                'name' => 'page_key',
                                'type' => 'select',
                                'choices' => [
                                    'home' => 'Главная / Каталог',
                                    'map' => 'Карта',
                                    'reviews' => 'Отзывы',
                                    'vip' => 'VIP',
                                    'independent' => 'Инди',
                                ],
                                'wrapper' => ['width' => '20'],
                            ],
                            [
                                'key' => 'field_city_page_h1',
                                'label' => 'H1 Заголовок',
                                'name' => 'h1',
                                'type' => 'text',
                                'wrapper' => ['width' => '40'],
                            ],
                            [
                                'key' => 'field_city_page_title',
                                'label' => 'SEO Title',
                                'name' => 'meta_title',
                                'type' => 'text',
                                'wrapper' => ['width' => '40'],
                            ],
                            [
                                'key' => 'field_city_page_desc',
                                'label' => 'Meta Description',
                                'name' => 'meta_description',
                                'type' => 'textarea',
                                'rows' => 2,
                            ],
                            [
                                'key' => 'field_city_page_text_top',
                                'label' => 'Текст сверху',
                                'name' => 'intro_text',
                                'type' => 'wysiwyg',
                                'media_upload' => false,
                                'tabs' => 'visual',
                                'toolbar' => 'basic',
                            ],
                            [
                                'key' => 'field_city_page_text_bottom',
                                'label' => 'SEO Текст (снизу)',
                                'name' => 'seo_text',
                                'type' => 'wysiwyg',
                                'media_upload' => false,
                                'tabs' => 'visual',
                                'toolbar' => 'basic',
                            ],
                        ],
                    ],
                ],
                'location' => [
                    [
                        [
                            'param' => 'taxonomy',
                            'operator' => '==',
                            'value' => 'city',
                        ],
                    ],
                ],
                'menu_order' => 0,
                'position' => 'normal',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => true,
                'description' => '',
            ]);
        });
    }
}
