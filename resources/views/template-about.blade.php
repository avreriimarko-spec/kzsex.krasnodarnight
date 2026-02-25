{{--
  Template Name: Страница "О нас"
--}}

@extends('layouts.app')

@section('content')

    {{-- СЕКЦИЯ 1: ЗАГОЛОВОК --}}
    <div class="container prose mx-auto px-4 pt-12 pb-8 text-center max-w-4xl">
        <h1 class="text-4xl md:text-6xl font-bold text-gray-900 capitalize mb-6 tracking-tight">
            {!! get_field('custom_h1') ?: get_the_title() !!}
        </h1>

        @if ($intro = get_field('intro_text'))
            <div class="text-xl text-gray-600 leading-relaxed max-w-2xl mx-auto">
                {!! $intro !!}
            </div>
        @endif
    </div>

    {{-- СЕКЦИЯ 2: ОСНОВНОЙ КОНТЕНТ --}}
    <div class="container mx-auto px-4 mb-16">
        <div class="bg-black border rounded-xl border-[#cd1d46] overflow-hidden">
            <div class="grid grid-cols-1 lg:grid-cols-2">

                {{-- Текст (слева) --}}
                @if ($seoText = get_field('main_seo_text'))
                    <div class="p-8 md:p-12 flex flex-col justify-center">
                        <article class="prose prose-lg prose-red max-w-none text-gray-700">
                            {!! $seoText !!}
                        </article>
                    </div>
                @endif

                {{-- Картинка (справа) --}}
                @if (has_post_thumbnail())
                    <div class="relative h-64 lg:h-auto">
                        <img src="{{ get_the_post_thumbnail_url(null, 'large') }}"
                            class="absolute inset-0 w-full h-full object-cover" alt="About Us">
                        {{-- Градиент поверх фото --}}
                        <div class="absolute inset-0 bg-gradient-to-r from-black/10 to-transparent"></div>
                    </div>
                @else
                    {{-- Логотип если нет фото у страницы --}}
                    <div class="bg-black flex items-center justify-center min-h-[300px]">
                        @php
                            $logo = get_field('schema_logo', 'option') ?: asset('resources/images/logo.png');
                        @endphp
                        <img src="{{ $logo }}" alt="{{ get_bloginfo('name') }}" class="h-24 w-auto opacity-80">
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- СЕКЦИЯ 3: СТАТИСТИКА (Темная полоса) --}}
    @if ($stats)
        <div class="bg-black text-black py-16 mb-16">
            <div class="container mx-auto px-4">
                <div class="grid md:grid-cols-4 gap-8 text-center divide-x divide-gray-800">
                    @foreach ($stats as $stat)
                        <div class="px-4">
                            <div class="text-4xl md:text-5xl font-bold text-red-500 mb-2">
                                {{ $stat['number'] }}
                            </div>
                            <div class="text-sm md:text-base text-gray-400 capitalize tracking-widest font-bold">
                                {{ $stat['label'] }}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- СЕКЦИЯ 4: ПРЕИМУЩЕСТВА (Сетка) --}}
    @if ($features)
        <div class="container mx-auto px-4 pb-20">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold capitalize text-black">Почему выбирают нас</h2>
                <div class="w-20 h-1 bg-[#cd1d46] mx-auto mt-4 rounded"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($features as $item)
                    <div
                        class="bg-black p-8 border rounded-xl border-[#cd1d46] shadow-sm hover:shadow-lg transition-shadow duration-300">
                        {{-- Иконка (галочка в круге) --}}
                        <div class="w-12 h-12 bg-[#cd1d46]/20 text-[#cd1d46] rounded-xl flex items-center justify-center mb-6">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>

                        <h3 class="text-xl font-bold text-black mb-3">
                            {{ $item['title'] }}
                        </h3>
                        <p class="text-gray-300 leading-relaxed">
                            {{ $item['description'] }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- СЕКЦИЯ 5: CTA (Призыв к действию) --}}
    <div class="bg-black py-16 border-t rounded-xl border-[#cd1d46]">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold mb-6 text-black">Готовы найти компанию?</h2>
            <p class="text-gray-300 mb-8 max-w-xl mx-auto">
                В нашем каталоге представлены только проверенные анкеты. Выберите девушку прямо сейчас.
            </p>
            <a href="{{ home_url('/') }}"
                class="inline-block bg-[#cd1d46] rounded-xl !text-black font-bold capitalize px-8 py-4 shadow-lg hover:bg-[#b71833] transition transform hover:-translate-y-1">
                Перейти в каталог
            </a>
        </div>
    </div>

@endsection
