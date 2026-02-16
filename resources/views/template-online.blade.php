{{--
  Template Name: Онлайн модели
--}}
@extends('layouts.app')

@section('content')
    @php
        // Получаем текущий город из URL
        $city_slug = get_query_var('city');
        $current_city = get_term_by('slug', $city_slug, 'city');
        
        // Если город не определен в URL, используем Алматы как дефолтный
        if (!$current_city) {
            $current_city = get_term_by('slug', 'almaty', 'city');
        }
        
        $city_name = $current_city ? $current_city->name : 'Город';
        
        // Получаем SEO данные из репитера city_pages_seo для онлайн страницы
        $seo_title = '';
        $meta_description = '';
        $page_h1 = '';
        $page_intro = '';
        $main_seo_text = '';
        
        if ($current_city) {
            // Ищем данные для онлайн страницы в репитере city_pages_seo на странице онлайн
            $city_pages_seo = get_field('city_pages_seo');
            
            if ($city_pages_seo && is_array($city_pages_seo)) {
                foreach ($city_pages_seo as $page_data) {
                    if (isset($page_data['city']) && is_object($page_data['city']) && $page_data['city']->term_id == $current_city->term_id) {
                        $seo_title = $page_data['seo_title'] ?? '';
                        $meta_description = $page_data['meta_description'] ?? '';
                        $page_h1 = $page_data['h1'] ?? '';
                        $page_intro = $page_data['description'] ?? '';
                        $main_seo_text = $page_data['main_text'] ?? '';
                        break;
                    }
                }
            }
        }
        
        // Устанавливаем значения по умолчанию если не найдены
        $page_h1 = $page_h1 ?: 'Онлайн модели';
        // Описание под H1 выводим только если заполнено
        
        // Устанавливаем SEO метаданные
        if ($seo_title) {
            add_filter('pre_get_document_title', function() use ($seo_title) { return $seo_title; }, 999);
        }
        
        // Meta description будет добавлен основным фильтром в app/filters.php
        
        // Получаем только онлайн модели
        $args = [
            'post_type'      => 'profile',
            'post_status'    => 'publish',
            'posts_per_page' => 24,
            'paged'          => get_query_var('paged') ?: get_query_var('page') ?: 1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => [
                [
                    'key'     => 'online',
                    'value'   => '1',
                    'compare' => '=',
                ],
            ],
            'tax_query'      => [
                [
                    'taxonomy' => 'city',
                    'field'    => 'slug',
                    'terms'    => [$current_city->slug],
                    'operator' => 'IN',
                ],
            ],
        ];
        
        $online_profiles = new WP_Query($args);
        
        // Определяем является ли первая карточка LCP
        $isLcp = !is_paged() && empty($_GET);
    @endphp

    <section class="min-h-screen">
        <div class="container mx-auto px-4 py-8">
            
            {{-- Заголовок страницы --}}
            <header class="text-center mb-12">
                <h1 class="font-serif text-4xl md:text-6xl text-[#DFC187] uppercase tracking-widest mb-4 drop-shadow-sm">
                    {!! $page_h1 !!}
                </h1>
                @if (!empty($page_intro))
                    <div class="max-w-3xl mx-auto">
                        <div class="text-gray-300 text-lg md:text-xl leading-relaxed">
                            {!! $page_intro !!}
                        </div>
                    </div>
                @endif
            </header>

            {{-- Количество онлайн моделей --}}
            <div class="text-center mb-8">
                <div class="inline-flex items-center gap-2 bg-[#0f0f0f] border border-[#cd1d46] px-6 py-3">
                    <span class="w-3 h-3 bg-green-500  shadow-[0_0_8px_rgba(34,197,94,0.6)]"></span>
                    <span class="text-white font-bold">
                        {{ $online_profiles->found_posts }} моделей онлайн
                    </span>
                </div>
            </div>

            @if($online_profiles->have_posts())
                {{-- Верхняя панель с количеством и переключателем --}}
                <div class="flex flex-wrap items-center justify-between mb-6 border-b border-[#cd1d46] pb-4 gap-4">
                    <h2 class="text-xl md:text-2xl font-bold text-white uppercase tracking-wide">
                        Найдено анкет: {{ $online_profiles->found_posts }}
    </h2>

                    {{-- ПЕРЕКЛЮЧАТЕЛЬ ВИДА --}}
                    <div class="flex items-center bg-black p-1 border border-[#cd1d46] gap-1">
                        
                        {{-- Кнопка Grid (4 квадратика) --}}
                        <button id="btn-view-grid" type="button" class="p-2 transition-colors text-[#cd1d46] hover:text-white" title="Сетка">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M4 4h7v7H4V4zm11 0h5v7h-5V4zm0 11h5v5h-5v-5zm-11 0h7v5H4v-5z" /> {{-- Имитация 4 блоков --}}
                            </svg>
                        </button>
                        
                        <div class="w-px h-4 bg-[#cd1d46]"></div>

                        {{-- Кнопка List (2 квадратика) --}}
                        <button id="btn-view-list" type="button" class="p-2 transition-colors text-[#cd1d46] hover:text-white" title="По 2 в ряд">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M4 6h7v12H4V6zm9 0h7v12h-7V6z" /> {{-- Имитация 2 блоков --}}
                            </svg>
                        </button>
                    </div>
                </div>
                
                {{-- 
                    ВАРИАНТ 1: ОБЫЧНАЯ СЕТКА (4 в ряд)
                    id="view-grid"
                --}}
                <ul id="view-grid" class="grid list-none grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6">
                    @while($online_profiles->have_posts())
                        @php
                            $online_profiles->the_post();
                            $isLcp = $online_profiles->current_post === 0 && !is_paged();
                        @endphp
                        <li><x-profile-card :lcp="$isLcp" /></li>
                    @endwhile
                </ul>

                @php $online_profiles->rewind_posts(); @endphp

                {{-- 
                    ВАРИАНТ 2: КАРТОЧКИ ПО СКРИНШОТУ (2 в ряд)
                    id="view-list"
                --}}
                <div id="view-list" class="hidden grid grid-cols-1 lg:grid-cols-2 gap-6">
                    @while($online_profiles->have_posts())
                        @php
                            $online_profiles->the_post();
                            $isLcp = $online_profiles->current_post === 0 && !is_paged();
                        @endphp
                        <x-profile-list-card :lcp="$isLcp" />
                    @endwhile
                </div>

                @php
                    wp_reset_postdata();
                @endphp

                {{-- Пагинация --}}
                @if($online_profiles->max_num_pages > 1)
                    <div class="flex justify-center mt-12">
                        <nav class="flex items-center space-x-2">
                            {{-- Кнопка "Назад" --}}
                            @if(get_previous_posts_link())
                                <div class="page-numbers">
                                    {{ get_previous_posts_link('←') }}
                                </div>
                            @endif

                            {{-- Номера страниц --}}
                            @php
                                $current_page = max(1, get_query_var('paged'));
                                $total_pages = $online_profiles->max_num_pages;
                                
                                // Показываем страницы вокруг текущей
                                $start_page = max(1, $current_page - 2);
                                $end_page = min($total_pages, $current_page + 2);
                                
                                // Всегда показываем первую страницу
                                if ($start_page > 1) {
                                    echo '<div class="page-numbers"><a href="' . get_pagenum_link(1) . '">1</a></div>';
                                    if ($start_page > 2) {
                                        echo '<span class="px-3 py-2 text-gray-500">...</span>';
                                    }
                                }
                                
                                // Основной диапазон страниц
                                for ($i = $start_page; $i <= $end_page; $i++) {
                                    if ($i == $current_page) {
                                        echo '<div class="page-numbers"><span class="bg-[#cd1d46] text-white border-[#cd1d46]">' . $i . '</span></div>';
                                    } else {
                                        echo '<div class="page-numbers"><a href="' . get_pagenum_link($i) . '">' . $i . '</a></div>';
                                    }
                                }
                                
                                // Всегда показываем последнюю страницу
                                if ($end_page < $total_pages) {
                                    if ($end_page < $total_pages - 1) {
                                        echo '<span class="px-3 py-2 text-gray-500">...</span>';
                                    }
                                    echo '<div class="page-numbers"><a href="' . get_pagenum_link($total_pages) . '">' . $total_pages . '</a></div>';
                                }
                            @endphp

                            {{-- Кнопка "Вперед" --}}
                            @if(get_next_posts_link())
                                <div class="page-numbers">
                                    {{ get_next_posts_link('→') }}
                                </div>
                            @endif
                        </nav>
                    </div>
                @endif

            @else
                {{-- Нет онлайн моделей --}}
                <div class="text-center py-20">
                    <div class="max-w-md mx-auto">
                        <div class="w-20 h-20 bg-gray-800  flex items-center justify-center mx-auto mb-6">
                            <svg class="w-10 h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-white mb-4">Сейчас нет онлайн моделей</h3>
                        <p class="text-gray-400 mb-8">
                            В данный момент ни одна модель не доступна для общения в {{ $city_name }}. 
                            Попробуйте зайти позже или посмотрите всех моделей.
                        </p>
                        <a href="{{ get_home_url() }}" class="inline-flex items-center gap-2 bg-[#cd1d46] text-white hover:!text-white px-6 py-3  font-bold hover:bg-[#b01530] transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            Все анкеты
                        </a>
                    </div>
                </div>
            @endif

            {{-- SEO Text --}}
            @if (!is_paged() && $main_seo_text)
                <div class="mt-16">
                    <article class="prose prose-lg prose-invert max-w-none bg-black p-8 md:p-12 border border-[#cd1d46]">
                        {!! $main_seo_text !!}
                    </article>
                </div>
            @endif

        </div>
    </section>
@endsection

{{-- СКРИПТ ПЕРЕКЛЮЧЕНИЯ ВИДА КАРТОЧЕК --}}
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
        localStorage.setItem('onlineViewMode', mode);
    }

    if (btnGrid && btnList) {
        btnGrid.addEventListener('click', () => setView('grid'));
        btnList.addEventListener('click', () => setView('list'));
    }

    const savedMode = localStorage.getItem('onlineViewMode') || 'grid';
    setView(savedMode);
});
</script>
