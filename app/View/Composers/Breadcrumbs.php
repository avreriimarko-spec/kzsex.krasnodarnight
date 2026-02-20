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
        $opt = [
            'home_label' => 'Главная',
            'blog_label' => 'Блог',
        ];

        $blogUrl = get_post_type_archive_link('blog');
        if (!$blogUrl) {
            $page = get_page_by_path('blog', OBJECT, 'page');
            $blogUrl = ($page && !is_wp_error($page)) ? get_permalink($page) : home_url('/blog/');
        }

        $hubSeg = (string) get_query_var('city');
        $hubTerm = $hubSeg !== '' ? get_term_by('slug', $hubSeg, 'city') : null;
        $hubName = ($hubTerm && !is_wp_error($hubTerm)) ? $hubTerm->name : '';
        $hubUrl = ($hubSeg !== '' && $hubName !== '') ? home_url('/' . $hubSeg . '/') : '';
        $isHubRoot = $hubSeg !== ''
            && !get_query_var('pagename')
            && !get_query_var('taxonomy')
            && !get_query_var('term')
            && !is_singular();

        $crumbs = [];
        $crumbs[] = ['label' => $opt['home_label'], 'url' => home_url('/')];

        $addHub = static function (array &$arr) use ($hubSeg, $hubName, $hubUrl, $isHubRoot): void {
            if (!$hubSeg || $isHubRoot || !$hubName || !$hubUrl) {
                return;
            }

            $arr[] = ['label' => $hubName, 'url' => $hubUrl];
        };

        if (is_singular('blog')) {
            $crumbs[] = ['label' => $opt['blog_label'], 'url' => $blogUrl];
            $crumbs[] = ['label' => get_the_title(), 'url' => ''];
        } elseif (is_post_type_archive('blog')) {
            $crumbs[] = ['label' => $opt['blog_label'], 'url' => ''];
        } else {
            if (is_page()) {
                $post = get_post();
                $anc = $post ? array_reverse(get_post_ancestors($post)) : [];
                if (!empty($anc)) {
                    foreach ($anc as $aid) {
                        $crumbs[] = ['label' => get_the_title($aid), 'url' => get_permalink($aid)];
                    }
                    $crumbs[] = ['label' => get_the_title($post), 'url' => ''];
                } else {
                    if ($isHubRoot) {
                        $crumbs[] = ['label' => $hubName ?: get_the_title($post), 'url' => ''];
                    } else {
                        $addHub($crumbs);
                        $crumbs[] = ['label' => get_the_title($post), 'url' => ''];
                    }
                }
            } elseif (is_singular()) {
                $addHub($crumbs);
                $crumbs[] = ['label' => get_the_title(), 'url' => ''];
            } else {
                $addHub($crumbs);
                if (is_404()) {
                    $crumbs[] = ['label' => '404', 'url' => ''];
                } elseif (is_search()) {
                    $crumbs[] = ['label' => 'Поиск: ' . get_search_query(), 'url' => ''];
                } elseif (is_archive()) {
                    $crumbs[] = ['label' => preg_replace('/^[^:]+: /', '', strip_tags(get_the_archive_title())), 'url' => ''];
                } elseif (is_home()) {
                    $crumbs[] = ['label' => strip_tags(single_post_title('', false)), 'url' => ''];
                } else {
                    $crumbs[] = ['label' => get_the_title(), 'url' => ''];
                }
            }
        }

        return array_map(static function (array $crumb): array {
            $url = $crumb['url'] ?? '';

            return [
                'label' => (string) ($crumb['label'] ?? ''),
                'url' => (string) $url,
                'current' => $url === '',
            ];
        }, $crumbs);
    }
}
