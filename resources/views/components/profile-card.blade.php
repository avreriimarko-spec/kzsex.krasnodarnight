@props(['post', 'details' => [], 'badges' => [], 'lcp' => false])

@php
    $loadingAttr = $lcp ? 'eager' : 'lazy';
    $fetchPriorityAttr = $lcp ? 'high' : 'low';
    $decodingAttr = $lcp ? 'auto' : 'async';

    $profileId = get_the_ID();
    $profileName = get_the_title();

    $cityTerms = get_the_terms($profileId, 'city');
    $cityName = !empty($cityTerms) && !is_wp_error($cityTerms) ? $cityTerms[0]->name : 'Москва';

    $metroTerms = get_the_terms($profileId, 'metro');
    $metroNames = (!empty($metroTerms) && !is_wp_error($metroTerms))
        ? array_values(array_unique(wp_list_pluck($metroTerms, 'name')))
        : [];

    $districtTerms = get_the_terms($profileId, 'district');
    $districtNames = (!empty($districtTerms) && !is_wp_error($districtTerms))
        ? array_values(array_unique(wp_list_pluck($districtTerms, 'name')))
        : [];

    $age = get_field('age');
    $height = get_field('height');
    $weight = get_field('weight');

    $breastTerms = get_the_terms($profileId, 'breast_size');
    $breast = null;
    if ($breastTerms && !is_wp_error($breastTerms)) {
        $breastName = $breastTerms[0]->name;
        if (preg_match('/([A-Z])$/', $breastName, $matches)) {
            $letter = $matches[1];
            $breastMap = ['A' => '1', 'B' => '2', 'C' => '3', 'D' => '4', 'E' => '5', 'F' => '6', 'G' => '7', 'H' => '8'];
            $breast = $breastMap[$letter] ?? $letter;
        } elseif (preg_match('/\d+/', $breastName, $matches)) {
            $breast = $matches[0];
        }
    }

    $price = \App\Services\ProfilePriceCalculator::apply(get_field('price') ?: []);
    $currency = strtoupper($price['currency'] ?? 'RUB');
    $price1h = $price['price_1h'] ?? null;
    $price2h = $price['price_2h'] ?? null;
    $priceNight = $price['price_night'] ?? null;
    $priceDay = $price['price_day'] ?? null;

    $inoutcallSlugs = wp_get_post_terms($profileId, 'inoutcall', ['fields' => 'slugs']);
    $inoutcallSlugs = is_wp_error($inoutcallSlugs) ? [] : $inoutcallSlugs;
    $isOutcall = in_array('outcall', $inoutcallSlugs, true) || in_array('incall-and-outcall', $inoutcallSlugs, true);

    $profilePhone = get_field('phone');
    $phoneDisplay = null;
    $phoneCopyValue = null;
    if (!empty($profilePhone)) {
        $phoneDigits = preg_replace('/\D+/', '', (string) $profilePhone);
        $phoneDisplay = trim(preg_replace('/\s+/', ' ', (string) $profilePhone));
        if ($phoneDigits) {
            if (strlen($phoneDigits) === 11 && str_starts_with($phoneDigits, '8')) {
                $phoneDigits = '7' . substr($phoneDigits, 1);
            }
            $phoneCopyValue = '+' . $phoneDigits;
            if (empty($phoneDisplay)) {
                $phoneDisplay = $phoneCopyValue;
            }
        }
    }

    $rawTg = get_field('telegram') ?: get_field('global_tg', 'option');
    $tgLink = null;
    if (!empty($rawTg)) {
        $tgLink = str_contains($rawTg, 'http') ? $rawTg : 'https://t.me/' . ltrim(str_replace('@', '', (string) $rawTg), '/');
    }

    $rawWa = get_field('whatsapp') ?: get_field('global_wa', 'option');
    $waLink = null;
    if (!empty($rawWa)) {
        if (str_contains($rawWa, 'http')) {
            $waLink = $rawWa;
        } else {
            $waDigits = preg_replace('/\D+/', '', (string) $rawWa);
            if (!empty($waDigits)) {
                $waLink = 'https://wa.me/' . $waDigits;
            }
        }
    }

    $rawMax = get_field('max') ?: get_field('max_link') ?: get_field('global_max', 'option') ?: get_field('global_max_link', 'option');
    $maxLink = null;
    if (!empty($rawMax)) {
        $maxRawString = trim((string) $rawMax);
        $maxLink = str_contains($maxRawString, 'http') ? $maxRawString : 'https://max.ru/' . ltrim($maxRawString, '/@');
    }

    $isVip = has_term('vip', 'vip', $profileId) || in_array('VIP', $badges, true);
    $isVerified = has_term('verified', 'verified', $profileId) || in_array('Verified', $badges, true);

    $altText = "Проститутка {$cityName} {$profileName}";
    if ($age) {
        $altText .= " {$age} лет";
    }
    if ($height) {
        $altText .= ", рост {$height} см";
    }
    if ($weight) {
        $altText .= ", вес {$weight} кг";
    }
