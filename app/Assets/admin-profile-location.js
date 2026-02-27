(function ($) {
    const config = window.kzsexProfileLocationAdmin || {};
    const termCityMap = config.termCityMap || {};
    const messages = config.messages || {};
    const locationTaxonomies = ['metro', 'district'];

    function getCityInputs() {
        return $('#citydiv input[name="tax_input[city][]"]');
    }

    function getLocationInputs(taxonomy) {
        return $('#' + taxonomy + 'div input[name="tax_input[' + taxonomy + '][]"]');
    }

    function toInt(value) {
        const parsed = parseInt(value, 10);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function normalizeCityInputsToSingleSelect() {
        const $cityInputs = getCityInputs();
        if (!$cityInputs.length) {
            return;
        }

        $cityInputs.attr('type', 'radio');

        let selectedCityId = 0;

        $cityInputs.each(function () {
            if ($(this).is(':checked') && selectedCityId === 0) {
                selectedCityId = toInt($(this).val());
            }
        });

        syncCitySelection(selectedCityId);
    }

    function syncCitySelection(selectedCityId) {
        const $cityInputs = getCityInputs();

        $cityInputs.each(function () {
            const currentId = toInt($(this).val());
            $(this).prop('checked', selectedCityId > 0 && currentId === selectedCityId);
        });
    }

    function getSelectedCityId() {
        const $selected = getCityInputs().filter(':checked').first();
        return $selected.length ? toInt($selected.val()) : 0;
    }

    function renderHint($box, cityId, visibleCount) {
        if (!$box.length) {
            return;
        }

        let $hint = $box.find('.kzsex-location-hint');
        if (!$hint.length) {
            $hint = $('<p class="kzsex-location-hint" style="margin-top:8px;color:#646970;"></p>');
            $box.find('.inside').append($hint);
        }

        if (cityId <= 0) {
            $hint.text(messages.selectCity || 'Сначала выберите город, затем метро и районы.').show();
            return;
        }

        if (visibleCount <= 0) {
            $hint.text(messages.noTerms || 'Для выбранного города нет доступных терминов.').show();
            return;
        }

        $hint.hide();
    }

    function applyLocationFilterForTaxonomy(taxonomy, cityId) {
        const $box = $('#' + taxonomy + 'div');
        if (!$box.length) {
            return;
        }

        const cityMap = termCityMap[taxonomy] || {};
        const $inputs = getLocationInputs(taxonomy);
        let visibleCount = 0;

        $inputs.each(function () {
            const $input = $(this);
            const termId = toInt($input.val());
            const relatedCityId = toInt(cityMap[termId]);
            const isVisible = cityId > 0 && relatedCityId === cityId;

            $input.prop('disabled', !isVisible);

            if (!isVisible) {
                $input.prop('checked', false);
            }

            $input.closest('li').toggle(isVisible);

            if (isVisible) {
                visibleCount += 1;
            }
        });

        renderHint($box, cityId, visibleCount);
    }

    function applyLocationFilters() {
        const cityId = getSelectedCityId();

        locationTaxonomies.forEach(function (taxonomy) {
            applyLocationFilterForTaxonomy(taxonomy, cityId);
        });
    }

    function init() {
        if (!$('#citydiv').length) {
            return;
        }

        normalizeCityInputsToSingleSelect();
        applyLocationFilters();

        $(document).on('change', '#citydiv input[name="tax_input[city][]"]', function () {
            const selectedCityId = toInt($(this).val());
            syncCitySelection(selectedCityId);
            applyLocationFilters();
        });
    }

    $(init);
})(jQuery);
