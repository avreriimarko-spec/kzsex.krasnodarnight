@if (!is_front_page())
    <div class="bg-[#0f0f0f] border-b border-gray-800">
        <div class="container mx-auto px-4 py-3">
            <nav class="flex text-xs md:text-sm text-gray-400 overflow-x-auto whitespace-nowrap scrollbar-hide"
                aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2">
                    @foreach ($crumbs as $key => $crumb)
                        <li class="inline-flex items-center">
                            @if (!$loop->first)
                                {{-- Разделитель (Слеш) --}}
                                <svg class="w-3 h-3 text-gray-500 mx-1" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7"></path>
                                </svg>
                            @endif

                            @if ($crumb['current'])
                                {{-- Текущая страница (не ссылка) --}}
                                <span class="text-black font-medium truncate max-w-[150px] md:max-w-xs"
                                    aria-current="page">
                                    {{ $crumb['label'] }}
                                </span>
                            @else
                                {{-- Ссылка --}}
                                <a href="{{ $crumb['url'] }}"
                                    class="inline-flex items-center hover:text-yellow-600 transition-colors">
                                    @if ($loop->first)
                                        {{-- Иконка домика для Главной --}}
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z">
                                            </path>
                                        </svg>
                                    @endif
                                    {{ $crumb['label'] }}
                                </a>
                            @endif
                        </li>
                    @endforeach

                </ol>
            </nav>
        </div>
    </div>
@endif