@endphp

<article class="group h-full overflow-hidden rounded-[22px] border border-[#2a3142] p-3 sm:p-4 shadow-[0_12px_34px_rgba(0,0,0,0.45)]">
    <div class="mb-3 flex items-start justify-between gap-3">
        <div class="min-w-0">
            <a href="{{ profile_url($profileId) }}">
                <h2 class="font-serif text-[19px] sm:text-[21px] font-bold leading-tight text-[#eef3ff] transition-colors hover:!text-[#cd1d46]">
                    {{ $profileName }}
                </h2>
            </a>

            <div class="mt-2 space-y-1 text-[13px] leading-tight text-[#9aa8c2]">
                <div>г. {{ $cityName }}</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-[minmax(0,1fr)_75px]">
        <figure class="relative aspect-[4/5] sm:aspect-[3/4] w-full overflow-hidden rounded-[24px] sm:rounded-[26px] border border-[#2a3142] bg-[#141a24]">
            @if (has_post_thumbnail())
                @php $imgData = wp_get_attachment_image_src(get_post_thumbnail_id(), 'profile_card'); @endphp
                @if ($imgData)
                    <img src="{{ $imgData[0] }}"
                        width="{{ $imgData[1] }}"
                        height="{{ $imgData[2] }}"
                        alt="{{ $altText }}"
                        class="h-full w-full object-cover object-top transition-transform duration-500 group-hover:scale-[1.02]"
                        loading="{{ $loadingAttr }}"
                        fetchpriority="{{ $fetchPriorityAttr }}"
                        decoding="{{ $decodingAttr }}">
                @endif
            @else
                <div class="flex h-full items-center justify-center text-4xl text-gray-500">?</div>
            @endif

            <a href="{{ profile_url($profileId) }}" class="absolute inset-0 z-10" aria-label="Просмотреть профиль {{ $profileName }}"></a>

            <div class="absolute left-3 top-3 z-20 flex flex-col items-start gap-2">
                @if ($isVerified)
                    <span class="rounded-full bg-gradient-to-r from-[#f5f7fa] via-[#c3cfe2] to-[#aab7c9] px-4 py-1.5 text-[11px] font-bold tracking-wide text-[#0e1015] shadow-[0_6px_16px_rgba(0,0,0,0.35)]">
                        Проверенная
                    </span>
                @endif
                @if ($isVip)
                    <span class="rounded-full bg-gradient-to-r from-[#ffd86f] via-[#ffb347] to-[#ff8f1f] px-5 py-1.5 text-[11px] font-bold tracking-wide text-[#0e1015] shadow-[0_6px_16px_rgba(0,0,0,0.35)]">
                        VIP
                    </span>
                @endif
            </div>
        </figure>

        <div class="grid grid-cols-2 gap-2 sm:grid-cols-1">
            <div class="rounded-[16px] sm:rounded-[18px] border border-[#2a3142] bg-[#151b25] p-2 text-center">
                <div class="text-[12px] sm:text-[13px] text-[#8ea0bc]">Вес</div>
                <div class="mt-1 rounded-[10px] sm:rounded-[12px] bg-[#1c2432] px-2 py-2 text-[12px] sm:text-[13px] font-bold leading-none text-[#eef3ff]">
                    {{ $weight ?: '-' }}
                </div>
            </div>

            <div class="rounded-[16px] sm:rounded-[18px] border border-[#2a3142] bg-[#151b25] p-2 text-center">
                <div class="text-[12px] sm:text-[13px] text-[#8ea0bc]">Грудь</div>
                <div class="mt-1 rounded-[10px] sm:rounded-[12px] bg-[#1c2432] px-2 py-2 text-[12px] sm:text-[13px] font-bold leading-none text-[#eef3ff]">
                    {{ $breast ?: '-' }}
                </div>
            </div>

            <div class="rounded-[16px] sm:rounded-[18px] border border-[#2a3142] bg-[#151b25] p-2 text-center">
                <div class="text-[12px] sm:text-[13px] text-[#8ea0bc]">Рост</div>
                <div class="mt-1 rounded-[10px] sm:rounded-[12px] bg-[#1c2432] px-2 py-2 text-[12px] sm:text-[13px] font-bold leading-none text-[#eef3ff]">
                    {{ $height ?: '-' }}
                </div>
            </div>

            <div class="rounded-[16px] sm:rounded-[18px] border border-[#2a3142] bg-[#151b25] p-2 text-center">
                <div class="text-[12px] sm:text-[13px] text-[#8ea0bc]">Возраст</div>
                <div class="mt-1 rounded-[10px] sm:rounded-[12px] bg-[#1c2432] px-2 py-2 text-[12px] sm:text-[13px] font-bold leading-none text-[#eef3ff]">
                    {{ $age ?: '-' }}
                </div>
            </div>
        </div>
    </div>

    <div class="mt-2 space-y-1 text-[13px] leading-tight text-[#9aa8c2]">
        @if (!empty($metroNames))
            <div>Метро: {{ implode(', ', $metroNames) }}</div>
        @endif
        @if (!empty($districtNames))
            <div>Районы: {{ implode(', ', $districtNames) }}</div>
        @endif
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-2">
        @if ($phoneCopyValue && $phoneDisplay)
            <button type="button"
                data-x-data="{
                    shown: false,
                    copied: false,
                    copyTimer: null,
                    phoneValue: @js($phoneCopyValue),
                    async handlePhoneClick() {
                        if (!this.shown) {
                            this.shown = true;
                            return;
                        }

                        let copiedOk = false;
                        try {
                            if (navigator.clipboard && window.isSecureContext) {
                                await navigator.clipboard.writeText(this.phoneValue);
                                copiedOk = true;
                            } else {
                                const textArea = document.createElement('textarea');
                                textArea.value = this.phoneValue;
                                textArea.setAttribute('readonly', '');
                                textArea.style.position = 'fixed';
                                textArea.style.left = '-9999px';
                                document.body.appendChild(textArea);
                                textArea.select();
                                copiedOk = document.execCommand('copy');
                                document.body.removeChild(textArea);
                            }
                        } catch (e) {
                            copiedOk = false;
                        }

                        if (!copiedOk) {
                            return;
                        }

                        this.copied = true;
                        clearTimeout(this.copyTimer);
                        this.copyTimer = setTimeout(() => {
                            this.copied = false;
                        }, 1600);
                    }
                }"
                data-x-on:click="handlePhoneClick"
                class="flex h-12 flex-1 items-center justify-center rounded-full border border-[#2a3142] bg-[#151b25] px-3 text-center font-semibold text-[14px] text-[#eef3ff] transition-colors hover:bg-[#1c2432] focus:outline-none">
                <span data-x-show="!shown && !copied">Посмотреть телефон</span>
                <span data-x-show="shown && !copied" style="display: none;">{{ $phoneDisplay }}</span>
                <span data-x-show="copied" style="display: none;">Номер скопирован</span>
            </button>
        @else
            <span class="group/phone relative flex h-12 flex-1 items-center justify-center rounded-full border border-[#2a3142] bg-[#232a35] font-semibold px-3 text-center text-[14px] text-[#909db3] cursor-not-allowed">
                Телефон отсутствует
                <span class="pointer-events-none absolute left-1/2 bottom-full z-30 mb-2 w-max max-w-[180px] -translate-x-1/2 rounded-md border border-[#2b2c33] bg-[#121319] px-2 py-1 text-[10px] font-medium normal-case tracking-normal text-[#d7d9e0] opacity-0 shadow-lg transition-opacity duration-200 group-hover/phone:opacity-100">
                    Номер телефона отсутствует
                </span>
            </span>
        @endif

        @if ($waLink)
            <a href="{{ $waLink }}" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-[#26d366] bg-[#163325] text-[#8de5ac] transition-colors hover:bg-[#1d422f] hover:!text-[#9ff0bc]">
                <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.008-.57-.008-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                </svg>
            </a>
        @endif

        @if ($tgLink)
            <a href="{{ $tgLink }}" target="_blank" rel="noopener noreferrer" aria-label="Telegram" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-[#2aa5e0] bg-[#15273a] text-[#91d2f2] transition-colors hover:bg-[#1c3249] hover:!text-[#a7def8]">
                <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12.068 12.068 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z" />
                </svg>
            </a>
        @endif

        @if ($maxLink)
            <a href="{{ $maxLink }}" target="_blank" rel="noopener noreferrer" aria-label="Max" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-[#6d8bff] bg-[#1a2746] text-[#b6c5ff] transition-colors hover:bg-[#23345b] hover:!text-[#c7d4ff]">
                <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4 18V6h3.2L12 12.2 16.8 6H20v12h-2.9v-7.3L12 16.8l-5.1-6.1V18z" />
                </svg>
            </a>
        @endif
    </div>

    <div class="mt-3 rounded-[20px] border border-[#2a3142] bg-[#151b25] px-3 sm:px-4 py-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-3 text-[13px] leading-none text-[#9aa8c2]">
            <div class="flex items-center justify-between gap-2">
                <span>1 час</span>
                <span class="font-bold text-[#ff2f7b]">
                    @if ($price1h)
                        {{ number_format((float) $price1h, 0, '.', ' ') }} <span class="text-[10px]">{{ $currency }}</span>
                    @else
                        -
                    @endif
                </span>
            </div>

            <div class="flex items-center justify-between gap-2">
                <span>ночь</span>
                <span class="font-bold text-[#ff2f7b]">
                    @if ($priceNight)
                        {{ number_format((float) $priceNight, 0, '.', ' ') }} <span class="text-[10px]">{{ $currency }}</span>
                    @else
                        -
                    @endif
                </span>
            </div>

            <div class="flex items-center justify-between gap-2">
                <span>2 часа</span>
                <span class="font-bold text-[#ff2f7b]">
                    @if ($price2h)
                        {{ number_format((float) $price2h, 0, '.', ' ') }} <span class="text-[10px]">{{ $currency }}</span>
                    @else
                        -
                    @endif
                </span>
            </div>

            <div class="flex items-center justify-between gap-2">
                <span>сутки</span>
                <span class="font-bold text-[#ff2f7b]">
                    @if ($priceDay)
                        {{ number_format((float) $priceDay, 0, '.', ' ') }} <span class="text-[10px]">{{ $currency }}</span>
                    @else
                        -
                    @endif
                </span>
            </div>
        </div>
    </div>
</article>
