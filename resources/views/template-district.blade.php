{{--
  Template Name: Районы
--}}
@extends('layouts.app')

@section('content')
    @include('partials.taxonomy-list', [
        'taxonomy' => 'district',
        'default_title' => 'Районы',
        'found_label' => 'Найдено районы',
        'not_found_text' => 'Районы не найдены'
    ])
@endsection