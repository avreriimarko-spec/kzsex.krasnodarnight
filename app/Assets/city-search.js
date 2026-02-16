/**
 * Поиск и быстрое добавление городов
 */
jQuery(document).ready(function($) {
    let selectedCities = [];
    let allCities = [];
    
    // Получаем все города через AJAX
    $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
            action: "get_all_cities",
            nonce: get_all_cities_nonce
        },
        success: function(response) {
            allCities = response.data;
            console.log('Cities loaded:', allCities);
        },
        error: function(xhr, status, error) {
            console.error('Error loading cities:', error);
        }
    });
    
    // Поиск городов
    $(document).on("input", '.acf-field[data-name="search_input"] input', function() {
        const query = $(this).val().toLowerCase();
        const $results = $("#city-search-results-container");
        
        console.log('Search query:', query);
        
        if (query.length < 2) {
            $results.empty().hide();
            return;
        }
        
        const filtered = allCities.filter(city => 
            city.name.toLowerCase().includes(query)
        );
        
        console.log('Filtered cities:', filtered);
        
        if (filtered.length === 0) {
            $results.html('<div class="city-search-result">Города не найдены</div>').show();
            return;
        }
        
        let html = "";
        filtered.forEach(city => {
            const isSelected = selectedCities.includes(city.term_id);
            html += `<div class="city-search-result ${isSelected ? "selected" : ""}" data-city-id="${city.term_id}" data-city-name="${city.name}">
                <span class="city-name">${city.name}</span>
                <span class="city-count">ID: ${city.term_id}</span>
            </div>`;
        });
        
        $results.html(html).show();
    });
    
    // Выбор города
    $(document).on("click", ".city-search-result", function() {
        const cityId = $(this).data("city-id");
        const cityName = $(this).data("city-name");
        
        console.log('City selected:', cityName, 'ID:', cityId);
        
        $(this).toggleClass("selected");
        
        if (selectedCities.includes(cityId)) {
            selectedCities = selectedCities.filter(id => id !== cityId);
        } else {
            selectedCities.push(cityId);
        }
        
        console.log('Selected cities:', selectedCities);
    });
    
    // Добавить выбранные города
    $(document).on("click", "#add-selected-cities", function(e) {
        e.preventDefault();
        
        console.log('Add selected cities clicked');
        
        if (selectedCities.length === 0) {
            alert("Выберите хотя бы один город");
            return;
        }
        
        console.log('Adding cities:', selectedCities);
        
        addCitiesToRepeater(selectedCities);
        selectedCities = [];
        $('.acf-field[data-name="search_input"] input').val("");
        $("#city-search-results-container").empty().hide();
    });
    
    // Функция добавления городов в repeater
    function addCitiesToRepeater(cityIds) {
        console.log('Adding cities to repeater:', cityIds);
        
        cityIds.forEach(cityId => {
            // Проверяем что город еще не добавлен
            const exists = $(".acf-field-repeater[data-name=\"city_pages_seo\"] .acf-row").filter(function() {
                return $(this).find(".acf-field[data-name=\"city\"] select").val() == cityId;
            }).length > 0;
            
            console.log('City exists check for ID', cityId, ':', exists);
            
            if (!exists) {
                // Добавляем новую строку в repeater
                console.log('Adding city ID:', cityId);
                
                // Используем правильный метод ACF для добавления строки
                const $repeater = $('.acf-field-repeater[data-name="city_pages_seo"]');
                const newRow = acf.get_field({
                    key: 'field_page_city_specific_repeater',
                    $el: $repeater
                }).addRow();
                
                // Устанавливаем город в новой строке
                setTimeout(function() {
                    const $citySelect = newRow.find('.acf-field[data-name="city"] select');
                    if ($citySelect.length) {
                        $citySelect.val(cityId).trigger('change');
                        
                        // Показываем поля
                        newRow.addClass("city-expanded");
                        newRow.find('.acf-field').not('[data-name="city"]').not('.city-header').show();
                        
                        console.log('City set to:', cityId);
                    }
                }, 100);
            } else {
                console.log('City already exists, skipping:', cityId);
            }
        });
    }
    
    // Добавляем стили
    $('<style>').text(`
        #city-search-results-container {
            margin-top: 10px;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .city-search-result {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        
        .city-search-result:hover {
            background: #e9e9e9;
        }
        
        .city-search-result.selected {
            background: #0073aa;
            color: white;
        }
        
        .city-search-result:last-child {
            border-bottom: none;
        }
        
        .city-search-result .city-name {
            font-weight: bold;
        }
        
        .city-search-result .city-count {
            color: #666;
            font-size: 12px;
        }
        
        /* Стили для отладки */
        .city-search-field-group {
            background: #f9f9f9 !important;
            border: 1px solid #ddd !important;
        }
    `).appendTo('head');
});
