@extends('layouts.app')

@section('content')
    @php
        // Получаем данные из URL
        $city_slug = get_query_var('city');
        $taxonomy = get_query_var('taxonomy');
        $term_slug = get_query_var('term');
        
        // Получаем объекты
        $current_city = get_term_by('slug', $city_slug, 'city');
        $current_term = get_term_by('slug', $term_slug, $taxonomy);
        
        // Получаем SEO данные для города
        $seo_data = get_taxonomy_seo($taxonomy, $term_slug, $city_slug);
        
        // Получаем специфичные данные для города из новых полей
        $city_specific_data = [
            'seo_title' => '',
            'seo_description' => '',
            'custom_h1' => '',
            'description' => '',
            'main_text' => '',
        ];
        if ($current_city && function_exists('get_field')) {
            $city_specific_data = [
                'seo_title' => get_field('city_seo_title', $current_term) ?: '',
                'seo_description' => get_field('city_seo_description', $current_term) ?: '',
                'custom_h1' => get_field('city_custom_h1', $current_term) ?: '',
                'description' => get_field('city_description', $current_term) ?: '',
                'main_text' => get_field('city_main_text', $current_term) ?: '',
            ];
        }
        
        // Получаем базовые данные термина
        $default_data = [
            'seo_title' => get_field('seo_title', $current_term) ?: '',
            'seo_description' => get_field('seo_description', $current_term) ?: '',
            'custom_h1' => get_field('custom_h1', $current_term) ?: '',
            'description' => get_field('description', $current_term) ?: '',
            'main_text' => get_field('main_text', $current_term) ?: '',
        ];
        
        // Формируем финальные данные: приоритет у специфичных для города
        $final_seo_data = [
            'seo_title' => $city_specific_data['seo_title'] ?: $default_data['seo_title'],
            'seo_description' => $city_specific_data['seo_description'] ?: $default_data['seo_description'],
            'custom_h1' => $city_specific_data['custom_h1'] ?: $default_data['custom_h1'],
            'description' => $city_specific_data['description'] ?: $default_data['description'],
            'main_text' => $city_specific_data['main_text'] ?: $default_data['main_text'],
        ];
        
        // Формируем заголовки
        $page_title = $final_seo_data['custom_h1'] ?: $current_term->name;
        $page_description = $final_seo_data['description'] ?: '';
        $seo_title = $final_seo_data['seo_title'] ?: '';
        $meta_description = $final_seo_data['seo_description'] ?: '';
        $main_text = $final_seo_data['main_text'] ?: '';

        // Получаем профили для этой услуги в этом городе
        $query_args = [
            'post_type' => 'profile',
            'posts_per_page' => 48,
            'paged' => get_query_var('paged') ?: 1,
            'tax_query' => [
                'relation' => 'AND',
                [
                    'taxonomy' => 'city',
                    'field' => 'term_id',
                    'terms' => $current_city->term_id,
                ],
                [
                    'taxonomy' => $taxonomy,
                    'field' => 'term_id',
                    'terms' => $current_term->term_id,
                ],
            ],
        ];

        $query_args = \App\Services\ProfileQuery::applyRequestFiltersToArgs($query_args, [$taxonomy]);
        $profiles_query = new WP_Query($query_args);

        if (in_array($taxonomy, ['metro', 'service', 'district'], true)) {
            $generated_seo_data = \App\Services\TaxonomySeoTextGenerator::generateForTerm($current_term, $profiles_query);

            if (empty($seo_title)) {
                $seo_title = $generated_seo_data['seo_title'] ?? '';
            }

            if (empty($meta_description)) {
                $meta_description = $generated_seo_data['meta_description'] ?? '';
            }

            if (empty($final_seo_data['custom_h1'])) {
                $page_title = $generated_seo_data['h1'] ?? '';
            }

            if (empty($page_description)) {
                $page_description = $generated_seo_data['description'] ?? '';
            }

            if (empty($main_text)) {
                $main_text = $generated_seo_data['main_seo_text'] ?? '';
            }
        }

        // Устанавливаем SEO мета-теги через фильтры
        if (!empty($seo_title)) {
            // Добавляем пагинацию к SEO title
            if (is_paged()) {
                $page_num = get_query_var('paged') ?: get_query_var('page');
                if ($page_num > 1) {
                    $seo_title .= ' | Страница ' . $page_num;
                }
            }

            add_filter('pre_get_document_title', function() use ($seo_title) {
                return $seo_title;
            }, 999);

            // Fallback для старых версий WordPress
            add_filter('wp_title', function() use ($seo_title) {
                return $seo_title;
            }, 999);
        }

        if (!empty($meta_description)) {
            add_filter('wpseo_metadesc', function() use ($meta_description) {
                return $meta_description;
            }, 999);

            add_filter('rank_math/frontend/description', function() use ($meta_description) {
                return $meta_description;
            }, 999);
        }
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
            @if (!is_paged() && $page_description)
                <div class="leading-relaxed max-w-2xl mx-auto">
                    {!! $page_description !!}
                </div>
            @endif
        </header>

        {{-- Фильтры и контент --}}
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 items-start">

            {{-- 1. САЙДБАР --}}
            <aside class="lg:col-span-1 hidden lg:block">
                <x-catalog-filters :filter-data="$filter_data" />
            </aside>

            {{-- 2. КОНТЕНТ --}}
            <div class="lg:col-span-3">
                
                {{-- Основной текст (если есть) --}}
                @if (!is_paged() && $main_text)
                    <div class="prose prose-lg max-w-none bg-gray-900 p-6 md:p-10  border border-gray-700 prose-invert mb-8">
                        {!! $main_text !!}
                    </div>
                @endif

                {{-- Анкеты --}}
                @if ($profiles_query->have_posts())
                    
                    {{-- ВЕРХНЯЯ ПАНЕЛЬ --}}
                    <div class="flex flex-wrap items-center justify-between mb-6 pb-4 gap-4">
                        <h2 class="text-xl md:text-2xl font-bold text-black tracking-wide">
                            Найдено анкет: {{ $profiles_query->found_posts }}
    </h2>

                    </div>

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
                            'mid_size' => 2,
                            'end_size' => 1,
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

    </div>

@endsection
