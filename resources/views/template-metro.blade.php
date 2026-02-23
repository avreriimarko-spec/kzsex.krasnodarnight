{{--
  Template Name: Метро
--}}
@extends('layouts.app')

@section('content')
    @include('partials.taxonomy-list', [
        'taxonomy' => 'metro',
        'default_title' => 'Метро',
        'found_label' => 'Найдено метро',
        'not_found_text' => 'Метро не найдены'
    ])
@endsection