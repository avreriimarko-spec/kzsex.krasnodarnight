@extends('layouts.app')

@section('content')
    @php
        // Получаем данные из URL
        $city_slug = get_query_var('city');
        $page_slug = get_queried_object()->post_name;
        $page_id = get_queried_object_id();
        
        // Получаем объект города
        $current_city = get_term_by('slug', $city_slug, 'city');
        
        // Получаем SEO данные для страницы с учетом города
        $seo_data = get_page_seo($page_id, $city_slug);
        
        // Получаем специфичные данные для города из новых полей
        $city_specific_data = [
            'seo_title' => '',
            'seo_description' => '',
            'custom_h1' => '',
            'description' => '',
            'main_text' => '',
        ];
        if ($current_city && function_exists('get_field')) {
            $city_specific_data = [
                'seo_title' => get_field('city_seo_title', get_the_ID()) ?: '',
                'seo_description' => get_field('city_seo_description', get_the_ID()) ?: '',
                'custom_h1' => get_field('city_custom_h1', get_the_ID()) ?: '',
                'description' => get_field('city_description', get_the_ID()) ?: '',
                'main_text' => get_field('city_main_text', get_the_ID()) ?: '',
            ];
        }
        
        // Получаем базовые данные страницы
        $default_data = [
            'seo_title' => get_field('seo_title') ?: '',
            'seo_description' => get_field('seo_description') ?: '',
            'custom_h1' => get_field('custom_h1') ?: '',
            'description' => get_field('description') ?: '',
            'main_text' => get_field('main_text') ?: '',
        ];
        
        // Формируем финальные данные: приоритет у специфичных для города
        $final_seo_data = [
            'seo_title' => $city_specific_data['seo_title'] ?: $default_data['seo_title'],
            'seo_description' => $city_specific_data['seo_description'] ?: $default_data['seo_description'],
            'custom_h1' => $city_specific_data['custom_h1'] ?: $default_data['custom_h1'],
            'description' => $city_specific_data['description'] ?: $default_data['description'],
            'main_text' => $city_specific_data['main_text'] ?: $default_data['main_text'],
        ];
        
        // Формируем заголовки
        $page_title = $final_seo_data['custom_h1'] ?: get_the_title();
        $page_description = $final_seo_data['description'] ?: '';
        $seo_title = $final_seo_data['seo_title'] ?: get_the_title() . ' в ' . $current_city->name;
        $meta_description = $final_seo_data['seo_description'] ?: '';
        $main_text = $final_seo_data['main_text'] ?: '';
        
        // Добавляем пагинацию к SEO title
        if (is_paged()) {
            $page_num = get_query_var('paged') ?: get_query_var('page');
            if ($page_num > 1) {
                $seo_title .= ' | Страница ' . $page_num;
            }
        }
    @endphp
    
    <div class="container mx-auto px-4 py-8">

        {{-- Header --}}
        <header class="prose mb-10 text-center max-w-4xl mx-auto">
            <h1 class="text-3xl md:text-5xl font-bold capitalize mb-4 tracking-tight">
                {{ $page_title }}
                @if (is_paged())
                    <span class="text-[#cd1d46]">| Страница {{ get_query_var('paged') ?: get_query_var('page') }}</span>
                @endif
            </h1>
            @if (!is_paged() && $page_description)
                <p class="leading-relaxed max-w-2xl mx-auto">
                    {!! $page_description !!}
                </p>
            @endif
        </header>

        {{-- Основной контент страницы --}}
        <div class="prose prose-lg max-w-none">
            
            {{-- Кастомный контент для города (если есть) --}}
            @if (!is_paged() && $main_text)
                <div class="bg-gray-900 p-6 md:p-10  border border-gray-700 prose-invert mb-8">
                    {!! $main_text !!}
                </div>
            @endif

            {{-- Стандартный контент страницы --}}
            @if (!is_paged())
                <div class="bg-gray-900 p-6 md:p-10  border border-gray-700 prose-invert">
                    {!! get_the_content() !!}
                </div>
            @endif

        </div>

    </div>

    {{-- SEO мета-теги --}}
    @if ($seo_title)
        @php
            // Устанавливаем SEO заголовок
            add_action('wp_head', function() use ($seo_title) {
                echo '<title>' . esc_html($seo_title) . '</title>';
            }, 1);
        @endphp
    @endif

    // Meta description будет добавлен основным фильтром в app/filters.php
@endsection
