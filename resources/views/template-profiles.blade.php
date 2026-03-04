{{--
  Template Name: Анкеты: Grid + List (Screenshot Style)
--}}
@extends('layouts.app')

@section('content')
    @php
        // -----------------------------------------------------------
        // 1. ОПРЕДЕЛЕНИЕ КОНТЕКСТА (ГОРОД И СТРАНИЦА)
        // -----------------------------------------------------------
        
        // Получаем текущий город из URL
        global $wp;
        $city_slug = isset($wp->query_vars['city']) ? $wp->query_vars['city'] : get_query_var('city');
        $current_city = get_term_by('slug', $city_slug, 'city');
        $special_page = isset($wp->query_vars['special_page']) ? $wp->query_vars['special_page'] : get_query_var('special_page');
        $pagename = isset($wp->query_vars['pagename']) ? (string) $wp->query_vars['pagename'] : (string) get_query_var('pagename');
        
        // Если город не определен в URL, используем дефолтный
        if (!$current_city) {
            $current_city = get_term_by('slug', \App\Helpers\UrlHelpers::DEFAULT_CITY_SLUG, 'city');
        }
        
        $city_name = $current_city ? $current_city->name : 'Город';
        
        // -----------------------------------------------------------
        // 2. ОПРЕДЕЛЕНИЕ ТИПА СТРАНИЦЫ И ПОЛУЧЕНИЕ РОДИТЕЛЬСКОЙ СТРАНИЦЫ
        // -----------------------------------------------------------
        
        $page_type = '';
        $parent_page = null;
        $source_id = (int) (get_queried_object_id() ?: get_the_ID()); // по умолчанию текущая страница
        
        // Определяем тип страницы и получаем родительскую страницу настроек
        if ($special_page === 'vip' || $pagename === 'vip' || is_page_template('template-vip.blade.php')) {
            $page_type = 'vip';
            $parent_page = get_page_by_path('vip');
        } elseif ($special_page === 'deshyovye' || $pagename === 'deshyovye' || is_page_template('template-cheap.blade.php')) {
            $page_type = 'deshyovye';
            $parent_page = get_page_by_path('deshyovye');
        } elseif ($special_page === 'outcall' || $pagename === 'prostitutki-na-vyezd' || is_page_template('template-outcall.blade.php')) {
            $page_type = 'outcall';
            $parent_page = get_page_by_path('prostitutki-na-vyezd');
        } elseif (($special_page === 'prostitutki') || ($pagename === 'prostitutki') || is_page_template('template-girls.blade.php')) {
            $page_type = 'prostitutki';
            $parent_page = get_page_by_path('prostitutki');
        } elseif ($special_page === 'incall' || $pagename === 'prostitutki-priyom' || is_page_template('template-incall.blade.php')) {
            $page_type = 'incall';
            $parent_page = get_page_by_path('prostitutki-priyom');
        } elseif ($special_page === 'novye' || $pagename === 'novye' || is_page_template('template-new.blade.php')) {
            $page_type = 'novye';
            $parent_page = get_page_by_path('novye');
        } elseif ($special_page === 'provereno' || $pagename === 'provereno' || is_page_template('template-verified.blade.php')) {
            $page_type = 'provereno';
            $parent_page = get_page_by_path('provereno');
        }
        
        // Используем ID родительской страницы только если в запросе не определилась текущая страница.
        if ($parent_page && ($source_id <= 0 || get_post_type($source_id) !== 'page')) {
            $source_id = (int) $parent_page->ID;
        }

        // Последний fallback: пытаемся найти страницу по pagename из query var.
        if ($source_id <= 0 && $pagename !== '') {
            $page_by_name = get_page_by_path($pagename);
            if ($page_by_name) {
                $source_id = (int) $page_by_name->ID;
            }
        }
        
        // -----------------------------------------------------------
        // 3. ПОИСК ДАННЫХ В REPEATER (Специфика города)
        // -----------------------------------------------------------
        
        $city_specific_data = [];
        $found_city_in_repeater = false;
        $repeater_rows = [];

        if ($current_city && function_exists('get_field')) {
            $resolve_city_term_id = static function ($raw_city) use (&$resolve_city_term_id): int {
                if ($raw_city instanceof \WP_Term) {
                    return (int) $raw_city->term_id;
                }

                if (is_object($raw_city) && isset($raw_city->term_id)) {
                    return (int) $raw_city->term_id;
                }

                if (is_array($raw_city)) {
                    if (!isset($raw_city['term_id']) && !isset($raw_city['id']) && !isset($raw_city['ID']) && !isset($raw_city['slug'])) {
                        $first = reset($raw_city);
                        if ($first !== false || (is_array($raw_city) && count($raw_city) > 0)) {
                            return $resolve_city_term_id($first);
                        }
                    }

                    if (isset($raw_city['term_id'])) {
                        return (int) $raw_city['term_id'];
                    }

                    if (isset($raw_city['id'])) {
                        return (int) $raw_city['id'];
                    }

                    if (isset($raw_city['ID'])) {
                        return (int) $raw_city['ID'];
                    }

                    if (!empty($raw_city['slug'])) {
                        $city_term = get_term_by('slug', (string) $raw_city['slug'], 'city');
                        return $city_term instanceof \WP_Term ? (int) $city_term->term_id : 0;
                    }
                }

                if (is_numeric($raw_city)) {
                    return (int) $raw_city;
                }

                if (is_string($raw_city) && $raw_city !== '') {
                    $city_term = get_term_by('slug', $raw_city, 'city');
                    return $city_term instanceof \WP_Term ? (int) $city_term->term_id : 0;
                }

                return 0;
            };

            $extract_city_row = static function (array $row): array {
                return [
                    'seo_title'       => (string) ($row['seo_title'] ?? $row['meta_title'] ?? ''),
                    'seo_description' => (string) ($row['meta_description'] ?? $row['seo_description'] ?? ''),
                    'custom_h1'       => (string) ($row['h1'] ?? $row['custom_h1'] ?? ''),
                    'description'     => (string) ($row['description'] ?? $row['intro_text'] ?? ''),
                    'main_text'       => (string) ($row['main_text'] ?? $row['seo_text'] ?? ''),
                ];
            };

            $source_candidates = [];
            $push_source_candidate = static function (array &$candidates, int $candidate): void {
                if ($candidate > 0 && !in_array($candidate, $candidates, true)) {
                    $candidates[] = $candidate;
                }
            };

            $push_source_candidate($source_candidates, (int) $source_id);
            $push_source_candidate($source_candidates, (int) (get_queried_object_id() ?: 0));
            $push_source_candidate($source_candidates, (int) (get_the_ID() ?: 0));
            $push_source_candidate($source_candidates, $parent_page ? (int) $parent_page->ID : 0);

            if ($pagename !== '') {
                $page_by_name = get_page_by_path($pagename);
                $push_source_candidate($source_candidates, $page_by_name ? (int) $page_by_name->ID : 0);
            }

            $matched_source_id = 0;
            foreach ($source_candidates as $candidate_id) {
                $rows = get_field('city_pages_seo', $candidate_id);
                if (!is_array($rows) || empty($rows)) {
                    continue;
                }

                foreach ($rows as $row) {
                    if (!is_array($row)) {
                        continue;
                    }

                    $row_city_id = $resolve_city_term_id($row['city'] ?? null);
                    if ($row_city_id > 0 && $row_city_id === (int) $current_city->term_id) {
                        $candidate_data = $extract_city_row($row);
                        $has_content = trim(implode('', $candidate_data)) !== '';

                        if ($has_content) {
                            $city_specific_data = $candidate_data;
                            $found_city_in_repeater = true;
                            $matched_source_id = (int) $candidate_id;
                            $repeater_rows = $rows;
                            break 2;
                        }
                    }
                }
            }

            if ($matched_source_id > 0) {
                $source_id = $matched_source_id;
            }
        }

        // -----------------------------------------------------------
        // 4. ПОЛУЧЕНИЕ ДЕФОЛТНЫХ ДАННЫХ (Если города нет в repeater)
        // -----------------------------------------------------------
        
        $default_data = [
            'seo_title'       => get_field('seo_title', $source_id) ?: '',
            'seo_description' => get_field('seo_description', $source_id) ?: '',
            'custom_h1'       => get_field('custom_h1', $source_id) ?: get_the_title(),
            'description'     => get_field('intro_text', $source_id) ?: '',
            'main_text'       => get_field('main_text', $source_id) ?: '',
        ];

        // -----------------------------------------------------------
        // 5. СЛИЯНИЕ ДАННЫХ (ФИНАЛЬНЫЙ НАБОР)
        // -----------------------------------------------------------
        
        // Если нашли данные для города - берем их, иначе - дефолт
        // Автоматически добавляем город ко всем заголовкам если его нет
        $h1_base = !empty($city_specific_data['custom_h1']) ? $city_specific_data['custom_h1'] : $default_data['custom_h1'];
        $title_base = !empty($city_specific_data['seo_title']) ? $city_specific_data['seo_title'] : $default_data['seo_title'];
        
        // Добавляем город к H1 если его нет
        if ($current_city && strpos(strtolower($h1_base), strtolower($city_name)) === false) {
            $h1_base .= ' ' . $city_name;
        }
        
        // Добавляем город к title если его нет
        if ($current_city && strpos(strtolower($title_base), strtolower($city_name)) === false) {
            $title_base .= ' ' . $city_name;
        }
        
        $final_data = [
            'h1'          => $h1_base,
            'intro'       => !empty($city_specific_data['description']) ? $city_specific_data['description'] : $default_data['description'],
            'main_text'   => !empty($city_specific_data['main_text'])   ? $city_specific_data['main_text']   : $default_data['main_text'],
            'seo_title'   => $title_base ?: (get_the_title() . ' ' . $city_name),
            'meta_desc'   => !empty($city_specific_data['seo_description']) ? $city_specific_data['seo_description'] : $default_data['seo_description'],
        ];

        // Добавляем пагинацию к SEO title
        if (is_paged()) {
            $page_num = get_query_var('paged') ?: get_query_var('page');
            if ($page_num > 1) {
                $final_data['seo_title'] .= ' | Страница ' . $page_num;
            }
        }

        // -----------------------------------------------------------
        // 6. ПОПЫТКА УСТАНОВКИ SEO METADATA (Фильтры)
        // -----------------------------------------------------------
        
        if ($final_data['seo_title']) {
            add_filter('pre_get_document_title', function() use ($final_data) { 
                return $final_data['seo_title']; 
            }, 999);
            add_filter('wpseo_title', function() use ($final_data) { 
                return $final_data['seo_title']; 
            }, 999);
            add_filter('rank_math/frontend/title', function() use ($final_data) { 
                return $final_data['seo_title']; 
            }, 999);
        }
        
        if ($final_data['meta_desc']) {
            add_filter('wpseo_metadesc', function() use ($final_data) { 
                return $final_data['meta_desc']; 
            }, 999);
            add_filter('rank_math/frontend/description', function() use ($final_data) { 
                return $final_data['meta_desc']; 
            }, 999);
            
            // Прямой вывод мета дескрипшн через wp_head
            add_action('wp_head', function() use ($final_data) {
                echo '<meta name="description" content="' . esc_attr($final_data['meta_desc']) . '">' . "\n";
            }, 1);
        }

        // Извлекаем переменные для использования в шаблоне
        $main_text = $final_data['main_text'] ?: '';

        // -----------------------------------------------------------
        // 7. ПОДГОТОВКА ЗАПРОСА (QUERY) И ФИЛЬТРОВ
        // -----------------------------------------------------------
        
        // Получаем данные для фильтров
        $filter_data = [];
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
            'what'          => 'Что',
            'parameters'    => 'Параметры',
            'metadata'      => 'Метаданные',
            'metro'         => 'Метро',
            'district'      => 'Районы',
            'appearance'    => 'Внешность',
            'place'         => 'Место',
        ];
        
        $location_taxonomies = location_taxonomies();

        foreach ($taxonomies as $slug => $label) {
            if (array_key_exists($slug, $location_taxonomies)) {
                $terms = get_location_terms($slug);
            } else {
                $terms = get_terms(['taxonomy' => $slug, 'hide_empty' => true]);
            }

            if (!is_wp_error($terms) && !empty($terms)) {
                $filter_data[$slug] = ['label' => $label, 'terms' => $terms];
            }
        }
        
        // Используем profiles_query из ProfilesCatalog composer
        // Он уже содержит всю логику фильтрации и пагинации
        $profiles_query = $profiles_query ?? null;
        
        if (!$profiles_query) {
            // Fallback: создаем базовый запрос если composer не предоставил данные
            $args = [
                'post_type' => 'profile',
                'posts_per_page' => 48,
                'paged' => get_query_var('paged') ?: 1,
                'tax_query' => [
                    'relation' => 'AND',
                    [
                        'taxonomy' => 'city',
                        'field'    => 'slug',
                        'terms'    => $current_city ? $current_city->slug : \App\Helpers\UrlHelpers::DEFAULT_CITY_SLUG,
                        'operator' => 'IN', // IN работает правильно для таксономий
                    ]
                ],
            ];
            
            // Добавляем фильтрацию по VIP если это VIP страница
            if ($page_type === 'vip' || $special_page === 'vip') {
                $args['tax_query'][] = [
                    'taxonomy' => 'vip',
                    'field'    => 'slug', 
                    'terms'    => ['vip'],
                    'operator' => 'IN',
                ];
            }

            if ($page_type === 'deshyovye' || $special_page === 'deshyovye') {
                $args['meta_query'][] = [
                    'key'     => 'price_price_1h',
                    'value'   => 15000,
                    'compare' => '<=',
                    'type'    => 'NUMERIC',
                ];
            }

            $args = \App\Services\ProfileQuery::applyRequestFiltersToArgs($args);
            
            $profiles_query = new WP_Query($args);
        }
        
        // Определяем является ли первая карточка LCP
        $isLcp = !is_paged() && empty($_GET);
    @endphp
    
    <div class="container mx-auto px-4 py-8">
        {{-- Header --}}
        <header class="prose mb-10 text-center max-w-4xl mx-auto">
            <h1 class="text-xl xl:text-3xl font-bold mb-8 mt-0 text-center px-1 md:px-0">
                {!! $final_data['h1'] !!}
                @if (is_paged())
                    <span class="text-[#cd1d46]">| Страница {{ get_query_var('paged') ?: get_query_var('page') }}</span>
                @endif
            </h1>
            
            {{-- Intro текст (Description) --}}
            @if (!is_paged() && !empty($final_data['intro']))
                <div class="leading-relaxed max-w-2xl mx-auto">
                    {!! $final_data['intro'] !!}
                </div>
            @endif
        </header>

        {{-- LAYOUT --}}
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 items-start">

            {{-- 1. САЙДБАР --}}
            <aside class="lg:col-span-1 hidden lg:block">
                <x-catalog-filters :filter-data="$filter_data" />
            </aside>

            {{-- 2. КОНТЕНТ --}}
            <div class="lg:col-span-3">

                {{-- Мобильная кнопка фильтра --}}
                <div class="lg:hidden mb-6">
                    <button onclick="openMobileFiltersGlobal()"
                            class="w-full bg-[#cd1d46] hover:bg-[#b71833] text-black font-bold capitalize py-4 rounded-xl shadow-lg transition-transform active:scale-95 flex items-center justify-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                        Фильтр
                    </button>
                </div>

                {{-- ВЕРХНЯЯ ПАНЕЛЬ --}}
                <div class="flex flex-wrap items-center justify-between mb-6 pb-4 gap-4">
                    <h2 class="text-xl md:text-2xl font-bold text-white tracking-wide">
                        Найдено анкет: {{ $profiles_query->found_posts }}
                    </h2>

                </div>

                @if ($profiles_query->have_posts())
                    
                    <ul class="grid list-none grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                        @while ($profiles_query->have_posts())
                            @php
                                $profiles_query->the_post();
                                $paged = get_query_var('paged') ?: get_query_var('page') ?: 1;
                                $isLcp = $profiles_query->current_post === 0 && $paged == 1;
                            @endphp
                            <li><x-profile-card :lcp="$isLcp" /></li>
                        @endwhile
                    </ul>

                    @php
                        wp_reset_postdata();
                    @endphp

                    {{-- Пагинация --}}
                    <div class="mt-12 flex justify-center">
                        @php
                            // Фильтр для исправления ссылок на первую страницу
                            add_filter('paginate_links', function($link) {
                                // Убираем /page/1/ из ссылок
                                if (strpos($link, '/page/1/') !== false) {
                                    $link = str_replace('/page/1/', '/', $link);
                                }
                                return $link;
                            });
                        @endphp
                        {!! paginate_links([
                            'base' => str_replace(999999999, '%#%', get_pagenum_link(999999999)),
                            'format' => '?paged=%#%',
                            'current' => max(1, get_query_var('paged'), get_query_var('page')),
                            'total' => $profiles_query->max_num_pages,
                            'type' => 'list',
                            'prev_text' => '&larr;',
                            'next_text' => '&rarr;',
                        ]) !!}
                    </div>
                @else
                    <div class="bg-black border-l-4 border-yellow-400 p-6 rounded text-yellow-800">
                        <p class="font-bold text-lg">Ничего не найдено 😔</p>
                    </div>
                @endif

            </div>
        </div>

        {{-- Мобильный фильтр --}}
        <div class="lg:hidden">
            <x-catalog-filters :filter-data="$filter_data" />
        </div>

        {{-- SEO Text --}}
        @if (!is_paged() && $main_text)
            <div class="mt-16">
                <article class="prose prose-lg max-w-none rounded-xl bg-black p-6 md:p-10 border border-[#cd1d46]">
                    {!! $main_text !!}
                </article>
            </div>
        @endif
    </div>

@endsection
