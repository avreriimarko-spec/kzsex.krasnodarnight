{{--
  Template Name: Карта сайта (HTML)
--}}

@extends('layouts.app')

@section('content')
    @php
        // 1. СЛАГИ ДЛЯ ИСКЛЮЧЕНИЯ ИЗ БЛОКА "ОСНОВНОЕ"
        $excluded_slugs = [
            'sample-page',
            'vip', 
            'individualki', 
            'online', 
            'prostitutki-na-vyezd', 
            'prostitutki-priem',    
            'otzyvy', 
            'catalog', 
            'map',
            'home',
            'glavnaya'
        ];

        // 2. СПИСОК ГОРОДОВ
        $cities = get_terms(['taxonomy' => 'city', 'hide_empty' => true, 'orderby' => 'name', 'order' => 'ASC']);

        // 3. ПОЛУЧЕНИЕ СТРАНИЦ
        $pages = get_pages([
            'sort_column' => 'menu_order, post_title',
            'sort_order' => 'ASC',
            'post_status' => 'publish'
        ]);

        // 4. ПОЛУЧЕНИЕ ТАКСОНОМИЙ
        $taxonomies = [];
        $taxonomy_types = ['service', 'hair_color', 'breast_size', 'nationality', 'figure'];
        
        foreach ($taxonomy_types as $tax_type) {
            $terms = get_terms([
                'taxonomy' => $tax_type,
                'hide_empty' => true,
                'orderby' => 'name',
                'order' => 'ASC'
            ]);
            
            if (!is_wp_error($terms) && !empty($terms)) {
                $tax_labels = get_taxonomy_labels(get_taxonomy($tax_type));
                $taxonomies[$tax_labels->name] = $terms;
            }
        }

        // 5. ПОЛУЧЕНИЕ БЛОГА
        $blog_posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
    @endphp

    <div class="container mx-auto px-4 py-12">

        {{-- ЗАГОЛОВОК --}}
        <header class="text-center mb-16">
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900 uppercase mb-4 tracking-tight">
                {!! get_field('custom_h1') ?: get_the_title() !!}
            </h1>
            <p class="text-gray-500 text-lg">
                Навигация по всем разделам сайта
            </p>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-12">

            {{-- === КОЛОНКА 1: ОСНОВНОЕ === --}}
            <div class="space-y-12">
                <section>
                    <h2 class="text-2xl font-bold text-gray-800 uppercase mb-6 border-b-2 border-red-600 inline-block pb-1">
                        Основное
                    </h2>
                    <ul class="space-y-2">
                        <li>
                            {{-- Главная страница (Строго корень) --}}
                            <a href="{{ home_url('/') }}" class="text-gray-700 hover:text-red-600 transition font-medium">
                                Главная
                            </a>
                        </li>

                        @foreach ($pages as $page)
                            @php
                                if (in_array($page->post_name, $excluded_slugs)) continue;
                                if ($page->ID == get_option('page_on_front')) continue;
                            @endphp
                            <li>
                                {{-- Обычные страницы (без вложения города) --}}
                                <a href="{{ home_url('/' . $page->post_name . '/') }}"
                                    class="text-gray-700 hover:text-red-600 transition">
                                    {{ $page->post_title }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            </div>

            {{-- === КОЛОНКА 2: ГОРОДА И РАЗДЕЛЫ === --}}
            <div class="space-y-12">
                
                {{-- Список городов --}}
                <section>
                    <h2 class="text-2xl font-bold text-gray-800 uppercase mb-6 border-b-2 border-red-600 inline-block pb-1">
                        Города
                    </h2>
                    <ul class="space-y-2 max-h-[300px] overflow-y-auto custom-scrollbar pr-2 border-b border-gray-100 pb-4">
                        @if (!is_wp_error($cities))
                            @foreach ($cities as $city)
                                @php
                                    // Алматы -> /, Остальные -> /slug/
                                    $link = ($city->slug === 'almaty') ? home_url('/') : home_url('/' . $city->slug . '/');
                                @endphp
                                <li>
                                    <a href="{{ $link }}" class="text-gray-700 hover:text-red-600 transition font-medium">
                                        {{ $city->name }}
                                    </a>
                                </li>
                            @endforeach
                        @endif
                    </ul>
                </section>

                {{-- Разделы по городам --}}
                <section>
                    <h2 class="text-xl font-bold text-gray-800 uppercase mb-6 border-b border-gray-200 pb-2">
                        Разделы по городам
                    </h2>
                    <div class="max-h-[600px] overflow-y-auto custom-scrollbar pr-2 space-y-6">
                        @if (!is_wp_error($cities))
                            @foreach ($cities as $city)
                                @php
                                    // Префикс всегда /slug, даже для Алматы
                                    $city_base = '/' . $city->slug;
                                @endphp
                                <div>
                                    <h3 class="font-bold text-gray-900 uppercase mb-2 text-sm">{{ $city->name }}</h3>
                                    <ul class="space-y-1 pl-2 border-l-2 border-gray-100 text-sm">
                                        <li><a href="{{ home_url($city_base . '/vip/') }}" class="text-gray-600 hover:text-red-600 transition">VIP в {{ $city->name }}</a></li>
                                        <li><a href="{{ home_url($city_base . '/individualki/') }}" class="text-gray-600 hover:text-red-600 transition">Независимые в {{ $city->name }}</a></li>
                                        <li><a href="{{ home_url($city_base . '/online/') }}" class="text-gray-600 hover:text-red-600 transition">Онлайн в {{ $city->name }}</a></li>
                                        <li><a href="{{ home_url($city_base . '/prostitutki-na-vyezd/') }}" class="text-gray-600 hover:text-red-600 transition">Выезд в {{ $city->name }}</a></li>
                                        <li><a href="{{ home_url($city_base . '/prostitutki-priem/') }}" class="text-gray-600 hover:text-red-600 transition">У себя в {{ $city->name }}</a></li>
                                    </ul>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </section>
            </div>

            {{-- === КОЛОНКА 3: УСЛУГИ ПО ГОРОДАМ И БЛОГ === --}}
            <div class="space-y-12">
                
                {{-- Услуги по городам --}}
                @if (isset($taxonomies['Услуги']) && !empty($taxonomies['Услуги']) && !is_wp_error($cities))
                    <section>
                        <h2 class="text-2xl font-bold text-gray-800 uppercase mb-6 border-b-2 border-red-600 inline-block pb-1">
                            Услуги по городам
                        </h2>
                        
                        <div class="max-h-[800px] overflow-y-auto custom-scrollbar pr-2 space-y-8">
                            @foreach ($cities as $city)
                                <div>
                                    <h3 class="font-bold text-gray-900 bg-black uppercase mb-3 border-b border-gray-100 pb-1 sticky top-0 z-10">
                                        {{ $city->name }}
                                    </h3>
                                    <ul class="space-y-1 pl-2 text-sm grid grid-cols-1 sm:grid-cols-2 gap-x-4">
                                        @foreach ($taxonomies['Услуги'] as $term)
                                            @php
                                                // Получаем стандартную ссылку на услугу
                                                $term_link = get_term_link($term);
                                                
                                                if (!is_wp_error($term_link)) {
                                                    // Извлекаем относительный путь (например, /uslugi/anal/)
                                                    $path = str_replace(home_url(), '', $term_link);
                                                    
                                                    // Формируем ссылку: /city-slug/uslugi/anal/
                                                    // Всегда добавляем город, включая Алматы
                                                    $final_link = home_url('/' . $city->slug . $path);
                                                } else {
                                                    $final_link = '#';
                                                }
                                            @endphp
                                            <li>
                                                <a href="{{ $final_link }}"
                                                    class="text-gray-600 hover:text-red-600 transition flex justify-between">
                                                    <span>{{ $term->name }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                {{-- Блог --}}
                @if ($blog_posts)
                    <section>
                        <h2 class="text-xl font-bold text-gray-800 uppercase mb-6 border-b border-gray-200 pb-2">
                            Блог
                        </h2>
                        <ul class="space-y-2">
                            @if ($blogId = get_option('page_for_posts'))
                                <li>
                                    <a href="{{ get_permalink($blogId) }}"
                                        class="text-gray-900 hover:text-red-600 transition font-bold">
                                        Все статьи &rarr;
                                    </a>
                                </li>
                            @endif

                            @foreach (array_slice($blog_posts, 0, 5) as $post)
                                <li>
                                    <a href="{{ get_permalink($post->ID) }}"
                                        class="text-gray-600 hover:text-red-600 transition text-sm">
                                        {{ $post->post_title }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                {{-- Модели (Анкеты) по городам --}}
                <section>
                    <h2 class="text-xl font-bold text-gray-800 uppercase mb-6 border-b border-gray-200 pb-2">
                        Модели
                    </h2>
                    <div class="max-h-[600px] overflow-y-auto custom-scrollbar pr-2 space-y-6">
                        @if (!is_wp_error($cities))
                            @foreach ($cities as $city)
                                @php
                                    // Получаем модели для текущего города (ограничим для производительности)
                                    $models_query = new WP_Query([
                                        'post_type' => 'profile',
                                        'posts_per_page' => 50, // Ограничиваем количество для производительности
                                        'tax_query' => [
                                            [
                                                'taxonomy' => 'city',
                                                'field' => 'term_id',
                                                'terms' => $city->term_id,
                                            ],
                                        ],
                                        'post_status' => 'publish',
                                        'orderby' => 'title',
                                        'order' => 'ASC',
                                    ]);
                                @endphp
                                
                                @if ($models_query->have_posts())
                                    <div>
                                        <h3 class="font-bold text-gray-900 uppercase mb-2 text-sm">
                                            {{ $city->name }} 
                                            @if ($models_query->found_posts > 50)
                                                (показано 50 из {{ $models_query->found_posts }})
                                            @else
                                                ({{ $models_query->found_posts }})
                                            @endif
                                        </h3>
                                        <ul class="space-y-1 pl-2 border-l-2 border-gray-100 text-sm max-h-[200px] overflow-y-auto">
                                            @while ($models_query->have_posts())
                                                @php $models_query->the_post(); @endphp
                                                <li>
                                                    <a href="{{ profile_url(get_the_ID()) }}" 
                                                       class="text-gray-600 hover:text-red-600 transition text-xs block truncate">
                                                        {{ get_the_title() }}
                                                        @php
                                                            $age = get_field('age');
                                                            if ($age) {
                                                                echo ' <span class="text-gray-400">(' . $age . ')</span>';
                                                            }
                                                        @endphp
                                                    </a>
                                                </li>
                                            @endwhile
                                            @php wp_reset_postdata(); @endphp
                                        </ul>
                                    </div>
                                @endif
                            @endforeach
                        @endif
                    </div>
                </section>
            </div>

        </div>

    </div>
@endsection