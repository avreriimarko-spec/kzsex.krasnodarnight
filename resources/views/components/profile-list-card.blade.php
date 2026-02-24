@props(['post', 'details' => [], 'badges' => [], 'lcp' => false])

@php
    // --- Данные анкеты ---
    $age = get_field('age');
    $height = get_field('height');
    $weight = get_field('weight');
    
    // --- Контакты ---
    $globalTg = get_field('global_tg', 'option');
    $globalWa = get_field('global_wa', 'option');
    
    // Логика очистки Telegram
    $tgLink = null;
    if ($globalTg) {
        if (strpos($globalTg, 'http') !== false) {
            $tgLink = $globalTg;
        } else {
            $tgClean = str_replace('@', '', $globalTg);
            $tgLink = "https://t.me/{$tgClean}";
        }
    }

    // Логика очистки WhatsApp
    $waLink = null;
    if ($globalWa) {
        if (strpos($globalWa, 'http') !== false) {
            $waLink = $globalWa;
        } else {
            $waClean = preg_replace('/[^0-9]/', '', $globalWa);
            $waLink = "https://wa.me/{$waClean}";
        }
    }

    $contacts = [
        'tg'    => $tgLink,
        'wa'    => $waLink,
    ];
    
    // --- Остальные данные ---
    $city_terms = get_the_terms(get_the_ID(), 'city');
    $city_name = !empty($city_terms) && !is_wp_error($city_terms) ? $city_terms[0]->name : null;
    
    $profile_name = get_the_title();
    
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
    
    $alt_text = $prefix . ($city_name ? " {$city_name}" : "") . " {$profile_name}" . ($age ? " {$age} лет" : "");
    
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
    
    $breast_terms = get_the_terms(get_the_ID(), 'breast_size');
    $breast = null;
    if ($breast_terms && !is_wp_error($breast_terms)) {
        $breast_name = $breast_terms[0]->name;
        if (preg_match('/([A-Z])$/', $breast_name, $matches)) {
            $letter = $matches[1];
            $breastMap = ['A'=>'1', 'B'=>'2', 'C'=>'3', 'D'=>'4', 'E'=>'5', 'F'=>'6', 'G'=>'7', 'H'=>'8'];
            $breast = $breastMap[$letter] ?? $letter;
        }
    }

    $price_group = get_field('price');
    $price_1h_out = $price_group['price_1h_out'] ?? null;
    $price_1h = $price_group['price_1h'] ?? null;
    $price_2h_out = $price_1h_out ? $price_1h_out * 2 : null;
    $price_2h = $price_1h ? $price_1h * 2 : null;
    
    $gallery = get_field('gallery');
    $photo_count = 1 + ($gallery && is_array($gallery) ? count($gallery) : 0);

    // Подготавливаем полный текст описания
    $full_content = strip_tags(get_the_content());
    $has_content = !empty(trim($full_content));
    
    // Ограничиваем описание до 300 символов
    $truncated_content = '';
    if ($has_content) {
        if (mb_strlen($full_content) > 300) {
            $truncated_content = mb_substr($full_content, 0, 300) . '...';
        } else {
            $truncated_content = $full_content;
        }
    }
    
    // Получаем услуги анкеты
    $services = get_the_terms(get_the_ID(), 'service');
    $services_list = [];
    if ($services && !is_wp_error($services)) {
        // Берем немного больше услуг, так как места теперь больше
        $services_list = array_slice($services, 0, 5); 
    }
@endphp

