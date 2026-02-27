{{--
    Контейнер для плавающих элементов.
    Мобилка: По центру (left-1/2 -translate-x-1/2), в ряд (flex-row).
    ПК (md): Справа (right-4), вертикально (flex-col).
--}}
<div id="floating-buttons-root" class="fixed bottom-8 z-40 flex flex-row gap-4 items-end pointer-events-none font-serif
            left-1/2 -translate-x-1/2 
            md:left-auto md:right-4 md:translate-x-0 md:flex-col">

    {{-- ============================================================== --}}
    {{-- 1. ТЕЛЕГРАМ (Большой попап или кнопка)                         --}}
    {{-- ============================================================== --}}

    @if (!empty($telegram['enabled']) && !empty($telegram['link']))
        <div data-x-data="{
            showPopup: false,
            showButton: false,
            link: '{{ $telegram['link'] }}',

            closeBtn: {
                ['data-x-on:click']() { this.minimize() }
            },

            subscribeBtn: {
                ['data-x-bind:href']() { return this.link },
                ['target']: '_blank',
                ['data-x-on:click']() { this.minimize() }
            },

            restoreBtn: {
                ['data-x-on:click']() { this.restorePopup() },
                ['data-x-show']() { return this.showButton },
                ['data-x-transition:enter']: 'transition ease-out duration-300',
                ['data-x-transition:enter-start']: 'opacity-0 scale-50',
                ['data-x-transition:enter-end']: 'opacity-100 scale-100'
            },

            popupAnim: {
                ['data-x-show']() { return this.showPopup },
                ['data-x-transition:enter']: 'transition ease-out duration-300',
                ['data-x-transition:enter-start']: 'opacity-0 translate-y-10 scale-90',
                ['data-x-transition:enter-end']: 'opacity-100 translate-y-0 scale-100',
                ['data-x-transition:leave']: 'transition ease-in duration-300',
                ['data-x-transition:leave-start']: 'opacity-100 translate-y-0 scale-100',
                ['data-x-transition:leave-end']: 'opacity-0 translate-y-10 scale-90'
            },

            init() {
                this.$el.classList.remove('pointer-events-none');
                this.$el.classList.add('pointer-events-auto');

                if (sessionStorage.getItem('tg_minimized') === 'true') {
                    this.showButton = true;
                    return;
                }

                let views = parseInt(sessionStorage.getItem('tg_views') || 0) + 1;
                sessionStorage.setItem('tg_views', views);

                const timer = setTimeout(() => { this.triggerPopup(); }, 15000);

                if (views >= 3) {
                    clearTimeout(timer);
                    setTimeout(() => this.triggerPopup(), 2000);
                }
            },

            triggerPopup() {
                if (!this.showButton) {
                    this.showPopup = true;
                }
            },

            minimize() {
                this.showPopup = false;
                setTimeout(() => {
                    this.showButton = true;
                    sessionStorage.setItem('tg_minimized', 'true');
                }, 300);
            },

            restorePopup() {
                this.showButton = false;
                this.showPopup = true;
            }
        }" class="relative pointer-events-auto flex items-end" data-x-cloak>

            {{-- А. ПОПАП --}}
            {{-- 
                 Мобилка: absolute bottom-14, центрирован относительно кнопки (left-1/2 -translate-x-1/2).
                 ПК: relative, сброс позиционирования.
            --}}
            <div data-x-bind="popupAnim"
                class="bg-[#050505] rounded border border-gray-800 shadow-2xl shadow-black p-5 w-72 z-50
                       absolute bottom-14 left-1/2 -translate-x-1/2
                       md:relative md:bottom-auto md:left-auto md:translate-x-0 md:mb-2">
                {{-- Крестик --}}
                <button type="button" data-x-bind="closeBtn"
                    class="absolute top-2 right-2 text-gray-500 hover:text-[#cd1d46] transition-colors p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>

                <div class="flex flex-col items-center text-center">
                    {{-- Иконка большая --}}
                    <div
                        class="w-16 h-16 border-2 border-[#cd1d46] bg-black flex items-center justify-center text-[#cd1d46] mb-3 shadow-[0_0_15px_rgba(205,29,70,0.3)]">
                        <svg class="w-8 h-8 ml-[-2px] mt-[2px]" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12.068 12.068 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z" />
                        </svg>
                    </div>
                    <span class="font-bold text-black capitalize tracking-wider mb-1">Наш Telegram</span>
                    <p class="text-xs text-gray-400 mb-5 font-medium">Приватный контент и скидки.</p>

                    <a data-x-bind="subscribeBtn"
                        class="bg-[#cd1d46] hover:bg-[#b71833] text-black hover:!text-black font-bold text-xs capitalize tracking-widest py-3 px-6 rounded shadow-lg shadow-[#cd1d46]/20 w-full block transition-transform active:scale-95 cursor-pointer">
                        Подписаться
                    </a>
                </div>
            </div>

            {{-- Б. КНОПКА СВЕРНУТАЯ (Маленькая) --}}
            <button type="button" data-x-bind="restoreBtn"
                class="w-12 h-12 bg-[#cd1d46] hover:bg-[#b71833] text-black shadow-lg shadow-[#cd1d46]/30 flex items-center justify-center transition-transform hover:scale-105 active:scale-95 border border-[#cd1d46]"
                aria-label="Открыть Telegram">
                <svg class="w-6 h-6 ml-[-1px] mt-[2px]" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12.068 12.068 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z" />
                </svg>
            </button>
        </div>
    @endif


    {{-- ============================================================== --}}
    {{-- 2. КНОПКА "НАВЕРХ" И ДОП. КОНТАКТЫ                             --}}
    {{-- ============================================================== --}}
    <div data-x-data="{
        visible: false,

        scrollBtn: {
            ['data-x-on:click']() { this.scrollToTop() },
            ['data-x-show']() { return this.visible },
            ['data-x-transition:enter']: 'transition ease-out duration-300',
            ['data-x-transition:enter-start']: 'opacity-0 translate-y-10',
            ['data-x-transition:enter-end']: 'opacity-100 translate-y-0',
            ['data-x-transition:leave']: 'transition ease-in duration-300',
            ['data-x-transition:leave-start']: 'opacity-100 translate-y-0',
            ['data-x-transition:leave-end']: 'opacity-0 translate-y-10'
        },

        init() {
            this.$el.classList.remove('pointer-events-none');
            this.$el.classList.add('pointer-events-auto');

            window.addEventListener('scroll', () => {
                this.visible = window.scrollY > 300;
            });
        },

        scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    }" class="pointer-events-auto flex flex-row md:flex-col gap-4 items-end" data-x-cloak>

        @php
            $maxRaw = trim((string) ($contacts['max'] ?? ''));
            $maxFloatingLink = null;

            if ($maxRaw !== '') {
                $maxFloatingLink = str_contains($maxRaw, 'http')
                    ? $maxRaw
                    : 'https://max.ru/' . ltrim($maxRaw, '/@');
            }
        @endphp

        {{-- Контейнер для WhatsApp, Telegram и Max --}}
        {{-- Flex-row для мобилки, Flex-col для ПК --}}
        <div class="flex flex-row md:flex-col gap-4">
            {{-- Кнопка WhatsApp --}}
            @if(isset($contacts['whatsapp']) && $contacts['whatsapp'])
                <a href="https://wa.me/{{ $contacts['whatsapp'] }}" target="_blank"
                   class="rounded w-12 h-12 bg-[#25D366] hover:bg-[#128C7E] text-black shadow-lg shadow-[#25D366]/30 flex items-center justify-center transition-transform hover:scale-105 active:scale-95"
                   aria-label="WhatsApp">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                    </svg>
                </a>
            @endif

            {{-- Кнопка Telegram (простая) --}}
            @if(isset($contacts['telegram']) && $contacts['telegram'])
                <a href="https://t.me/{{ $contacts['telegram'] }}" target="_blank"
                   class="rounded w-12 h-12 bg-[#0088cc] hover:bg-[#006699] text-black shadow-lg shadow-[#0088cc]/30 flex items-center justify-center transition-transform hover:scale-105 active:scale-95"
                   aria-label="Telegram">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12.068 12.068 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z" />
                    </svg>
                </a>
            @endif

            {{-- Кнопка Max --}}
            @if($maxFloatingLink)
                <a href="{{ $maxFloatingLink }}" target="_blank"
                   class="rounded w-12 h-12 bg-[#1a2746] hover:bg-[#23345b] text-[#b6c5ff] shadow-lg shadow-[#6d8bff]/30 flex items-center justify-center transition-transform hover:scale-105 active:scale-95 border border-[#6d8bff]"
                   aria-label="Max">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M4 18V6h3.2L12 12.2 16.8 6H20v12h-2.9v-7.3L12 16.8l-5.1-6.1V18z" />
                    </svg>
                </a>
            @endif
        </div>

        {{-- Кнопка "Наверх" --}}
        <button type="button" data-x-bind="scrollBtn"
            class="w-12 h-12 bg-[#050505] text-[#cd1d46] rounded border border-gray-800 shadow-xl flex items-center justify-center hover:bg-[#cd1d46] hover:text-[#fff] hover:border-[#cd1d46] transition-all active:scale-95 group"
            aria-label="Наверх">
            <svg class="w-6 h-6 group-hover:-translate-y-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18">
                </path>
            </svg>
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const floatingButtonsRoot = document.getElementById('floating-buttons-root');
    if (!floatingButtonsRoot) return;

    // Скрываем плавающие кнопки контактов для подмены на заглушки
    const floatingContactLinks = floatingButtonsRoot.querySelectorAll('a[href*="wa.me"], a[href*="t.me"], a[href*="max.ru"], a[aria-label="Max"]');
    
    floatingContactLinks.forEach(link => {
        // Пропускаем подписку
        if(link.hasAttribute('data-x-bind')) return;

        const span = document.createElement('span');
        span.className = link.className;
        span.innerHTML = link.innerHTML;
        span.style.cursor = 'default';
        span.style.color = 'inherit';
        span.style.textDecoration = 'none';
        span.title = '';
        
        span.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            window.open(link.href, '_blank');
        });
        
        link.parentNode.replaceChild(span, link);
    });
});
</script>
