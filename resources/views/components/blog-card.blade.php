@props(['post'])

@php
    // Подготавливаем данные (аналог деструктуризации props)
    $acf_h1    = function_exists('get_field') ? (get_field('h1_statiya', $post->ID) ?: '') : '';
    $acf_p     = function_exists('get_field') ? (get_field('p_statiya', $post->ID) ?: '') : '';
    $acf_seo   = function_exists('get_field') ? (get_field('seo_statiya', $post->ID) ?: '') : '';
    $acf_photo = function_exists('get_field') ? (get_field('photo_statiya', $post->ID) ?: '') : '';
    
    $permalink = get_permalink($post->ID);
    $title     = $acf_h1 !== '' ? $acf_h1 : get_the_title($post->ID);
    
    // Формируем описание
    $desc_source = $acf_p !== '' ? $acf_p
        : ($acf_seo !== '' ? wp_strip_all_tags($acf_seo)
            : (has_excerpt($post->ID) ? get_the_excerpt($post->ID) : wp_strip_all_tags(get_the_content(null, false, $post->ID))));
    $desc = wp_trim_words($desc_source, 20, '…');
    
    // Логика получения картинки (перенесли внутрь компонента для инкапсуляции)
    $img_url = '';
    if ($acf_photo) {
        if (is_numeric($acf_photo)) {
            $img = wp_get_attachment_image_src((int)$acf_photo, 'large');
            $img_url = $img ? $img[0] : '';
        } elseif (is_array($acf_photo) && !empty($acf_photo['url'])) {
            $img_url = $acf_photo['url'];
        } elseif (is_array($acf_photo) && !empty($acf_photo['ID'])) {
            $img = wp_get_attachment_image_src((int)$acf_photo['ID'], 'large');
            $img_url = $img ? $img[0] : '';
        } elseif (is_string($acf_photo)) {
            $img_url = $acf_photo;
        }
    }
    
    if (!$img_url) {
        $thumb_id = get_post_thumbnail_id($post->ID);
        $img      = $thumb_id ? wp_get_attachment_image_src($thumb_id, 'large') : null;
        $img_url  = $img ? $img[0] : '';
    }
    
    $date_human = date_i18n('j F Y', get_the_time('U', $post->ID));
    $date_iso   = get_the_date('c', $post->ID);
@endphp

<article class="flex flex-col group h-full bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-300 border border-neutral-100">
    <a href="{{ esc_url($permalink) }}" class="flex flex-col h-full">
        <div class="relative overflow-hidden aspect-[16/10]">
            @if ($img_url)
                <img
                    src="{{ esc_url($img_url) }}"
                    alt="{{ esc_attr($title) }}"
                    class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                    loading="lazy" decoding="async">
            @else
                <div class="w-full h-full bg-neutral-100 flex items-center justify-center text-neutral-400">
                    <svg class="w-10 h-10 opacity-30" fill="currentColor" viewBox="0 0 24 24"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                </div>
            @endif
            <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        </div>

        <div class="p-5 flex flex-col flex-grow">
            <div class="flex items-center justify-between mb-3">
                <time datetime="{{ esc_attr($date_iso) }}" class="text-xs font-medium text-neutral-500 uppercase tracking-wider">
                    {{ esc_html($date_human) }}
                </time>
            </div>

            <h2 class="text-lg font-bold text-neutral-900 leading-tight mb-2 group-hover:text-accent transition-colors line-clamp-2">
                {{ esc_html($title) }}
            </h2>

            @if ($desc)
                <p class="text-sm text-neutral-600 line-clamp-3 mb-4 leading-relaxed">
                    {{ esc_html($desc) }}
                </p>
            @endif

            <div class="mt-auto pt-3 border-t border-neutral-100 flex items-center text-accent text-sm font-semibold">
                Читать далее
                <svg class="w-4 h-4 ml-1 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </div>
        </div>
    </a>
</article>