<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;
use WP_Query;

class TemplateBlog extends Composer
{
    /**
     * List of views served by this composer.
     * Здесь обязательно должно быть static!
     *
     * @var array
     */
    protected static $views = [
        'template-blog',
    ];

    /**
     * Data to be passed to view before rendering.
     *
     * @return array
     */
    public function with()
    {
        $paged = max(1, get_query_var('paged') ?: get_query_var('page') ?: 1);
        
        $args = [
            'post_type'      => 'blog',
            'post_status'    => 'publish',
            'posts_per_page' => 12,
            'paged'          => $paged,
        ];

        $query = new WP_Query($args);

        return [
            'blog_query' => $query,
            'paged'      => $paged,
            'heading'    => function_exists('get_field') ? (get_field('h1') ?: get_the_title()) : get_the_title(),
            'lead'       => function_exists('get_field') ? (get_field('p') ?: '') : '',
        ];
    }
}