<?php

namespace App\Fields;

add_action('acf/input/admin_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Флаг для отслеживания инициализации
        let isInitializing = true;
        
        // Функция очистки специфичных полей для городов при смене города
        function clearTaxonomyCitySpecificFields() {
            // Не очищаем поля при инициализации страницы
            if (isInitializing) {
                return;
            }
            
            // Находим все специфичные поля для городов
            var fields = [
                'field_taxonomy_city_specific_seo_title',
                'field_taxonomy_city_specific_seo_description', 
                'field_taxonomy_city_specific_custom_h1',
                'field_taxonomy_city_specific_description',
                'field_taxonomy_city_specific_main_text'
            ];
            
            fields.forEach(function(fieldKey) {
                var field = $('[data-key="' + fieldKey + '"]');
                
                // Очищаем текстовые поля
                field.find('input[type="text"]').val('');
                field.find('textarea').val('');
                
                // Очищаем WYSIWYG редакторы ACF
                field.find('.acf-field-wysiwyg').each(function() {
                    var $this = $(this);
                    var editorId = $this.find('textarea').attr('id');
                    
                    // Очищаем через tinyMCE если доступен
                    if (typeof tinyMCE !== 'undefined' && tinyMCE.get(editorId)) {
                        tinyMCE.get(editorId).setContent('');
                    }
                    
                    // Очищаем textarea
                    $this.find('textarea').val('');
                    
                    // Очищаем визуальный iframe
                    $this.find('iframe').contents().find('body').html('');
                });
            });
        }
        
        // Следим за изменением поля выбора города на таксономиях
        $(document).on('change', '[data-key="field_taxonomy_city_specific_selector"] select', function() {
            // Небольшая задержка для обновления ACF
            setTimeout(function() {
                clearTaxonomyCitySpecificFields();
            }, 200);
        });
        
        // Также следим за кликом по опциям в taxonomy поле на таксономиях
        $(document).on('click', '[data-key="field_taxonomy_city_specific_selector"] .acf-taxonomy-field .choices li', function() {
            setTimeout(function() {
                clearTaxonomyCitySpecificFields();
            }, 200);
        });
        
        // После загрузки страницы сбрасываем флаг инициализации
        setTimeout(function() {
            isInitializing = false;
        }, 500);
    });
    </script>
    <?php
});
