@php
    // --- ГЕНЕРАЦИЯ SEO ТАЙТЛА ДЛЯ АНКЕТЫ ---
    $profile_name = get_the_title();
    $age = get_field('age');
    
    // Получаем город
    $city_terms = get_the_terms(get_the_ID(), 'city');
    $city_name = !empty($city_terms) && !is_wp_error($city_terms) ? $city_terms[0]->name : '';
    
    // Получаем размер груди
    $breast_size = get_field('breast_size');
    $breast_size_text = '';
    if ($breast_size && !is_wp_error($breast_size)) {
        $breast_size_text = $breast_size->name ?? '';
    }
    
    // Формируем тайтл
    $seo_title = "Проститутка {$profile_name}";
    if ($age) {
        // Определяем правильное склонение для возраста
        $last_digit = $age % 10;
        $last_two_digits = $age % 100;
        
        if ($last_two_digits >= 11 && $last_two_digits <= 14) {
            $age_text = $age . ' лет';
        } elseif ($last_digit === 1) {
            $age_text = $age . ' год';
        } elseif ($last_digit >= 2 && $last_digit <= 4) {
            $age_text = $age . ' года';
        } else {
            $age_text = $age . ' лет';
        }
        
        $seo_title .= " {$age_text}";
    }
    if ($city_name) {
        $seo_title .= " в {$city_name}";
    }
    if ($breast_size_text) {
        $seo_title .= ", размер груди {$breast_size_text}";
    }
    $seo_title .= " - доступна 24/7";
    if ($city_name) {
        $seo_title .= " в {$city_name}";
    }
    
    // Формируем meta description
    $seo_description = "Проститутка {$profile_name}";
    if ($age) {
        // Определяем правильное склонение для возраста
        $last_digit = $age % 10;
        $last_two_digits = $age % 100;
        
        if ($last_two_digits >= 11 && $last_two_digits <= 14) {
            $age_text = $age . ' лет';
        } elseif ($last_digit === 1) {
            $age_text = $age . ' год';
        } elseif ($last_digit >= 2 && $last_digit <= 4) {
            $age_text = $age . ' года';
        } else {
            $age_text = $age . ' лет';
        }
        
        $seo_description .= ", {$age_text}";
    }
    if ($city_name) {
        $seo_description .= " из {$city_name}";
    }
    if ($breast_size_text) {
        $seo_description .= ". Размер груди: {$breast_size_text}";
    }
    $seo_description .= ". Индивидуалка предлагает интим-услуги, доступна 24/7. Выезд и по квартире.";
    
    // Устанавливаем SEO тайтл через фильтры
    add_filter('pre_get_document_title', function() use ($seo_title) { return $seo_title; }, 999);
    add_filter('wpseo_title', function() use ($seo_title) { return $seo_title; }, 999);
    add_filter('rank_math/frontend/title', function() use ($seo_title) { return $seo_title; }, 999);
    
    // Meta description будет добавлен основным фильтром в app/filters.php

    // Словарь для перевода
    $labelMap = [
        'Breast' => 'Грудь',
        'Bust' => 'Грудь',
        'Height' => 'Рост',
        'Weight' => 'Вес',
        'Hair' => 'Волосы',
        'Hair Color' => 'Цвет волос',
        'Eyes' => 'Глаза',
        'Nationality' => 'Нация',
        'Dress Size' => 'Размер одежды',
        'Clothing' => 'Одежда',
        'Languages' => 'Языки',
        'Figure' => 'Фигура',
        'Tattoos' => 'Тату',
        'Age' => 'Возраст',
        'City' => 'Город'
    ];

    // --- ЛОГИКА ФОТО ---
    $allPhotos = [];
    
    // Получаем город и возраст для alt атрибутов
    $city_terms = get_the_terms(get_the_ID(), 'city');
    $city_name = !empty($city_terms) && !is_wp_error($city_terms) ? $city_terms[0]->name : '';
    $profile_name = get_the_title();
    $age = get_field('age');
    
    // Базовый alt текст
    $is_vip = has_term('vip', 'vip', get_the_ID());
    $is_independent = has_term('independent', 'independent', get_the_ID());
    
    // Определяем префикс согласно логике
    if ($is_vip && !$is_independent) {
        $base_alt = "Элитная проститутка";
    } elseif ($is_independent && !$is_vip) {
        $base_alt = "Индивидуалка";
    } else {
        // Если ни то ни другое, или оба статуса вместе
        $base_alt = "Проститутка";
    }
    if ($city_name) {
        $base_alt .= " {$city_name}";
    }
    $base_alt .= " {$profile_name}";
    if ($age) {
        $base_alt .= " {$age} лет";
    }
    
    // Добавляем основные параметры
    $hair_color = get_field('hair_color');
    $height = get_field('height');
    $weight = get_field('weight');
    
    if ($hair_color && !is_wp_error($hair_color)) {
        $hair_color_name = $hair_color->name ?? '';
        if ($hair_color_name) {
            $base_alt .= ", {$hair_color_name} волосы";
        }
    }
    
    if ($height) {
        $base_alt .= ", рост {$height} см";
    }
    
    if ($weight) {
        $base_alt .= ", вес {$weight} кг";
    }

    if (has_post_thumbnail()) {
        $allPhotos[] = [
            'type' => 'main',
            'full' => get_the_post_thumbnail_url(null, 'full'),
            'thumb' => get_the_post_thumbnail_url(null, 'profile_single'),
            'alt'  => $base_alt,
        ];
    }

    if (!empty($gallery) && is_array($gallery)) {
        foreach ($gallery as $index => $imgData) {
            // ACF Gallery может возвращать массив данных или ID
            $imgId = is_array($imgData) ? $imgData['ID'] : $imgData;
            $imgUrl = wp_get_attachment_url($imgId);
            $imgSizes = wp_get_attachment_image_src($imgId, 'profile_single');
            
            if ($imgUrl) {
                $allPhotos[] = [
                    'type' => 'gallery',
                    'full' => $imgUrl,
                    'thumb' => $imgSizes ? $imgSizes[0] : $imgUrl,
                    'alt'  => $base_alt . ' фото ' . ($index + 1),
                ];
            }
        }
    }

    $totalPhotos = count($allPhotos);

    // --- ЛОГИКА ОПИСАНИЯ (200 символов) ---
    $fullContent = apply_filters('the_content', get_the_content());
    $rawText = wp_strip_all_tags($fullContent);
    $isLongDescription = mb_strlen($rawText) > 200;
    $shortDescription = $isLongDescription ? mb_substr($rawText, 0, 200) . '...' : $rawText;

    // --- ЛОГИКА ПОХОЖИХ МОДЕЛЕЙ ---
    $relatedModels = new WP_Query([
        'post_type' => get_post_type(),
        'posts_per_page' => 4,
        'post__not_in' => [get_the_ID()], // Исключаем текущую
        'orderby' => 'rand', // Случайный порядок
        'no_found_rows' => true, // Оптимизация
        'ignore_sticky_posts' => true,
        'meta_key' => 'views',
        'orderby' => 'meta_value_num',
    ]);

    // --- ЛОГИКА ОТЗЫВОВ ---
    $reviewsCount = is_array($reviews) ? count($reviews) : 0;
