{{--
  Template Name: Страница FAQ
--}}

@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-16 max-w-4xl">

        {{-- Заголовок --}}
        <header class="text-center mb-16 prose mx-auto">
            <h1 class="text-3xl md:text-5xl font-bold uppercase mb-4 tracking-tight">
                {!! get_field('custom_h1') ?: get_the_title() !!}
            </h1>

            @if ($intro = get_field('intro_text'))
                <p class="text-gray-600 text-lg max-w-2xl mx-auto leading-relaxed">
                    {{ $intro }}
                </p>
            @endif
        </header>

        {{-- Список вопросов --}}
        @if ($faq_items)
            <div class="space-y-4">
                @foreach ($faq_items as $item)
                    {{-- Стилизация под карточки услуг: черный фон, красная рамка --}}
                    <div
                        class="bg-black  border border-[#cd1d46] shadow-sm hover:shadow-[0_4px_20px_rgba(205,29,70,0.15)] transition-shadow duration-300 overflow-hidden">
                        <details class="group">
                            {{-- Вопрос --}}
                            <summary
                                class="flex justify-between items-center font-bold cursor-pointer list-none p-6 text-black hover:text-[#cd1d46] transition-colors select-none">
                                <span class="text-lg pr-4">{{ $item['question'] }}</span>
                                
                                {{-- Иконка с анимацией (цвет #cd1d46) --}}
                                <span class="transform transition-transform duration-300 group-open:rotate-180 text-[#cd1d46] flex-shrink-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </span>
                            </summary>

                            {{-- Ответ --}}
                            <div class="px-6 pb-6 text-gray-400 leading-relaxed animate-fade-in-down">
                                {{-- Разделитель внутри карточки делаем темным или полупрозрачным красным --}}
                                <div class="pt-4 border-t border-[#cd1d46]/30 prose prose-invert max-w-none prose-p:text-gray-400 prose-a:text-[#cd1d46]">
                                    {!! $item['answer'] !!}
                                </div>
                            </div>
                        </details>
                    </div>
                @endforeach
            </div>
        @else
            {{-- Пустое состояние --}}
            <div class="text-center text-gray-400 py-20 bg-black  border border-[#cd1d46]">
                Вопросы еще не добавлены.
            </div>
        @endif

        {{-- Дополнительный контент --}}
        @if (get_the_content())
            {{-- prose-invert инвертирует цвета типографики для темного фона --}}
            <div class="mt-16 prose prose-lg prose-invert max-w-none bg-black p-8 md:p-12  border border-[#cd1d46]">
                @php(the_content())
            </div>
        @endif
    </div>
@endsection