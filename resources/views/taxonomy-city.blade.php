@extends('layouts.app')

@section('content')
    @php
        // Получаем текущий город
        $current_city = get_queried_object();
        $city_name = $current_city ? $current_city->name : 'Город';
        
        // Получаем SEO данные города
        $special_page = get_query_var('special_page');
        
        if ($special_page && $current_city) {
            // Для спецстраниц (VIP и др.) ищем данные в repeater
            $city_pages = get_field('city_pages_seo', 'city_' . $current_city->term_id);
            $seo_data = [];
            
            if (is_array($city_pages)) {
                foreach ($city_pages as $page_data) {
                    if (isset($page_data['page_key']) && $page_data['page_key'] === $special_page) {
                        $seo_data = [
                            'seo_title' => $page_data['meta_title'] ?? '',
                            'seo_description' => $page_data['meta_description'] ?? '',
                            'custom_h1' => $page_data['h1'] ?? '',
                            'description' => $page_data['intro_text'] ?? '',
                            'main_text' => $page_data['seo_text'] ?? '',
                        ];
                        break;
                    }
                }
            }
            
            // Используем данные для спецстраницы или базовые данные города
            $seo_title = $seo_data['seo_title'] ?? '' ?: get_field('seo_title', $current_city);
            $seo_description = $seo_data['seo_description'] ?? '' ?: get_field('seo_description', $current_city);
            $custom_h1 = $seo_data['custom_h1'] ?? '' ?: get_field('custom_h1', $current_city);
            $description = $seo_data['description'] ?? '' ?: get_field('description', $current_city);
            $main_text = $seo_data['main_text'] ?? '' ?: get_field('main_text', $current_city);
        } else {
            // Базовые данные города (для главной страницы города)
            $seo_title = get_field('seo_title', $current_city);
            $seo_description = get_field('seo_description', $current_city);
            $custom_h1 = get_field('custom_h1', $current_city);
            $description = get_field('description', $current_city);
            $main_text = get_field('main_text', $current_city);
        }
        
        // Дополнительная информация
        $population = get_field('population', $current_city);
        $coordinates = get_field('coordinates', $current_city);
        $phone_code = get_field('phone_code', $current_city);
        
        // Формируем заголовки с автоматической подстановкой города
        $page_title = $custom_h1 ?: $city_name;
        $meta_title = $seo_title ?: $city_name;
        $meta_description = $seo_description ?: '';
        
        // Добавляем город к title если его нет
        if ($city_name && strpos(strtolower($meta_title), strtolower($city_name)) === false) {
            $meta_title .= ' ' . $city_name;
        }
        
        // Добавляем город к H1 если его нет
        if ($city_name && strpos(strtolower($page_title), strtolower($city_name)) === false) {
            $page_title .= ' ' . $city_name;
        }
        
        // Добавляем пагинацию к SEO title
        if (is_paged()) {
            $page_num = get_query_var('paged') ?: get_query_var('page');
            if ($page_num > 1) {
                $meta_title .= ' | Страница ' . $page_num;
            }
        }
        
        // Устанавливаем SEO метаданные
        if ($meta_title) {
            add_filter('pre_get_document_title', function() use ($meta_title) { return $meta_title; }, 999);
            add_filter('wpseo_title', function() use ($meta_title) { return $meta_title; }, 999);
            add_filter('rank_math/frontend/title', function() use ($meta_title) { return $meta_title; }, 999);
        }
        
        if ($meta_description) {
            add_filter('wpseo_metadesc', function() use ($meta_description) { return $meta_description; }, 999);
            add_filter('rank_math/frontend/description', function() use ($meta_description) { return $meta_description; }, 999);
        }
        
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
        ];
        
        foreach ($taxonomies as $slug => $label) {
            $terms = get_terms([
                'taxonomy'   => $slug,
                'hide_empty' => true,
            ]);

            if (!is_wp_error($terms) && !empty($terms)) {
                $filter_data[$slug] = [
                    'label' => $label,
                    'terms' => $terms,
                ];
            }
        }
        
        // Получаем анкеты только для текущего города
        $tax_query = [
            [
                'taxonomy' => 'city',
                'field' => 'term_id',
                'terms' => $current_city->term_id,
            ],
        ];
        
        // Добавляем фильтр по специальной странице (VIP)
        if ($special_page === 'vip') {
            $vip_term = get_term_by('slug', 'vip', 'vip');
            if ($vip_term) {
                $tax_query[] = [
                    'taxonomy' => 'vip',
                    'field' => 'term_id',
                    'terms' => $vip_term->term_id,
                ];
            }
        }
        
        $query_args = [
            'post_type' => 'profile',
            'posts_per_page' => 48,
            'paged' => get_query_var('paged') ?: 1,
            'tax_query' => $tax_query,
        ];

        $query_args = \App\Services\ProfileQuery::applyRequestFiltersToArgs($query_args);
        $profiles_query = new WP_Query($query_args);
    @endphp
    <div class="container mx-auto px-4 py-8">

        {{-- Header --}}
        <header class="prose mb-10 text-center max-w-4xl mx-auto">
            <h1 class="text-3xl md:text-5xl font-bold capitalize mb-4 tracking-tight">
                {{ $page_title }}
                @if (is_paged())
                    <span class="text-[#cd1d46]">| Страница {{ get_query_var('paged') ?: get_query_var('page') }}</span>
                @endif
            </h1>
            @if (!is_paged() && $description)
                <p class="leading-relaxed max-w-2xl mx-auto">
                    {!! $description !!}
                </p>
            @endif
        </header>

        {{-- Основной контент --}}
        <div class="prose prose-lg max-w-none">
        

            {{-- Дополнительная информация о городе --}}
            @if (!is_paged() && ($population || $coordinates || $phone_code))
                <div class="bg-gray-900 p-6 md:p-10  border border-gray-700 prose-invert mb-8">
                    <h2 class="text-xl font-bold text-black mb-4">Информация о городе</h2>
                    @if ($population)
                        <p><strong>Население:</strong> {{ number_format($population) }} человек</p>
                    @endif
                    @if ($coordinates)
                        <p><strong>Координаты:</strong> {{ $coordinates }}</p>
                    @endif
                    @if ($phone_code)
                        <p><strong>Телефонный код:</strong> +{{ $phone_code }}</p>
                    @endif
                </div>
            @endif
        </div>

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
                            class="w-full bg-[#cd1d46] rounded-xl hover:bg-[#b71833] text-black font-bold capitalize py-4  shadow-lg transition-transform active:scale-95 flex items-center justify-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                        Фильтр
                    </button>
                </div>

                {{-- ВЕРХНЯЯ ПАНЕЛЬ --}}
                <div class="flex flex-wrap items-center justify-between mb-6 pb-4 gap-4">
                    <h2 class="text-xl md:text-2xl font-bold text-black tracking-wide">
                        Найдено анкет: {{ $profiles_query->found_posts }}
    </h2>

                </div>

                @if ($profiles_query->have_posts())
                    
                    <ul class="grid list-none md:grid-cols-2 lg:grid-cols-3 gap-10">
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

                    {{-- Кастомный контент для города (если есть) --}}
            @if (!is_paged() && $main_text)
                <div class="prose prose-lg mt-4 max-w-none bg-black p-6 md:p-10 border border-[#cd1d46]">
                    {!! $main_text !!}
                </div>
            @endif

        {{-- SEO Text --}}
        @if (!is_paged() && $seoText = get_field('main_seo_text'))
            <div class="mt-16">
                <article class="prose prose-lg prose-invert max-w-none bg-black p-8 md:p-12  border border-[#cd1d46]">
                    {!! $seoText !!}
                </article>
            </div>
        @endif

    </div>

@endsection