@endphp

<article <?php post_class('bg-black min-h-screen text-black font-serif selection:bg-[#cd1d46] selection:text-black'); ?>>

    <div class="container mx-auto px-4 py-8 md:py-12">
        
        {{-- ЗАГОЛОВОК (ИМЯ) МОБИЛЬНЫЙ --}}
        <div class="lg:hidden mb-6 text-center">
@if ( wp_is_mobile() )
            <h1 class="font-serif text-2xl text-[#DFC187] uppercase tracking-[0.2em] mb-1 leading-none drop-shadow-sm">
                {{ get_the_title() }}
                @php
                    $age = get_field('age');
                    if ($age) {
                        // Определяем правильное склонение
                        $last_digit = $age % 10;
                        $last_two_digits = $age % 100;
                        
                        if ($last_two_digits >= 11 && $last_two_digits <= 14) {
                            $age_text = $age . ' лет';
                        } elseif ($last_digit === 1) {
                            $age_text = $age . ' год';
                        } elseif ($last_digit >= 2 && $last_digit <= 4) {
                            $age_text = $age . ' года';
                        } else {
                            $age_text = $age . ' лет';
                        }
                        
                        echo ' <span class="text-lg">' . $age_text . '</span>';
                    }
                    // Добавляем город к H1
                    $city_terms = get_the_terms(get_the_ID(), 'city');
                    $city_name = !empty($city_terms) && !is_wp_error($city_terms) ? $city_terms[0]->name : '';
                    if ($city_name) {
                        echo ' <span class="text-base text-gray-300">' . $city_name . '</span>';
                    }
                @endphp
            </h1>
