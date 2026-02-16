<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class Breadcrumbs extends Composer
{
    protected static $views = [
        'partials.breadcrumbs',
    ];

    public function with()
    {
        return [
            'crumbs' => $this->getCrumbs(),
        ];
    }

    private function getCrumbs()
    {
        $crumbs = [];

        // 1. Главная (Всегда первая)
        $crumbs[] = [
            'label' => 'Главная',
            'url'   => home_url('/'),
            'current' => false,
        ];

        // Получаем ID страницы Блога (для ссылок)
        $blogPageId = get_option('page_for_posts');
        $blogTitle  = $blogPageId ? get_the_title($blogPageId) : 'Блог';
        $blogUrl    = $blogPageId ? get_permalink($blogPageId) : home_url('/blog/');

        // --- ЛОГИКА ПО ТИПАМ СТРАНИЦ ---

        // 2. Главная страница Блога
        if (is_home()) {
            $crumbs[] = [
                'label' => $blogTitle,
                'url'   => '',
                'current' => true,
            ];
        }

        // 3. Одиночная статья (Post)
        elseif (is_singular('post')) {
            // Ссылка на Блог
            if ($blogPageId) {
                $crumbs[] = [
                    'label' => $blogTitle,
                    'url'   => $blogUrl,
                    'current' => false
                ];
            }

            // Рубрика (первая)
            $cats = get_the_category();
            if ($cats && !is_wp_error($cats)) {
                $crumbs[] = [
                    'label' => $cats[0]->name,
                    'url'   => get_category_link($cats[0]->term_id),
                    'current' => false
                ];
            }

            // Название статьи
            $crumbs[] = [
                'label' => get_the_title(),
                'url'   => '',
                'current' => true,
            ];
        }

        // 4. Одиночная Анкета (Profile)
        elseif (is_singular('profile')) {
            $crumbs[] = [
                'label' => get_the_title(),
                'url'   => '',
                'current' => true,
            ];
        }

        // 5. Обычные страницы (Page)
        elseif (is_page()) {
            global $post;
            if ($post->post_parent) {
                $parent_id  = $post->post_parent;
                $breadcrumbs = [];
                while ($parent_id) {
                    $page = get_post($parent_id);
                    $breadcrumbs[] = [
                        'label' => get_the_title($page->ID),
                        'url'   => get_permalink($page->ID),
                        'current' => false
                    ];
                    $parent_id = $page->post_parent;
                }
                $crumbs = array_merge($crumbs, array_reverse($breadcrumbs));
            }

            $crumbs[] = [
                'label' => get_the_title(),
                'url'   => '',
                'current' => true,
            ];
        }

        // 6. Архивы (Рубрики, Теги, Таксономии)
        elseif (is_archive()) {
            // Если это стандартная Рубрика или Тег (часть блога) -> добавляем родителя "Блог"
            if ((is_category() || is_tag()) && $blogPageId) {
                $crumbs[] = [
                    'label' => $blogTitle,
                    'url'   => $blogUrl,
                    'current' => false
                ];
            }

            // Получаем чистый заголовок архива
            $title = single_term_title('', false);
            if (!$title) {
                $title = get_the_archive_title();
                $title = preg_replace('/^[\w\s]+:\s/iu', '', strip_tags($title));
            }

            $crumbs[] = [
                'label' => $title,
                'url'   => '',
                'current' => true,
            ];
        }

        // 7. Поиск / 404
        elseif (is_search()) {
            $crumbs[] = [
                'label' => 'Поиск: ' . get_search_query(),
                'url'   => '',
                'current' => true,
            ];
        } elseif (is_404()) {
            $crumbs[] = [
                'label' => 'Ошибка 404',
                'url'   => '',
                'current' => true,
            ];
        }

        return $crumbs;
    }
}
