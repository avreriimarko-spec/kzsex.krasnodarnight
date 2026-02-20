<?php

/**
 * Theme filters.
 */

namespace App;

/**
 * Add "… Continued" to the excerpt.
 */
add_filter('excerpt_more', function () {
    return sprintf(' &hellip; <a href="%s">%s</a>', get_permalink(), __('Continued', 'sage'));
});

/**
 * Настройка количества постов на странице архива/блога
 */
add_action('pre_get_posts', function ($query) {
    if (!is_admin() && $query->is_main_query()) {
        // Исключаем страницы профилей и специальные страницы
        if (
            ($query->is_home() || $query->is_archive()) && 
            !$query->is_post_type_archive('profile') &&
            !get_query_var('city') &&
            !get_query_var('special_page')
        ) {
            $query->set('posts_per_page', 48);
        }

        // Применяем GET-фильтры каталога к главному запросу таксономий/архивов профилей
        $hasCatalogFilters = false;
        foreach (array_keys($_GET) as $key) {
            if (
                in_array($key, ['price_min', 'price_max', 'age_min', 'age_max', 'height_min', 'height_max', 'weight_min', 'weight_max'], true)
                || str_starts_with($key, 'f_')
            ) {
                $hasCatalogFilters = true;
                break;
            }
        }

        if (!$hasCatalogFilters) {
            return;
        }

        $postType = $query->get('post_type');
        $isProfilePostType =
            $postType === 'profile'
            || (is_array($postType) && in_array('profile', $postType, true));

        $isCatalogContext =
            $query->is_post_type_archive('profile')
            || $query->is_tax()
            || $isProfilePostType
            || (bool) get_query_var('city')
            || (bool) get_query_var('special_page');

        if (!$isCatalogContext) {
            return;
        }

        $excludeTaxonomies = [];
        $currentTaxonomy = $query->get('taxonomy');
        if (is_string($currentTaxonomy) && $currentTaxonomy !== '') {
            $excludeTaxonomies[] = $currentTaxonomy;
        }

        $args = [
            'tax_query'  => (array) ($query->get('tax_query') ?: []),
            'meta_query' => (array) ($query->get('meta_query') ?: []),
        ];

        $args = \App\Services\ProfileQuery::applyRequestFiltersToArgs($args, $excludeTaxonomies);

        $query->set('tax_query', $args['tax_query']);
        $query->set('meta_query', $args['meta_query']);
    }
});

/**
 * 1. Логика TITLE (Вкладка браузера)
 */
add_filter('pre_get_document_title', function ($title) {
    $seoTitle = '';

    // А. Получаем кастомный заголовок из ACF

    // Если это страница Блога
    if (is_home()) {
        $blogPageId = get_option('page_for_posts');
        $seoTitle = get_field('seo_title', $blogPageId);
    }
    // Если это таксономия (включая категории, теги, кастомные таксономии)
    elseif (is_tax() || is_category() || is_tag()) {
        $queried_object = get_queried_object();
        if ($queried_object && !is_wp_error($queried_object)) {
            $seoTitle = get_field('seo_title', $queried_object);
        }
    }
    // Обычная логика (Страницы, Посты) - но не для анкет
    elseif (is_singular() && get_post_type() !== 'profile') {
        // Для остальных типов записей используем стандартную логику
        $seoTitle = get_field('seo_title');
    }

    // Если нашли SEO заголовок, используем его, иначе оставляем стандартный WP
    if ($seoTitle) {
        $title = $seoTitle;
    }

    // Б. Добавляем пагинацию "| Страница N"
    if (is_paged()) {
        // Получаем номер страницы (работает и для архивов, и для статических страниц)
        $page = get_query_var('paged') ?: get_query_var('page');
        if ($page > 1) {
            $title .= ' | Страница ' . $page;
        }
    }

    return $title;
}, 20);

