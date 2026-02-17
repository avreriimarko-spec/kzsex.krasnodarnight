@php
    // 1. Определяем текущий город
    $city_obj = get_current_city();
    $current_slug = $city_obj ? $city_obj->slug : \App\Helpers\UrlHelpers::DEFAULT_CITY_SLUG;

    // 2. Ссылка для Логотипа (всегда на главную без города)
    $logo_url = home_url('/');

    // 3. Текущий путь (для проверки активных ссылок)
    $current_request_path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');

    // 4. Проверяем, на главной ли мы (для логотипа)
    $logo_path_clean = trim(parse_url($logo_url, PHP_URL_PATH), '/');
    $is_home_page = ($current_request_path === $logo_path_clean);

    // 5. Фильтр для меню футера
    add_filter('nav_menu_link_attributes', function($atts, $item, $args, $depth) use ($current_slug, $current_request_path) {
        // Применяем только к навигации футера
        if ($args->theme_location !== 'footer_navigation') {
            return $atts;
        }

        // Классы стилей (Tailwind)
        // Активная ссылка (текущая страница): белый цвет, курсор дефолтный
        $active_classes = 'text-white cursor-default uppercase tracking-widest transition-colors';
        // Обычная ссылка: серый цвет, при наведении белый
        $default_classes = 'text-gray-300 hover:text-white uppercase tracking-widest transition-colors';

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
                $atts['class'] = $active_classes;
            } else {
                $atts['class'] = $default_classes;
            }
        }

        return $atts;
    }, 10, 4);
@endphp

<footer class="bg-[#0f0f0f] text-white border-t border-gray-900 mt-auto font-serif">

    <div class="container mx-auto px-4 md:px-8">

        {{-- 1. ВЕРХНЯЯ ЧАСТЬ: Логотип и Навигация --}}
        <div class="flex flex-col md:flex-row justify-between items-center py-8 border-b border-gray-800">
            
            {{-- Логотип --}}
            @if($is_home_page)
                {{-- Если главная: НЕ ссылка --}}
                <div class="flex flex-col items-start mb-6 md:mb-0 cursor-default">
                    <span class="font-serif text-2xl tracking-[0.15em] uppercase text-white font-medium">
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
                <a href="{{ $logo_url }}" class="flex flex-col items-start mb-6 md:mb-0 group">
                    <span class="font-serif text-2xl tracking-[0.15em] uppercase text-white font-medium group-hover:text-gray-300 transition-colors">
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
                <nav aria-label="Меню футера">
                    {!! wp_nav_menu([
                        'theme_location' => 'footer_navigation',
                        // Убираем стили ссылок отсюда, так как они задаются в фильтре PHP
                        'menu_class' => 'flex flex-wrap justify-center md:justify-end gap-x-6 gap-y-2 list-none p-0 m-0 text-xs md:text-sm font-light',
                        'container' => false,
                        'echo' => false,
                        'depth' => 1,
                    ]) !!}
                </nav>
            @else
                {{-- Фоллбэк (если меню не настроено, но с поддержкой городов) --}}
                <div class="flex gap-6 text-xs md:text-sm font-light tracking-widest uppercase text-gray-300">
                    @php
                        $links = [
                            '/' => 'Главная',
                            '/about' => 'О нас',
                            '/ankety' => 'Анкеты', // Пример слага
                            '/job' => 'Работа',
                            '/contacts' => 'Контакты'
                        ];
                    @endphp
                    @foreach($links as $path => $label)
                        @php
                            // Формируем ссылку без города для футера
                            if ($path === '/') {
                                $url = home_url('/');
                            } else {
                                $path_clean = trim($path, '/');
                                $url = home_url("/{$path_clean}/");
                            }
                            
                            // Проверка активности
                            $url_path = trim(parse_url($url, PHP_URL_PATH), '/');
                            $is_active = ($url_path === $current_request_path);
                        @endphp

                        @if($is_active)
                            <span class="text-white cursor-default">{{ $label }}</span>
                        @else
                            <a href="{{ $url }}" class="hover:text-white transition-colors">{{ $label }}</a>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        {{-- 2. СРЕДНЯЯ ЧАСТЬ: Домен и Копирайт --}}
        <div class="flex flex-col md:flex-row justify-between items-center py-6 border-b border-gray-800 gap-4">
            
            {{-- Домен сайта --}}
            <div class="font-serif text-sm md:text-base tracking-[0.15em] uppercase text-white">
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
                <span class="text-white uppercase font-medium mr-1">ОТКАЗ ОТ ОТВЕТСТВЕННОСТИ:</span>
                Обратите внимание, что данный веб-сайт содержит контент и изображения, не предназначенные для детей. 
                Если вам не исполнилось 18 лет или вас оскорбляют материалы для взрослых, пожалуйста, покиньте этот ресурс. 
                Выбирая продолжение просмотра данной страницы, вы освобождаете владельца этого веб-сайта и всех лиц, 
                участвующих в его создании, поддержке и размещении, от любой ответственности, которая может возникнуть в результате ваших действий. 
                Мы предоставляем только рекламное пространство и не являемся эскорт-агентством.
            </p>
        </div>

    </div>
</footer>