{{-- КАРТОЧКА --}}
<article class="bg-[#050505] border border-gray-800 hover:border-gray-600 transition-colors shadow-lg overflow-hidden flex flex-col group/card">
    
    {{-- ВЕРХНЯЯ ЧАСТЬ (Разделенная на 2 колонки) --}}
    <div class="flex flex-row items-stretch">
        
        {{-- 1. ЛЕВАЯ КОЛОНКА: ФОТО + КОНТАКТЫ (45%) --}}
        <div class="w-5/12 relative flex flex-col shrink-0">
            
            {{-- Ссылка на фото --}}
            <a href="{{ profile_url(get_the_ID()) }}" class="block relative w-full h-[300px] shrink-0 group overflow-hidden">
                @if(has_post_thumbnail())
                    <img src="{{ get_the_post_thumbnail_url(null, 'profile_single') }}" 
                         alt="{{ $alt_text }}" 
                         class="w-full h-full object-cover object-top transition-transform duration-700 group-hover:scale-105"
                         loading="lazy">
                @else
                    <div class="w-full h-full bg-gray-900 flex items-center justify-center text-gray-700 text-3xl">?</div>
                @endif

                {{-- СЧЕТЧИК --}}
                <div class="absolute top-2 left-2 z-10">
                    <div class="bg-black/60 backdrop-blur-sm text-black text-[10px] px-2 py-1 rounded font-bold shadow-sm">
                     Фото {{ $photo_count }}
                    </div>
                </div>

                {{-- БЕЙДЖИ --}}
                <div class="absolute top-0 right-0 flex flex-col items-end pointer-events-none z-10">
                    @foreach ($badges as $badge)
                        @php
                            $badgeConfig = match($badge) {
                                'New'         => ['class' => 'bg-yellow-500 text-black', 'label' => 'Новая'],
                                'Verified'    => ['class' => 'bg-green-600 text-black',   'label' => 'Проверена'],
                                'VIP'         => ['class' => 'bg-[#cd1d46] text-black',   'label' => 'ВИП'],
                                'Independent' => ['class' => 'bg-black/60 text-black',    'label' => 'Индивидуалка'],
                                default       => ['class' => 'bg-black/60 text-black',    'label' => $badge],
                            };
                        @endphp
                        <span class="{{ $badgeConfig['class'] }} backdrop-blur-sm text-[9px] px-2 py-0.5 capitalize mb-px font-bold shadow-sm">
                            {{ $badgeConfig['label'] }}
                        </span>
                    @endforeach
                </div>
            </a>

            {{-- КОНТАКТЫ (Внизу левой колонки) --}}
            <div class="bg-black p-2 z-20 relative border-t border-gray-900">
                <div class="flex flex-col gap-2 w-full">
                    
                    {{-- Telegram --}}
                    @if ($contacts['tg'])
                        <a href="{{ $contacts['tg'] }}" 
                           target="_blank" 
                           class="flex items-center justify-center gap-2 w-full py-1.5 bg-[#2AABEE] text-black hover:bg-[#229ED9] transition-all shadow-sm hover:shadow-md cursor-pointer group/btn"
                           aria-label="Telegram">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12.068 12.068 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z" /></svg>
                            <span class="text-[10px] font-bold capitalize tracking-wide">Telegram</span>
                        </a>
                    @endif
                    
                    {{-- WhatsApp --}}
                    @if ($contacts['wa'])
                        <a href="{{ $contacts['wa'] }}" 
                           target="_blank" 
                           class="flex items-center justify-center gap-2 w-full py-1.5 bg-[#25D366] text-black hover:bg-[#20bd5a] transition-all shadow-sm hover:shadow-md cursor-pointer group/btn"
                           aria-label="WhatsApp">
                            <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.008-.57-.008-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                            <span class="text-[10px] font-bold capitalize tracking-wide">WhatsApp</span>
                        </a>
                    @endif
                    
                    @if(!$contacts['tg'] && !$contacts['wa'])
                         <span class="text-gray-700 text-[10px] text-center">Нет соцсетей</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- 2. ПРАВАЯ КОЛОНКА: ИНФО + УСЛУГИ (55%) --}}
        <div class="w-7/12 p-3 flex flex-col bg-black border-l border-gray-800">
            
            <div class="mb-2 shrink-0">
                <div class="flex items-center gap-2 mb-1">
                    <h3 class="text-lg font-serif text-black capitalize tracking-widest leading-none truncate">
                         {{ get_the_title() }}
                    </h3>
                    @php $is_online = get_field('online'); @endphp
                    @if($is_online)
                        <span class="px-2 py-1 bg-green-500 text-black text-[10px] font-bold capitalize shrink-0">Online</span>
                    @endif
                </div>
            </div>

            {{-- Параметры --}}
            <div class="mb-2 shrink-0">
                <h4 class="text-black text-base font-serif mb-1 leading-none">Параметры</h4>
                <ul class="text-[11px] text-gray-400 space-y-0.5 font-light">
                    <li class="flex justify-between"><span>Возраст:</span> <span class="text-gray-200">{{ $age ? $age . ' лет' : '-' }}</span></li>
                    <li class="flex justify-between"><span>Рост:</span> <span class="text-gray-200">{{ $height ? $height . ' см' : '-' }}</span></li>
                    <li class="flex justify-between"><span>Вес:</span> <span class="text-gray-200">{{ $weight ? $weight . ' кг' : '-' }}</span></li>
                    <li class="flex justify-between"><span>Размер груди:</span> <span class="text-gray-200">{{ $breast ? $breast : '-' }}</span></li>
                    <li class="flex justify-between"><span>Город:</span> <span class="text-gray-200">{{ $city_name ?: '-' }}</span></li>
                </ul>
            </div>

            <div class="h-px bg-white/20 w-full mb-2 shrink-0"></div>

            {{-- Цены --}}
            <div class="mb-2 shrink-0">
                <h4 class="text-black text-base font-serif mb-1 leading-none">Цена</h4>
                <table class="w-full text-[11px] text-gray-400 border-collapse border border-gray-700">
                    <thead>
                        <tr class="border-b border-gray-700">
                            <th class="text-left py-1 px-1 font-medium text-gray-300 border-r border-gray-700">Услуга</th>
                            <th class="text-right py-1 px-1 font-medium text-gray-300 border-r border-gray-700">1 час</th>
                            <th class="text-right py-1 px-1 font-medium text-gray-300">2 часа</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($price_1h_out)
                            <tr class="border-b border-gray-800">
                                <td class="py-1 px-1 text-gray-300 border-r border-gray-700">Выезд</td>
                                <td class="py-1 px-1 text-right text-gray-200 border-r border-gray-700">{{ number_format($price_1h_out, 0, '.', ' ') }}</td>
                                <td class="py-1 px-1 text-right text-gray-200">{{ number_format($price_2h_out, 0, '.', ' ') }}</td>
                            </tr>
                        @endif
                        @if($price_1h)
                            <tr class="border-b border-gray-800">
                                <td class="py-1 px-1 text-gray-300 border-r border-gray-700">Прием</td>
                                <td class="py-1 px-1 text-right text-gray-200 border-r border-gray-700">{{ number_format($price_1h, 0, '.', ' ') }}</td>
                                <td class="py-1 px-1 text-right text-gray-200">{{ number_format($price_2h, 0, '.', ' ') }}</td>
                            </tr>
                        @endif
                         @if(!$price_1h_out && !$price_1h)
                            <tr><td colspan="3" class="py-1 px-1 text-center">По запросу</td></tr>
                        @endif
                    </tbody>
                </table>
            </div>
            
            {{-- УСЛУГИ (Остаются в правой колонке) --}}
            <div class="pt-2 border-t border-white/20 mt-2">
                <h4 class="text-black text-base font-serif mb-1 leading-none">Услуги</h4>
                @if(!empty($services_list))
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($services_list as $service)
                            <span class="text-[9px] bg-[#cd1d46]/10 text-[#cd1d46] px-1.5 py-0.5 rounded border border-[#cd1d46]/30 capitalize tracking-wide">
                                {{ $service->name }}
                            </span>
                        @endforeach
                        @if(count($services) > 5)
                            <span class="text-[9px] text-gray-500 italic flex items-center">+{{ count($services) - 5 }}</span>
                        @endif
                    </div>
                @else
                    <p class="text-[10px] text-gray-600 mb-2">-</p>
                @endif
            </div>

        </div>
    </div>

    {{-- НИЖНЯЯ ЧАСТЬ: ОПИСАНИЕ (Во всю ширину, только если есть текст) --}}
    @if($has_content && !empty($truncated_content))
        <div class="w-full bg-black border-t border-gray-800 p-2 text-center">
            
            {{-- Заголовок по центру --}}
            <div class="text-left mb-1">
                <span class="text-[#cd1d46] text-[11px] capitalize px-2">
                    Описание
                </span>
            </div>
            
            {{-- Блок с текстом (ограниченный до 300 символов) --}}
            <div class="text-[11px] text-gray-400 leading-relaxed pt-1 text-left px-2">
                {{ $truncated_content }}
            </div>
        </div>
    @endif

</article>

@once
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Ссылки на карточку
            const listCards = document.querySelectorAll('.profile-list-card a[href]');
            listCards.forEach(link => {
                const originalHref = link.getAttribute('href');
                if (originalHref) {
                    link.setAttribute('data-href', originalHref);
                    link.removeAttribute('href');
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        window.location.href = originalHref;
                    });
                    link.style.cursor = 'pointer';
                }
            });
            // JS для скрытия/раскрытия описания удален, так как оно теперь статично
        });
    </script>
@endonce