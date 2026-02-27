@php
    // 1. Определяем устройство
    $is_mobile = wp_is_mobile();
    $default_city_slug = \App\Helpers\UrlHelpers::DEFAULT_CITY_SLUG;
    $default_city_name = \App\Helpers\CityCatalog::getDefaultCityName();

    // 2. Определяем текущий город (если нет — дефолтный)
    $city_obj = get_current_city();
    $current_slug = $city_obj ? $city_obj->slug : $default_city_slug;
    
    // 3. Ссылки
    // Логотип: Для дефолтного города -> '/', для других -> '/city/'
    $logo_url = ($current_slug === $default_city_slug) ? home_url('/') : home_url("/{$current_slug}/");
    
    // Online: ВСЕГДА с городом
    $online_url = home_url("/{$current_slug}/onlajn/");

    // 4. Получаем текущий путь
    $current_request_path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');

    // 5. Проверка для логотипа (Главная)
    $logo_path_clean = trim(parse_url($logo_url, PHP_URL_PATH), '/');
    $is_home_page = ($current_request_path === $logo_path_clean);
    $site_title = $siteName ?? get_bloginfo('name');
    $header_logo = get_theme_file_uri('/resources/images/logo.png') . '?v=20260227';

    // 6. Фильтр для меню WP
    add_filter('nav_menu_link_attributes', function($atts, $item, $args, $depth) use ($current_slug, $current_request_path, $default_city_slug) {
        if ($args->theme_location !== 'primary_navigation') return $atts;

        $default_classes = 'text-sm font-medium capitalize tracking-widest text-white hover:text-[#cd1d46] transition-colors';
        $active_classes  = 'text-sm font-medium capitalize tracking-widest text-white cursor-default';

        if (isset($atts['href'])) {
            $original_url = $atts['href'];
            
            if (strpos($original_url, home_url()) === 0 && !strpos($original_url, '#') && !strpos($original_url, 'tel:') && !strpos($original_url, 'mailto:')) {
                $path = str_replace(home_url(), '', $original_url);
                $path = trim($path, '/');
                
                // Главная (пустой путь)
                if (empty($path)) {
                    $atts['href'] = ($current_slug === $default_city_slug) ? home_url('/') : home_url("/{$current_slug}/");
                } 
                // Ссылка Online (всегда добавляем город)
                elseif ($path === 'onlajn') {
                    $atts['href'] = home_url("/{$current_slug}/onlajn/");
                } 
                // Список услуг всегда в формате /{city}/uslugi/
                elseif ($path === 'uslugi' || $path === 'service' || preg_match('#^[^/]+/(uslugi|service)$#', $path)) {
                    $atts['href'] = home_url("/{$current_slug}/uslugi/");
                }
                // Остальные внутренние страницы
                else {
                    $path_parts = explode('/', $path);
                    // Если в пути еще нет слага города, добавляем его
                    if ($path_parts[0] !== $current_slug) {
                        $atts['href'] = home_url("/{$current_slug}/{$path}/");
                    } else {
                        $atts['href'] = home_url("/{$path}/");
                    }
                }
            }

            // Проверка на активность ссылки
            $link_path = trim(parse_url($atts['href'], PHP_URL_PATH), '/');
            
            if ($link_path === $current_request_path) {
                unset($atts['href']);
                $atts['class'] = $active_classes;
            } else {
                $atts['class'] = $default_classes;
            }
        }
        return $atts;
    }, 10, 4);

    // Проверка активности Online для ручной ссылки
    $online_path = trim(parse_url($online_url, PHP_URL_PATH), '/');
    $is_online_active = ($online_path === $current_request_path);

    // Классы для ссылок меню
    $menu_classes_default = 'text-sm font-medium capitalize tracking-widest text-white hover:text-[#cd1d46] transition-colors';
    $menu_classes_active  = 'text-sm font-medium capitalize tracking-widest text-white cursor-default';

    // Классы для десктопа
    $link_classes_default = 'text-sm font-medium capitalize tracking-widest text-white hover:text-[#cd1d46] transition-colors flex items-center gap-2';
    $link_classes_active  = 'text-sm font-medium capitalize tracking-widest text-white cursor-default flex items-center gap-2';
    
    // Классы для мобилки
    $mobile_classes_default = 'text-lg font-medium capitalize tracking-widest text-white hover:text-[#cd1d46] transition-colors flex justify-center items-center gap-2';
    $mobile_classes_active  = 'text-lg font-medium capitalize tracking-widest text-white cursor-default flex justify-center items-center gap-2';

    // Десктоп-меню: берём top-level пункты и готовим URL/active вручную
    $desktop_menu_items = [];
    $menu_locations = get_nav_menu_locations();
    $primary_menu_id = $menu_locations['primary_navigation'] ?? null;

    if ($primary_menu_id) {
        $raw_menu_items = wp_get_nav_menu_items($primary_menu_id);

        if (is_array($raw_menu_items)) {
            foreach ($raw_menu_items as $menu_item) {
                if ((int) ($menu_item->menu_item_parent ?? 0) !== 0) {
                    continue;
                }

                $item_url = $menu_item->url ?? '';

                if (
                    strpos($item_url, home_url()) === 0
                    && strpos($item_url, '#') === false
                    && strpos($item_url, 'tel:') === false
                    && strpos($item_url, 'mailto:') === false
                ) {
                    $path = str_replace(home_url(), '', $item_url);
                    $path = trim($path, '/');

                    if (empty($path)) {
                        $item_url = ($current_slug === $default_city_slug) ? home_url('/') : home_url("/{$current_slug}/");
                    } elseif ($path === 'onlajn') {
                        $item_url = home_url("/{$current_slug}/onlajn/");
                    } elseif ($path === 'uslugi' || $path === 'service' || preg_match('#^[^/]+/(uslugi|service)$#', $path)) {
                        $item_url = home_url("/{$current_slug}/uslugi/");
                    } else {
                        $path_parts = explode('/', $path);
                        if (($path_parts[0] ?? '') !== $current_slug) {
                            $item_url = home_url("/{$current_slug}/{$path}/");
                        } else {
                            $item_url = home_url("/{$path}/");
                        }
                    }
                }

                $item_path = trim(parse_url($item_url, PHP_URL_PATH), '/');
                $desktop_menu_items[] = [
                    'title' => $menu_item->title ?? '',
                    'url' => $item_url,
                    'is_active' => ($item_path === $current_request_path),
                ];
            }
        }
    }

    // Fallback: если пункта "Главная" нет по названию, добавляем его первым.
    $expected_home_url = ($current_slug === $default_city_slug)
        ? home_url('/')
        : home_url("/{$current_slug}/");
    $expected_home_path = trim(parse_url($expected_home_url, PHP_URL_PATH), '/');

    $has_home_menu_item = false;
    foreach ($desktop_menu_items as $menu_item) {
        $menu_item_title = mb_strtolower(trim((string) ($menu_item['title'] ?? '')));

        if ($menu_item_title === 'главная' || $menu_item_title === 'home') {
            $has_home_menu_item = true;
            break;
        }
    }

    if (!$has_home_menu_item) {
        array_unshift($desktop_menu_items, [
            'title' => 'Главная',
            'url' => $expected_home_url,
            'is_active' => ($expected_home_path === $current_request_path),
        ]);
    }

    // Dropdown "Доступность": объединяем "У себя" и "Выезд" по slug, а не по индексам.
    $price_item_a = null; // incall / у себя
    $price_item_b = null; // outcall / выезд
    $price_item_a_index = null;
    $price_item_b_index = null;

    foreach ($desktop_menu_items as $menu_index => $menu_item) {
        $menu_path = trim(parse_url($menu_item['url'] ?? '', PHP_URL_PATH), '/');
        $menu_parts = explode('/', $menu_path);
        $menu_slug = strtolower(end($menu_parts) ?: '');

        if ($price_item_a === null && in_array($menu_slug, ['prostitutki-priyom', 'incall'], true)) {
            $price_item_a = $menu_item;
            $price_item_a_index = $menu_index;
            continue;
        }

        if ($price_item_b === null && in_array($menu_slug, ['prostitutki-na-vyezd', 'outcall'], true)) {
            $price_item_b = $menu_item;
            $price_item_b_index = $menu_index;
        }
    }

    $can_group_price_dropdown = $price_item_a !== null && $price_item_b !== null;
    $availability_skip_indices = $can_group_price_dropdown ? [$price_item_a_index, $price_item_b_index] : [];
    $availability_insert_index = $can_group_price_dropdown ? min($availability_skip_indices) : null;
    $price_dropdown_active = $can_group_price_dropdown && (
        !empty($price_item_a['is_active']) || !empty($price_item_b['is_active'])
    );

    // Отдельный dropdown "Цена": только VIP + deshyovye
    $price_menu_vip_item = null;
    $price_menu_deshyovye_item = null;
    $price_menu_vip_index = null;
    $price_menu_deshyovye_index = null;

    foreach ($desktop_menu_items as $menu_index => $menu_item) {
        // Элементы блока "Доступность" пропускаем из "Цена"
        if ($can_group_price_dropdown && in_array($menu_index, $availability_skip_indices, true)) {
            continue;
        }

        $menu_path = trim(parse_url($menu_item['url'] ?? '', PHP_URL_PATH), '/');
        $menu_parts = explode('/', $menu_path);
        $menu_slug = strtolower(end($menu_parts) ?: '');

        if ($price_menu_vip_item === null && $menu_slug === 'vip') {
            $price_menu_vip_item = $menu_item;
            $price_menu_vip_index = $menu_index;
            continue;
        }

        if ($price_menu_deshyovye_item === null && $menu_slug === 'deshyovye') {
            $price_menu_deshyovye_item = $menu_item;
            $price_menu_deshyovye_index = $menu_index;
        }
    }

    $can_group_price_menu = $price_menu_vip_item !== null && $price_menu_deshyovye_item !== null;
    $price_menu_items = $can_group_price_menu ? [$price_menu_vip_item, $price_menu_deshyovye_item] : [];
    $price_menu_skip_indices = $can_group_price_menu ? [$price_menu_vip_index, $price_menu_deshyovye_index] : [];
    $price_menu_insert_index = $can_group_price_menu ? min($price_menu_skip_indices) : null;
    $price_menu_active = $can_group_price_menu && (
        !empty($price_menu_vip_item['is_active']) || !empty($price_menu_deshyovye_item['is_active'])
    );

