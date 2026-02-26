@if (!is_front_page())
    <div class="container mx-auto px-4 py-2.5 md:py-3">
        <nav class="overflow-x-auto whitespace-nowrap scrollbar-hide" aria-label="Breadcrumb">
            <ol class="inline-flex min-w-max items-center gap-1 text-xs md:gap-1.5 md:text-sm text-gray-400">
                @foreach ($crumbs as $crumb)
                    <li class="inline-flex items-center gap-1 md:gap-1.5">
                        @if (!$loop->first)
                            <svg class="h-3.5 w-3.5 text-gray-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        @endif

                        @if ($crumb['current'])
                            <span class="inline-flex max-w-[65vw] md:max-w-[28rem] items-center rounded-lg border border-[#cd1d46]/30 bg-[#cd1d46]/10 px-2.5 py-1 font-semibold text-gray-100 truncate" aria-current="page">
                                {{ $crumb['label'] }}
                            </span>
                        @else
                            <a href="{{ $crumb['url'] }}" class="inline-flex items-center rounded-lg px-2.5 py-1 text-gray-400 transition-colors hover:bg-white/5 hover:text-[#cd1d46]">
                                @if ($loop->first)
                                    <svg class="mr-1 h-3.5 w-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                        <path
                                            d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z">
                                        </path>
                                    </svg>
                                @endif
                                <span class="truncate max-w-[45vw] md:max-w-none">{{ $crumb['label'] }}</span>
                            </a>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    </div>
@endif
