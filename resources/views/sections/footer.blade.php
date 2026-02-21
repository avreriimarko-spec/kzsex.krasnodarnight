@php
    // 1. Ссылка для Логотипа (всегда на главную без города)
    $logo_url = home_url('/');

    // 2. Текущий путь (для проверки активных ссылок)
    $current_request_path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');

    // 3. Проверяем, на главной ли мы (для логотипа)
    $logo_path_clean = trim(parse_url($logo_url, PHP_URL_PATH), '/');
    $is_home_page = ($current_request_path === $logo_path_clean);

    // 4. Фильтр для меню футера
    add_filter('nav_menu_link_attributes', function($atts, $item, $args, $depth) use ($current_request_path) {
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
                } elseif (in_array($path, $no_city_paths)) {
                    // Эти страницы всегда без города
                    $atts['href'] = home_url("/{$path}/");
                } else {
                    // Остальные страницы также без города в футере
                    $atts['href'] = home_url("/{$path}/");
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

<footer class="bg-[#0f0f0f] text-black border-t border-gray-900 mt-auto font-serif">

    <div class="container mx-auto px-4 md:px-8">

        {{-- 1. ВЕРХНЯЯ ЧАСТЬ: Логотип и Навигация --}}
        <div class="pt-8 pb-24 border-b border-gray-800 flex gap-50">
            
            {{-- Логотип --}}
            @if($is_home_page)
                {{-- Если главная: НЕ ссылка --}}
                <div class="flex flex-col items-start cursor-default">
                    <span class="font-serif text-2xl tracking-[0.15em] uppercase text-black font-medium">
                        {{ $siteName ?? get_bloginfo('name') }}
                    </span>
                    <div class="flex items-center w-full mt-1 gap-2">
                        <span class="h-px bg-white/40 flex-1"></span>
                        <span class="text-[9px] tracking-[0.3em] uppercase text-gray-400 whitespace-nowrap">
                            {{ get_bloginfo('description')}}
                        </span>
                        <span class="h-px bg-white/40 flex-1"></span>
                    </div>
                </div>
            @else
                {{-- Если другая страница: Ссылка --}}
                <a href="{{ $logo_url }}" class="flex flex-col items-start group">
                    <span class="font-serif text-2xl tracking-[0.15em] uppercase text-black font-medium group-hover:text-gray-300 transition-colors">
                        {{ $siteName ?? get_bloginfo('name') }}
                    </span>
                    <div class="flex items-center w-full mt-1 gap-2">
                        <span class="h-px bg-white/40 flex-1"></span>
                        <span class="text-[9px] tracking-[0.3em] uppercase text-gray-400 whitespace-nowrap">
                            {{ get_bloginfo('description')}}
                        </span>
                        <span class="h-px bg-white/40 flex-1"></span>
                    </div>
                </a>
            @endif

            {{-- Меню (WP Menu) --}}
            @if (has_nav_menu('footer_navigation'))
                <nav class="footer-nav" aria-label="Меню футера">
                    {!! wp_nav_menu([
                        'theme_location' => 'footer_navigation',
                        'menu_class' => 'footer-nav-grid',
                        'container' => false,
                        'echo' => false,
                        'depth' => 2,
                    ]) !!}
                </nav>
            @else
                {{-- Фоллбэк (если меню не настроено, но с поддержкой городов) --}}
                @php
                    $fallback_columns = [
                        [
                            'title' => 'Каталог',
                            'links' => [
                                '/ankety' => 'Анкеты',
                                '/vip' => 'VIP',
                                '/verified' => 'Проверенные',
                            ],
                        ],
                        [
                            'title' => 'Сотрудничество',
                            'links' => [
                                '/job' => 'Работа',
                                '/contacts' => 'Контакты',
                                '/about' => 'О нас',
                            ],
                        ],
                        [
                            'title' => 'Помощь',
                            'links' => [
                                '/privacy' => 'Политика конфиденциальности',
                                '/rules' => 'Правила размещения',
                                '/faq' => 'FAQ',
                            ],
                        ],
                    ];
                @endphp

                <div class="footer-nav mt-8">
                    <ul class="footer-nav-grid">
                        @foreach($fallback_columns as $column)
                            <li>
                                <span class="footer-nav-title-link">{{ $column['title'] }}</span>
                                <ul class="sub-menu">
                                    @foreach($column['links'] as $path => $label)
                                        @php
                                            $path_clean = trim($path, '/');
                                            $url = home_url("/{$path_clean}/");
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
            @endif
        </div>

        {{-- 2. СРЕДНЯЯ ЧАСТЬ: Домен и Копирайт --}}
        <div class="flex flex-col md:flex-row justify-between items-center py-6 border-b border-gray-800 gap-4">
            
            {{-- Домен сайта --}}
            <div class="font-serif text-sm md:text-base tracking-[0.15em] uppercase text-black">
                {{ strtoupper($_SERVER['HTTP_HOST'] ?? 'SITE.COM') }}
            </div>

            {{-- Копирайт --}}
            <div class="text-[10px] md:text-xs tracking-widest text-gray-500 uppercase">
                &copy; {{ date('Y') }} Все права защищены.
            </div>
        </div>

        {{-- 3. НИЖНЯЯ ЧАСТЬ: Дисклеймер --}}
        <div class="py-8">
            <p class="text-[11px] md:text-[12px] leading-relaxed text-gray-400 text-justify font-light opacity-80">
                <span class="text-black uppercase font-medium mr-1">ОТКАЗ ОТ ОТВЕТСТВЕННОСТИ:</span>
                Обратите внимание, что данный веб-сайт содержит контент и изображения, не предназначенные для детей. 
                Если вам не исполнилось 18 лет или вас оскорбляют материалы для взрослых, пожалуйста, покиньте этот ресурс. 
                Выбирая продолжение просмотра данной страницы, вы освобождаете владельца этого веб-сайта и всех лиц, 
                участвующих в его создании, поддержке и размещении, от любой ответственности, которая может возникнуть в результате ваших действий. 
                Мы предоставляем только рекламное пространство и не являемся эскорт-агентством.
            </p>
        </div>

    </div>
</footer>
