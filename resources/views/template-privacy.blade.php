{{--
  Template Name: Текстовая страница (Политика)
--}}

@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-12 max-w-4xl">

        {{-- Основной контейнер: Черный фон, красная рамка --}}
        <article class="bg-black p-6 md:p-12  border border-[#cd1d46]">
            
            {{-- Хедер страницы --}}
            <header class="mb-8 border-b border-[#cd1d46]/30 pb-8">
                <h1 class="text-3xl md:text-5xl font-bold text-[#cd1d46] uppercase tracking-tight mb-4">
                    {!! get_field('custom_h1') ?: get_the_title() !!}
                </h1>

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
        </article>

    </div>
@endsection