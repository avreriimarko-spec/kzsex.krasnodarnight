<?php

namespace App\Helpers;

add_action('wp_ajax_get_all_cities', 'get_all_cities_ajax');
add_action('wp_ajax_nopriv_get_all_cities', 'get_all_cities_ajax');

function get_all_cities_ajax() {
    // Проверяем nonce для безопасности
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'get_all_cities_nonce')) {
        wp_die('Security check failed');
    }
    
    // Получаем все города
    $cities = get_terms([
        'taxonomy' => 'city',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC'
    ]);
    
    $city_data = [];
    
    if (!is_wp_error($cities) && !empty($cities)) {
        foreach ($cities as $city) {
            $city_data[] = [
                'term_id' => $city->term_id,
                'name' => $city->name,
                'slug' => $city->slug
            ];
        }
    }
    
    wp_send_json_success($city_data);
}

// Добавляем nonce в JavaScript
add_action('wp_head', function() {
    if (is_admin()) {
        ?>
        <script>
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            var get_all_cities_nonce = '<?php echo wp_create_nonce('get_all_cities_nonce'); ?>';
        </script>
        <?php
    }
});
