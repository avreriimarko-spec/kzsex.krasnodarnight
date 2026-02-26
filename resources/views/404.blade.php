@extends('layouts.app')

@section('content')
@php
  // Минималистичные ссылки (можешь поменять пути под свой роутинг)
  $home = home_url('/');
  $sitemap = home_url('/karta-sajta/');
@endphp

<div class="min-h-[70vh] flex items-center justify-center px-4 py-16 bg-black">
  <div class="w-full max-w-xl text-center">
    {{-- 404 --}}
    <div class="mx-auto mb-6 inline-flex items-center justify-center">
      <span class="text-7xl md:text-8xl font-semibold tracking-tight !text-red-600">
        404
      </span>
    </div>

    {{-- Текст --}}
    <h1 class="text-2xl md:text-3xl font-semibold text-black">
      Страница не найдена
    </h1>
    <p class="mt-3 text-base text-gray-400">
      Возможно, ссылка устарела или страница была перемещена.
    </p>

    {{-- Кнопки --}}
    <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
      <a
        href="{{ $home }}"
        class="inline-flex items-center justify-center bg-red-600 px-5 py-3 text-sm font-semibold !text-black hover:bg-red-700 transition"
      >
        На главную
      </a>

      <a
        href="{{ $sitemap }}"
        class="inline-flex items-center justify-center border border-white px-5 py-3 text-sm font-semibold text-black transition"
      >
        Карта сайта
      </a>
    </div>
  </div>
</div>
@endsection
