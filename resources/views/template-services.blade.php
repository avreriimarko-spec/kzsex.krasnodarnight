{{--
  Template Name: Услуги
--}}
@extends('layouts.app')

@section('content')
    @php
        // Получаем услуги в начале шаблона
        $services = get_terms([
            'taxonomy' => 'service',
            'hide_empty' => true,
            'orderby' => 'name',
            'order' => 'ASC'
        ]);
    @endphp

    <div class="container mx-auto px-4 py-8">

        {{-- Header --}}
        <header class="prose mb-10 text-center mx-auto">
            <h1 class="text-3xl md:text-5xl font-bold mb-4 uppercase tracking-tight text-[#cd1d46]">
                {!! get_field('custom_h1') ?: 'Услуги' !!}
            </h1>
            @if ($intro = get_field('intro_text'))
                <p class="leading-relaxed max-w-2xl mx-auto text-gray-300">
                    {{ $intro }}
                </p>
            @endif
        </header>

        {{-- LAYOUT --}}
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 items-start">

            {{-- 2. КОНТЕНТ --}}
            <div class="lg:col-span-4">

                {{-- ВЕРХНЯЯ ПАНЕЛЬ --}}
                <div class="flex flex-wrap items-center justify-between mb-6 border-b border-[#cd1d46] pb-4 gap-4">
                    <h2 class="text-xl md:text-2xl font-bold text-white uppercase tracking-wide">
                        Найдено услуг: {{ $services && !is_wp_error($services) ? count($services) : 0 }}
                    </h2>
                </div>

                {{-- Services Grid --}}
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6">
                    @if($services && !is_wp_error($services))
                        @foreach($services as $service)
                            <a href="{{ get_term_link($service) }}" 
                               class="group block bg-black border border-[#cd1d46] p-4 text-center hover:bg-[#cd1d46] transition-all duration-300 transform hover:scale-105">
                                
                                
                                <h3 class="text-white font-bold text-sm md:text-base uppercase tracking-wide mb-2 group-hover:text-white">
                                    {{ $service->name }}
                                </h3>
                                
                                @if($service->count > 0)
                                    <p class="text-xs text-gray-400 group-hover:text-gray-200">
                                        {{ $service->count }} {{ $service->count == 1 ? 'анкета' : ($service->count < 5 ? 'анкеты' : 'анкет') }}
                                    </p>
                                @endif
                            </a>
                        @endforeach
                    @else
                        <div class="col-span-full text-center py-12">
                            <p class="text-gray-400 text-lg">Услуги не найдены</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>
@endsection
