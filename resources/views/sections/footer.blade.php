@php
    // 1. Ссылка для Логотипа (всегда на главную без города)
    $logo_url = home_url('/');

    // 2. Текущий путь (для проверки активных ссылок)
    $current_request_path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    $default_city_slug = \App\Helpers\UrlHelpers::DEFAULT_CITY_SLUG;
    $city_obj = get_current_city();
    $current_city_slug = $city_obj ? $city_obj->slug : $default_city_slug;

    // 3. Проверяем, на главной ли мы (для логотипа)
    $logo_path_clean = trim(parse_url($logo_url, PHP_URL_PATH), '/');
    $is_home_page = ($current_request_path === $logo_path_clean);
    $site_description = trim((string) get_bloginfo('description'));

    // 4. Фильтр для меню футера
    add_filter('nav_menu_link_attributes', function($atts, $item, $args, $depth) use ($current_request_path, $current_city_slug) {
        // Применяем только к навигации футера
        if (($args->theme_location ?? null) !== 'footer_navigation') {
            return $atts;
        }

        $is_column_title = ((int) ($item->menu_item_parent ?? 0) === 0);
        $title_default_class = 'footer-nav-title-link';
        $title_active_class = 'footer-nav-title-link footer-nav-title-link-active';
        $link_default_class = 'footer-nav-link';
        $link_active_class = 'footer-nav-link footer-nav-link-active';
        $atts['class'] = $is_column_title ? $title_default_class : $link_default_class;

        if (isset($atts['href'])) {
            $original_url = $atts['href'];

            // Для футера не добавляем город к ссылкам (кроме специальных случаев)
            if (strpos($original_url, home_url()) === 0 && !strpos($original_url, '#') && !strpos($original_url, 'tel:') && !strpos($original_url, 'mailto:')) {
                $path = str_replace(home_url(), '', $original_url);
                $path = trim($path, '/');

                // Специальные случаи, которые должны быть без города
                $no_city_paths = ['online', 'map', 'reviews', 'catalog'];
                
                if (empty($path)) {
                    $atts['href'] = home_url('/');
                } elseif (preg_match('#^[^/]+/(service|metro|district)$#', $path, $matches)) {
                    $page_slug = $matches[1];
                    $atts['href'] = home_url("/{$current_city_slug}/{$page_slug}/");
                } elseif (in_array($path, $no_city_paths)) {
                    // Эти страницы всегда без города
                    $atts['href'] = home_url("/{$path}/");
                } else {
                    // Для всех остальных страниц (verified, new, vip, online, individualki)
                    // добавляем текущий город в начало пути
                    $path_parts = explode('/', $path);
                    if ($path_parts[0] !== $current_city_slug) {
                        $atts['href'] = home_url("/{$current_city_slug}/{$path}/");
                    } else {
                        $atts['href'] = home_url("/{$path}/");
                    }
                }
            }

            // Проверка на активность
            $link_path = trim(parse_url($atts['href'], PHP_URL_PATH), '/');
            
            if ($link_path === $current_request_path) {
                // Если это текущая страница - убираем ссылку
                unset($atts['href']);
                $atts['class'] = $is_column_title ? $title_active_class : $link_active_class;
            } else {
                $atts['class'] = $is_column_title ? $title_default_class : $link_default_class;
            }
        }

        return $atts;
    }, 10, 4);
@endphp

