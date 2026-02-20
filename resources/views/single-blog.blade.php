@extends('layouts.app')

@section('content')
    @php
        $getf = function_exists('get_field');

        $imgFromAcf = static function ($photo, string $size = 'full'): string {
            if (!$photo) {
                return '';
            }
            if (is_numeric($photo)) {
                $img = wp_get_attachment_image_src((int) $photo, $size);
                return $img ? (string) $img[0] : '';
            }
            if (is_array($photo)) {
                if (!empty($photo['url'])) {
                    return (string) $photo['url'];
                }
                if (!empty($photo['ID'])) {
                    $img = wp_get_attachment_image_src((int) $photo['ID'], $size);
                    return $img ? (string) $img[0] : '';
                }
                return '';
            }
            if (is_string($photo)) {
                return $photo;
            }
            return '';
        };

        $starline = static function ($rating): string {
            $rating = max(0, min(5, (int) $rating));
            $out = '<div class="flex items-center gap-0.5" aria-label="Рейтинг ' . $rating . ' из 5">';
            for ($i = 1; $i <= 5; $i++) {
                $fill = $i <= $rating ? 'currentColor' : 'none';
                $cls = $i <= $rating ? 'text-yellow-500' : 'text-neutral-300';
                $out .= '<svg class="w-4 h-4 ' . $cls . '" viewBox="0 0 24 24" fill="' . $fill . '" stroke="currentColor" stroke-width="1.5"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.25l-7.19-.61L12 2 9.19 8.64 2 9.25l5.46 4.72L5.82 21z"/></svg>';
            }
            return $out . '</div>';
        };
    @endphp

    @while (have_posts())
        @php
            the_post();
        @endphp

        @php
            $postId = get_the_ID();
            $h1 = $getf ? (get_field('h1_statiya') ?: '') : '';
            $lead = $getf ? (get_field('p_statiya') ?: '') : '';
            $seoBlockHtml = $getf ? (get_field('seo_statiya') ?: '') : '';
            $photo = $getf ? (get_field('photo_statiya') ?: '') : '';

            $dateHuman = date_i18n('j F, Y', get_post_time('U', true));
            $dateIso = get_post_time('c', true);
            $titleOut = $h1 !== '' ? $h1 : get_the_title();

            $photoUrl = $imgFromAcf($photo, 'full');
            if (!$photoUrl) {
                $thumbId = get_post_thumbnail_id();
                if ($thumbId) {
                    $img = wp_get_attachment_image_src($thumbId, 'full');
                    $photoUrl = $img ? (string) $img[0] : '';
                }
            }

            $ajaxUrl = admin_url('admin-ajax.php');
            $nonce = wp_create_nonce('blog_review_nonce');

            $comments = get_comments([
                'post_id' => $postId,
                'status' => 'approve',
                'type' => 'comment',
                'orderby' => 'comment_date_gmt',
                'order' => 'DESC',
                'number' => 0,
            ]);

            $side = new WP_Query([
                'post_type' => 'blog',
                'post_status' => 'publish',
                'posts_per_page' => 10,
                'post__not_in' => [$postId],
                'no_found_rows' => true,
            ]);
        @endphp

        <main id="blog-article-page" class="px-4 py-10">
            <div class="blog-article-wrap">
                <div class="blog-article-grid">
                    <section class="lg:col-span-2">
                        <h1 class="blog-article-title text-[40px] leading-tight font-extrabold text-black mb-4">
                            {{ esc_html($titleOut) }}
                        </h1>

                        <div class="mb-6">
                            <div class="w-fit">
                                <time class="blog-article-date block text-[13px] font-semibold text-neutral-800"
                                    datetime="{{ esc_attr($dateIso) }}">
                                    {{ esc_html($dateHuman) }}
                                </time>
                                <span aria-hidden="true" class="blog-article-date-line mt-1 block h-[2px] w-full bg-neutral-300"></span>
                            </div>
                        </div>

                        @if ($photoUrl)
                            <figure class="blog-article-cover rounded-sm overflow-hidden">
                                <img src="{{ esc_url($photoUrl) }}" alt="{{ esc_attr($titleOut) }}"
                                    class="blog-article-cover-img w-full max-h-[320px] object-cover" loading="eager" decoding="async" />
                            </figure>
                            <div class="h-8"></div>
                        @endif

                        @if ($lead)
                            <p class="blog-article-lead text-[17px] leading-relaxed text-neutral-800 mb-6 max-w-3xl">
                                {{ esc_html($lead) }}
                            </p>
                        @endif

                        <article class="blog-article-prose prose prose-neutral max-w-none prose-img:rounded-sm prose-a:text-[#e865a0]">
                            @php
                                the_content();
                            @endphp
                            @php
                                wp_link_pages([
                                    'before' => '<div class="mt-6 text-sm text-neutral-600">Страницы: ',
                                    'after' => '</div>',
                                ]);
                            @endphp
