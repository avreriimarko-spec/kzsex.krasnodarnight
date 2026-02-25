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

    $price = \App\Services\ProfilePriceCalculator::apply(get_field('price') ?: []);
    $currency = strtoupper($price['currency'] ?? 'RUB');
    $price1h = $price['price_1h_out'] ?? ($price['price_1h'] ?? null);
    $price2h = $price['price_2h_out'] ?? ($price['price_2h'] ?? null);
    $priceNight = $price['price_night_out'] ?? ($price['price_night'] ?? null);

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

<article class="group h-full overflow-visible rounded-[14px] border border-[#2a3142] bg-[#0e1015] shadow-[0_8px_24px_rgba(0,0,0,0.45)]">
    <figure class="relative aspect-[3/4] w-full overflow-hidden rounded-t-[14px] bg-[#141a24]">
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

    <div class="space-y-3 p-4">
        <div class="flex items-end gap-2 leading-none">
            <a href="{{ profile_url($profileId) }}" class="">
                <h2 class="relative z-20 font-serif text-[21px] font-extrabold text-white hover:!text-[#cd1d46] transition-colors">
                    {{ $profileName }}
                </h2>    
            </a>
        </div>

        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] capitalize tracking-wide text-[#98a4bc]">
            <span>г. {{ mb_strtoupper($cityName) }}</span>
            @if ($metroName)
                <span class="inline-flex items-center gap-1">
                    <span class="h-2 w-2 rounded-full bg-[#f0a100]"></span>
                    м. {{ mb_strtoupper($metroName) }}
                </span>
            @endif
        </div>

        <div class="grid grid-cols-4 gap-2">
            <div class="rounded-lg border border-[#2a3142] bg-[#151c28] px-2 py-2 text-center">
                <div class="text-[10px] capitalize tracking-wide text-[#8ea0bc]">Возраст</div>
                <div class="mt-1 text-[11px] font-[#fff] leading-none text-[#eef3ff]">{{ $age ?: '-' }}</div>
            </div>
            <div class="rounded-lg border border-[#2a3142] bg-[#151c28] px-2 py-2 text-center">
                <div class="text-[10px] capitalize tracking-wide text-[#8ea0bc]">Рост</div>
                <div class="mt-1 text-[11px] font-[#fff] leading-none text-[#eef3ff]">{{ $height ?: '-' }}</div>
            </div>
            <div class="rounded-lg border border-[#2a3142] bg-[#151c28] px-2 py-2 text-center">
                <div class="text-[10px] capitalize tracking-wide text-[#8ea0bc]">Вес</div>
                <div class="mt-1 text-[11px] font-[#fff] leading-none text-[#eef3ff]">{{ $weight ?: '-' }}</div>
            </div>
            <div class="rounded-lg border border-[#2a3142] bg-[#151c28] px-2 py-2 text-center">
                <div class="text-[10px] capitalize tracking-wide text-[#8ea0bc]">Грудь</div>
                <div class="mt-1 text-[11px] font-[#fff] leading-none text-[#eef3ff]">{{ $breast ?: '-' }}</div>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-2">
            <div class="rounded-lg border border-[#2a3142] bg-[#151c28] px-2 py-2 text-center">
                <div class="text-[12px] tracking-wide text-[#8ea0bc]">1 час</div>
                <div class="mt-1 text-[11px] font-[#fff] leading-none text-[#eef3ff]">
                    {{ $price1h ? number_format((float) $price1h, 0, '.', ' ') . ' ' . $currency : '-' }}
                </div>
            </div>
            <div class="rounded-lg border border-[#2a3142] bg-[#151c28] px-2 py-2 text-center">
                <div class="text-[12px] tracking-wide text-[#8ea0bc]">2 часа</div>
                <div class="mt-1 text-[11px] font-[#fff] leading-none text-[#eef3ff]">
                    {{ $price2h ? number_format((float) $price2h, 0, '.', ' ') . ' ' . $currency : '-' }}
                </div>
            </div>
            <div class="rounded-lg border border-[#2a3142] bg-[#151c28] px-2 py-2 text-center">
                <div class="text-[12px] tracking-wide text-[#8ea0bc]">ночь</div>
                <div class="mt-1 text-[11px] font-[#fff] leading-none text-[#eef3ff]">
                    {{ $priceNight ? number_format((float) $priceNight, 0, '.', ' ') . ' ' . $currency : '-' }}
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between border-y border-[#2a3142] py-2 text-[12px] capitalize tracking-wide text-[#98a4bc]">
            <span>Выезд</span>
            <span class="font-extrabold text-[#eef3ff]">{{ $isOutcall ? 'Да' : 'Нет' }}</span>
        </div>

        <div class="grid grid-cols-4 gap-2">
            @if ($phoneHref)
                <a href="{{ $phoneHref }}" class="relative z-20 rounded bg-[#181920] px-1 py-2 text-center text-[11px] text-white !text-white visited:!text-white hover:!text-white focus:!text-white active:!text-white transition-colors duration-200 hover:bg-[#23242d]">
                    Позвонить
                </a>
            @else
                <span class="group/phone relative rounded bg-[#9a9ca8] px-1 py-2 text-center text-[11px] text-white cursor-not-allowed">
                    Позвонить
                    <span class="pointer-events-none absolute left-1/2 bottom-full z-30 mb-2 w-max max-w-[180px] -translate-x-1/2 rounded-md border border-[#2b2c33] bg-[#121319] px-2 py-1 text-[10px] font-medium normal-case tracking-normal text-[#d7d9e0] opacity-0 shadow-lg transition-opacity duration-200 group-hover/phone:opacity-100">
                        Номер телефона отсутствует
                    </span>
                </span>
            @endif

            @if ($waLink)
                <a href="{{ $waLink }}" target="_blank" rel="noopener noreferrer" class="relative z-20 rounded bg-[#26d366] px-1 py-2 text-center text-[11px] text-white !text-white visited:!text-white hover:!text-white focus:!text-white active:!text-white transition-colors duration-200 hover:bg-[#1fba57]">
                    WhatsApp
                </a>
            @else
                <span class="group/wa relative rounded bg-[#8fd9ab] px-1 py-2 text-center text-[11px] text-white cursor-not-allowed">
                    WhatsApp
                    <span class="pointer-events-none absolute left-1/2 bottom-full z-30 mb-2 w-max max-w-[180px] -translate-x-1/2 rounded-md border border-[#2b2c33] bg-[#121319] px-2 py-1 text-[10px] font-medium normal-case tracking-normal text-[#d7d9e0] opacity-0 shadow-lg transition-opacity duration-200 group-hover/wa:opacity-100">
                        WhatsApp отсутствует
                    </span>
                </span>
            @endif

            @if ($tgLink)
                <a href="{{ $tgLink }}" target="_blank" rel="noopener noreferrer" class="relative z-20 rounded bg-[#2aa5e0] px-1 py-2 text-center text-[11px] text-white !text-white visited:!text-white hover:!text-white focus:!text-white active:!text-white transition-colors duration-200 hover:bg-[#1d8fc6]">
                    Telegram
                </a>
            @else
                <span class="group/tg relative rounded bg-[#8fbfdd] px-1 py-2 text-center text-[11px] text-white cursor-not-allowed">
                    Telegram
                    <span class="pointer-events-none absolute left-1/2 bottom-full z-30 mb-2 w-max max-w-[180px] -translate-x-1/2 rounded-md border border-[#2b2c33] bg-[#121319] px-2 py-1 text-[10px] font-medium normal-case tracking-normal text-[#d7d9e0] opacity-0 shadow-lg transition-opacity duration-200 group-hover/tg:opacity-100">
                        Telegram отсутствует
                    </span>
                </span>
            @endif

            @if ($maxLink)
                <a href="{{ $maxLink }}" target="_blank" rel="noopener noreferrer" class="relative z-20 rounded bg-[#1e3a8a] px-1 py-2 text-center text-[11px] font-semibold text-white !text-white visited:!text-white hover:!text-white focus:!text-white active:!text-white transition-colors duration-200 hover:bg-[#1e40af]">
                    Max
                </a>
            @else
                <span class="group/max relative rounded bg-[#6477a8] px-1 py-2 text-center text-[11px] font-semibold text-white cursor-not-allowed">
                    Max
                    <span class="pointer-events-none absolute left-1/2 bottom-full z-30 mb-2 w-max max-w-[180px] -translate-x-1/2 rounded-md border border-[#2b2c33] bg-[#121319] px-2 py-1 text-[10px] font-medium normal-case tracking-normal text-[#d7d9e0] opacity-0 shadow-lg transition-opacity duration-200 group-hover/max:opacity-100">
                        Max отсутствует
                    </span>
                </span>
            @endif
        </div>
    </div>
</article>
