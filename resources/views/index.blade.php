@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-12">

        {{-- Получаем ID страницы, назначенной как Блог --}}
        @php
            $blogId = get_option('page_for_posts');
        @endphp

        <header class="mb-12 text-center max-w-3xl mx-auto">
            {{-- Заголовок: Если есть кастомный H1, берем его, иначе стандартный --}}
            <h1 class="text-4xl md:text-5xl font-bold text-white uppercase mb-4 tracking-tight">
                {!! get_field('custom_h1', $blogId) ?: get_the_title($blogId) !!} @if (is_paged())
                    <span class="text-gray-400">| Страница {{ get_query_var('paged') ?: get_query_var('page') }}</span>
                @endif
            </h1>

            {{-- Описание: Интро текст из ACF --}}
            @if (!is_paged())
                @if ($intro = get_field('intro_text', $blogId))
                    <p class="text-gray-300 text-lg">
                        {{ $intro }}
                    </p>
                @endif
            @endif
        </header>

        {{-- Вывод категорий (фильтр) --}}
        <div class="flex flex-wrap justify-center gap-4 mb-12">
            <a href="{{ get_permalink($blogId) }}"
                class="px-4 py-2  text-sm font-bold uppercase transition {{ is_home() ? 'bg-[#cd1d46] text-black' : 'bg-[#0f0f0f] text-gray-300 hover:bg-gray-700' }}">
                Все
            </a>
            @foreach (get_categories(['hide_empty' => true]) as $cat)
                <a href="{{ get_category_link($cat->term_id) }}"
                    class="px-4 py-2  bg-[#0f0f0f] text-gray-300 text-sm font-bold uppercase hover:bg-[#cd1d46] hover:text-black transition">
                    {{ $cat->name }}
                </a>
            @endforeach
        </div>

        @if (!have_posts())
            <div class="bg-gray-900 border-l-4 border-[#cd1d46] p-6 rounded text-center">
                <p class="text-gray-300">Статей пока нет.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @while (have_posts())
                    @php
                        the_post();
                    @endphp

                    <article
                        class="bg-gray-900 shadow-sm hover:shadow-xl transition-shadow duration-300 border border-[#cd1d46] overflow-hidden flex flex-col h-full group">
                        {{-- Картинка --}}
                        <a href="{{ get_permalink() }}" class="relative h-56 overflow-hidden block">
                            @if (has_post_thumbnail())
                                <img src="{{ get_the_post_thumbnail_url(null, 'medium_large') }}"
                                    alt="{{ get_the_title() }}"
                                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                                    loading="lazy">
                            @else
                                <div class="w-full h-full bg-[#cd1d46] flex items-center justify-center text-[#cd1d46]">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </div>
                            @endif

                            {{-- Категория (поверх картинки) --}}
                            @php
                                $cats = get_the_category();
                            @endphp
                            @if ($cats)
                                <div
                                    class="absolute top-4 left-4 bg-black/90 backdrop-blur-sm px-3 py-1 text-xs font-bold uppercase text-[#cd1d46]  shadow-sm">
                                    {{ $cats[0]->name }}
                                </div>
                            @endif
                        </a>

                        <div class="p-6 flex flex-col flex-grow">
                            <div class="text-xs text-[#cd1d46] mb-3 flex items-center gap-2">
                                <span class="text-[#cd1d46]">&bull;</span>
                                {{ get_the_date('d.m.Y') }}
                            </div>

                            <h2
                                class="text-xl font-bold text-white mb-3 leading-tight group-hover:text-[#cd1d46] transition-colors">
                                <a href="{{ get_permalink() }}">{!! get_the_title() !!}</a>
                            </h2>

                            <div class="text-[#cd1d46] text-sm line-clamp-3 mb-6 flex-grow">
                                @php
                                    the_excerpt();
                                @endphp
                            </div>

                            <a href="{{ get_permalink() }}"
                                class="inline-flex items-center text-[#cd1d46] font-bold uppercase text-xs tracking-wider hover:underline mt-auto">
                                Читать далее &rarr;
                            </a>
                        </div>
                    </article>
                @endwhile
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
                    'current' => max(1, get_query_var('paged')),
                    'total' => $GLOBALS['wp_query']->max_num_pages,
                    'type' => 'list',
                    'prev_text' => '&larr;',
                    'next_text' => '&rarr;',
                    'mid_size' => 2,
                    'end_size' => 1,
                ]) !!}
            </div>
        @endif

        {{-- SEO Text --}}
        @if (!is_paged())
            @if ($seoText = get_field('main_seo_text', $blogId))
                <div class="mt-16">
                    <article class="prose prose-lg max-w-none bg-gray-900 p-6 md:p-10 border border-gray-700 prose-invert">
                        {!! $seoText !!}
                    </article>
                </div>
            @endif
        @endif
    </div>
@endsection
