{{--
  Template Name: Страница Работа
--}}

@extends('layouts.app')

@section('content')

    {{-- 1. HERO SECTION (Темный фон) --}}
    <div class="bg-gray-900 text-black pt-20 pb-24 relative overflow-hidden">
        {{-- Декоративный элемент --}}
        <div
            class="absolute top-0 right-0 w-64 h-64 bg-red-600  mix-blend-multiply filter blur-3xl opacity-20 animate-blob">
        </div>

        <div class="container mx-auto px-4 text-center max-w-4xl relative z-10">
            <span
                class="inline-block py-1 px-3 rounded bg-red-600 text-black text-xs font-bold uppercase mb-4 tracking-widest">
                Вакансии
            </span>
            <h1 class="text-4xl md:text-6xl font-bold uppercase mb-6 tracking-tight leading-tight">
                {!! get_field('custom_h1') ?: get_the_title() !!}
            </h1>

            @if ($intro = get_field('intro_text'))
                <p class="text-xl text-gray-300 leading-relaxed max-w-2xl mx-auto">
                    {{ $intro }}
                </p>
            @endif

            <div class="mt-8">
                <a href="#apply-form"
                    class="inline-block bg-white text-gray-900 hover:bg-gray-100 font-bold uppercase py-4 px-10  shadow-lg transition transform hover:-translate-y-1">
                    Заполнить анкету
                </a>
            </div>
        </div>
    </div>

    {{-- 2. ПРЕИМУЩЕСТВА (Сетка карточек) --}}
    @if ($benefits)
        <div class="bg-gray-50 py-20">
            <div class="container mx-auto px-4">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-gray-900 uppercase">Почему выбирают нас</h2>
                    <div class="w-16 h-1 bg-red-600 mx-auto mt-4"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach ($benefits as $item)
                        <div
                            class="bg-white p-8  shadow-sm border border-gray-100 hover:shadow-xl hover:border-red-100 transition duration-300 group">
                            <div
                                class="w-12 h-12 bg-red-50 text-red-600  flex items-center justify-center mb-6 group-hover:bg-red-600 group-hover:text-black transition-colors">
                                {{-- Иконка галочки --}}
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 mb-3">{{ $item['title'] }}</h3>
                            <p class="text-gray-600 leading-relaxed">{{ $item['description'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- 3. ТРЕБОВАНИЯ И УСЛОВИЯ --}}
    @if ($requirements)
        <div class="py-20 bg-white">
            <div class="container mx-auto px-4 max-w-5xl">
                <div class="bg-gray-900 p-8 md:p-12 shadow-2xl relative overflow-hidden">
                    {{-- Декор --}}
                    <div
                        class="absolute top-0 right-0 w-full h-full bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10">
                    </div>

                    <div class="relative z-10 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                        <div>
                            <h2 class="text-3xl font-bold text-black uppercase mb-6">
                                Кого мы ищем?
                            </h2>
                            <p class="text-gray-400 mb-8">
                                Мы ценим честность, пунктуальность и желание зарабатывать. Если вы соответствуете этим
                                критериям, мы ждем вас в нашей команде.
                            </p>

                            <ul class="space-y-4">
                                @foreach ($requirements as $req)
                                    <li class="flex items-start text-black">
                                        <span
                                            class="flex-shrink-0 w-6 h-6 bg-green-500  flex items-center justify-center mr-4 mt-0.5">
                                            <svg class="w-3 h-3 text-black font-bold" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        </span>
                                        <span class="text-lg">{{ $req['text'] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        {{-- Блок CTA внутри условий --}}
                        <div class="bg-white/10 backdrop-blur-sm p-8 border border-white/10 text-center">
                            <div class="text-4xl font-bold text-red-500 mb-2">100%</div>
                            <div class="text-black font-bold uppercase mb-6">Конфиденциальность</div>
                            <div class="text-4xl font-bold text-red-500 mb-2">24/7</div>
                            <div class="text-black font-bold uppercase">Поддержка</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- 4. ФОРМА ЗАЯВКИ --}}
    <div id="apply-form" class="bg-gray-50 py-20">
        <div class="container mx-auto px-4 max-w-3xl">
            <div class="text-center mb-10">
                <h2 class="text-3xl font-bold text-gray-900 uppercase">Отправить анкету</h2>
                <p class="text-gray-600 mt-4">Заполните форму ниже, и наш менеджер свяжется с вами в ближайшее время для
                    обсуждения деталей.</p>
            </div>

            <div class="bg-white p-8 md:p-10 shadow-lg border border-gray-200">
                @if ($form_shortcode)
                    <div class="work-form-style">
                        {!! do_shortcode($form_shortcode) !!}
                    </div>
                @else
                    <div class="text-center py-10 bg-gray-50 rounded border border-dashed border-gray-300 text-gray-400">
                        Форма заявки не настроена.
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
