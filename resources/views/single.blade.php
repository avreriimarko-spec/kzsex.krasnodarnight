@extends('layouts.app')

@section('content')
    @while(have_posts())
        @php(the_post())

        <article @php(post_class('bg-white pb-20 prose'))>

            {{-- Хедер статьи --}}
            <header class="pt-10 pb-8 text-center bg-gray-50 border-b border-gray-100 mb-10">
                <div class="container mx-auto px-4 max-w-4xl">

                    {{-- Метаданные --}}
                    <div
                        class="flex items-center justify-center gap-4 text-xs font-bold text-gray-500 capitalize tracking-widest mb-4">
                        @php($cats = get_the_category())
                        @if ($cats)
                            <a href="{{ get_category_link($cats[0]->term_id) }}" class="text-red-600 hover:text-red-700">
                                {{ $cats[0]->name }}
                            </a>
                            <span>&bull;</span>
                        @endif
                        <time datetime="{{ get_post_time('c', true) }}">{{ get_the_date() }}</time>
                    </div>

                    <h1 class="text-3xl md:text-5xl font-bold text-gray-900 leading-tight mb-6">
                        {!! get_the_title() !!}
                    </h1>
                </div>
            </header>

            {{-- Контент --}}
            <div class="container mx-auto px-4">
                <div class="max-w-4xl mx-auto">

                    {{-- Главное фото --}}
                    @if (has_post_thumbnail())
                        <figure class="mb-10 shadow-lg  overflow-hidden">
                            <img src="{{ get_the_post_thumbnail_url(null, 'large') }}" alt="{{ get_the_title() }}"
                                class="w-full h-auto object-cover">
                        </figure>
                    @endif

                    {{-- Текст статьи (используем наш .prose класс из app.css) --}}
                    <div class="prose prose-lg prose-red max-w-none mx-auto">
                        @php(the_content())
                    </div>

                    {{-- Теги --}}
                    @if (has_tag())
                        <div class="mt-12 pt-8 border-t border-gray-100">
                            <div class="flex flex-wrap gap-2">
                                @foreach (get_the_tags() as $tag)
                                    <a href="{{ get_tag_link($tag->term_id) }}"
                                        class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-3 py-1 rounded text-sm transition">
                                        #{{ $tag->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>
            </div>

        </article>

        {{-- Навигация (Предыдущий / Следующий пост) --}}
        <div class="bg-gray-50 border-t border-gray-200 py-12">
            <div class="container mx-auto px-4 max-w-4xl">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                    <div class="text-left">
                        @php($prev = get_previous_post())
                        @if ($prev)
                            <div class="text-xs text-gray-400 capitalize font-bold mb-1">Предыдущая статья</div>
                            <a href="{{ get_permalink($prev->ID) }}"
                                class="text-lg font-bold text-gray-800 hover:text-red-600 transition">
                                &larr; {{ $prev->post_title }}
                            </a>
                        @endif
                    </div>

                    <div class="text-right">
                        @php($next = get_next_post())
                        @if ($next)
                            <div class="text-xs text-gray-400 capitalize font-bold mb-1">Следующая статья</div>
                            <a href="{{ get_permalink($next->ID) }}"
                                class="text-lg font-bold text-gray-800 hover:text-red-600 transition">
                                {{ $next->post_title }} &rarr;
                            </a>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    @endwhile
@endsection
