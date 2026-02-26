{{--
  Template Name: Правила добавления анкет
--}}
@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-12 max-w-4xl">

        {{-- Основной контейнер: Черный фон, красная рамка --}}
        <article class="bg-black p-6 md:p-12 border rounded-xl border-[#cd1d46]">
            
            {{-- Хедер страницы --}}
            <header class="mb-8 border-b border-[#cd1d46]/30 pb-8">
                <h1 class="text-3xl md:text-5xl font-bold text-[#cd1d46] capitalize tracking-tight mb-4">
                    {!! get_field('custom_h1') ?: get_the_title() !!}
                </h1>

                @if ($intro_text = get_field('intro_text'))
                    <div class="text-gray-300 text-lg leading-relaxed">
                        {!! $intro_text !!}
                    </div>
                @endif


            </header>

            {{-- Контент --}}
            {{-- 
                prose-invert - инвертирует стандартные цвета Tailwind Typography для темного фона.
                prose-a:text-[#cd1d46] - красит ссылки в брендовый красный.
                prose-headings:text-black - заголовки внутри текста белые.
            --}}
            <div class="prose prose-lg prose-invert max-w-none text-gray-300 prose-headings:text-black prose-a:text-[#cd1d46] hover:prose-a:text-black prose-strong:text-black prose-blockquote:border-[#cd1d46]">
                @if ($main_seo_text = get_field('main_seo_text'))
                    {!! $main_seo_text !!}
                @else
                    @php(the_content())
                @endif
            </div>
                            <div class="text-sm text-right text-gray-400 mt-4">
                    Последнее обновление: <time
                        datetime="{{ get_the_modified_date('c') }}">{{ get_the_modified_date('d.m.Y') }}</time>
                </div>
        </article>

    </div>
@endsection