@endif
        </div>

        {{-- ОСНОВНАЯ СЕТКА --}}
        <div class="flex flex-col lg:grid lg:grid-cols-12 gap-10 items-start mb-20">

            {{-- ========================================================================
                 БЛОК 1: ФОТОГРАФИИ
                 ======================================================================== --}}
            <div class="w-full order-1 lg:col-span-8 lg:col-start-5 lg:row-start-1 space-y-4">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="photo-gallery-container">
                    @foreach ($allPhotos as $index => $photo)
                        @php
                            $visibilityClass = ($index >= 3) ? 'hidden md:block mobile-hidden-photo' : '';
                        @endphp

                        <div class="md:col-span-1 {{ $visibilityClass }} relative group overflow-hidden">
                            <a href="{{ $photo['full'] }}" 
                               data-fancybox="gallery" 
                               class="block w-full aspect-[3/4] bg-[#0f0f0f] group">
                                
                                <img src="{{ $photo['thumb'] }}" 
                                     alt="{{ $photo['alt'] }}" 
                                     class="w-full h-full object-cover transition-transform duration-[1.5s] ease-in-out group-hover:scale-110 opacity-90 group-hover:opacity-100 grayscale-[10%] group-hover:grayscale-0">
                                
                                {{-- БЕЙДЖ VIP НА ГЛАВНОМ ФОТО --}}
                                @if ($photo['type'] === 'main' && in_array('VIP', $badges))
                                    <div class="absolute top-4 right-4 bg-[#cd1d46] backdrop-blur-md border border-white/10 px-4 py-1.5 text-[10px] uppercase tracking-[0.2em] text-black font-bold z-10 shadow-[0_0_15px_rgba(205,29,70,0.5)]">
                                        VIP
                                    </div>
                                @endif
                            </a>
                        </div>
                    @endforeach
                </div>

                {{-- КНОПКА "ПОКАЗАТЬ ВСЕ ФОТО" --}}
                @if ($totalPhotos > 3)
                    <button id="show-more-photos" class="w-full md:hidden border border-white/30 py-4 text-xs font-bold uppercase tracking-[0.2em] hover:bg-[#cd1d46] hover:border-[#cd1d46] hover:text-black transition-colors">
                        Показать все фото ({{ $totalPhotos }})
                    </button>
                @endif
            </div>


            {{-- ========================================================================
                 БЛОК 2: САЙДБАР (ЛЕВАЯ КОЛОНКА)
                 ======================================================================== --}}
            <aside class="w-full order-2 lg:order-1 lg:col-span-4 lg:col-start-1 lg:row-start-1 lg:row-span-full lg:sticky lg:top-24 lg:max-h-[calc(100vh-6rem)] lg:overflow-y-auto scrollbar-hide space-y-12">

                {{-- ИМЯ (Десктоп) --}}
                <div class="hidden lg:block">
                    @if ( ! wp_is_mobile() )
                    <h1 class="font-serif text-2xl xl:text-3xl text-[#DFC187] font-[500] tracking-[0.15em] mb-3 leading-none drop-shadow-sm">
                        {{ get_the_title() }}
                        @php
                            $age = get_field('age');
                            if ($age) {
                                // Определяем правильное склонение
                                $last_digit = $age % 10;
                                $last_two_digits = $age % 100;
                                
                                if ($last_two_digits >= 11 && $last_two_digits <= 14) {
                                    $age_text = $age . ' лет';
                                } elseif ($last_digit === 1) {
                                    $age_text = $age . ' год';
                                } elseif ($last_digit >= 2 && $last_digit <= 4) {
                                    $age_text = $age . ' года';
                                } else {
                                    $age_text = $age . ' лет';
                                }
                                
                                echo ' <span class="text-2xl xl:text-3xl ">' . $age_text . '</span>';
                            }
                            // Добавляем город к H1
                            $city_terms = get_the_terms(get_the_ID(), 'city');
                            $city_name = !empty($city_terms) && !is_wp_error($city_terms) ? $city_terms[0]->name : '';
                            if ($city_name) {
                                echo ' <span class="text-2xl xl:text-3xl ">' . $city_name . '</span>';
                            }
                        @endphp
                    </h1>
                    @endif
                    <div class="h-px w-16 bg-[#cd1d46]/50 mb-4"></div>
                </div>

                {{-- ПАРАМЕТРЫ --}}
                <section>
                    <h2 class="font-serif text-2xl text-black uppercase tracking-widest mb-6 border-l-2 border-[#cd1d46] pl-4">
                        Параметры
                    </h2>
                    <div class="space-y-4 font-serif text-xs md:text-sm tracking-wide">
                        @if (!empty($details['age']))
                            <div class="flex items-center justify-between group">
                                <span class="text-gray-400 font-bold uppercase w-1/3 text-[11px] tracking-widest group-hover:text-black transition-colors">
                                    Возраст:
                                </span>
                                <div class="border border-white/20 bg-white/5 px-5 py-1.5 min-w-[90px] text-center text-gray-200 group-hover:border-[#cd1d46] group-hover:bg-white/10 transition-all duration-300">
                                    {{ $details['age'] }}
                                </div>
                            </div>
                        @endif

                        @foreach ($traits as $label => $value)
                            <div class="flex items-center justify-between group">
                                <span class="text-gray-400 font-bold uppercase w-1/3 text-[11px] tracking-widest group-hover:text-black transition-colors">
                                    {{ $labelMap[$label] ?? $label }}:
                                </span>
                                <div class="border border-white/20 bg-white/5 px-5 py-1.5 min-w-[90px] text-center text-gray-200 group-hover:border-[#cd1d46] group-hover:bg-white/10 transition-all duration-300">
                                    {{ $value }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                {{-- ТАРИФЫ --}}
                @if ($price)
                    @php
                        $price = \App\Services\ProfilePriceCalculator::apply((array) $price);
                        $currency = $price['currency'] ?? 'RUB';
                        $apartmentTariffs = [
                            ['label' => '1 Час', 'value' => $price['price_1h'] ?? null],
                            ['label' => '2 Часа', 'value' => $price['price_2h'] ?? null],
                            ['label' => '4 Часа', 'value' => $price['price_4h'] ?? null],
                            ['label' => 'Ночь', 'value' => $price['price_night'] ?? null],
                            ['label' => 'Сутки', 'value' => $price['price_day'] ?? null],
                        ];
                        $outcallTariffs = [
                            ['label' => '1 Час', 'value' => $price['price_1h_out'] ?? null],
                            ['label' => '2 Часа', 'value' => $price['price_2h_out'] ?? null],
                            ['label' => '4 Часа', 'value' => $price['price_4h_out'] ?? null],
                            ['label' => 'Ночь', 'value' => $price['price_night_out'] ?? null],
                            ['label' => 'Сутки', 'value' => $price['price_day_out'] ?? null],
                        ];
                        $showTaxiForApartments = false;
                        $showTaxiForOutcall = true;
                    @endphp
                    <section>
                        <h2 class="font-serif text-2xl text-black uppercase tracking-widest mb-6 border-l-2 border-[#cd1d46] pl-4">
                            Тарифы
                        </h2>
                        <div class="space-y-4 font-serif text-xs md:text-sm tracking-wide">
                            <div class="inline-flex rounded border border-white/20 overflow-hidden">
                                <button type="button"
                                        id="tariff-tab-apartments-btn"
                                        data-tariff-tab-target="apartments"
                                        class="tariff-tab-btn bg-[#cd1d46] text-black px-4 py-2 text-[11px] font-bold uppercase tracking-widest transition-colors">
                                    Аппартаменты
                                </button>
                                <button type="button"
                                        id="tariff-tab-outcall-btn"
                                        data-tariff-tab-target="outcall"
                                        class="tariff-tab-btn bg-white/5 text-gray-300 px-4 py-2 text-[11px] font-bold uppercase tracking-widest transition-colors">
                                    Выезд
                                </button>
                            </div>

                            <div id="tariff-tab-apartments" class="space-y-3">
                                @foreach($apartmentTariffs as $tariff)
                                    <div class="flex items-center justify-between group">
                                        <span class="text-gray-400 font-bold uppercase text-[11px] tracking-widest">
                                            {{ $tariff['label'] }}:
                                        </span>
                                        <div class="border border-white/20 bg-white/5 px-6 py-2 text-gray-200">
                                            @if($tariff['value'])
                                                {{ number_format((float) $tariff['value'], 0, '.', ' ') }} {{ $currency }}
                                            @else
                                                По запросу
                                            @endif
                                            @if($showTaxiForApartments)
                                                <span class="text-[9px] text-gray-500 uppercase ml-1">+ Такси</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div id="tariff-tab-outcall" class="space-y-3 hidden">
                                @foreach($outcallTariffs as $tariff)
                                    <div class="flex items-center justify-between group">
                                        <span class="text-gray-400 font-bold uppercase text-[11px] tracking-widest">
                                            {{ $tariff['label'] }}:
                                        </span>
                                        <div class="border border-white/20 bg-white/5 px-6 py-2 text-gray-200">
                                            @if($tariff['value'])
                                                {{ number_format((float) $tariff['value'], 0, '.', ' ') }} {{ $currency }}
                                            @else
                                                По запросу
                                            @endif
                                            @if($showTaxiForOutcall)
                                                <span class="text-[9px] text-gray-500 uppercase ml-1">+ Такси</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endif

                {{-- КОНТАКТЫ --}}
                <section class="space-y-4 pt-6">
                    <h2 class="font-serif text-2xl text-black uppercase tracking-widest mb-6 border-l-2 border-[#cd1d46] pl-4">
                        Контакты
                    </h2>
                    
                    @php
                        // Как в карточках - берем напрямую из глобальных настроек
                        $globalTg = get_field('global_tg', 'option');
                        $globalWa = get_field('global_wa', 'option');
                        
                        // Логика очистки Telegram (как в карточках)
                        $tgLink = null;
                        if ($globalTg) {
                            if (strpos($globalTg, 'http') !== false) {
                                $tgLink = $globalTg;
                            } else {
                                $tgClean = str_replace('@', '', $globalTg);
                                $tgLink = "https://t.me/{$tgClean}";
                            }
                        }

                        // Логика очистки WhatsApp (как в карточках)
                        $waLink = null;
                        if ($globalWa) {
                            if (strpos($globalWa, 'http') !== false) {
                                $waLink = $globalWa;
                            } else {
                                $waClean = preg_replace('/[^0-9]/', '', $globalWa);
                                $waLink = "https://wa.me/{$waClean}";
                            }
                        }
                    @endphp
                    
                    <div class="grid grid-cols-1 gap-3">
                        @if ($tgLink)
                            <a href="{{ $tgLink }}" target="_blank" rel="noopener noreferrer"
                               class="rounded group relative overflow-hidden border border-[#8ccff0] bg-white px-4 py-4 transition-all duration-500 hover:border-[#46b3e7] hover:shadow-[0_10px_26px_rgba(0,136,204,0.18)]">
                               <span class="absolute inset-0 bg-gradient-to-r from-[#d8f1ff] via-[#88d8ff] to-[#2eb2ec] opacity-0 transition-opacity duration-500 group-hover:opacity-100"></span>
                               <span class="absolute inset-0 bg-[radial-gradient(circle_at_82%_18%,rgba(255,255,255,0.7),transparent_45%)] opacity-0 transition-opacity duration-500 group-hover:opacity-100"></span>
                               <span class="relative z-10 flex items-center justify-between">
                                   <span class="flex items-center gap-3">
                                       <span class="flex h-10 w-10 items-center justify-center border border-[#74c8f4] bg-[#bfe7fb]">
                                           <svg class="h-5 w-5 fill-current text-[#0b5376]" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12.068 12.068 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z" /></svg>
                                       </span>
                                       <span class="leading-tight">
                                           <span class="block text-[10px] font-bold uppercase tracking-[0.22em] text-[#0b5376]/70">Быстрый чат</span>
                                           <span class="block text-sm font-extrabold uppercase tracking-[0.12em] text-[#0b5376]">Telegram</span>
                                       </span>
                                   </span>
                                   <span class="text-[11px] font-extrabold uppercase tracking-[0.2em] text-[#0b5376]/80">Написать</span>
                               </span>
                            </a>
                        @endif
                        
                        @if ($waLink)
                            <a href="{{ $waLink }}" target="_blank" rel="noopener noreferrer"
                               class=" rounded group relative overflow-hidden border border-[#9bdcaf] bg-white px-4 py-4 transition-all duration-500 hover:border-[#47bf74] hover:shadow-[0_10px_26px_rgba(37,211,102,0.16)]">
                               <span class="absolute inset-0 bg-gradient-to-r from-[#dff9e7] via-[#96e5b1] to-[#33ca70] opacity-0 transition-opacity duration-500 group-hover:opacity-100"></span>
                               <span class="absolute inset-0 bg-[radial-gradient(circle_at_82%_18%,rgba(255,255,255,0.7),transparent_45%)] opacity-0 transition-opacity duration-500 group-hover:opacity-100"></span>
                               <span class="relative z-10 flex items-center justify-between">
                                   <span class="flex items-center gap-3">
                                       <span class="flex h-10 w-10 items-center justify-center border border-[#6fd394] bg-[#c8f4d6]">
                                           <svg class="h-5 w-5 fill-current text-[#1d6b3b]" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.008-.57-.008-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                                       </span>
                                       <span class="leading-tight">
                                           <span class="block text-[10px] font-bold uppercase tracking-[0.22em] text-[#1d6b3b]/70">Оперативно</span>
                                           <span class="block text-sm font-extrabold uppercase tracking-[0.12em] text-[#1d6b3b]">WhatsApp</span>
                                       </span>
                                   </span>
                                   <span class="text-[11px] font-extrabold uppercase tracking-[0.2em] text-[#1d6b3b]/80">Написать</span>
                               </span>
                            </a>
                        @endif
                    </div>
                    
                    @if (!$tgLink && !$waLink)
                        <div class="border border-[#d9d9d9] bg-[#f4f4f4] px-4 py-5 text-center text-xs uppercase tracking-[0.16em] text-gray-500">
                            Контакты временно недоступны
                        </div>
                    @endif
                </section>
            </aside>


            {{-- ========================================================================
                 БЛОК 3: ИНФОРМАЦИЯ + УСЛУГИ (ПРАВАЯ КОЛОНКА)
                 ======================================================================== --}}
            <div class="w-full order-3 lg:col-span-8 lg:col-start-5 space-y-12">

                {{-- ОБО МНЕ --}}
                <section class="max-w-4xl">
                    <h2 class="font-serif text-2xl text-black uppercase tracking-widest mb-8 border-l-2 border-[#cd1d46] pl-4">
                        Обо мне
                    </h2>
                    
                    <div class="prose prose-invert prose-p:text-gray-300 prose-p:font-light prose-p:leading-8 prose-p:text-justify max-w-none text-sm md:text-base">
                        <div id="desc-short" class="{{ $isLongDescription ? '' : 'hidden' }}">
                            <p>{{ $shortDescription }}</p>
                        </div>
                        <div id="desc-full" class="{{ $isLongDescription ? 'hidden' : '' }}">
                            <p>{{ $rawText }}</p>
                        </div>
                    </div>

                    @if ($isLongDescription)
                        <button id="toggle-desc-btn" class="mt-4 text-xs font-bold uppercase tracking-[0.15em] text-black/70 hover:text-[#cd1d46] border-b border-white/30 hover:border-[#cd1d46] transition-all pb-1">
                            Читать полностью
                        </button>
                    @endif
                </section>
                
                {{-- УСЛУГИ (С ССЫЛКАМИ) --}}
                @if (!empty($services))
                    <section class="border-t border-gray-900 pt-10">
                        <h2 class="font-serif text-xl text-black uppercase tracking-widest mb-8">
                            Услуги
                        </h2>
                        <div class="flex flex-wrap gap-x-4 gap-y-3">
                            @foreach ($services as $service)
                                <a href="{{ term_url($service) }}" 
                                   class="border border-white/20 bg-white/5 px-4 py-2 text-xs text-gray-300 uppercase tracking-widest hover:bg-[#cd1d46] hover:border-[#cd1d46] hover:!text-black transition-colors cursor-pointer">
                                    {{ $service->name }}
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif

                {{-- ФОРМА ОТПРАВКИ ОТЗЫВА --}}
                <section class="border-t border-gray-900 pt-10">
                    <h2 class="font-serif text-2xl text-black uppercase tracking-widest mb-8 border-l-2 border-[#cd1d46] pl-4">
                        Оставить отзыв
                    </h2>
                    
                    <div class="max-w-2xl mx-auto">
                        <form id="reviewForm" class="space-y-6">
                            {{-- ИСПРАВЛЕНИЕ: @csrf удален, так как создает скрытый инпут с autocomplete="off", что вызывает ошибку валидации. --}}
                            {{-- Защита nonce уже реализована в JavaScript ниже. --}}
                            <input type="hidden" name="profile_id" value="{{ get_the_ID() }}">
                            
                            {{-- Имя автора --}}
                            <div>
                                <label class="block text-gray-300 text-sm font-medium mb-2">
                                    Ваше имя *
                                </label>
                                <input type="text" name="author" required
                                       class="w-full px-4 py-3 bg-black border border-gray-700 text-black placeholder-gray-500 focus:outline-none focus:border-[#cd1d46] focus:ring-1 focus:ring-[#cd1d46]/20 transition-colors"
                                       placeholder="Введите ваше имя">
                            </div>
                            
                            {{-- Рейтинг --}}
                            <div>
                                <label class="block text-gray-300 text-sm font-medium mb-2">
                                    Ваша оценка *
                                </label>
                                <div class="flex gap-2" id="ratingStars">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <button type="button" 
                                                class="rating-star text-3xl text-gray-600 hover:text-yellow-400 transition-colors focus:outline-none"
                                                data-rating="{{ $i }}">
                                            ★
                                        </button>
                                    @endfor
                                </div>
                                {{-- ИСПРАВЛЕНИЕ: Удален атрибут required из скрытого поля --}}
                                <input type="hidden" name="rating" id="ratingValue" value="5">
                            </div>
                            
                            {{-- Текст отзыва --}}
                            <div>
                                <label class="block text-gray-300 text-sm font-medium mb-2">
                                    Текст отзыва *
                                </label>
                                <textarea name="content" rows="5" required
                                          class="w-full px-4 py-3 bg-black border border-gray-700 text-black placeholder-gray-500 focus:outline-none focus:border-[#cd1d46] focus:ring-1 focus:ring-[#cd1d46]/20 transition-colors resize-none"
                                          placeholder="Расскажите о вашем опыте..."></textarea>
                            </div>
                            
                            {{-- Кнопка отправки --}}
                            <div class="text-center">
                                <button type="submit" 
                                        class="rounded bg-[#cd1d46] hover:bg-[#b01530] text-black font-bold uppercase px-8 py-4 transition-colors transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-[#cd1d46]/20">
                                    Отправить отзыв
                                </button>
                            </div>
                        </form>
                        
                        {{-- Сообщение об успешной отправке --}}
                        <div id="reviewSuccess" class="hidden mt-4 bg-green-900/50 border border-green-500 text-green-300 px-6 py-4 text-center">
                            <p class="font-bold">Спасибо за ваш отзыв!</p>
                            <p class="text-sm mt-1">Ваш отзыв будет опубликован после модерации.</p>
                        </div>
                        
                        {{-- Сообщение об ошибке --}}
                        <div id="reviewError" class="hidden mt-4 bg-red-900/50 border border-red-500 text-red-300 px-6 py-4  text-center">
                            <p class="font-bold">Ошибка при отправке</p>
                            <p class="text-sm mt-1">Пожалуйста, попробуйте еще раз.</p>
                        </div>
                    </div>
                </section>

                {{-- ОТЗЫВЫ --}}
                @if ($reviews && $reviewsCount > 0)
                    <section class="border-t border-gray-900 pt-10">
                        <h2 class="font-serif text-xl text-black uppercase tracking-widest mb-8">
                            Отзывы ({{ $reviewsCount }})
                        </h2>
                        <div class="space-y-6">
                            @foreach ($reviews as $review)
                                @php
                                    $ratingPercent = isset($review['rating']) ? ($review['rating'] * 100) : 0;

                                    // Конвертируем рейтинг в 1-5 формат если нужно (как было)
                                    $displayRating = $review['rating'] ?? 0;
                                    if ($displayRating <= 1) {
                                        $displayRating = $displayRating * 5;
                                    }

                                    // ---- ВАЖНО: нормализованный рейтинг (1-5) для звёзд и текста ----
                                    $rating = isset($review['rating']) ? (float)$review['rating'] : 0.0;
                                    if ($rating > 0 && $rating <= 1) {
                                        $rating = $rating * 5;
                                    }
                                    if ($rating < 0) $rating = 0;
                                    if ($rating > 5) $rating = 5;

                                    // Текст рейтинга: 5.0 -> 5, 4.5 -> 4.5
                                    $ratingText = ((float)$rating === (float)intval($rating))
                                        ? (string)intval($rating)
                                        : number_format((float)$rating, 1, '.', '');

                                    $reviewDate = isset($review['date']) ? date('d.m.Y', strtotime($review['date'])) : '';
                                @endphp
                                <div class="bg-white/5 border border-white/10 p-6 hover:bg-white/10 transition-colors">
                                    {{-- Заголовок отзыва --}}
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-[#cd1d46]  flex items-center justify-center text-black font-bold text-sm">
                                                {{ strtoupper(substr($review['author'] ?? 'А', 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="font-serif text-black font-bold">
                                                    {{ $review['author'] ?? 'Аноним' }}
                                                </div>
                                                @if (!empty($reviewDate))
                                                    <div class="text-xs text-gray-400">
                                                        {{ $reviewDate }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        {{-- Рейтинг в звездах --}}
                                        @if (isset($review['rating']))
                                            <div class="flex items-center gap-1">
                                                <div class="flex">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        @php
                                                            $starFill = $i <= round($rating) ? 'fill-yellow-400' : 'fill-gray-600';
                                                        @endphp
                                                        <svg class="w-4 h-4 {{ $starFill }}" viewBox="0 0 20 20">
                                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                                        </svg>
                                                    @endfor
                                                </div>

                                                {{-- ВАЖНО: было number_format($rating, 1) -> стало $ratingText --}}
                                                <span class="text-xs text-gray-400 ml-2">
                                                    {{ $ratingText }}/5
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    {{-- Заголовок отзыва если есть --}}
                                    @if (!empty($review['title']))
                                        <h3 class="font-serif text-lg text-[#DFC187] mb-3">
                                            {{ $review['title'] }}
                                        </h3>
                                    @endif
                                    
                                    {{-- Текст отзыва --}}
                                    @if (!empty($review['content']))
                                        <div class="prose prose-invert prose-p:text-gray-300 prose-p:text-sm prose-p:leading-relaxed max-w-none">
                                            {!! $review['content'] !!}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

        </div>

        {{-- ========================================================================
             БЛОК 4: ДРУГИЕ МОДЕЛИ (В НИЗУ СТРАНИЦЫ)
             ======================================================================== --}}
        @if ($relatedModels->have_posts())
            <section class="border-t border-gray-900 pt-16">
                <h2 class="font-serif text-2xl md:text-3xl text-black uppercase tracking-[0.2em] text-center mb-10">
                    Другие модели
                </h2>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                    @while ($relatedModels->have_posts()) 
                        @php $relatedModels->the_post(); @endphp
                        
                        <a href="{{ get_permalink() }}" class="group block relative aspect-[3/4] overflow-hidden bg-[#0f0f0f]">
                            {{-- Изображение --}}
                            @if(has_post_thumbnail())
                                @php
                                    // Получаем данные для похожих моделей
                                    $related_city_terms = get_the_terms(get_the_ID(), 'city');
                                    $related_city_name = !empty($related_city_terms) && !is_wp_error($related_city_terms) ? $related_city_terms[0]->name : '';
                                    $related_profile_name = get_the_title();
                                    $related_age = get_field('age');
                                    
                                    // Генерируем alt для похожих моделей
                                    $related_is_vip = has_term('vip', 'vip', get_the_ID());
                                    $related_is_independent = has_term('independent', 'independent', get_the_ID());
                                    
                                    // Определяем префикс согласно логике
                                    if ($related_is_vip && !$related_is_independent) {
                                        $related_alt = "Элитная проститутка";
                                    } elseif ($related_is_independent && !$related_is_vip) {
                                        $related_alt = "Индивидуалка";
                                    } else {
                                        // Если ни то ни другое, или оба статуса вместе
                                        $related_alt = "Проститутка";
                                    }
                                    if ($related_city_name) {
                                        $related_alt .= " {$related_city_name}";
                                    }
                                    $related_alt .= " {$related_profile_name}";
                                    if ($related_age) {
                                        $related_alt .= " {$related_age} лет";
                                    }
                                @endphp
                                <img src="{{ get_the_post_thumbnail_url(null, 'profile_single') }}" 
                                     alt="{{ $related_alt }}" 
                                     class="w-full h-full object-cover transition-transform duration-[1.2s] ease-in-out group-hover:scale-110 opacity-80 group-hover:opacity-100">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-gray-800 text-gray-600">
                                    <span class="text-4xl opacity-20">?</span>
                                </div>
                            @endif
                            
                            {{-- Градиент снизу --}}
                            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/20 to-transparent opacity-80"></div>

                            {{-- БЕЙДЖИ (С ЦВЕТАМИ) --}}
                            @php
                                $badges = [];
                                // Логика определения бейджей
                                if (has_term('vip', 'category', get_the_ID())) {
                                    $badges[] = 'VIP';
                                }
                                if (has_term('independent', 'category', get_the_ID())) {
                                    $badges[] = 'Independent';
                                }
                                $verified = get_field('verified', get_the_ID());
                                if ($verified) {
                                    $badges[] = 'Verified';
                                }
                                $postDate = get_the_date('U', get_the_ID());
                                $weekAgo = time() - (7 * 24 * 60 * 60);
                                if ($postDate > $weekAgo) {
                                    $badges[] = 'New';
                                }
                            @endphp

                            @if(!empty($badges))
                                <div class="absolute top-3 left-3 z-10 flex flex-col gap-1 items-start">
                                    @foreach($badges as $badge)
                                        @php
                                            // Цвета бейджей (как в карточке)
                                            $badgeConfig = match($badge) {
                                                'New'         => ['class' => 'bg-yellow-500 text-black', 'label' => 'Новая'],
                                                'Verified'    => ['class' => 'bg-green-600 text-black',   'label' => 'Проверена'],
                                                'VIP'         => ['class' => 'bg-[#cd1d46] text-black',   'label' => 'ВИП'],
                                                'Independent' => ['class' => 'bg-black/60 text-black',    'label' => 'Индивидуалка'],
                                                default       => ['class' => 'bg-black/60 text-black',    'label' => $badge],
                                            };
                                        @endphp
                                        <div class="{{ $badgeConfig['class'] }} backdrop-blur-sm px-2 py-1 text-[8px] md:text-[9px] uppercase tracking-widest font-bold shadow-lg">
                                            {{ $badgeConfig['label'] }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Текст внизу --}}
                            <div class="absolute bottom-0 left-0 w-full p-4 text-center transform translate-y-2 group-hover:translate-y-0 transition-transform duration-300">
                                {{-- Имя модели золотым цветом, как в карточке товара --}}
                                <h3 class="font-serif text-lg md:text-xl text-[#DFC187] uppercase tracking-widest leading-none mb-1 group-hover:text-black transition-colors drop-shadow-sm">
                                    {{ get_the_title() }}
                                </h3>
                            </div>
                        </a>
                    @endwhile
                    @php wp_reset_postdata(); @endphp
                </div>
            </section>
        @endif

    </div>
</article>

{{-- JavaScript для формы отзыва --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработка рейтинга звездами
    const stars = document.querySelectorAll('.rating-star');
    const ratingValue = document.getElementById('ratingValue');
    
    stars.forEach((star, index) => {
        star.addEventListener('click', function() {
            const rating = parseInt(this.dataset.rating);
            ratingValue.value = rating;
            
            // Обновляем визуальное состояние звезд
            stars.forEach((s, i) => {
                if (i < rating) {
                    s.classList.remove('text-gray-600');
                    s.classList.add('text-yellow-400');
                } else {
                    s.classList.remove('text-yellow-400');
                    s.classList.add('text-gray-600');
                }
            });
        });
        
        star.addEventListener('mouseenter', function() {
            const rating = parseInt(this.dataset.rating);
            
            stars.forEach((s, i) => {
                if (i < rating) {
                    s.classList.add('text-yellow-400');
                    s.classList.remove('text-gray-600');
                } else {
                    s.classList.remove('text-yellow-400');
                    s.classList.add('text-gray-600');
                }
            });
        });
    });
    
    // Возвращаем исходное состояние при уходе мыши
    document.getElementById('ratingStars').addEventListener('mouseleave', function() {
        const currentRating = parseInt(ratingValue.value);
        
        stars.forEach((s, i) => {
            if (i < currentRating) {
                s.classList.remove('text-gray-600');
                s.classList.add('text-yellow-400');
            } else {
                s.classList.remove('text-yellow-400');
                s.classList.add('text-gray-600');
            }
        });
    });
    
    // Обработка отправки формы
    const reviewForm = document.getElementById('reviewForm');
    const successMessage = document.getElementById('reviewSuccess');
    const errorMessage = document.getElementById('reviewError');
    const submitButton = reviewForm.querySelector('button[type="submit"]');
    
    reviewForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Показываем загрузку
        submitButton.disabled = true;
        submitButton.textContent = 'Отправка...';
        successMessage.classList.add('hidden');
        errorMessage.classList.add('hidden');
        
        // Собираем данные формы
        const formData = new FormData(reviewForm);
        formData.append('action', 'submit_review');
        formData.append('_ajax_nonce', '<?php echo wp_create_nonce('submit_review_nonce'); ?>');
        
        // Отправляем AJAX запрос
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Показываем сообщение об успехе
                successMessage.classList.remove('hidden');
                reviewForm.reset();
                
                // Сбрасываем рейтинг
                ratingValue.value = 5;
                stars.forEach((s, i) => {
                    if (i < 5) {
                        s.classList.remove('text-gray-600');
                        s.classList.add('text-yellow-400');
                    } else {
                        s.classList.remove('text-yellow-400');
                        s.classList.add('text-gray-600');
                    }
                });
            } else {
                // Показываем сообщение об ошибке
                errorMessage.classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            errorMessage.classList.remove('hidden');
        })
        .finally(() => {
            // Возвращаем кнопку в исходное состояние
            submitButton.disabled = false;
            submitButton.textContent = 'Отправить отзыв';
        });
    });
});
</script>

{{-- СКРИПТЫ --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Фото галерея
    const showMorePhotosBtn = document.getElementById('show-more-photos');
    if (showMorePhotosBtn) {
        showMorePhotosBtn.addEventListener('click', function() {
            document.querySelectorAll('.mobile-hidden-photo').forEach(function(photo) {
                photo.classList.remove('hidden');
                photo.style.opacity = '0';
                photo.style.transition = 'opacity 0.5s ease';
                requestAnimationFrame(() => {
                    photo.style.opacity = '1';
                });
            });
            showMorePhotosBtn.style.display = 'none';
        });
    }

    // 2.5 Переключатель тарифов (Аппартаменты / Выезд)
    const tariffTabButtons = document.querySelectorAll('.tariff-tab-btn');
    const tariffApartments = document.getElementById('tariff-tab-apartments');
    const tariffOutcall = document.getElementById('tariff-tab-outcall');

    if (tariffTabButtons.length && tariffApartments && tariffOutcall) {
        const setTariffTab = (target) => {
            const isApartments = target === 'apartments';

            tariffApartments.classList.toggle('hidden', !isApartments);
            tariffOutcall.classList.toggle('hidden', isApartments);

            tariffTabButtons.forEach((btn) => {
                const isActive = btn.dataset.tariffTabTarget === target;
                btn.classList.toggle('bg-[#cd1d46]', isActive);
                btn.classList.toggle('text-black', isActive);
                btn.classList.toggle('bg-white/5', !isActive);
                btn.classList.toggle('text-gray-300', !isActive);
            });
        };

        tariffTabButtons.forEach((btn) => {
            btn.addEventListener('click', function() {
                setTariffTab(this.dataset.tariffTabTarget);
            });
        });

        setTariffTab('apartments');
    }

    // 2. Описание (Читать полностью)
    const toggleDescBtn = document.getElementById('toggle-desc-btn');
    if (toggleDescBtn) {
        toggleDescBtn.addEventListener('click', function() {
            const shortBlock = document.getElementById('desc-short');
            const fullBlock = document.getElementById('desc-full');
            
            shortBlock.classList.add('hidden');
            fullBlock.classList.remove('hidden');
            
            fullBlock.style.opacity = '0';
            fullBlock.style.transition = 'opacity 0.5s ease';
            requestAnimationFrame(() => {
                fullBlock.style.opacity = '1';
            });

            toggleDescBtn.style.display = 'none';
        });

        // --- СКРЫТИЕ КОНТАКТНЫХ ССЫЛОК ---
        document.addEventListener('DOMContentLoaded', function() {
            // Находим все контактные ссылки
            const contactLinks = document.querySelectorAll('a[href*="wa.me"], a[href*="t.me"], a[href*="telegram"]');
            
            // Скрываем ссылки, заменяя их на span с таким же оформлением
            contactLinks.forEach(link => {
                const span = document.createElement('span');
                span.className = link.className;
                span.innerHTML = link.innerHTML;
                span.style.cursor = 'default'; // Обычный курсор, как у текста
                span.style.color = 'inherit'; // Наследуем цвет текста
                span.style.textDecoration = 'none'; // Убираем подчеркивание
                span.title = ''; // Убираем tooltip
                
                // Добавляем обработчик клика для перенаправления
                span.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Перенаправляем на оригинальную ссылку
                    window.open(link.href, '_blank');
                });
                
                // Заменяем ссылку на span
                link.parentNode.replaceChild(span, link);
            });
        });
    }
});
</script>
