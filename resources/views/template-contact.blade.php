{{--
  Template Name: Страница Контакты
--}}

@extends('layouts.app')

@section('content')
    {{-- ЗАГОЛОВОК (Оставляем темным, чтобы переходило в темные карточки) --}}
    <div class="bg-black text-black pt-16 pb-24">
        <div class="container mx-auto px-4 text-center max-w-4xl">
            {{-- Используем стили заголовка как в услугах --}}
            <h1 class="text-4xl md:text-6xl font-bold capitalize mb-6 tracking-tight text-[#cd1d46]">
                {!! get_field('custom_h1') ?: get_the_title() !!}
            </h1>

            @if ($intro = get_field('intro_text'))
                <p class="text-xl text-gray-300 leading-relaxed max-w-2xl mx-auto">
                    {{ $intro }}
                </p>
            @endif
        </div>
    </div>

    {{-- ОСНОВНОЙ КОНТЕНТ --}}
    <div class="container mx-auto px-4 -mt-16 pb-20 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

            {{-- ЛЕВАЯ КОЛОНКА: Контактная информация --}}
            <div class="lg:col-span-5 space-y-6">

                {{-- Карточка с данными: Фон черный, обводка красная (как в услугах) --}}
                <div class="bg-black rounded-xl p-8 border border-[#cd1d46]">
                    <h2 class="text-2xl font-bold text-black mb-6">Свяжитесь с нами</h2>

                    <div class="space-y-4">
                        @php
                            $contactMaxRaw = trim((string) ($contacts['max'] ?? ''));
                            $contactMaxLink = null;

                            if ($contactMaxRaw !== '') {
                                $contactMaxLink = str_contains($contactMaxRaw, 'http')
                                    ? $contactMaxRaw
                                    : 'https://max.ru/' . ltrim($contactMaxRaw, '/@');
                            }
                        @endphp

                        {{-- Telegram --}}
                        @if ($contacts['tg'])
                            <a href="https://t.me/{{ str_replace('@', '', $contacts['tg']) }}" target="_blank"
                                class="group flex items-center gap-4 p-4  bg-black border rounded-xl border-[#cd1d46] hover:bg-[#cd1d46] transition-all duration-300 transform hover:scale-105">
                                
                                {{-- Иконка: стиль скопирован из услуг (красный круг, белая иконка -> при ховере белый круг, красная иконка) --}}
                                <div class="w-12 h-12 flex-shrink-0 rounded-full bg-[#cd1d46] flex items-center justify-center transition-colors">
                                    <svg class="w-6 h-6 text-black group-hover:text-[#cd1d46]" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12.068 12.068 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z" />
                                    </svg>
                                </div>
                                
                                <div class="overflow-hidden">
                                    <div class="text-xs font-bold capitalize text-gray-400 group-hover:text-gray-200">Telegram</div>
                                    <div class="font-bold text-lg text-black group-hover:text-black truncate">{{ $contacts['tg'] }}</div>
                                </div>
                            </a>
                        @endif

                        {{-- WhatsApp --}}
                        @if ($contacts['wa'])
                            <a href="https://wa.me/{{ $contacts['wa'] }}" target="_blank"
                                class="group flex items-center gap-4 p-4 rounded-xl bg-black border border-[#cd1d46] hover:bg-[#cd1d46] transition-all duration-300 transform hover:scale-105">
                                
                                <div class="w-12 h-12 flex-shrink-0 rounded-full bg-[#cd1d46] flex items-center justify-center transition-colors">
                                    <svg class="w-6 h-6 text-black group-hover:text-[#cd1d46]" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.008-.57-.008-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                                    </svg>
                                </div>
                                
                                <div class="overflow-hidden">
                                    <div class="text-xs font-bold capitalize text-gray-400 group-hover:text-gray-200">WhatsApp</div>
                                    <div class="font-bold text-lg text-black group-hover:text-black truncate">{{ $contacts['wa'] }}</div>
                                </div>
                            </a>
                        @endif

                        {{-- Max --}}
                        @if ($contactMaxLink)
                            <a href="{{ $contactMaxLink }}" target="_blank"
                                class="group flex items-center gap-4 p-4 rounded-xl bg-black border border-[#cd1d46] hover:bg-[#cd1d46] transition-all duration-300 transform hover:scale-105">
                                
                                <div class="w-12 h-12 flex-shrink-0 rounded-full bg-[#cd1d46] flex items-center justify-center transition-colors">
                                    <svg class="w-6 h-6 text-black group-hover:text-[#cd1d46]" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M4 18V6h3.2L12 12.2 16.8 6H20v12h-2.9v-7.3L12 16.8l-5.1-6.1V18z" />
                                    </svg>
                                </div>
                                
                                <div class="overflow-hidden">
                                    <div class="text-xs font-bold capitalize text-gray-400 group-hover:text-gray-200">Max</div>
                                    <div class="font-bold text-lg text-black group-hover:text-black truncate">{{ $contacts['max'] }}</div>
                                </div>
                            </a>
                        @endif

                        {{-- Email --}}
                        @if ($contacts['email'])
                            <a href="mailto:{{ $contacts['email'] }}"
                                class="group flex items-center gap-4 p-4 rounded-xl bg-black border border-[#cd1d46] hover:bg-[#cd1d46] transition-all duration-300 transform hover:scale-105">
                                
                                <div class="w-12 h-12 flex-shrink-0 rounded-full bg-[#cd1d46] flex items-center justify-center transition-colors">
                                    <svg class="w-6 h-6 text-black group-hover:text-[#cd1d46]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                
                                <div class="overflow-hidden">
                                    <div class="text-xs font-bold capitalize text-gray-400 group-hover:text-gray-200">Email</div>
                                    <div class="font-bold text-lg text-black group-hover:text-black truncate">{{ $contacts['email'] }}</div>
                                </div>
                            </a>
                        @endif
                    </div>

                    @if ($contacts['hours'])
                        <div class="mt-8 pt-6 border-t border-gray-800">
                            <div class="text-xs font-bold capitalize text-gray-400 mb-1">Режим работы</div>
                            <div class="font-bold text-black">{{ $contacts['hours'] }}</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ПРАВАЯ КОЛОНКА: Форма обратной связи --}}
            <div class="lg:col-span-7">
                {{-- Стиль контейнера изменен на черный с красной обводкой --}}
                <div class="bg-black  p-8 border rounded-xl border-[#cd1d46] h-full">
                    <h2 class="text-2xl font-bold text-black mb-2">Напишите нам</h2>
                    <p class="text-gray-400 mb-8 text-sm">
                        Оставьте заявку, и наш менеджер свяжется с вами в ближайшее время.
                    </p>

                    @if ($form_shortcode)
                        {{-- Добавляем класс, чтобы инпуты внутри шорткода (если возможно) выглядели нормально на темном фоне --}}
                        <div class="contact-form-style dark-mode-form">
                            {!! do_shortcode($form_shortcode) !!}
                        </div>
                    @else
                        {{-- Наша форма "Напишите нам" --}}
                        <x-contact-form />
                    @endif
                </div>
            </div>

        </div>
    </div>
@endsection
