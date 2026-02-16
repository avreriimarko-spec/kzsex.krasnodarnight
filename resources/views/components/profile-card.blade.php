@props(['post', 'details' => [], 'badges' => [], 'lcp' => false])

@php
    // --- НАСТРОЙКИ ПРОИЗВОДИТЕЛЬНОСТИ ---
    $loadingAttr = $lcp ? 'eager' : 'lazy';
    $fetchPriorityAttr = $lcp ? 'high' : 'low';
    $decodingLcp = 'auto';
    $decodingLazy = 'async';
    $decodingAttr = $lcp ? $decodingLcp : $decodingLazy;
    
    // Получаем город для подписи снизу и для alt
    $city_terms = get_the_terms(get_the_ID(), 'city');
    $city_name = !empty($city_terms) && !is_wp_error($city_terms) ? $city_terms[0]->name : 'KZSEX';
    
    // Генерируем уникальный alt с учетом города
    $profile_name = get_the_title();
    $age = get_field('age');
    
    // Проверяем статусы анкеты
    $is_vip = has_term('vip', 'vip', get_the_ID());
    $is_independent = has_term('independent', 'independent', get_the_ID());
    
    // Определяем префикс согласно логике
    if ($is_vip && !$is_independent) {
        $prefix = "Элитная проститутка";
    } elseif ($is_independent && !$is_vip) {
        $prefix = "Индивидуалка";
    } else {
        // Если ни то ни другое, или оба статуса вместе
        $prefix = "Проститутка";
    }
    
    $alt_text = "{$prefix} {$city_name} {$profile_name}";
    if ($age) {
        $alt_text .= " {$age} лет";
    }
    
    // Добавляем основные параметры
    $hair_color = get_field('hair_color');
    $height = get_field('height');
    $weight = get_field('weight');
    
    if ($hair_color && !is_wp_error($hair_color)) {
        $hair_color_name = $hair_color->name ?? '';
        if ($hair_color_name) {
            $alt_text .= ", {$hair_color_name} волосы";
        }
    }
    
    if ($height) {
        $alt_text .= ", рост {$height} см";
    }
    
    if ($weight) {
        $alt_text .= ", вес {$weight} кг";
    }
    
    // Получаем размер груди
    $breast_terms = get_the_terms(get_the_ID(), 'breast_size');
    $breast = null;
    if ($breast_terms && !is_wp_error($breast_terms)) {
        $breast_name = $breast_terms[0]->name;
        if (preg_match('/([A-Z])$/', $breast_name, $matches)) {
            $letter = $matches[1];
            $breastMap = ['A' => '1', 'B' => '2', 'C' => '3', 'D' => '4', 'E' => '5', 'F' => '6', 'G' => '7', 'H' => '8'];
            $breast = $breastMap[$letter] ?? $letter;
        }
    }
    
    // Получаем цену
    $price = get_field('price');
@endphp

<article class="group relative flex flex-col h-full">

    {{-- Основная ссылка на весь блок --}}
    <a href="{{ profile_url(get_the_ID()) }}" class="absolute inset-0 z-30"
        aria-label="Просмотреть профиль {{ get_the_title() }}"></a>

    {{-- 1. ИЗОБРАЖЕНИЕ (Сверху) --}}
    <figure class="relative w-full aspect-[3/4] overflow-hidden bg-[#0f0f0f] mb-3">
        @if (has_post_thumbnail())
            @php
                $imgData = wp_get_attachment_image_src(get_post_thumbnail_id(), 'large'); 
            @endphp
            @if ($imgData)
                <img src="{{ $imgData[0] }}" width="{{ $imgData[1] }}" height="{{ $imgData[2] }}"
                    alt="{{ $alt_text }}"
                    class="w-full h-full object-cover transition-transform duration-700 ease-in-out group-hover:scale-105"
                    loading="{{ $loadingAttr }}" fetchpriority="{{ $fetchPriorityAttr }}"
                    @if ($decodingAttr) decoding="{{ $decodingAttr }}" @endif>
            @endif
        @else
            <div class="flex items-center justify-center h-full text-gray-700 bg-[#0f0f0f]">
                <span class="text-4xl opacity-20">?</span>
            </div>
        @endif

        {{-- ========================================================== --}}
        {{-- БЕЙДЖИ (С ЦВЕТАМИ)                                         --}}
        {{-- ========================================================== --}}
        <div class="absolute top-4 right-0 z-20 flex flex-col items-end gap-1">
            @foreach ($badges as $badge)
                @php
                    // Определение стилей и текста в зависимости от бейджа
                    $badgeConfig = match($badge) {
                        'New'         => ['class' => 'bg-yellow-500 text-black', 'label' => 'Новая'],
                        'Verified'    => ['class' => 'bg-green-600 text-white',   'label' => 'Проверена'],
                        'VIP'         => ['class' => 'bg-[#cd1d46] text-white',   'label' => 'ВИП'],
                        'Independent' => ['class' => 'bg-black/60 text-white',    'label' => 'Индивидуалка'],
                        default       => ['class' => 'bg-black/60 text-white',    'label' => $badge],
                    };
                @endphp

                <div class="{{ $badgeConfig['class'] }} backdrop-blur-sm px-3 py-1 text-[10px] md:text-xs uppercase tracking-widest font-bold shadow-sm">
                    {{ $badgeConfig['label'] }}
                </div>
            @endforeach
        </div>
        
        {{-- Градиент снизу --}}
        <div class="absolute inset-x-0 bottom-0 h-1/4 bg-gradient-to-t from-black/40 to-transparent pointer-events-none"></div>
    </figure>

    {{-- 2. ИНФОРМАЦИЯ (Снизу под фото) --}}
    <div class="relative z-20 px-1">
        
        {{-- Имя и статус --}}
        <div class="flex items-center gap-2 mb-1">
            <h2 class="font-serif text-lg md:text-2xl text-[#DFC187] uppercase tracking-widest leading-none drop-shadow-sm group-hover:text-white transition-colors">
                {{ get_the_title() }}
                @php
                    $age = get_field('age');
                    if ($age) {
                        echo ' <span class="text-base md:text-lg">' . $age . '</span>';
                    }
                @endphp
            </h2>
            
            {{-- Онлайн статус --}}
            @php
                $is_online = get_field('online');
            @endphp
            @if($is_online)
                <span class="px-2 py-1 bg-green-500 text-white text-[10px] font-bold uppercase">Online</span>
            @endif
        </div>

        {{-- Подзаголовок --}}
        <div class="text-white text-[10px] md:text-xs font-serif uppercase tracking-widest font-medium opacity-90">
            ВИП Эскорт {{ $city_name }}
            @if(isset($price['price_1h_out']) && $price['price_1h_out'] > 0)
                <span class="ml-2 text-green-400">{{ number_format($price['price_1h_out'], 0, '.', ' ') }}₽/ч</span>
            @endif
        </div>
    </div>

</article>