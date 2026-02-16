<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class PageSitemap extends Composer
{
    protected static $views = [
        'template-sitemap',
    ];

    public function with()
    {
        return [
            'pages'      => $this->getPages(),
            'blog_posts' => $this->getBlogPosts(),
            'taxonomies' => $this->getTaxonomies(),
        ];
    }

    private function getPages()
    {
        // Получаем все опубликованные страницы, кроме самой карты сайта (чтобы не было рекурсии)
        return get_pages([
            'post_status' => 'publish',
            'exclude'     => [get_the_ID()], // Исключаем текущую страницу
            'sort_column' => 'post_title',
            'sort_order'  => 'ASC',
        ]);
    }

    private function getBlogPosts()
    {
        // Получаем последние 50 статей блога (чтобы не перегружать, если их тысячи)
        return get_posts([
            'post_type'      => 'post',
            'posts_per_page' => 50,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);
    }

    private function getTaxonomies()
    {
        $data = [];

        // Загружаем только услуги
        $taxs = [
            'service' => 'Услуги',
        ];

        foreach ($taxs as $slug => $label) {
            $terms = get_terms([
                'taxonomy'   => $slug,
                'hide_empty' => true, // Не показывать пустые категории
                'orderby'    => 'name',
                'order'      => 'ASC',
            ]);

            if (!is_wp_error($terms) && !empty($terms)) {
                $data[$label] = $terms;
            }
        }

        return $data;
    }
}
