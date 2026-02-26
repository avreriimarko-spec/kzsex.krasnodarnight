{{-- resources/views/partials/taxonomy-list.blade.php --}}
@php
    $terms = get_terms([
        'taxonomy' => $taxonomy,
        'hide_empty' => true,
        'orderby' => 'name',
        'order' => 'ASC'
    ]);
@endphp

<div class="container mx-auto px-4 py-8">
    {{-- Header --}}
    <header class="prose mb-10 text-center mx-auto">
        <h1 class="text-3xl md:text-5xl font-bold mb-4 capitalize tracking-tight text-[#cd1d46]">
            {!! get_field('custom_h1') ?: $default_title !!}
        </h1>
        @if ($intro = get_field('intro_text'))
            <p class="leading-relaxed max-w-2xl mx-auto text-gray-300">
                {{ $intro }}
            </p>
        @endif
    </header>

    {{-- LAYOUT --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 items-start">
        <div class="lg:col-span-4">
            {{-- ВЕРХНЯЯ ПАНЕЛЬ --}}
            <div class="flex flex-wrap items-center justify-between mb-6 pb-4 gap-4">
                <h2 class="text-xl md:text-2xl font-bold text-black tracking-wide">
                    {{ $found_label }}: {{ $terms && !is_wp_error($terms) ? count($terms) : 0 }}
                </h2>
            </div>

            {{-- Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6">
                @if($terms && !is_wp_error($terms))
                    @foreach($terms as $term)
                        <a href="{{ term_url($term) }}"
                           class="group block rounded-xl border border-[#cd1d46]/45 bg-[#11151d] px-4 py-5 text-center shadow-[0_10px_24px_rgba(0,0,0,0.35)] transition-all duration-300 hover:-translate-y-1 hover:border-[#cd1d46] hover:bg-[#171d27] hover:shadow-[0_12px_28px_rgba(205,29,70,0.28)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#cd1d46] focus-visible:ring-offset-2 focus-visible:ring-offset-[#0e1015]">
                            <h3 class="font-bold text-sm md:text-base capitalize tracking-wide text-gray-100 transition-colors duration-300 group-hover:text-[#ff4b73]">
                                {{ $term->name }}
                            </h3>
                        </a>
                    @endforeach
                @else
                    <div class="col-span-full text-center py-12">
                        <p class="text-gray-400 text-lg">{{ $not_found_text }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