</article>

                        @if ($seoBlockHtml)
                            <section class="blog-article-seo mt-10 prose prose-neutral max-w-none content">
                                {!! $seoBlockHtml !!}
                            </section>
                        @endif

                        <section id="post-reviews" class="mt-12">
                            <h2 class="text-2xl font-extrabold text-black mb-3">Отзывы к статье</h2>
                            <p class="text-sm text-neutral-700 mb-4">
                                Оставьте отзыв — он появится после модерации. E-mail виден только модератору.
                            </p>

                            <form id="blog-review-form"
                                class="rounded-xl border border-[rgba(232,101,160,.18)] bg-white p-4 md:p-5 space-y-4"
                                method="post" data-ajax="{{ esc_url($ajaxUrl) }}">
                                <input type="hidden" name="action" value="blog_add_review">
                                <input type="hidden" name="nonce" value="{{ esc_attr($nonce) }}">
                                <input type="hidden" name="post_id" value="{{ (int) $postId }}">
                                <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off">

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm mb-1">Имя*</label>
                                        <input name="name" type="text" required placeholder="Ваше имя"
                                            class="w-full rounded-lg border border-neutral-300 bg-white text-black placeholder-neutral-400 px-3 py-2 outline-none focus:border-[#e865a0]">
                                    </div>
                                    <div>
                                        <label class="block text-sm mb-1">E-mail*</label>
                                        <input name="email" type="email" required placeholder="you@example.com"
                                            class="w-full rounded-lg border border-neutral-300 bg-white text-black placeholder-neutral-400 px-3 py-2 outline-none focus:border-[#e865a0]">
                                        <p class="mt-1 text-[12px] text-neutral-500">Не публикуется.</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm mb-1">Оценка*</label>
                                        <select name="rating" required
                                            class="w-full rounded-lg border border-neutral-300 bg-white text-black px-3 py-2 outline-none focus:border-[#e865a0]">
                                            <option value="">Выберите</option>
                                            <option value="5">5 — Отлично</option>
                                            <option value="4">4 — Хорошо</option>
                                            <option value="3">3 — Нормально</option>
                                            <option value="2">2 — Плохо</option>
                                            <option value="1">1 — Ужасно</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm mb-1">Сообщение*</label>
                                    <textarea name="message" rows="4" required placeholder="Ваш отзыв"
                                        class="w-full rounded-lg border border-neutral-300 bg-white text-black placeholder-neutral-400 px-3 py-2 outline-none focus:border-[#e865a0]"></textarea>
                                </div>

                                <button id="blog-review-submit" type="submit"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-full bg-[#e865a0] text-white hover:bg-[#e62967] transition">
                                    Отправить на модерацию
                                </button>

                                <div id="blog-review-alert" class="hidden mt-2 text-sm rounded-lg px-3 py-2"></div>
                            </form>

                            <div class="mt-6">
                                @if ($comments)
                                    <ul class="space-y-4">
                                        @foreach ($comments as $c)
                                            @php
                                                $cName = $c->comment_author ?: 'Гость';
                                                $cDate = date_i18n('d.m.Y', strtotime($c->comment_date_gmt));
                                                $cIso = date('c', strtotime($c->comment_date_gmt));
                                                $cText = wpautop(esc_html($c->comment_content));
                                                $cRating = (int) get_comment_meta($c->comment_ID, '_rating', true);
                                            @endphp
                                            <li class="rounded-xl border border-neutral-200 bg-white p-4">
                                                <div class="flex items-center justify-between gap-3">
                                                    <div class="font-semibold text-black truncate">{{ esc_html($cName) }}</div>
                                                    <div class="flex items-center gap-3 shrink-0">
                                                        {!! $starline($cRating) !!}
                                                        <time class="text-xs text-neutral-500"
                                                            datetime="{{ esc_attr($cIso) }}">
                                                            {{ esc_html($cDate) }}
                                                        </time>
                                                    </div>
                                                </div>
                                                <div class="mt-2 text-[15px] leading-relaxed text-neutral-800">
                                                    {!! $cText !!}
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-neutral-600 text-sm">Пока нет опубликованных отзывов.</p>
                                @endif
                            </div>
                        </section>
                    </section>

                    <aside class="lg:col-span-1">
                        <div class="lg:sticky lg:top-24">
                            <h2 class="text-lg font-extrabold mb-3">Другие статьи</h2>

                            @if ($side->have_posts())
                                <ul class="space-y-2" id="side-posts">
                                    @php
                                        $i = 0;
                                    @endphp
                                    @while ($side->have_posts())
                                        @php
                                            $side->the_post();
                                            $sTitle = get_the_title();
                                            $sLink = get_permalink();
                                            $sDateHuman = date_i18n('j F Y', get_post_time('U', true));
                                            $sDateIso = get_post_time('c', true);

                                            $sP = $getf ? (get_field('p_statiya') ?: '') : '';
                                            $sSeo = $getf ? (get_field('seo_statiya') ?: '') : '';
                                            $sDescSrc = $sP !== '' ? $sP : ($sSeo !== '' ? wp_strip_all_tags($sSeo) : (has_excerpt() ? get_the_excerpt() : wp_strip_all_tags(get_the_content(''))));
                                            $sDesc = wp_trim_words($sDescSrc, 14, '…');

                                            $sPhoto = $getf ? (get_field('photo_statiya') ?: '') : '';
                                            $sImg = $imgFromAcf($sPhoto, 'medium');
                                            if (!$sImg) {
                                                $sThumbId = get_post_thumbnail_id();
                                                if ($sThumbId) {
                                                    $sIm = wp_get_attachment_image_src($sThumbId, 'medium');
                                                    $sImg = $sIm ? (string) $sIm[0] : '';
                                                }
                                            }

                                            $hiddenCls = $i >= 4 ? 'hidden side-more' : '';
                                        @endphp
                                        <li class="{{ esc_attr($hiddenCls) }}">
                                            <a href="{{ esc_url($sLink) }}"
                                                class="blog-side-card group grid grid-cols-[80px,1fr] gap-3 items-start rounded-xl border border-[rgba(232,101,160,.18)] bg-white p-2 hover:shadow-[0_6px_16px_rgba(0,0,0,.06)] transition">
                                                <div class="blog-side-thumb w-[80px] h-[80px] rounded-lg overflow-hidden bg-neutral-100">
                                                    @if ($sImg)
                                                        <img src="{{ esc_url($sImg) }}" alt="{{ esc_attr($sTitle) }}"
                                                            class="blog-side-thumb-img w-full h-full object-cover transition group-hover:scale-[1.03]"
                                                            loading="lazy" decoding="async" />
                                                    @else
                                                        <div
                                                            class="w-full h-full flex items-center justify-center text-neutral-400 text-xs">
                                                            Нет фото
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="min-w-0">
                                                    <span class="blog-side-date-pill inline-flex items-center rounded-full bg-[rgba(232,101,160,.08)] text-[#e865a0] px-2 py-[2px] text-[11px] font-semibold">
                                                        <time
                                                            datetime="{{ esc_attr($sDateIso) }}">{{ esc_html($sDateHuman) }}</time>
                                                    </span>
                                                    <h3 class="blog-side-title mt-1 text-[14px] font-extrabold leading-snug text-black group-hover:underline underline-offset-4 decoration-[#e865a0]">
                                                        {{ esc_html($sTitle) }}
                                                    </h3>
                                                    @if ($sDesc)
                                                        <p class="blog-side-desc mt-[2px] text-[12px] text-neutral-700 line-clamp-2">
                                                            {{ esc_html($sDesc) }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </a>
                                        </li>
                                        @php
                                            $i++;
                                        @endphp
                                    @endwhile
                                    @php
                                        wp_reset_postdata();
                                    @endphp
                                </ul>

                                @if ($i > 4)
                                    <div class="mt-3">
                                        <button type="button" id="side-more-btn"
                                            class="w-full text-center text-[13px] font-semibold px-3 py-2 rounded-full border border-[#e865a0] text-[#e865a0] hover:bg-[rgba(232,101,160,.06)] transition">
                                            Показать ещё
                                        </button>
                                    </div>
                                @endif
                            @else
                                <p class="text-neutral-500 text-sm">Пока нет других статей.</p>
                            @endif
                        </div>
                    </aside>
                </div>
            </div>
        </main>
    @endwhile

    <style>
        #blog-article-page {
            padding: 40px 16px;
        }

        #blog-article-page .blog-article-wrap {
            max-width: 1200px;
            margin: 0 auto;
        }

        #blog-article-page .blog-article-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 40px;
        }

        @media (min-width: 1024px) {
            #blog-article-page .blog-article-grid {
                grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
            }
        }

        #blog-article-page .blog-article-title {
            font-size: 40px;
            line-height: 1.1;
            font-weight: 800;
            color: #000;
            margin-bottom: 16px;
            letter-spacing: 0;
            text-transform: none;
        }

        #blog-article-page .blog-article-date {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #262626;
        }

        #blog-article-page .blog-article-date-line {
            display: block;
            height: 2px;
            width: 100%;
            background: #d4d4d4;
            margin-top: 4px;
        }

        #blog-article-page .blog-article-cover {
            border-radius: 2px;
            overflow: hidden;
        }

        #blog-article-page .blog-article-cover-img {
            width: 100%;
            max-height: 320px;
            object-fit: cover;
            display: block;
        }

        #blog-article-page .blog-article-lead {
            font-size: 17px;
            line-height: 1.65;
            color: #262626;
            margin-bottom: 24px;
            max-width: 48rem;
        }

        #blog-article-page .blog-article-prose,
        #blog-article-page .blog-article-seo {
            color: #404040;
            line-height: 1.75;
        }

        #blog-article-page .blog-article-prose p,
        #blog-article-page .blog-article-seo p {
            margin: 0 0 16px;
            font-size: 16px;
            line-height: 1.75;
        }

        #blog-article-page .blog-article-prose h1,
        #blog-article-page .blog-article-prose h2,
        #blog-article-page .blog-article-prose h3,
        #blog-article-page .blog-article-prose h4,
        #blog-article-page .blog-article-seo h1,
        #blog-article-page .blog-article-seo h2,
        #blog-article-page .blog-article-seo h3,
        #blog-article-page .blog-article-seo h4 {
            color: #111827;
            text-transform: none;
            letter-spacing: 0;
            border: 0;
            margin: 24px 0 12px;
            padding: 0;
        }

        #blog-article-page .blog-side-card {
            display: grid;
            grid-template-columns: 80px minmax(0, 1fr);
            gap: 12px;
            align-items: start;
            padding: 8px;
        }

        #blog-article-page .blog-side-thumb {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            background: #f5f5f5;
        }

        #blog-article-page .blog-side-thumb-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        #blog-article-page .blog-side-date-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            background: rgba(232, 101, 160, 0.08);
            color: #e865a0;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 600;
        }

        #blog-article-page .blog-side-title {
            margin-top: 4px;
            font-size: 14px;
            line-height: 1.35;
            font-weight: 800;
            color: #000;
            text-transform: none;
            letter-spacing: 0;
        }

        #blog-article-page .blog-side-desc {
            margin-top: 2px;
            font-size: 12px;
            line-height: 1.4;
            color: #404040;
        }
    </style>

    <script>
        (function() {
            const form = document.getElementById('blog-review-form');
            if (form) {
                const ajaxUrl = form.dataset.ajax || '/wp-admin/admin-ajax.php';
                const btn = document.getElementById('blog-review-submit');
                const alertBox = document.getElementById('blog-review-alert');

                function showAlert(type, msg) {
                    alertBox.classList.remove('hidden');
                    alertBox.className = 'mt-2 text-sm rounded-lg px-3 py-2 ' +
                        (type === 'ok' ?
                            'bg-green-50 text-green-700 border border-green-200' :
                            'bg-red-50 text-red-700 border border-red-200');
                    alertBox.textContent = msg;
                }

                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    alertBox.classList.add('hidden');

                    const email = (form.email?.value || '').trim();
                    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                        showAlert('err', 'Пожалуйста, укажите корректный e-mail.');
                        return;
                    }

                    btn.disabled = true;
                    btn.classList.add('opacity-60', 'cursor-not-allowed');

                    try {
                        const fd = new FormData(form);
                        const res = await fetch(ajaxUrl, {
                            method: 'POST',
                            credentials: 'same-origin',
                            body: fd
                        });
                        const txt = await res.text();
                        let j;
                        try {
                            j = JSON.parse(txt);
                        } catch (_e) {
                            throw new Error('Сбой сети или неверный ответ');
                        }

                        if (!j || !j.success) {
                            throw new Error((j && j.data && j.data.message) || 'Не удалось отправить');
                        }

                        showAlert('ok', (j.data && j.data.message) || 'Спасибо! Отзыв отправлен на модерацию.');
                        form.reset();
                    } catch (err) {
                        showAlert('err', err.message || 'Ошибка. Попробуйте позже.');
                    } finally {
                        btn.disabled = false;
                        btn.classList.remove('opacity-60', 'cursor-not-allowed');
                    }
                });
            }

            const sideBtn = document.getElementById('side-more-btn');
            const sideItems = document.querySelectorAll('.side-more');
            if (!sideBtn) {
                return;
            }

            let open = false;
            sideBtn.addEventListener('click', () => {
                open = !open;
                sideItems.forEach(el => el.classList.toggle('hidden', !open));
                sideBtn.textContent = open ? 'Скрыть' : 'Показать ещё';
            });
        })();
    </script>
@endsection
