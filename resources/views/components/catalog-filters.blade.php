@props(['filterData'])

{{-- 
    ГЛОБАЛЬНЫЙ КОНТЕЙНЕР КОМПОНЕНТА
--}}
<div class="relative font-serif filter-component-wrapper">

    {{-- ПОДЛОЖКА (Backdrop) --}}
    <div class="filter-backdrop fixed inset-0 bg-black/90 backdrop-blur-sm z-40 transition-opacity" 
         style="display: none;"
         onclick="closeMobileFilter(this)"></div>

    {{-- САЙДБАР --}}
    {{-- Ширина w-[320px] для комфортного чтения. Паддинг p-6 (нормальный отступ от краев) --}}
    <aside class="filter-sidebar fixed inset-y-0 right-0 z-50 w-[320px] bg-black shadow-2xl transform transition-transform duration-300 translate-x-full 
               lg:translate-x-0 lg:static lg:w-auto lg:border-0 lg:shadow-none lg:sticky lg:top-24 lg:z-40 lg:h-[calc(100vh-6rem)] lg:overflow-hidden 
               flex flex-col lg:p-0 p-6">
        
        {{-- ШАПКА МОБИЛЬНАЯ --}}
        <div class="flex items-center justify-between pb-6 mb-2 lg:hidden border-b border-gray-800 shrink-0">
            <span class="text-xl font-bold text-black uppercase tracking-wider">Фильтры</span>
            {{-- Кнопка закрытия крупнее для удобства --}}
            <button type="button" onclick="closeMobileFilter(this)" class="text-[#cd1d46] hover:text-black p-1">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>

        {{-- ЗАГОЛОВОК ДЕСКТОПНЫЙ --}}
        <div class="hidden lg:block pb-5 border-b border-gray-800 mb-4">
            <span class="text-lg font-bold text-black uppercase tracking-widest flex items-center gap-2">
                <svg class="w-5 h-5 text-[#cd1d46]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                Фильтры
