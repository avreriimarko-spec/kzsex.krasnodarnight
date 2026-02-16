@extends('layouts.app')

@section('content')
    @php
        // Получаем данные для фильтров как в ProfilesCatalog
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
            'inoutcall'     => 'У себя / Выезд'
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
    @endphp
    <div class="container mx-auto px-4 py-8">

        {{-- Header --}}
        <header class="prose mb-10 text-center max-w-4xl mx-auto">
            @php
                $queried_object = get_queried_object();
                $custom_h1 = '';
                $custom_description = '';
                $seo_title = '';
                $meta_description = '';
                
                if ($queried_object && !is_wp_error($queried_object)) {
                    $custom_h1 = get_field('custom_h1', $queried_object);
                    $custom_description = get_field('description', $queried_object);
                    
                    // Генерируем SEO данные для услуг
                    if ($queried_object->taxonomy === 'service') {
                        $service_name = $queried_object->name;
                        $profile_count = $queried_object->count;
                        
                        // Определяем текущий город
                        $city_name = 'Алматы'; // по умолчанию
                        $city_slug = get_query_var('city');
                        if ($city_slug) {
                            $city_term = get_term_by('slug', $city_slug, 'city');
                            if ($city_term && !is_wp_error($city_term)) {
                                $city_name = $city_term->name;
                            }
                        }
                        
                        // Если есть кастомные поля, используем их
                        if ($custom_h1 || $custom_description) {
                            $seo_title = get_field('seo_title', $queried_object) ?: '';
                            $meta_description = get_field('seo_description', $queried_object) ?: '';
                        }
                        
                        // Если нет кастомных SEO полей, генерируем по шаблону
                        if (empty($seo_title)) {
                            $seo_title = "Проститутки для услуги {$service_name} {$city_name} - {$profile_count} свободных девушек | Kzsex 24/7";
                        }
                        
                        if (empty($meta_description)) {
                            $meta_description = "Заказать шлюху или индивидуалку с услугой {$service_name} в городе {$city_name}. Большой каталог проверенных проституток на любой вкус с фильтрами по районам и внешности.";
                        }
                        
                        if (empty($custom_h1)) {
                            $custom_h1 = "Проститутки с услугой {$service_name} в {$city_name}";
                        }
                    }
                }
                
                // Устанавливаем SEO мета-теги через фильтры
                if (!empty($seo_title)) {
                    add_filter('pre_get_document_title', function() use ($seo_title) {
                        return $seo_title;
                    }, 999);
                    
                    // Fallback для старых версий WordPress
                    add_filter('wp_title', function() use ($seo_title) {
                        return $seo_title;
                    }, 999);
                }
                
                // Meta description будет добавлен основным фильтром в app/filters.php
            @endphp
            
            <h1 class="text-3xl md:text-5xl font-bold uppercase mb-4 tracking-tight text-[#cd1d46]">
                {!! $custom_h1 ?: ($queried_object ? $queried_object->name : get_the_archive_title()) !!} @if (is_paged())
                    <span class="text-[#cd1d46]">| Страница {{ get_query_var('paged') ?: get_query_var('page') }}</span>
                @endif
            </h1>
            @if (!is_paged() && $custom_description)
                <div class="text-gray-300 leading-relaxed max-w-2xl mx-auto">
                    {!! $custom_description !!}
                </div>
            @elseif (!is_paged() && get_the_archive_description())
                <div class="text-gray-300 leading-relaxed max-w-2xl mx-auto">
                    {!! get_the_archive_description() !!}
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
                            class="w-full bg-[#cd1d46] hover:bg-[#b71833] text-white font-bold uppercase py-4  shadow-lg transition-transform active:scale-95 flex items-center justify-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                        Фильтр
                    </button>
                </div>

                {{-- ВЕРХНЯЯ ПАНЕЛЬ --}}
                <div class="flex flex-wrap items-center justify-between mb-6 border-b border-[#cd1d46] pb-4 gap-4">
                    <h2 class="text-xl md:text-2xl font-bold text-white uppercase tracking-wide">
                        Найдено: {{ $GLOBALS['wp_query']->found_posts }}
                    </h2>

                    {{-- ПЕРЕКЛЮЧАТЕЛЬ ВИДА --}}
                    <div class="flex items-center bg-black p-1 border border-[#cd1d46] gap-1">
                        
                        {{-- Кнопка Grid (4 квадратика) --}}
                        <button id="btn-view-grid" type="button" class="p-2 transition-colors text-[#cd1d46] hover:text-white" title="Сетка">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M4 4h7v7H4V4zm11 0h5v7h-5V4zm0 11h5v5h-5v-5zm-11 0h7v5H4v-5z" />
                            </svg>
                        </button>
                        
                        <div class="w-px h-4 bg-[#cd1d46]"></div>

                        {{-- Кнопка List (2 квадратика) --}}
                        <button id="btn-view-list" type="button" class="p-2 transition-colors text-[#cd1d46] hover:text-white" title="По 2 в ряд">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M4 6h7v12H4V6zm9 0h7v12h-7V6z" />
                            </svg>
                        </button>
                    </div>
                </div>

                @if (!have_posts())
                    <div class="bg-black border-l-4 border-[#cd1d46] p-6 rounded text-center">
                        <p class="text-gray-400">Записей в этом разделе пока нет.</p>
                    </div>
                @else
                    
                    {{-- 
                        ВАРИАНТ 1: ОБЫЧНАЯ СЕТКА (4 в ряд)
                        id="view-grid"
                    --}}
                    <ul id="view-grid" class="grid list-none grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6">
                        @while (have_posts())
                            @php
                                the_post();
                            @endphp
                            <li><x-profile-card /></li>
                        @endwhile
                    </ul>

                    @php
                        wp_reset_postdata();
                    @endphp
                    
                    {{-- 
                        ВАРИАНТ 2: КАРТОЧКИ ПО СКРИНШОТУ (2 в ряд)
                        id="view-list"
                    --}}
                    <div id="view-list" class="hidden grid grid-cols-1 lg:grid-cols-2 gap-6">
                        @while (have_posts())
                            @php
                                the_post();
                            @endphp
                            <x-profile-list-card />
                        @endwhile
                        @php
                            wp_reset_postdata();
                        @endphp
                    </div>

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
                            'total' => $GLOBALS['wp_query']->max_num_pages,
                            'type' => 'list',
                            'prev_text' => '&larr;',
                            'next_text' => '&rarr;',
                        ]) !!}
                    </div>
                @endif
            </div>
        </div>

        {{-- Мобильный фильтр --}}
        <div class="lg:hidden">
            <x-catalog-filters :filter-data="$filter_data" />
        </div>

        {{-- SEO Text --}}
        @if (!is_paged() && $seoText = get_field('main_seo_text'))
            <div class="mt-16">
                <article class="prose prose-lg prose-invert max-w-none bg-black p-8 md:p-12  border border-[#cd1d46]">
                    {!! $seoText !!}
                </article>
            </div>
        @endif

    </div>

    {{-- СКРИПТ ПЕРЕКЛЮЧЕНИЯ --}}
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const btnGrid = document.getElementById('btn-view-grid');
        const btnList = document.getElementById('btn-view-list');
        const viewGrid = document.getElementById('view-grid');
        const viewList = document.getElementById('view-list');

        const activeClasses = ['bg-[#cd1d46]', 'text-white', 'shadow-sm'];
        const inactiveClasses = ['text-[#cd1d46]', 'hover:text-white'];

        function setView(mode) {
            if (mode === 'grid') {
                if(viewGrid) viewGrid.classList.remove('hidden');
                if(viewList) viewList.classList.add('hidden');
                
                btnGrid.classList.add(...activeClasses);
                btnGrid.classList.remove(...inactiveClasses);
                btnList.classList.remove(...activeClasses);
                btnList.classList.add(...inactiveClasses);
            } else {
                if(viewGrid) viewGrid.classList.add('hidden');
                if(viewList) viewList.classList.remove('hidden');
                
                btnList.classList.add(...activeClasses);
                btnList.classList.remove(...inactiveClasses);
                btnGrid.classList.remove(...activeClasses);
                btnGrid.classList.add(...inactiveClasses);
            }
            localStorage.setItem('catalogViewMode', mode);
        }

        if (btnGrid && btnList) {
            btnGrid.addEventListener('click', () => setView('grid'));
            btnList.addEventListener('click', () => setView('list'));
        }

        const savedMode = localStorage.getItem('catalogViewMode') || 'grid';
        setView(savedMode);
    });
    </script>
@endsection