// Дополнительные фильтры для SEO плагинов (Yoast, RankMath)
add_filter('wpseo_title', function ($title) {
    if (is_paged()) {
        $page = get_query_var('paged') ?: get_query_var('page');
        if ($page > 1) {
            $title .= ' | Страница ' . $page;
        }
    }
    return $title;
}, 20);

add_filter('rank_math/frontend/title', function ($title) {
    if (is_paged()) {
        $page = get_query_var('paged') ?: get_query_var('page');
        if ($page > 1) {
            $title .= ' | Страница ' . $page;
        }
    }
    return $title;
}, 20);

/**
 * 2. Логика META DESCRIPTION
 */
add_action('wp_head', function () {
    // ВАЖНО: Если это пагинация (2, 3 страница...), description НЕ выводим
    if (is_paged()) {
        return false;
    }

    $desc = '';

    // Если это страница Блога
    if (is_home()) {
        $blogPageId = get_option('page_for_posts');
        $desc = get_field('seo_description', $blogPageId);
    }
    // Если это главная страница (статичная)
    elseif (is_front_page()) {
        $frontPageId = get_option('page_on_front');
        if ($frontPageId) {
            $desc = get_field('seo_description', $frontPageId);
            
            // Если главная страница - это template-profiles, проверяем специфичные данные для города
            if (!$desc && get_query_var('city')) {
                $current_city = get_term_by('slug', get_query_var('city'), 'city');
                if ($current_city) {
                    // Пробуем получить city_seo_description для страницы
                    $city_desc = get_field('city_seo_description', 'page_' . $frontPageId);
                    if ($city_desc) {
                        $desc = $city_desc;
                    }
                }
            }
        }
    }
    // Если это таксономия (включая категории, теги, кастомные таксономии)
    elseif (is_tax() || is_category() || is_tag()) {
        $queried_object = get_queried_object();
        if ($queried_object && !is_wp_error($queried_object)) {
            // Сначала пробуем получить SEO описание из ACF поля
            $desc = get_field('seo_description', $queried_object);
            
            // Если это таксономия с городом, пробуем получить специфичные данные для города
            if (!$desc && get_query_var('city')) {
                $current_city = get_term_by('slug', get_query_var('city'), 'city');
                if ($current_city) {
                    // Пробуем получить city_seo_description для таксономии
                    $city_desc = get_field('city_seo_description', $queried_object->taxonomy . '_' . $current_city->term_id);
                    if ($city_desc) {
                        $desc = $city_desc;
                    }
                }
            }
        }
    }
    // Обычная логика
    elseif (is_singular()) {
        // Специальная логика для анкет (profile)
        if (get_post_type() === 'profile') {
            $profile_id = get_the_ID();
            
            // 1. Сначала пробуем получить кастомное описание страницы модели
            $desc = get_field('profile_description', $profile_id);
            
            // 2. Если нет описания страницы, пробуем SEO описание
            if (!$desc) {
                $desc = get_field('seo_description', $profile_id);
            }
            
            // 3. Если нет ни одного описания, берем контент анкеты (как в SchemaGenerator)
            if (!$desc) {
                $desc = get_the_excerpt();
            }
        } else {
            // Для остальных типов записей используем стандартную логику
            $desc = get_field('seo_description');
        }
    }

    return $desc ? $desc : $default_desc;
}, 1);

// Теперь подключаем нашу функцию к SEO-плагинам через фильтры

// Если используется Rank Math SEO
add_filter('rank_math/frontend/description', function ($description) {
    return get_custom_meta_description($description);
}, 20);

// Если используется Yoast SEO
add_filter('wpseo_metadesc', function ($description) {
    return get_custom_meta_description($description);
}, 20);

/**
 * 3. Логика CANONICAL
 * (Если вы отключили стандартный rel_canonical в OptimizationServiceProvider)
 */
add_action('wp_head', function () {
    global $wp;

    // Собираем текущий URL (включая /page/2/)
    $currentUrl = home_url(add_query_arg([], $wp->request));

    // Гарантируем слеш в конце
    $currentUrl = trailingslashit($currentUrl);

    echo '<link rel="canonical" href="' . esc_url($currentUrl) . '" />' . "\n";
}, 1);

