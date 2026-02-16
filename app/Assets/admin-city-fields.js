/**
 * Улучшение UX для полей городов на страницах
 */
jQuery(document).ready(function($) {
    // Хранилище для временных данных городов
    let cityDataStore = {};
    
    // Сохраняем данные всех городов перед изменением
    function saveAllCityData() {
        cityDataStore = {};
        $('.acf-field-repeater[data-name="city_pages_seo"] .acf-row').each(function() {
            const $row = $(this);
            const $citySelect = $row.find('.acf-field[data-name="city"] select');
            const cityId = $citySelect.val();
            
            if (cityId) {
                cityDataStore[cityId] = {
                    city: $citySelect.val(),
                    seo_title: $row.find('.acf-field[data-name="seo_title"] input').val(),
                    meta_description: $row.find('.acf-field[data-name="meta_description"] textarea').val(),
                    h1: $row.find('.acf-field[data-name="h1"] input').val(),
                    description: $row.find('.acf-field[data-name="description"] .acf-input-wrap textarea').val(),
                    main_text: $row.find('.acf-field[data-name="main_text"] .acf-input-wrap textarea').val()
                };
            }
        });
    }
    
    // Восстанавливаем данные для конкретного города
    function restoreCityData(cityId) {
        if (cityDataStore[cityId]) {
            const data = cityDataStore[cityId];
            const $row = $('.acf-field-repeater[data-name="city_pages_seo"] .acf-row').filter(function() {
                return $(this).find('.acf-field[data-name="city"] select').val() === cityId;
            });
            
            if ($row.length) {
                $row.find('.acf-field[data-name="seo_title"] input').val(data.seo_title || '');
                $row.find('.acf-field[data-name="meta_description"] textarea').val(data.meta_description || '');
                $row.find('.acf-field[data-name="h1"] input').val(data.h1 || '');
                $row.find('.acf-field[data-name="description"] .acf-input-wrap textarea').val(data.description || '');
                $row.find('.acf-field[data-name="main_text"] .acf-input-wrap textarea').val(data.main_text || '');
            }
        }
    }
    
    // Обновление заголовков городов при выборе
    function updateCityHeaders() {
        $('.acf-field-repeater[data-name="city_pages_seo"] .acf-row').each(function() {
            const $row = $(this);
            const $citySelect = $row.find('.acf-field[data-name="city"] select');
            const $header = $row.find('.city-header .acf-label');
            
            if ($citySelect.length && $header.length) {
                const selectedText = $citySelect.find('option:selected').text();
                
                if (selectedText && selectedText !== 'Select') {
                    // Обновляем заголовок с именем города
                    $header.text('📍 ' + selectedText);
                    
                    // Добавляем цветовую индикацию
                    $row.addClass('city-selected');
                    
                    // Показываем все поля для выбранного города
                    showCityFields($row);
                } else {
                    $header.text(' Новый город');
                    $row.removeClass('city-selected');
                    
                    // Скрываем поля если город не выбран
                    hideCityFields($row);
                }
            }
        });
    }
    
    // Показать поля для города
    function showCityFields($row) {
        // Показываем все поля кроме выбора города
        $row.find('.acf-field').not('[data-name="city"]').not('.city-header').show();
        
        // Разворачиваем строку
        $row.removeClass('acf-row-collapsed');
        
        // Добавляем класс что город выбран
        $row.addClass('city-expanded');
    }
    
    // Скрыть поля для города
    function hideCityFields($row) {
        // Скрываем все поля кроме выбора города
        $row.find('.acf-field').not('[data-name="city"]').not('.city-header').hide();
        
        // Сворачиваем строку
        $row.addClass('acf-row-collapsed');
        
        // Убираем класс что город выбран
        $row.removeClass('city-expanded');
    }
    
    // Обновление при загрузке страницы
    updateCityHeaders();
    saveAllCityData(); // Сохраняем начальное состояние
    
    // Обновление при изменении выбора города
    $(document).on('change', '.acf-field[data-name="city"] select', function() {
        const $row = $(this).closest('.acf-row');
        const selectedText = $(this).find('option:selected').text();
        const cityId = $(this).val();
        
        // Сохраняем текущие данные перед изменением
        saveAllCityData();
        
        if (selectedText && selectedText !== 'Select') {
            showCityFields($row);
            // Восстанавливаем данные для этого города если они есть
            restoreCityData(cityId);
        } else {
            hideCityFields($row);
        }
        
        setTimeout(updateCityHeaders, 100);
    });
    
    // Сохраняем данные перед отправкой формы
    $('#post').on('submit', function() {
        saveAllCityData();
    });
    
    // Обновление при добавлении новой строки
    $(document).on('acf/add_row', function(e, $row) {
        // Скрываем поля в новой строке
        hideCityFields($row);
        
        // Фокус на выбор города
        setTimeout(function() {
            $row.find('.acf-field[data-name="city"] select').focus();
        }, 100);
        
        setTimeout(updateCityHeaders, 100);
    });
    
    // Обновление при удалении строки
    $(document).on('acf/delete_row', function() {
        setTimeout(function() {
            saveAllCityData();
            updateCityHeaders();
        }, 100);
    });
    
    // Обработка клика по заголовку для переключения
    $(document).on('click', '.city-header', function(e) {
        e.preventDefault();
        const $row = $(this).closest('.acf-row');
        
        if ($row.hasClass('city-expanded')) {
            hideCityFields($row);
        } else {
            showCityFields($row);
        }
    });
    
    // Добавляем стили для лучшей визуализации
    $('<style>').text(`
        .acf-field-repeater[data-name="city_pages_seo"] .acf-row.city-selected {
            border-left: 4px solid #0073aa;
            background: #f9f9f9;
        }
        
        .acf-field-repeater[data-name="city_pages_seo"] .city-header .acf-label {
            font-weight: bold;
            color: #0073aa;
            font-size: 14px;
            cursor: pointer;
            padding: 8px 0;
            transition: all 0.2s ease;
        }
        
        .acf-field-repeater[data-name="city_pages_seo"] .city-header .acf-label:hover {
            color: #005a87;
            text-decoration: underline;
        }
        
        .acf-field-repeater[data-name="city_pages_seo"] .acf-row:not(.city-selected) {
            opacity: 0.6;
            border-left: 4px solid #ddd;
        }
        
        .acf-field-repeater[data-name="city_pages_seo"] .acf-row-handle {
            cursor: pointer;
        }
        
        .acf-field-repeater[data-name="city_pages_seo"] .acf-row-handle:hover {
            background: #e9e9e9;
        }
        
        .acf-field-repeater[data-name="city_pages_seo"] .acf-row.city-expanded {
            background: #f0f6fc;
            border-left: 4px solid #0073aa;
        }
        
        .acf-field-repeater[data-name="city_pages_seo"] .city-header::before {
            content: '▶ ';
            display: inline-block;
            transition: transform 0.2s ease;
        }
        
        /* Скрываем поля по умолчанию */
        .acf-field-repeater[data-name="city_pages_seo"] .acf-row:not(.city-expanded) .acf-field:not([data-name="city"]):not(.city-header) {
            display: none !important;
        }
    `).appendTo('head');
});