@endphp

<header class="bg-[#0e101599] backdrop-blur-sm mx-auto rounded-xl lg:border lg:border-[#1f232e] text-white shadow-lg sticky top-0 lg:top-3 z-50 w-full lg:w-9/10 font-serif">
    <div class="container mx-auto px-4 py-1">
        <div class="flex items-center h-15 relative gap-3">
            {{-- 1. ЛЕВАЯ ЧАСТЬ: место под логотип (ПК + моб) --}}
            <div class="shrink-0 w-[6.5rem] sm:w-[8rem] lg:w-[9.5rem]">
                @if($is_home_page)
                    <span class="flex items-center h-10">
                        @if(!empty($header_logo))
                            <img src="{{ $header_logo }}" alt="{{ $site_title }}" class="h-8 sm:h-9 w-auto object-contain" />
                        @else
                            <span class="text-sm sm:text-base font-semibold tracking-[0.12em] uppercase">{{ $site_title }}</span>
                        @endif
                    </span>
                @else
                    <a href="{{ $logo_url }}" class="group flex items-center h-10">
                        @if(!empty($header_logo))
                            <img src="{{ $header_logo }}" alt="{{ $site_title }}" class="h-8 sm:h-9 w-auto object-contain group-hover:opacity-85 transition-opacity" />
                        @else
                            <span class="text-sm sm:text-base font-semibold tracking-[0.12em] uppercase group-hover:text-[#cd1d46] transition-colors">{{ $site_title }}</span>
                        @endif
                    </a>
                @endif
            </div>

            {{-- 2. НАВИГАЦИЯ (Только ПК) --}}
            @if (!$is_mobile)
                @if (has_nav_menu('primary_navigation'))
                    <nav class="flex flex-1 justify-center px-6" aria-label="Main Navigation">
                        <ul class="flex items-center gap-6 xl:gap-8 text-sm font-medium capitalize tracking-widest text-white">
                            @foreach($desktop_menu_items as $index => $item)
                                @if($can_group_price_dropdown && $index === $availability_insert_index)
                                    <li class="relative">
                                        <button id="price-dropdown-btn"
                                                class="{{ $price_dropdown_active ? $menu_classes_active : $menu_classes_default }} group flex items-center gap-2 focus:outline-none"
                                                aria-expanded="false">
                                            <span>Доступность</span>
                                            <svg id="price-dropdown-chevron" class="w-4 h-4 text-[#cd1d46] group-hover:text-white transition-transform transition-colors duration-300 transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>

                                        <div id="price-dropdown-list"
                                             class="hidden absolute left-0 top-full mt-4 w-64 bg-[#0e101599] backdrop-blur-xl border border-[#1f232e] shadow-[0_20px_50px_-25px_rgba(0,0,0,0.95)] rounded-lg z-[60] overflow-hidden">
                                            <ul class="py-1">
                                                @foreach([$price_item_a, $price_item_b] as $price_item)
                                                    @if($price_item)
                                                        <li class="border-b border-[#293142]/70 last:border-0">
                                                            @if($price_item['is_active'])
                                                                <span class="{{ $menu_classes_active }} flex items-center px-5 py-3">
                                                                    {{ $price_item['title'] }}
                                                                </span>
                                                            @else
                                                                <a href="{{ $price_item['url'] }}"
                                                                   class="{{ $menu_classes_default }} flex items-center px-5 py-3 hover:bg-[#cd1d46] hover:!text-white transition-colors">
                                                                    {{ $price_item['title'] }}
                                                                </a>
                                                            @endif
                                                        </li>
                                                    @endif
                                                @endforeach
                                            </ul>
                                        </div>
                                    </li>
                                    @continue
                                @endif

                                @if($can_group_price_dropdown && in_array($index, $availability_skip_indices, true) && $index !== $availability_insert_index)
                                    @continue
                                @endif

                                @if($can_group_price_menu && $index === $price_menu_insert_index)
                                    <li class="relative">
                                        <button id="price-menu-dropdown-btn"
                                                class="{{ $price_menu_active ? $menu_classes_active : $menu_classes_default }} group flex items-center gap-2 focus:outline-none"
                                                aria-expanded="false">
                                            <span>Цена</span>
                                            <svg id="price-menu-dropdown-chevron" class="w-4 h-4 text-[#cd1d46] group-hover:text-white transition-transform transition-colors duration-300 transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </button>

                                        <div id="price-menu-dropdown-list"
                                             class="hidden absolute left-0 top-full mt-4 w-64 bg-[#0e101599] backdrop-blur-xl border border-[#1f232e] shadow-[0_20px_50px_-25px_rgba(0,0,0,0.95)] rounded-lg z-[60] overflow-hidden">
                                            <ul class="py-1">
                                                @foreach($price_menu_items as $price_menu_item)
                                                    <li class="border-b border-[#293142]/70 last:border-0">
                                                        @if($price_menu_item['is_active'])
                                                            <span class="{{ $menu_classes_active }} flex items-center px-5 py-3">
                                                                {{ $price_menu_item['title'] }}
                                                            </span>
                                                        @else
                                                            <a href="{{ $price_menu_item['url'] }}"
                                                               class="{{ $menu_classes_default }} flex items-center px-5 py-3 hover:bg-[#cd1d46] hover:!text-white transition-colors">
                                                                {{ $price_menu_item['title'] }}
                                                            </a>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </li>
                                    @continue
                                @endif

                                @if($can_group_price_menu && in_array($index, $price_menu_skip_indices, true))
                                    @continue
                                @endif

                                <li>
                                    @if($item['is_active'])
                                        <span class="{{ $menu_classes_active }}">{{ $item['title'] }}</span>
                                    @else
                                        <a href="{{ $item['url'] }}" class="{{ $menu_classes_default }}">{{ $item['title'] }}</a>
                                    @endif
                                </li>
                            @endforeach

                            {{-- Ссылка Online (выводится с учетом города) --}}
                            <li>
                                @if($is_online_active)
                                    <span class="{{ $link_classes_active }}">
                                        <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                        Online
                                    </span>
                                @else
                                    <a href="{{ $online_url }}" class="{{ $link_classes_default }}">
                                        <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                        Online
                                    </a>
                                @endif
                            </li>
                        </ul>
                    </nav>
                @endif
            @endif

            {{-- 2.5. ВЫБОР ГОРОДА (Только Мобилка) --}}
            @if($is_mobile)
                @if(!empty($cities) && !is_wp_error($cities))
                    <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 z-10">
                        <div class="relative">
                            <button id="city-dropdown-mobile-btn"
                                    class="group inline-flex items-center gap-2 rounded-full border border-[#2a3142] bg-[#141925]/90 px-4 py-2 text-[10px] font-semibold uppercase tracking-[0.2em] text-white hover:border-[#cd1d46] hover:bg-[#1b2231] transition-colors focus:outline-none"
                                    aria-expanded="false">
                                @php
                                    $display_name = $city_obj ? $city_obj->name : $default_city_name;
                                @endphp
                                <span class="text-[#9da9c2]">Город</span>
                                <span class="max-w-[110px] truncate text-white">{{ $display_name }}</span>
                            </button>

                            <div id="city-dropdown-mobile-list"
                                 class="hidden absolute left-1/2 top-full mt-3 w-60 -translate-x-1/2 bg-[#0e101599] backdrop-blur-xl border border-[#1f232e] shadow-[0_20px_50px_-25px_rgba(0,0,0,0.95)] rounded-lg z-[60] overflow-hidden">
                                <div class="max-h-[250px] overflow-y-auto py-1 scrollbar-thin scrollbar-thumb-[#3b455d] scrollbar-track-transparent">
                                    @foreach($cities as $city)
                                        @php
                                            $city_link = ($city->slug === $default_city_slug) ? home_url('/') : home_url("/{$city->slug}/");
                                        @endphp
                                        <a href="{{ $city_link }}"
                                           class="flex justify-between items-center px-4 py-2.5 text-white hover:bg-[#cd1d46] hover:!text-white transition-colors border-b border-[#293142]/70 last:border-0 text-sm">
                                            <span class="tracking-wide capitalize font-medium">{{ $city->name }}</span>
                                            @if($city->count > 0)
                                                <span class="text-[10px] text-[#98a4bc] group-hover:text-white transition-colors">{{ $city->count }}</span>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            {{-- 3. ПРАВАЯ ЧАСТЬ --}}
            <div class="ml-auto flex items-center justify-end gap-4 md:gap-6 shrink-0 w-[6.5rem] sm:w-[8rem] lg:w-auto">

                {{-- === ДРОПДАУН ГОРОДОВ (Только ПК) === --}}
                @if(!$is_mobile)
                    @if(!empty($cities) && !is_wp_error($cities))
                        <div class="relative block">
                            <button id="city-dropdown-btn" 
                                    class="group flex items-center gap-2 text-xs md:text-sm font-bold capitalize tracking-widest text-white hover:text-[#cd1d46] transition-colors focus:outline-none py-2" 
                                    aria-expanded="false">
                                @php
                                    $display_name = $city_obj ? $city_obj->name : $default_city_name;
                                @endphp
                                <span>{{ $display_name }}</span>
                                <svg id="city-chevron" class="w-4 h-4 text-[#cd1d46] group-hover:text-white transition-transform transition-colors duration-300 transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div id="city-dropdown-list" 
                                 class="hidden absolute right-0 top-full mt-4 w-64 bg-[#0e101599] backdrop-blur-xl border border-[#1f232e] shadow-[0_20px_50px_-25px_rgba(0,0,0,0.95)] rounded-lg z-[60] overflow-hidden">
                                <div class="max-h-[300px] overflow-y-auto py-1 scrollbar-thin scrollbar-thumb-[#3b455d] scrollbar-track-transparent">
                                    @foreach($cities as $city)
                                        @php
                                            $city_link = ($city->slug === $default_city_slug) ? home_url('/') : home_url("/{$city->slug}/");
                                        @endphp
                                        <a href="{{ $city_link }}" 
                                           class="flex justify-between items-center px-5 py-3 text-white hover:bg-[#cd1d46] hover:!text-white transition-colors border-b border-[#293142]/70 last:border-0 group">
                                            <span class="text-sm tracking-wide capitalize font-medium">{{ $city->name }}</span>
                                            @if($city->count > 0)
                                                <span class="text-[10px] text-[#98a4bc] group-hover:text-white transition-colors">{{ $city->count }}</span>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                @endif

                {{-- === МОБИЛЬНЫЕ ЭЛЕМЕНТЫ (Только Мобилка) === --}}
                @if($is_mobile)
                    <button id="mobile-menu-btn" class="text-white focus:outline-none p-2 relative z-50">
                        <svg id="icon-menu" class="w-6 h-6 block transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        <svg id="icon-close" class="w-6 h-6 hidden transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- МОБИЛЬНОЕ МЕНЮ --}}
    @if($is_mobile)
        <div id="mobile-menu" class="hidden absolute left-0 w-full bg-[#0e1015f2] backdrop-blur-xl border-t border-[#2a3142] shadow-[0_24px_55px_-30px_rgba(0,0,0,0.95)] pb-8 max-h-[calc(100vh-6rem)] overflow-y-auto">
            <nav class="container mx-auto px-4 py-6 flex flex-col gap-6 text-center">
                
                @if (has_nav_menu('primary_navigation'))
                    <ul class="flex flex-col gap-2 text-lg font-medium capitalize tracking-widest bg-[#0e1015e6] rounded-lg p-2">
                        @if(!$has_home_menu_item)
                            <li>
                                @if($is_home_page)
                                    <span class="{{ $mobile_classes_active }}">Главная</span>
                                @else
                                    <a href="{{ $logo_url }}" class="{{ $mobile_classes_default }}">Главная</a>
                                @endif
                            </li>
                        @endif

                        {!! wp_nav_menu([
                            'theme_location' => 'primary_navigation',
                            'menu_class' => 'flex flex-col gap-6',
                            'echo' => false,
                            'container' => false,
                            'items_wrap' => '%3$s',
                        ]) !!}
                        
                        <li>
                            @if($is_online_active)
                                <span class="{{ $mobile_classes_active }}">
                                    <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                    Online
                                </span>
                            @else
                                <a href="{{ $online_url }}" class="{{ $mobile_classes_default }}">
                                    <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                                    Online
                                </a>
                            @endif
                        </li>
                    </ul>
                @endif

            </nav>
        </div>
    @endif