<footer class="bg-[#0f0f0f] border-t border-gray-900 mt-auto font-serif">

    <div class="container mx-auto px-4 md:px-8">

        {{-- 1. ВЕРХНЯЯ ЧАСТЬ: Логотип и Навигация --}}
        <div class="pt-8 pb-12 md:pb-24 border-b border-gray-800 flex flex-col md:flex-row md:items-start gap-8 md:gap-65">
            
            {{-- Логотип --}}
            @if($is_home_page)
                {{-- Если главная: НЕ ссылка --}}
                <div class="flex flex-col items-start cursor-default">
                    <span class="font-serif text-2xl tracking-[0.15em] capitalize font-medium">
                        {{ $siteName ?? get_bloginfo('name') }}
                    </span>
                    @if ($site_description !== '')
                        <div class="flex items-center w-full mt-1 gap-2">
                            <span class="h-px bg-white/40 flex-1"></span>
                            <span class="text-[9px] tracking-[0.3em] capitalize text-gray-400 whitespace-nowrap">
                                {{ $site_description }}
                            </span>
                            <span class="h-px bg-white/40 flex-1"></span>
                        </div>
                    @endif
                </div>
            @else
                {{-- Если другая страница: Ссылка --}}
                <a href="{{ $logo_url }}" class="flex flex-col items-start group">
                    <span class="font-serif text-2xl tracking-[0.15em] capitalize  font-medium group-hover:text-gray-300 transition-colors">
                        {{ $siteName ?? get_bloginfo('name') }}
                    </span>
                    @if ($site_description !== '')
                        <div class="flex items-center w-full mt-1 gap-2">
                            <span class="h-px bg-white/40 flex-1"></span>
                            <span class="text-[9px] tracking-[0.3em] capitalize text-gray-400 whitespace-nowrap">
                                {{ $site_description }}
                            </span>
                            <span class="h-px bg-white/40 flex-1"></span>
                        </div>
                    @endif
                </a>
            @endif

            {{-- Меню футера: 2 колонки (первые 4 ссылки + остальные) --}}
            @php
                $footer_links = [];

                if (has_nav_menu('footer_navigation')) {
                    $menu_locations = get_nav_menu_locations();
                    $footer_menu_id = $menu_locations['footer_navigation'] ?? null;

                    if ($footer_menu_id) {
                        $menu_items = wp_get_nav_menu_items($footer_menu_id);

                        if (is_array($menu_items) && !empty($menu_items)) {
                            $children_by_parent = [];
                            foreach ($menu_items as $menu_item) {
                                $parent_id = (int) ($menu_item->menu_item_parent ?? 0);
                                $children_by_parent[$parent_id][] = $menu_item;
                            }

                            foreach ($menu_items as $menu_item) {
                                $item_id = (int) ($menu_item->ID ?? 0);
                                $parent_id = (int) ($menu_item->menu_item_parent ?? 0);

                                if ($parent_id !== 0) {
                                    continue;
                                }

                                $children = $children_by_parent[$item_id] ?? [];

                                if (!empty($children)) {
                                    foreach ($children as $child) {
                                        $child_url = (string) ($child->url ?? '');
                                        $child_title = trim((string) ($child->title ?? ''));

                                        if ($child_url !== '' && $child_title !== '') {
                                            $footer_links[$child_url] = $child_title;
                                        }
                                    }
                                    continue;
                                }

                                $item_url = (string) ($menu_item->url ?? '');
                                $item_title = trim((string) ($menu_item->title ?? ''));

                                if ($item_url !== '' && $item_title !== '') {
                                    $footer_links[$item_url] = $item_title;
                                }
                            }
                        }
                    }
                }

                if (empty($footer_links)) {
                    $footer_links = [
                        '/ankety' => 'Анкеты',
                        '/vip' => 'VIP',
                        '/verified' => 'Проверенные',
                        '/job' => 'Работа',
                        '/contacts' => 'Контакты',
                        '/about' => 'О нас',
                        '/privacy' => 'Политика конфиденциальности',
                        '/rules' => 'Правила размещения',
                        '/faq' => 'FAQ',
                    ];
                }

                $footer_columns = [
                    [
                        'title' => 'Страницы',
                        'links' => array_slice($footer_links, 0, 4, true),
                    ],
                    [
                        'title' => 'Сотрудничество',
                        'links' => array_slice($footer_links, 4, 4, true),
                    ],
                    [
                        'title' => 'Помощь',
                        'links' => array_slice($footer_links, 8, null, true),
                    ],
                ];
            @endphp

            <div class="footer-nav w-full">
                <ul class="footer-nav-grid">
                    @foreach($footer_columns as $column)
                        <li>
                            <h3 class="footer-nav-title-link">{{ $column['title'] }}</h3>
                            <ul class="sub-menu">
                                @foreach($column['links'] as $path_or_url => $label)
                                    @php
                                        $url = (string) $path_or_url;

                                        if (
                                            strpos($url, home_url()) !== 0
                                            && !preg_match('#^[a-z]+:#i', $url)
                                            && strpos($url, '//') !== 0
                                        ) {
                                            $path_clean = trim($url, '/');
                                            $url = home_url("/{$path_clean}/");
                                        }

                                        $url_path = trim(parse_url($url, PHP_URL_PATH), '/');
                                        $is_active = ($url_path === $current_request_path);
                                    @endphp
                                    <li>
                                        @if($is_active)
                                            <span class="footer-nav-link footer-nav-link-active">{{ $label }}</span>
                                        @else
                                            <a href="{{ $url }}" class="footer-nav-link">{{ $label }}</a>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- 2. СРЕДНЯЯ ЧАСТЬ: Домен и Копирайт --}}
        <div class="flex flex-col md:flex-row justify-between items-center py-6 border-b border-gray-800 gap-4">
            
            {{-- Домен сайта --}}
            <div class="font-serif text-sm md:text-base tracking-[0.15em] capitalize ">
                {{ strtoupper($_SERVER['HTTP_HOST'] ?? 'SITE.COM') }}
            </div>

            {{-- Копирайт --}}
            <div class="text-[10px] md:text-xs tracking-widest text-gray-400 capitalize">
                &copy; {{ date('Y') }} Все права защищены.
            </div>
        </div>

        {{-- 3. НИЖНЯЯ ЧАСТЬ: Дисклеймер --}}
        <div class="py-8">
            <p class="text-[11px] md:text-[12px] leading-relaxed text-justify font-light opacity-80">
                <span class="capitalize font-medium mr-1">ОТКАЗ ОТ ОТВЕТСТВЕННОСТИ:</span>
                <span class="text-gray-400">
                    Обратите внимание, что данный веб-сайт содержит контент и изображения, не предназначенные для детей. 
                    Если вам не исполнилось 18 лет или вас оскорбляют материалы для взрослых, пожалуйста, покиньте этот ресурс. 
                    Выбирая продолжение просмотра данной страницы, вы освобождаете владельца этого веб-сайта и всех лиц, 
                    участвующих в его создании, поддержке и размещении, от любой ответственности, которая может возникнуть в результате ваших действий. 
                    Мы предоставляем только рекламное пространство и не являемся эскорт-агентством.
                </span>
            </p>
        </div>

    </div>
</footer>
