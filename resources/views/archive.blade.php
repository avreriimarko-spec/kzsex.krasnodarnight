@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-12">

        <header class="mb-12 text-center max-w-3xl mx-auto">
            <div class="text-red-600 font-bold capitalize tracking-widest text-sm mb-2">
                @if (is_category())
                    Рубрика
                @elseif(is_tag())
                    Тег
                @else
                    Архив
                @endif
            </div>
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900 capitalize mb-4 tracking-tight">
                {!! preg_replace('/^[\w\s]+:\s/iu', '', subject: get_the_archive_title()) !!} @if (is_paged())
                    <span class="text-gray-400">| Страница {{ get_query_var('paged') ?: get_query_var('page') }}</span>
                @endif
            </h1>
            @if (!is_paged())
                @if (get_the_archive_description())
                    <div class="text-gray-600 text-lg">
                        {!! get_the_archive_description() !!}
                    </div>
                @endif
            @endif
        </header>

        @if (!have_posts())
            <div class="bg-gray-50 border-l-4 border-red-500 p-6 rounded text-center">
                <p class="text-gray-600">Записей в этом разделе пока нет.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @while (have_posts())
                    @php(the_post())

                    {{-- Карточка поста (такая же как в index) --}}
                    <article
                        class="bg-white shadow-sm hover:shadow-xl transition-shadow duration-300 border border-gray-100 overflow-hidden flex flex-col h-full group">
                        <a href="{{ get_permalink() }}" class="relative h-56 overflow-hidden block">
                            @if (has_post_thumbnail())
                                <img src="{{ get_the_post_thumbnail_url(null, 'medium_large') }}"
                                    alt="{{ get_the_title() }}"
                                    class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                                    loading="lazy">
                            @else
                                <div class="w-full h-full bg-gray-100 flex items-center justify-center text-gray-400">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </div>
                            @endif
                        </a>
                        <div class="p-6 flex flex-col flex-grow">
                            <div class="text-xs text-gray-400 mb-3">{{ get_the_date('d.m.Y') }}</div>
                            <h2
                                class="text-xl font-bold text-gray-900 mb-3 leading-tight group-hover:text-red-600 transition-colors">
                                <a href="{{ get_permalink() }}">{!! get_the_title() !!}</a>
                            </h2>
                            <div class="text-gray-600 text-sm line-clamp-3 mb-6 flex-grow">
                                @php(the_excerpt())
                            </div>
                            <a href="{{ get_permalink() }}"
                                class="inline-flex items-center text-red-600 font-bold capitalize text-xs tracking-wider hover:underline mt-auto">Читать
                                далее &rarr;</a>
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
    </div>
@endsection
