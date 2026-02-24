{{--
  Template Name: Все отзывы
--}}
@extends('layouts.app')

@section('content')
<main class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        
        {{-- Заголовок --}}
        <header class="text-center mb-10">
            <h1 class="text-3xl md:text-5xl font-bold capitalize mb-4 tracking-tight text-[#cd1d46]">
                Все отзывы моделей
            </h1>
            <p class="text-gray-400 text-lg">
                Читайте реальные отзывы о наших моделях
            </p>
        </header>
        
        @if(empty($allReviews))
            <div class="text-center py-12 bg-black border border-[#cd1d46]">
                <p class="text-gray-400 text-lg">Пока нет ни одного отзыва</p>
            </div>
        @else
            <div class="space-y-6">
                @foreach($allReviews as $review)
                    <div class="bg-black p-6 border border-[#cd1d46] hover:shadow-[0_4px_20px_rgba(205,29,70,0.15)] transition-shadow duration-300">
                        <div class="flex items-start space-x-4">
                            
                            {{-- Аватар --}}
                            @if(!empty($review['profile_photo']))
                                <img src="{{ $review['profile_photo'] }}" alt="{{ $review['profile_title'] }}" 
                                     class="w-16 h-16  object-cover flex-shrink-0 border-2 border-[#cd1d46]">
                            @else
                                <div class="w-16 h-16  bg-[#cd1d46] flex-shrink-0 flex items-center justify-center border-2 border-[#cd1d46]">
                                    <span class="text-black text-xl font-bold">?</span>
                                </div>
                            @endif
                            
                            <div class="flex-1">
                                <div class="flex flex-col md:flex-row md:items-center justify-between mb-4">
                                    <div class="mb-4 md:mb-0">
                                        <h3 class="font-bold text-lg capitalize tracking-wide">
                                            <a href="{{ $review['profile_url'] }}" class="text-black hover:text-[#cd1d46] transition-colors">
                                                {{ $review['profile_title'] }}
                                            </a>
                                        </h3>
                                        <p class="text-xs text-gray-500 mt-1">
                                            Отзыв от: <span class="text-gray-300">{{ $review['author'] }}</span>
                                            @if(!empty($review['date_formatted']))
                                                <span class="mx-2">|</span> {{ $review['date_formatted'] }}
                                            @endif
                                        </p>
                                    </div>
                                    
                                    {{-- РЕЙТИНГ (Исправленная логика) --}}
                                    @if(isset($review['rating']) && $review['rating'] > 0)
                                        <div class="inline-flex items-center bg-[#111827] px-4 py-2  border border-gray-800">
                                            <div class="flex items-center space-x-1 mr-3">
                                                @php 
                                                    $rawRating = floatval($review['rating']);
                                                    
                                                    // Если рейтинг <= 1 (например, 0.96), значит это коэффициент. Умножаем на 5.
                                                    // Если вдруг придет нормальный рейтинг (4.5), оставляем как есть.
                                                    $finalRating = ($rawRating <= 1) ? $rawRating * 5 : $rawRating;
                                                    
                                                    // Для подсветки звезд округляем (4.8 -> 5 звезд)
                                                    $starsCount = round($finalRating);
                                                @endphp

                                                @for($i = 1; $i <= 5; $i++)
                                                    <svg class="w-5 h-5 {{ $i <= $starsCount ? 'text-[#cd1d46]' : 'text-gray-700' }} fill-current" viewBox="0 0 20 20">
                                                        <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                                    </svg>
                                                @endfor
                                            </div>
                                            {{-- Выводим число, например "4.8/5" --}}
                                            <span class="text-black font-bold text-sm leading-none pt-0.5">
                                                {{ number_format($finalRating, 1) }}/5
                                            </span>
                                        </div>
                                    @endif
                                </div>
                                
                                {{-- Текст отзыва (HTML) --}}
                                @if(!empty($review['content']))
                                    <div class="prose prose-sm prose-invert max-w-none text-gray-300 leading-relaxed">
                                        {!! $review['content'] !!}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <div class="mt-8 text-center border-t border-gray-800 pt-6">
                <p class="text-sm text-gray-500">
                    Всего отзывов: <span class="text-black font-bold">{{ count($allReviews) }}</span>
                </p>
            </div>
        @endif
    </div>
</main>
@endsection