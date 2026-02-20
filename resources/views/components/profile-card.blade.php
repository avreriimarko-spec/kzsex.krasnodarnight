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
    $metroName = !empty($metroTerms) && !is_wp_error($metroTerms) ? $metroTerms[0]->name : null;

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

    $price = get_field('price') ?: [];
    $currency = strtoupper($price['currency'] ?? 'RUB');
    $price1h = $price['price_1h_out'] ?? ($price['price_1h'] ?? null);
    $price2h = $price['price_2h_out'] ?? ($price['price_2h'] ?? ($price1h ? $price1h * 2 : null));
    $priceNight = $price['price_night_out'] ?? ($price['price_night'] ?? ($price1h ? ($price1h * 5 + ($price2h ?: 0)) : null));

    $inoutcallSlugs = wp_get_post_terms($profileId, 'inoutcall', ['fields' => 'slugs']);
    $inoutcallSlugs = is_wp_error($inoutcallSlugs) ? [] : $inoutcallSlugs;
    $isOutcall = in_array('outcall', $inoutcallSlugs, true) || in_array('incall-and-outcall', $inoutcallSlugs, true);

    $profilePhone = get_field('phone');
    $phoneHref = null;
    if (!empty($profilePhone)) {
        $phoneDigits = preg_replace('/\D+/', '', (string) $profilePhone);
        if ($phoneDigits) {
            if (strlen($phoneDigits) === 11 && str_starts_with($phoneDigits, '8')) {
                $phoneDigits = '7' . substr($phoneDigits, 1);
            }
            $phoneHref = 'tel:+' . $phoneDigits;
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

<article class="group h-full overflow-hidden rounded-[14px] border border-[#d8d8d8] bg-[#efefef] shadow-[0_4px_14px_rgba(0,0,0,0.10)]">
    <figure class="relative aspect-[3/4] w-full overflow-hidden bg-[#d9d9d9]">
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
                <span class="rounded-full bg-white px-4 py-1.5 text-[11px] font-extrabold uppercase tracking-wide text-[#232323] shadow">
                    Проверенная
                </span>
            @endif
            @if ($isVip)
                <span class="rounded-full bg-[#1f1f26] px-5 py-1.5 text-[11px] font-extrabold uppercase tracking-wide text-white shadow">
                    VIP
                </span>
            @endif
        </div>
    </figure>

    <div class="space-y-3 p-4">
        <div class="flex items-end gap-2 leading-none">
            <a href="{{ profile_url($profileId) }}" class="">
                <h2 class="relative z-20 font-serif text-[21px] font-extrabold text-[#1f1f1f] hover:!text-[#1f1f1f]">
                    {{ $profileName }}
                </h2>    
            </a>
        </div>

        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] uppercase tracking-wide text-[#777]">
            <span>г. {{ mb_strtoupper($cityName) }}</span>
            @if ($metroName)
                <span class="inline-flex items-center gap-1">
                    <span class="h-2 w-2 rounded-full bg-[#f0a100]"></span>
                    м. {{ mb_strtoupper($metroName) }}
                </span>
            @endif
        </div>

        <div class="grid grid-cols-4 gap-2">
            <div class="rounded-lg border border-[#d7d7d7] bg-[#ececec] px-2 py-2 text-center">
                <div class="text-[10px] uppercase tracking-wide text-[#9d9d9d]">Возраст</div>
                <div class="mt-1 text-[11px] font-black leading-none text-[#2a2a2a]">{{ $age ?: '-' }}</div>
            </div>
            <div class="rounded-lg border border-[#d7d7d7] bg-[#ececec] px-2 py-2 text-center">
                <div class="text-[10px] uppercase tracking-wide text-[#9d9d9d]">Рост</div>
                <div class="mt-1 text-[11px] font-black leading-none text-[#2a2a2a]">{{ $height ?: '-' }}</div>
            </div>
            <div class="rounded-lg border border-[#d7d7d7] bg-[#ececec] px-2 py-2 text-center">
                <div class="text-[10px] uppercase tracking-wide text-[#9d9d9d]">Вес</div>
                <div class="mt-1 text-[11px] font-black leading-none text-[#2a2a2a]">{{ $weight ?: '-' }}</div>
            </div>
            <div class="rounded-lg border border-[#d7d7d7] bg-[#ececec] px-2 py-2 text-center">
                <div class="text-[10px] uppercase tracking-wide text-[#9d9d9d]">Грудь</div>
                <div class="mt-1 text-[11px] font-black leading-none text-[#2a2a2a]">{{ $breast ?: '-' }}</div>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-2">
            <div class="rounded-lg border border-[#d7d7d7] bg-[#ececec] px-2 py-2 text-center">
                <div class="text-[12px] tracking-wide text-[#9d9d9d]">1 час</div>
                <div class="mt-1 text-[11px] font-black leading-none text-[#2a2a2a]">
                    {{ $price1h ? number_format((float) $price1h, 0, '.', ' ') . ' ' . $currency : '-' }}
                </div>
            </div>
            <div class="rounded-lg border border-[#d7d7d7] bg-[#ececec] px-2 py-2 text-center">
                <div class="text-[12px] tracking-wide text-[#9d9d9d]">2 часа</div>
                <div class="mt-1 text-[11px] font-black leading-none text-[#2a2a2a]">
                    {{ $price2h ? number_format((float) $price2h, 0, '.', ' ') . ' ' . $currency : '-' }}
                </div>
            </div>
            <div class="rounded-lg border border-[#d7d7d7] bg-[#ececec] px-2 py-2 text-center">
                <div class="text-[12px] tracking-wide text-[#9d9d9d]">ночь</div>
                <div class="mt-1 text-[11px] font-black leading-none text-[#2a2a2a]">
                    {{ $priceNight ? number_format((float) $priceNight, 0, '.', ' ') . ' ' . $currency : '-' }}
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between border-y border-[#dddddd] py-2 text-[12px] uppercase tracking-wide text-[#6f6f6f]">
            <span>Выезд</span>
            <span class="font-extrabold text-[#434343]">{{ $isOutcall ? 'Да' : 'Нет' }}</span>
        </div>

        <div class="grid grid-cols-3 gap-2">
            @if ($phoneHref)
                <a href="{{ $phoneHref }}" class="relative z-20 rounded-lg bg-[#181920] px-1 py-2 text-center text-[11px] text-white">
                    Позвонить
                </a>
            @else
                <span class="rounded-lg bg-[#9a9ca8] px-1 py-2 text-center text-[11px] text-white">Позвонить</span>
            @endif

            @if ($waLink)
                <a href="{{ $waLink }}" target="_blank" rel="noopener noreferrer" class="relative z-20 rounded-lg bg-[#26d366] px-1 py-2 text-center text-[11px] text-white">
                    WhatsApp
                </a>
            @else
                <span class="rounded-lg bg-[#8fd9ab] px-1 py-2 text-center text-[11px] text-white">WhatsApp</span>
            @endif

            @if ($tgLink)
                <a href="{{ $tgLink }}" target="_blank" rel="noopener noreferrer" class="relative z-20 rounded-lg bg-[#2aa5e0] px-1 py-2 text-center text-[11px] text-white">
                    Telegram
                </a>
            @else
                <span class="rounded-lg bg-[#8fbfdd] px-1 py-2 text-center text-[11px] text-white">Telegram</span>
            @endif
        </div>
    </div>
</article>
