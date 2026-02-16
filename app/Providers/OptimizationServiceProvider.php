<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class OptimizationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // 1. Безопасность и API (XML-RPC, REST)
        $this->hardenSecurity();

        // 2. Отключение обновлений и проверок (для Bedrock это норма)
        $this->disableUpdates();

        // --- ДАЛЕЕ ТОЛЬКО ДЛЯ ФРОНТА ---
        if ($this->isFrontend()) {
            $this->cleanupHead();
            $this->cleanupGutenberg();
            $this->cleanupScripts();
            $this->cleanupFeedsAndArchives();
            $this->fixSeoGarbage();
        }
    }

    /**
     * Проверка: является ли запрос "фронтовым" (не админка, не аякс, не крон, не CLI)
     */
    protected function isFrontend(): bool
    {
        return (
            !is_admin() &&
            !wp_doing_ajax() &&
            !wp_doing_cron() &&
            !(defined('WP_CLI') && WP_CLI) &&
            !(defined('REST_REQUEST') && REST_REQUEST)
        );
    }

    /**
     * 1. Безопасность: XML-RPC и Харденинг REST API
     */
    protected function hardenSecurity(): void
    {
        // Отключаем XML-RPC
        add_filter('xmlrpc_enabled', '__return_false');
        add_filter('wp_headers', function ($headers) {
            unset($headers['X-Pingback']);
            return $headers;
        });

        // Скрываем пользователей из REST API (защита от перебора логинов)
        add_filter('rest_endpoints', function ($endpoints) {
            if (!is_user_logged_in()) {
                if (isset($endpoints['/wp/v2/users'])) unset($endpoints['/wp/v2/users']);
                if (isset($endpoints['/wp/v2/users/(?P<id>[\d]+)'])) unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
            }
            return $endpoints;
        });

        // Отключаем Heartbeat на фронте
        add_action('init', function () {
            if ($this->isFrontend()) {
                wp_deregister_script('heartbeat');
            }
        }, 1);
    }

    /**
     * 2. Блокировка обновлений (управляем через Composer)
     */
    protected function disableUpdates(): void
    {
        add_filter('automatic_updater_disabled', '__return_true');
        add_filter('auto_update_core', '__return_false');
        add_filter('auto_update_plugin', '__return_false');
        add_filter('auto_update_theme', '__return_false');
    }

    /**
     * 3. Очистка <head> от мусора
     */
    protected function cleanupHead(): void
    {
        add_action('init', function () {
            // Emojis
            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('admin_print_scripts', 'print_emoji_detection_script');
            remove_action('wp_print_styles', 'print_emoji_styles');
            remove_action('admin_print_styles', 'print_emoji_styles');
            remove_filter('the_content_feed', 'wp_staticize_emoji');
            remove_filter('comment_text_rss', 'wp_staticize_emoji');
            remove_filter('wp_mail', 'wp_staticize_emoji_for_email');

            // oEmbed (встраивание)
            remove_action('wp_head', 'wp_oembed_add_discovery_links');
            remove_action('wp_head', 'wp_oembed_add_host_js');
            add_filter('embed_oembed_discover', '__return_false');
            add_filter('wp_img_tag_add_auto_sizes', '__return_false');

            remove_action('wp_footer', 'wp_print_speculation_rules');
            remove_action('wp_head', 'wp_print_speculation_rules'); // Иногда бывает в хедере

            // На всякий случай фильтр, чтобы вернуть пустоту
            add_filter('wp_speculation_rules_configuration', '__return_empty_array');

            // Разное
            remove_action('wp_head', 'rsd_link');
            remove_action('wp_head', 'wlwmanifest_link');
            remove_action('wp_head', 'wp_generator');
            remove_action('wp_head', 'rel_canonical');
            remove_action('wp_head', 'wp_shortlink_wp_head');
            remove_action('wp_head', 'rest_output_link_wp_head');

            // Удаляем ссылки на REST в заголовках
            remove_action('template_redirect', 'rest_output_link_header', 11);
        });
    }

    /**
     * 4. Тотальное отключение стилей Гутенберга на фронте
     */
    protected function cleanupGutenberg(): void
    {
        // Отключаем SVG фильтры (Duotone) в body
        add_action('after_setup_theme', function () {
            remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
            remove_action('wp_footer', 'wp_enqueue_global_styles', 1);
            remove_action('wp_body_open', 'wp_global_styles_render_svg_filters');
        });

        // Удаляем CSS библиотеку блоков
        add_action('wp_enqueue_scripts', function () {
            wp_dequeue_style('global-styles');
            wp_dequeue_style('wp-block-library');
            wp_dequeue_style('wp-block-library-theme');
            wp_dequeue_style('wc-block-style'); // Если есть WooCommerce
            wp_dequeue_style('classic-theme-styles');
        }, 100);

        // Отключаем инлайн стили
        add_filter('should_load_separate_core_block_assets', '__return_true');
        add_action('wp_footer', function () {
            wp_dequeue_style('core-block-supports');
        });
    }

    /**
     * 5. Очистка скриптов (jQuery)
     */
    protected function cleanupScripts(): void
    {
        // Убираем jQuery Migrate
        add_action('wp_default_scripts', function ($scripts) {
            if (!is_admin() && isset($scripts->registered['jquery'])) {
                $script = $scripts->registered['jquery'];
                if ($script->deps) {
                    $script->deps = array_diff($script->deps, ['jquery-migrate']);
                }
            }
        });

        // Убираем версию WP из стилей и скриптов (?ver=x.x)
        add_filter('style_loader_src', [$this, 'removeVersion'], 9999);
        add_filter('script_loader_src', [$this, 'removeVersion'], 9999);
    }

    public function removeVersion($src)
    {
        if (strpos($src, 'ver=')) {
            $src = remove_query_arg('ver', $src);
        }
        return $src;
    }

    /**
     * 6. Отключение RSS фидов и Архивов авторов (если не блог)
     */
    protected function cleanupFeedsAndArchives(): void
    {
        // 1. Отключаем фиды (RSS, Atom)
        add_action('after_setup_theme', function () {
            remove_action('wp_head', 'feed_links_extra', 3);
            remove_action('wp_head', 'feed_links', 2);
        });

        foreach (['do_feed', 'do_feed_rdf', 'do_feed_rss', 'do_feed_rss2', 'do_feed_atom'] as $feed) {
            add_action($feed, function () {
                wp_redirect(home_url(), 301);
                exit;
            }, 1);
        }

        // 2. Редирект паразитных страниц
        add_action('template_redirect', function () {
            // Если это архив автора (admin) или архив по датам (2023/12)
            if (is_author() || is_date()) {
                wp_redirect(home_url(), 301);
                exit;
            }

            // Если это страница вложения (картинки), редиректим на сам файл или на родительский пост
            if (is_attachment()) {
                global $post;
                if ($post && $post->post_parent) {
                    wp_redirect(get_permalink($post->post_parent), 301);
                } else {
                    wp_redirect(home_url(), 301);
                }
                exit;
            }

            // Если это стандартные теги (post_tag), а мы их не используем
            /*             if (is_tag()) {
                wp_redirect(home_url(), 301);
                exit;
            } */

            // Если это страница поиска, но поиск пустой или это не наш фильтр
            // (Опционально, если хотите закрыть стандартный поиск ?s=test)
            // if (is_search() && !isset($_GET['f_service'])) { ... }
        });
    }

    /**
     * 7. Удаление мусорных GET параметров (Google Merchant и т.д.)
     */
    protected function fixSeoGarbage(): void
    {
        if (isset($_GET['srsltid'])) {
            $url = strtok($_SERVER["REQUEST_URI"], '?');
            if (!empty($_GET)) {
                unset($_GET['srsltid']);
                if (!empty($_GET)) {
                    $url .= '?' . http_build_query($_GET);
                }
            }
            wp_redirect($url, 301);
            exit;
        }
    }
}
