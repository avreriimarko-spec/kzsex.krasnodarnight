{{--
  Template Name: Услуги
--}}
@extends('layouts.app')

@section('content')
    @include('partials.taxonomy-list', [
        'taxonomy' => 'service',
        'default_title' => 'Услуги',
        'found_label' => 'Найдено услуг',
        'not_found_text' => 'Услуги не найдены'
    ])
@endsection