</header>

{{-- JS (Без изменений) --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const setupDropdown = (btnId, listId, chevronId = null) => {
            const btn = document.getElementById(btnId);
            const list = document.getElementById(listId);
            const chevron = chevronId ? document.getElementById(chevronId) : null;
            if (btn && list) {
                const toggle = (show) => {
                    if (show) {
                        list.classList.remove('hidden');
                        if (chevron) chevron.classList.add('rotate-180');
                        btn.setAttribute('aria-expanded', 'true');
                    } else {
                        list.classList.add('hidden');
                        if (chevron) chevron.classList.remove('rotate-180');
                        btn.setAttribute('aria-expanded', 'false');
                    }
                };
                btn.addEventListener('click', (e) => { toggle(list.classList.contains('hidden')); console.log('btn'); });
                document.addEventListener('click', (e) => { if (!list.classList.contains('hidden') && !btn.contains(e.target) && !list.contains(e.target)) toggle(false); console.log('document'); });
                document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !list.classList.contains('hidden')) toggle(false); });
            }
        };
        setupDropdown('price-dropdown-btn', 'price-dropdown-list', 'price-dropdown-chevron');
        setupDropdown('price-menu-dropdown-btn', 'price-menu-dropdown-list', 'price-menu-dropdown-chevron');
        setupDropdown('city-dropdown-btn', 'city-dropdown-list', 'city-chevron');
        setupDropdown('city-dropdown-mobile-btn', 'city-dropdown-mobile-list');

        const menuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        if (menuBtn && mobileMenu) {
            menuBtn.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');
                document.getElementById('icon-menu')?.classList.toggle('hidden');
                document.getElementById('icon-close')?.classList.toggle('hidden');
                document.getElementById('icon-close')?.classList.toggle('block');
                document.body.style.overflow = mobileMenu.classList.contains('hidden') ? '' : 'hidden';
            });
        }
    });
</script>