// Фильтр для исправления ссылок на первую страницу
add_filter('paginate_links', function($link) {
    if (!is_string($link) || $link === '') {
        return $link;
    }

    // Убираем /page/1/ из ссылок
    if (strpos($link, '/page/1/') !== false) {
        $link = str_replace('/page/1/', '/', $link);
    }
    return $link;
});

/**
 * AJAX обработчик для подгрузки блога.
 * Аналог создания /api/blog маршрута во фронтенде.
 */
add_action('template_redirect', function () {
    if (isset($_GET['ajax_blog']) && $_GET['ajax_blog'] === '1') {
        $paged = isset($_GET['paged']) ? (int)$_GET['paged'] : 1;
        
        $args = [
            'post_type'      => 'blog',
            'post_status'    => 'publish',
            'posts_per_page' => 12,
            'paged'          => $paged,
        ];

        $q = new \WP_Query($args);

        if ($q->have_posts()) {
            while ($q->have_posts()) {
                $q->the_post();
                // Рендерим только компонент карточки и отдаем готовый HTML
                echo \Roots\view('components.blog-card', ['post' => get_post()])->render();
            }
        }
        
        wp_reset_postdata();
        exit; // Прерываем дальнейшую загрузку WP (не выводим header/footer)
    }
});

/**
 * AJAX обработчик отзывов к статьям блога.
 */
$blogReviewHandler = static function () {
    if (empty($_POST['nonce']) || !wp_verify_nonce((string) $_POST['nonce'], 'blog_review_nonce')) {
        wp_send_json_error(['message' => 'Неверный токен безопасности. Обновите страницу.'], 400);
    }

    // Простая honeypot-защита.
    if (!empty($_POST['website'])) {
        wp_send_json_error(['message' => 'Спам-блокировка.'], 400);
    }

    $postId = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
    if ($postId <= 0 || get_post_type($postId) !== 'blog') {
        wp_send_json_error(['message' => 'Некорректная статья.'], 400);
    }

    $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash((string) $_POST['name'])) : '';
    $email = isset($_POST['email']) ? sanitize_email(wp_unslash((string) $_POST['email'])) : '';
    $rating = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;
    $message = isset($_POST['message']) ? trim(wp_kses_post(wp_unslash((string) $_POST['message']))) : '';

    if ($name === '' || $message === '' || $rating < 1 || $rating > 5) {
        wp_send_json_error(['message' => 'Заполните имя, сообщение и оценку.'], 400);
    }

    if ($email === '' || !is_email($email)) {
        wp_send_json_error(['message' => 'Укажите корректный e-mail.'], 400);
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $antiFloodKey = 'blog_rev_last_' . md5((string) $ip);
    $lastRequest = (int) get_transient($antiFloodKey);

    if ($lastRequest && (time() - $lastRequest) < 30) {
        wp_send_json_error(['message' => 'Слишком часто. Попробуйте чуть позже.'], 429);
    }

    $commentId = wp_insert_comment(wp_slash([
        'comment_post_ID' => $postId,
        'comment_author' => $name,
        'comment_author_email' => $email,
        'comment_content' => $message,
        'comment_type' => '',
        'comment_approved' => 0,
        'comment_author_IP' => $ip,
        'user_id' => get_current_user_id(),
    ]));

    if (is_wp_error($commentId) || !$commentId) {
        wp_send_json_error(['message' => 'Не удалось сохранить отзыв.'], 500);
    }

    update_comment_meta($commentId, '_rating', max(1, min(5, $rating)));
    set_transient($antiFloodKey, time(), 30);

    wp_send_json_success(['message' => 'Спасибо! Ваш отзыв отправлен на модерацию.']);
};

add_action('wp_ajax_blog_add_review', $blogReviewHandler);
add_action('wp_ajax_nopriv_blog_add_review', $blogReviewHandler);