</span>
        </div>

        {{-- ФОРМА С ФИЛЬТРАМИ --}}
        {{-- space-y-8: Хороший отступ между смысловыми блоками (Цена / Возраст / Категории) --}}
        <form action="{{ url()->current() }}" method="GET" class="flex-1 overflow-y-auto filter-scroll pr-2 lg:pr-4 space-y-8 pb-6">
            
            {{-- 1. ЦЕНА (РАДИО) --}}
            <div>
                <h3 class="text-base font-bold text-black mb-4 uppercase tracking-wide">Цена</h3>
                
                <input type="hidden" name="price_min" value="{{ request('price_min') }}" class="price-min-input">
                <input type="hidden" name="price_max" value="{{ request('price_max') }}" class="price-max-input">

                {{-- space-y-3: Пункты не слипаются --}}
                <div class="space-y-3">
                    @php
                        $priceRanges = [
                            ['min' => '', 'max' => '15000', 'label' => 'меньше 15000'],
                            ['min' => '15000', 'max' => '25000', 'label' => '15000-25000'],
                            ['min' => '25000', 'max' => '', 'label' => 'больше 25000'],
                        ];
                        $curPriceMin = request('price_min');
                        $curPriceMax = request('price_max');
                    @endphp

                    @foreach($priceRanges as $range)
                        @php
                            $isActive = ($curPriceMin == $range['min'] && $curPriceMax == $range['max']);
                        @endphp
                        {{-- py-1: Добавляет высоту строке для кликабельности --}}
                        <div class="flex items-center cursor-pointer group py-1"
                             onclick="setRange(this, 'price', '{{ $range['min'] }}', '{{ $range['max'] }}')">
                            
                            <div class="w-5 h-5  border-2 border-gray-600 flex items-center justify-center mr-3 group-hover:border-[#cd1d46] transition-colors radio-circle {{ $isActive ? 'border-[#cd1d46]' : '' }}">
                                <div class="w-3 h-3  bg-[#cd1d46] transition-transform duration-200 transform {{ $isActive ? 'scale-100' : 'scale-0' }} radio-dot"></div>
                            </div>
                            
                            <span class="text-base text-[#cd1d46] group-hover:text-black transition-colors radio-label {{ $isActive ? 'text-black font-medium' : '' }}">
                                {{ $range['label'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="h-px bg-gray-800 w-full"></div>

            {{-- 2. ВОЗРАСТ (РАДИО) --}}
            <div>
                <h3 class="text-base font-bold text-black mb-4 uppercase tracking-wide">Возраст</h3>
                
                <input type="hidden" name="age_min" value="{{ request('age_min') }}" class="age-min-input">
                <input type="hidden" name="age_max" value="{{ request('age_max') }}" class="age-max-input">

                <div class="space-y-3">
                    @php
                        $ageRanges = [
                            ['min' => '18', 'max' => '23', 'label' => '18-23'],
                            ['min' => '24', 'max' => '30', 'label' => '24-30'],
                            ['min' => '30', 'max' => '', 'label' => '30+'],
                        ];
                        $curAgeMin = request('age_min');
                        $curAgeMax = request('age_max');
                    @endphp

                    @foreach($ageRanges as $range)
                        @php
                            $isActive = ($curAgeMin == $range['min'] && $curAgeMax == $range['max']);
                        @endphp
                        <div class="flex items-center cursor-pointer group py-1"
                             onclick="setRange(this, 'age', '{{ $range['min'] }}', '{{ $range['max'] }}')">
                            <div class="w-5 h-5  border-2 border-gray-600 flex items-center justify-center mr-3 group-hover:border-[#cd1d46] transition-colors radio-circle {{ $isActive ? 'border-[#cd1d46]' : '' }}">
                                <div class="w-3 h-3  bg-[#cd1d46] transition-transform duration-200 transform {{ $isActive ? 'scale-100' : 'scale-0' }} radio-dot"></div>
                            </div>
                            <span class="text-base text-[#cd1d46] group-hover:text-black transition-colors radio-label {{ $isActive ? 'text-black font-medium' : '' }}">
                                {{ $range['label'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="h-px bg-gray-800 w-full"></div>

            {{-- 3. ТАКСОНОМИИ (ЧЕКБОКСЫ) --}}
            @foreach ($filterData as $slug => $data)
                @php
                    $paramName = 'f_' . $slug;
                    $selected = (array) request($paramName);
                    $isOpen = !empty(array_filter($selected)); 
                @endphp

                <div class="filter-group">
                    <button type="button" onclick="toggleFilterSection(this)" class="flex items-center justify-between w-full mb-4 group">
                        {{-- ИСПРАВЛЕНИЕ: h3 заменен на span, так как h3 нельзя вкладывать в button --}}
                        <span class="text-base font-bold text-black uppercase tracking-wide group-hover:text-[#cd1d46] transition-colors">{{ $data['label'] }}</span>
                        <svg class="w-4 h-4 text-[#cd1d46] transform transition-transform duration-200 filter-arrow {{ $isOpen ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div class="space-y-3 pl-1 filter-content" style="display: {{ $isOpen ? 'block' : 'none' }};">
                        @foreach ($data['terms'] as $term)
                            <label class="flex items-center cursor-pointer group py-0.5">
                                <span class="relative flex items-center mr-3">
                                    <input type="checkbox" name="f_{{ $slug }}[]"
                                        value="{{ $term->slug }}"
                                        class="peer w-5 h-5 appearance-none  border-2 border-gray-600 bg-transparent checked:border-[#cd1d46] checked:bg-transparent focus:ring-0 transition-all cursor-pointer"
                                        {{ in_array($term->slug, $selected) ? 'checked' : '' }}>
                                    
                                    <span class="absolute inset-0 m-auto w-2.5 h-2.5  bg-[#cd1d46] opacity-0 peer-checked:opacity-100 transition-opacity pointer-events-none"></span>
                                </span>
                                
                                <span class="text-base text-[#cd1d46] group-hover:text-black transition-colors {{ in_array($term->slug, $selected) ? 'text-black font-medium' : '' }}">
                                    {{ $term->name }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
                
                @if(!$loop->last)
                    <div class="h-px bg-gray-800 w-full"></div>
                @endif
            @endforeach

        </form>

        {{-- ФУТЕР --}}
        {{-- Вернул высоту кнопке (py-4) и отступ сверху (pt-6) --}}
        <div class="pt-6 mt-auto border-t border-gray-800 bg-[#050505] shrink-0 space-y-4 pb-6 lg:pb-0">
            <button type="button" onclick="this.closest('aside').querySelector('form').submit()"
                class="w-full bg-[#cd1d46] hover:bg-[#b71833] text-black font-bold uppercase text-sm py-4 rounded tracking-widest transition-transform active:scale-95 shadow-lg shadow-[#cd1d46]/20">
                Применить фильтр
            </button>
            
            @if (request()->query())
                <a href="{{ url()->current() }}"
                   class="block w-full text-center text-xs text-[#cd1d46] hover:text-black uppercase tracking-widest transition-colors pb-2">
                   × Сбросить всё
                </a>
            @endif
        </div>

    </aside>
</div>

{{-- СКРИПТЫ --}}
<script>
    function openMobileFiltersGlobal() {
        const mobileWrapper = document.querySelector('.lg\\:hidden .filter-component-wrapper');
        if (mobileWrapper) {
            const sidebar = mobileWrapper.querySelector('.filter-sidebar');
            const backdrop = mobileWrapper.querySelector('.filter-backdrop');
            if (sidebar) sidebar.classList.remove('translate-x-full');
            if (backdrop) backdrop.style.display = 'block';
            document.body.style.overflow = 'hidden'; 
        }
    }

    function closeMobileFilter(element) {
        const wrapper = element.closest('.filter-component-wrapper');
        if (!wrapper) return;
        const sidebar = wrapper.querySelector('.filter-sidebar');
        const backdrop = wrapper.querySelector('.filter-backdrop');
        if (sidebar) sidebar.classList.add('translate-x-full');
        if (backdrop) backdrop.style.display = 'none';
        document.body.style.overflow = ''; 
    }

    function setRange(element, type, min, max) {
        const form = element.closest('form');
        const minInput = form.querySelector('.' + type + '-min-input');
        const maxInput = form.querySelector('.' + type + '-max-input');
        
        let isReset = false;
        if (minInput.value === min && maxInput.value === max) {
            minInput.value = '';
            maxInput.value = '';
            isReset = true;
        } else {
            minInput.value = min;
            maxInput.value = max;
        }

        const siblings = form.querySelectorAll(`div[onclick*="'${type}'"]`);
        siblings.forEach(el => {
            el.querySelector('.radio-circle').classList.remove('border-yellow-600');
            const dot = el.querySelector('.radio-dot');
            dot.classList.remove('scale-100');
            dot.classList.add('scale-0');
            el.querySelector('.radio-label').classList.remove('text-black', 'font-medium');
        });

        if (!isReset) {
            element.querySelector('.radio-circle').classList.add('border-yellow-600');
            const activeDot = element.querySelector('.radio-dot');
            activeDot.classList.remove('scale-0');
            activeDot.classList.add('scale-100');
            element.querySelector('.radio-label').classList.add('text-black', 'font-medium');
        }
    }

    function toggleFilterSection(button) {
        const content = button.nextElementSibling; 
        const arrow = button.querySelector('.filter-arrow'); 
        
        if (content.style.display === 'none') {
            content.style.display = 'block';
            arrow.classList.add('rotate-180');
        } else {
            content.style.display = 'none';
            arrow.classList.remove('rotate-180');
        }
    }
</script>
