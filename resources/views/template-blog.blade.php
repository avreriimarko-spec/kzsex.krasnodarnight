{{--
  Template Name: Блог
--}}

@extends('layouts.app')

@section('content')
  <main class="page-hero page-hero--blog pb-16">
      <div class="page-hero__inner container mx-auto px-4">

          <header class="mb-10 text-center max-w-4xl mx-auto">
              <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold mb-4 text-neutral-900">
                  {{ esc_html($heading) }}
              </h1>
              @if ($lead)
                  <p class="text-lg text-neutral-600 leading-relaxed max-w-2xl mx-auto">
                      {{ esc_html($lead) }}
                  </p>
              @endif
          </header>

          @if ($blog_query->have_posts())
              <div id="blog-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                  @while ($blog_query->have_posts()) @php $blog_query->the_post() @endphp
                      {{-- Вызываем наш компонент карточки и передаем туда текущий пост как prop --}}
                      <x-blog-card :post="get_post()" />
                  @endwhile
                  @php wp_reset_postdata() @endphp
              </div>

              @if ($blog_query->max_num_pages > 1)
                  <div class="mt-12 flex justify-center">
                      <button id="blog-load-more"
                          class="group relative px-6 py-3 bg-white border border-neutral-300 text-neutral-700 font-medium rounded-full hover:border-accent hover:text-accent focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent transition-all duration-300 flex items-center space-x-2"
                          data-current="{{ (int)$paged }}"
                          data-total="{{ (int)$blog_query->max_num_pages }}"
                      >
                          <span class="relative z-10">Показать ещё</span>
                          <svg class="w-5 h-5 animate-spin hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                          </svg>
                      </button>
                  </div>
              @endif

          @else
              <div class="py-20 text-center">
                  <div class="inline-block p-4 rounded-full bg-neutral-50 mb-4">
                      <svg class="w-12 h-12 text-neutral-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                      </svg>
                  </div>
                  <p class="text-xl text-neutral-600">Статей пока нет.</p>
              </div>
          @endif

      </div>
  </main>

  {{-- JS скрипт оставляем без изменений, он отлично работает --}}
  <script>
  document.addEventListener('DOMContentLoaded', function() {
      const btn = document.getElementById('blog-load-more');
      if (!btn) return;
      
      const list = document.getElementById('blog-list');
      const spinner = btn.querySelector('svg');
      const label = btn.querySelector('span');
      
      let current = parseInt(btn.dataset.current || '1', 10);
      const total = parseInt(btn.dataset.total || '1', 10);
      
      if (current >= total) {
          btn.style.display = 'none';
          return;
      }

      let isLoading = false;

      btn.addEventListener('click', function() {
          if (isLoading || current >= total) return;
          
          isLoading = true;
          btn.classList.add('opacity-75', 'cursor-not-allowed');
          if (spinner) spinner.classList.remove('hidden');
          label.textContent = 'Загрузка...';
          
          const next = current + 1;
          const url = window.location.pathname + (window.location.search ? window.location.search + '&' : '?') + 'ajax_blog=1&paged=' + next;

          fetch(url)
              .then(response => {
                  if (!response.ok) throw new Error('Network error');
                  return response.text();
              })
              .then(html => {
                  if (!html.trim()) {
                      btn.style.display = 'none';
                      return;
                  }
                  
                  list.insertAdjacentHTML('beforeend', html);
                  current = next;
                  btn.dataset.current = current;
                  
                  if (current >= total) {
                      btn.style.display = 'none';
                  }
              })
              .catch(err => {
                  console.error(err);
                  label.textContent = 'Ошибка';
                  setTimeout(() => {
                       label.textContent = 'Показать ещё';
                  }, 2000);
              })
              .finally(() => {
                  isLoading = false;
                  btn.classList.remove('opacity-75', 'cursor-not-allowed');
                  if (spinner) spinner.classList.add('hidden');
                  if (current < total) {
                      label.textContent = 'Показать ещё';
                  }
              });
      });
  });
  </script>
@endsection