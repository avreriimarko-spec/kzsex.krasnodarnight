<?php

namespace App\Services;

use App\Helpers\UrlHelpers;
use WP_Query;
use WP_Term;

class TaxonomySeoTextGenerator
{
    public static function generateForTerm(WP_Term $term, ?WP_Query $query = null): array
    {
        if ($term->taxonomy === 'metro') {
            return self::generateMetroTemplate($term, $query);
        }

        if ($term->taxonomy === 'service') {
            return self::generateServiceTemplate($term, $query);
        }

        if ($term->taxonomy === 'district') {
            return self::generateDistrictTemplate($term, $query);
        }

        return [];
    }

    private static function generateMetroTemplate(WP_Term $term, ?WP_Query $query = null): array
    {
        $cityTerm = UrlHelpers::getCurrentCity();
        $cityName = $cityTerm instanceof WP_Term ? $cityTerm->name : 'Москва';
        $cityInPrepositional = self::toPrepositionalCase($cityName);

        $stationName = (string) $term->name;
        $profilesCount = max(0, (int) (($query instanceof WP_Query ? $query->found_posts : $term->count) ?? 0));
        $districtName = self::getPrimaryDistrictName($term, $cityTerm);
        $timeRange = self::estimateMeetingTime($profilesCount);
        $priceFrom = self::getMinimalPriceForTermTaxonomy('metro', $term, $cityTerm);
        $priceFromText = number_format($priceFrom, 0, '.', ' ');
        $nearbyStations = self::getNearbyStations($term, $cityTerm, 4);

        $escapedStation = esc_html($stationName);
        $escapedCity = esc_html($cityName);
        $escapedDistrict = esc_html($districtName);
        $escapedTimeRange = esc_html($timeRange);
        $escapedPriceFrom = esc_html($priceFromText);
        $stationPlain = wp_strip_all_tags($stationName);
        $cityPlain = wp_strip_all_tags($cityName);
        $cityPrepositionalPlain = wp_strip_all_tags($cityInPrepositional);

        $description = '<p>В этом разделе представлены ' . $profilesCount . ' проверенных анкет моделей, работающих рядом со станцией метро ' . $escapedStation . '. Локация относится к ' . $escapedDistrict . ' и считается удобной для быстрых встреч.</p>'
            . '<p>Эскорт ' . $escapedCity . ' вблизи станции ' . $escapedStation . ' подходит тем, кто ценит время. Среднее время организации встречи в этой зоне составляет ' . $escapedTimeRange . '.</p>';

        $nearbyItemsHtml = '<li>Соседние станции подбираются по ближайшим пересадкам.</li>';
        if (!empty($nearbyStations)) {
            $nearbyItemsHtml = '';
            foreach ($nearbyStations as $nearbyStation) {
                if (!($nearbyStation instanceof WP_Term)) {
                    continue;
                }

                $nearbyStationName = (string) $nearbyStation->name;
                $nearbyUrl = get_term_link($nearbyStation);

                if (is_wp_error($nearbyUrl)) {
                    $nearbyItemsHtml .= '<li>Эскортницы у метро ' . esc_html($nearbyStationName) . '</li>';
                    continue;
                }

                $nearbyItemsHtml .= '<li>Эскортницы у метро <a href="' . esc_url((string) $nearbyUrl) . '">' . esc_html($nearbyStationName) . '</a></li>';
            }
        }

        $mainSeoText = '<h2>Цены и формат встреч у метро ' . $escapedStation . '</h2>'
            . '<p>Стоимость услуг в этой части города начинается от ' . $escapedPriceFrom . ' рублей. Итоговая цена зависит от продолжительности встречи и времени суток.</p>'
            . '<p>Многие анкеты имеют статус проверки, что подтверждает актуальность фото и параметров.</p>'
            . '<h2>Если нет свободных анкет на ' . $escapedStation . '</h2>'
            . '<ul>' . $nearbyItemsHtml . '</ul>'
            . '<p>Расширение радиуса поиска позволяет найти подходящий вариант в пределах 10-20 минут.</p>';

        return [
            'seo_title' => 'Эскорт у метро ' . $stationPlain . ' - эскортницы ' . $cityPlain . ' рядом',
            'meta_description' => 'Эскорт в ' . $cityPrepositionalPlain . ' у метро ' . $stationPlain . '. Проверенные анкеты, выезд в отель, быстрый подбор.',
            'h1' => 'Эскортницы ' . $cityPlain . ' у метро ' . $stationPlain,
            'description' => $description,
            'main_seo_text' => $mainSeoText,
        ];
    }

    private static function generateServiceTemplate(WP_Term $term, ?WP_Query $query = null): array
    {
        $cityTerm = UrlHelpers::getCurrentCity();
        $cityName = $cityTerm instanceof WP_Term ? $cityTerm->name : 'Москва';
        $cityInPrepositional = self::toPrepositionalCase($cityName);

        $serviceName = (string) $term->name;
        $profilesCount = max(0, (int) (($query instanceof WP_Query ? $query->found_posts : $term->count) ?? 0));
        $priceFrom = self::getMinimalPriceForTermTaxonomy('service', $term, $cityTerm);
        $priceFromText = number_format($priceFrom, 0, '.', ' ');
        $formats = self::getServiceFormats($term, $cityTerm, 4);
        $relatedServices = self::getRelatedServices($term, $cityTerm, 2);

        $servicePlain = wp_strip_all_tags($serviceName);
        $cityPlain = wp_strip_all_tags($cityName);
        $cityPrepositionalPlain = wp_strip_all_tags($cityInPrepositional);

        $serviceEsc = esc_html($serviceName);
        $cityEsc = esc_html($cityName);
        $cityPrepositionalEsc = esc_html($cityInPrepositional);
        $priceFromEsc = esc_html($priceFromText);

        $description = '<p>В этом разделе представлены ' . $profilesCount . ' проверенных анкет моделей, предлагающих формат ' . $serviceEsc . ' в ' . $cityPrepositionalEsc . '. Это востребованное направление для клиентов, которым важны конфиденциальность и заранее согласованные условия встречи.</p>'
            . '<p>Эскорт ' . $cityEsc . ' в формате ' . $serviceEsc . ' предполагает гибкость по времени и выбор удобной локации.</p>';

        $formatItemsHtml = '';
        foreach ($formats as $formatLabel) {
            $formatItemsHtml .= '<li>' . esc_html($formatLabel) . '</li>';
        }

        $relatedItemsHtml = '';
        foreach ($relatedServices as $related) {
            $label = wp_strip_all_tags((string) ($related['label'] ?? ''));
            $url = (string) ($related['url'] ?? '');

            if ($label === '') {
                continue;
            }

            if ($url !== '') {
                $relatedItemsHtml .= '<li><a href="' . esc_url($url) . '">' . esc_html($label) . '</a></li>';
            } else {
                $relatedItemsHtml .= '<li>' . esc_html($label) . '</li>';
            }
        }

        if ($relatedItemsHtml === '') {
            $relatedItemsHtml = '<li>Индивидуальный выездной формат</li><li>Персональное сопровождение по запросу</li>';
        }

        $mainSeoText = '<h2>Что включает формат ' . $serviceEsc . '</h2>'
            . '<p>Формат предполагает персональный подход и предварительное согласование условий.</p>'
            . '<p>Доступные варианты:</p>'
            . '<ul>' . $formatItemsHtml . '</ul>'
            . '<p>Продолжительность встречи и детали обсуждаются заранее. Такой формат позволяет организовать сопровождение без спешки.</p>'
            . '<h2>Стоимость ' . $serviceEsc . ' в ' . $cityPrepositionalEsc . '</h2>'
            . '<p>Стоимость начинается от ' . $priceFromEsc . ' рублей. Цена зависит от продолжительности, времени суток и выбранной локации.</p>'
            . '<p>Анкеты с подтвержденным статусом гарантируют соответствие фото и параметров.</p>'
            . '<h2>Где доступна услуга</h2>'
            . '<p>Формат ' . $serviceEsc . ' доступен в разных районах города. Выезд возможен в центр, жилые комплексы и гостиницы.</p>'
            . '<p>Эскортницы ' . $cityEsc . ' работают по гибкому графику, однако рекомендуется согласовывать время заранее.</p>'
            . '<h2>Если нет свободных анкет</h2>'
            . '<p>При высокой загруженности рекомендуем рассмотреть смежные форматы:</p>'
            . '<ul>' . $relatedItemsHtml . '</ul>'
            . '<p>Это позволит подобрать альтернативный вариант без значительного увеличения времени ожидания.</p>';

        return [
            'seo_title' => $servicePlain . ' в ' . $cityPrepositionalPlain . ' - эскорт ' . $cityPlain . ' 24/7',
            'meta_description' => $servicePlain . ' в ' . $cityPrepositionalPlain . '. Проверенные анкеты, реальные фото, выезд в отель и гибкий формат. Быстрый подбор по району и времени встречи.',
            'h1' => $servicePlain . ' - эскорт в ' . $cityPrepositionalPlain,
            'description' => $description,
            'main_seo_text' => $mainSeoText,
        ];
    }

    private static function generateDistrictTemplate(WP_Term $term, ?WP_Query $query = null): array
    {
        $cityTerm = UrlHelpers::getCurrentCity();
        $cityName = $cityTerm instanceof WP_Term ? $cityTerm->name : 'Москва';
        $cityInPrepositional = self::toPrepositionalCase($cityName);

        $districtName = (string) $term->name;
        $profilesCount = max(0, (int) (($query instanceof WP_Query ? $query->found_posts : $term->count) ?? 0));
        $priceFrom = self::getMinimalPriceForTermTaxonomy('district', $term, $cityTerm);
        $priceFromText = number_format($priceFrom, 0, '.', ' ');
        $nearbyDistricts = self::getNearbyDistricts($term, $cityTerm, 2);

        $districtPlain = wp_strip_all_tags($districtName);
        $cityPlain = wp_strip_all_tags($cityName);
        $cityPrepositionalPlain = wp_strip_all_tags($cityInPrepositional);

        $districtEsc = esc_html($districtName);
        $cityEsc = esc_html($cityName);
        $cityPrepositionalEsc = esc_html($cityInPrepositional);
        $priceFromEsc = esc_html($priceFromText);

        $description = '<p>В этом разделе представлены ' . $profilesCount . ' проверенных анкет моделей, работающих в районе ' . $districtEsc . '. Локация считается одной из востребованных благодаря сочетанию деловой активности и жилой застройки.</p>'
            . '<p>Эскорт ' . $cityEsc . ' в районе ' . $districtEsc . ' подходит для тех, кто предпочитает встречи без длительных переездов. Организация выезда в пределах района занимает минимальное время.</p>';

        $nearbyItemsHtml = '';
        foreach ($nearbyDistricts as $nearbyDistrict) {
            if (!($nearbyDistrict instanceof WP_Term)) {
                continue;
            }

            $nearbyName = (string) $nearbyDistrict->name;
            $nearbyUrl = get_term_link($nearbyDistrict);

            if (is_wp_error($nearbyUrl)) {
                $nearbyItemsHtml .= '<li>' . esc_html($nearbyName) . '</li>';
                continue;
            }

            $nearbyItemsHtml .= '<li><a href="' . esc_url((string) $nearbyUrl) . '">' . esc_html($nearbyName) . '</a></li>';
        }

        if ($nearbyItemsHtml === '') {
            $nearbyItemsHtml = '<li>Соседний район по локации</li><li>Ближайший район с доступными анкетами</li>';
        }

        $mainSeoText = '<h2>Эскорт в ' . $cityPrepositionalEsc . ' в районе ' . $districtEsc . '</h2>'
            . '<p>Выбор анкеты в районе ' . $districtEsc . ' позволяет сократить ожидание и упростить согласование встречи. Эскортницы ' . $cityEsc . ' принимают в апартаментах и жилых комплексах либо выезжают в гостиницы и бизнес-отели.</p>'
            . '<p>Преимущества локации:</p>'
            . '<ul><li>развитая инфраструктура</li><li>деловые центры</li><li>современные жилые комплексы</li><li>удобные подъездные пути</li></ul>'
            . '<p>Такой формат удобен как для вечерних встреч, так и для сопровождения на мероприятия.</p>'
            . '<h2>Цены и формат встреч в районе ' . $districtEsc . '</h2>'
            . '<p>Стоимость услуг в этой части города начинается от ' . $priceFromEsc . ' рублей. Итоговая сумма зависит от продолжительности встречи и выбранного формата.</p>'
            . '<p>Анкеты со статусом проверки подтверждают актуальность информации и соответствие фото.</p>'
            . '<h2>Если нет подходящих вариантов в ' . $districtEsc . '</h2>'
            . '<p>В часы повышенного спроса часть анкет может быть занята. В этом случае рекомендуется расширить поиск и рассмотреть соседние районы:</p>'
            . '<ul>' . $nearbyItemsHtml . '</ul>'
            . '<p>Расширение радиуса поиска позволяет найти вариант в пределах 15-20 минут.</p>';

        return [
            'seo_title' => 'Эскорт в районе ' . $districtPlain . ' - эскортницы ' . $cityPlain . ' 24/7',
            'meta_description' => 'Эскорт в районе ' . $districtPlain . ' (' . $cityPlain . '). Проверенные анкеты, реальные фото, выезд по локации и быстрый подбор без ожидания.',
            'h1' => 'Эскортницы ' . $cityPlain . ' в районе ' . $districtPlain,
            'description' => $description,
            'main_seo_text' => $mainSeoText,
        ];
    }

    private static function getPrimaryDistrictName(WP_Term $metroTerm, ?WP_Term $cityTerm): string
    {
        $profileIds = self::findProfileIdsForTaxonomyTerm('metro', $metroTerm, $cityTerm, 120);
        if (empty($profileIds)) {
            return 'центральной части города';
        }

        $districtFrequency = [];
        foreach ($profileIds as $profileId) {
            $districtTerms = get_the_terms((int) $profileId, 'district');
            if (empty($districtTerms) || is_wp_error($districtTerms)) {
                continue;
            }

            foreach ($districtTerms as $districtTerm) {
                $districtName = trim((string) $districtTerm->name);
                if ($districtName === '') {
                    continue;
                }

                if (!isset($districtFrequency[$districtName])) {
                    $districtFrequency[$districtName] = 0;
                }

                $districtFrequency[$districtName]++;
            }
        }

        if (empty($districtFrequency)) {
            return 'центральной части города';
        }

        arsort($districtFrequency);
        $topDistrict = (string) array_key_first($districtFrequency);

        return $topDistrict !== '' ? $topDistrict : 'центральной части города';
    }

    private static function estimateMeetingTime(int $profilesCount): string
    {
        if ($profilesCount >= 40) {
            return '15-25 минут';
        }

        if ($profilesCount >= 20) {
            return '20-30 минут';
        }

        if ($profilesCount >= 10) {
            return '25-35 минут';
        }

        return '30-45 минут';
    }

    private static function getMinimalPriceForTermTaxonomy(string $taxonomy, WP_Term $term, ?WP_Term $cityTerm): int
    {
        $taxQuery = [
            'relation' => 'AND',
            [
                'taxonomy' => $taxonomy,
                'field' => 'term_id',
                'terms' => [(int) $term->term_id],
            ],
        ];

        if ($cityTerm instanceof WP_Term) {
            $taxQuery[] = [
                'taxonomy' => 'city',
                'field' => 'term_id',
                'terms' => [(int) $cityTerm->term_id],
            ];
        }

        $ids = get_posts([
            'post_type' => 'profile',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'orderby' => 'meta_value_num',
            'meta_key' => 'price_price_1h',
            'order' => 'ASC',
            'meta_query' => [
                [
                    'key' => 'price_price_1h',
                    'value' => 0,
                    'compare' => '>',
                    'type' => 'NUMERIC',
                ],
            ],
            'tax_query' => $taxQuery,
            'no_found_rows' => true,
            'suppress_filters' => false,
        ]);

        $firstProfileId = (int) ($ids[0] ?? 0);
        if ($firstProfileId <= 0) {
            return 5000;
        }

        $rawPrice = get_post_meta($firstProfileId, 'price_price_1h', true);
        $price = (int) $rawPrice;

        return $price > 0 ? $price : 5000;
    }

    private static function getNearbyStations(WP_Term $currentStation, ?WP_Term $cityTerm, int $limit = 4): array
    {
        $allStations = get_terms([
            'taxonomy' => 'metro',
            'hide_empty' => true,
            'orderby' => 'count',
            'order' => 'DESC',
            'number' => 20,
        ]);

        if (is_wp_error($allStations) || empty($allStations)) {
            return [];
        }

        $stations = [];
        foreach ($allStations as $station) {
            if (!($station instanceof WP_Term)) {
                continue;
            }

            if ((int) $station->term_id === (int) $currentStation->term_id) {
                continue;
            }

            if ($cityTerm instanceof WP_Term && function_exists('get_related_city_id_for_location_term')) {
                $relatedCityId = (int) get_related_city_id_for_location_term('metro', $station);
                if ($relatedCityId > 0 && $relatedCityId !== (int) $cityTerm->term_id) {
                    continue;
                }
            }

            $stations[] = $station;

            if (count($stations) >= $limit) {
                break;
            }
        }

        return $stations;
    }

    private static function getNearbyDistricts(WP_Term $currentDistrict, ?WP_Term $cityTerm, int $limit = 2): array
    {
        $allDistricts = get_terms([
            'taxonomy' => 'district',
            'hide_empty' => true,
            'orderby' => 'count',
            'order' => 'DESC',
            'number' => 30,
        ]);

        if (is_wp_error($allDistricts) || empty($allDistricts)) {
            return [];
        }

        $districts = [];
        foreach ($allDistricts as $district) {
            if (!($district instanceof WP_Term)) {
                continue;
            }

            if ((int) $district->term_id === (int) $currentDistrict->term_id) {
                continue;
            }

            if ($cityTerm instanceof WP_Term && function_exists('get_related_city_id_for_location_term')) {
                $relatedCityId = (int) get_related_city_id_for_location_term('district', $district);
                if ($relatedCityId > 0 && $relatedCityId !== (int) $cityTerm->term_id) {
                    continue;
                }
            }

            $districts[] = $district;
            if (count($districts) >= $limit) {
                break;
            }
        }

        return $districts;
    }

    private static function getServiceFormats(WP_Term $serviceTerm, ?WP_Term $cityTerm, int $limit = 4): array
    {
        $profileIds = self::findProfileIdsForTaxonomyTerm('service', $serviceTerm, $cityTerm, 160);
        $frequency = [];

        foreach ($profileIds as $profileId) {
            $inoutTerms = get_the_terms((int) $profileId, 'inoutcall');
            if (empty($inoutTerms) || is_wp_error($inoutTerms)) {
                continue;
            }

            foreach ($inoutTerms as $inoutTerm) {
                if (!($inoutTerm instanceof WP_Term)) {
                    continue;
                }

                $label = self::mapInoutcallFormatLabel($inoutTerm);
                if ($label === '') {
                    continue;
                }

                if (!isset($frequency[$label])) {
                    $frequency[$label] = 0;
                }

                $frequency[$label]++;
            }
        }

        if (!empty($frequency)) {
            arsort($frequency);
        }

        $formats = array_keys($frequency);
        $defaults = [
            'Выезд к клиенту в отель или апартаменты',
            'Встреча на территории модели по договоренности',
            'Краткие и длительные встречи (1-2 часа или ночь)',
            'Подбор по району и удобному времени',
        ];

        foreach ($defaults as $defaultFormat) {
            if (!in_array($defaultFormat, $formats, true)) {
                $formats[] = $defaultFormat;
            }
        }

        return array_slice($formats, 0, $limit);
    }

    private static function mapInoutcallFormatLabel(WP_Term $term): string
    {
        if ($term->slug === 'outcall') {
            return 'Выезд к клиенту в отель или апартаменты';
        }

        if ($term->slug === 'incall') {
            return 'Встреча на территории модели';
        }

        if ($term->slug === 'incall-and-outcall') {
            return 'Гибкий формат: у модели или с выездом';
        }

        return trim((string) $term->name);
    }

    private static function getRelatedServices(WP_Term $currentService, ?WP_Term $cityTerm, int $limit = 2): array
    {
        $services = get_terms([
            'taxonomy' => 'service',
            'hide_empty' => true,
            'orderby' => 'count',
            'order' => 'DESC',
            'number' => 30,
        ]);

        if (is_wp_error($services) || empty($services)) {
            return [];
        }

        $related = [];
        foreach ($services as $serviceTerm) {
            if (!($serviceTerm instanceof WP_Term)) {
                continue;
            }

            if ((int) $serviceTerm->term_id === (int) $currentService->term_id) {
                continue;
            }

            if (!self::hasProfilesForServiceInCity($serviceTerm, $cityTerm)) {
                continue;
            }

            $termLink = get_term_link($serviceTerm);
            $related[] = [
                'label' => (string) $serviceTerm->name,
                'url' => is_wp_error($termLink) ? '' : (string) $termLink,
            ];

            if (count($related) >= $limit) {
                break;
            }
        }

        return $related;
    }

    private static function hasProfilesForServiceInCity(WP_Term $serviceTerm, ?WP_Term $cityTerm): bool
    {
        $taxQuery = [
            'relation' => 'AND',
            [
                'taxonomy' => 'service',
                'field' => 'term_id',
                'terms' => [(int) $serviceTerm->term_id],
            ],
        ];

        if ($cityTerm instanceof WP_Term) {
            $taxQuery[] = [
                'taxonomy' => 'city',
                'field' => 'term_id',
                'terms' => [(int) $cityTerm->term_id],
            ];
        }

        $ids = get_posts([
            'post_type' => 'profile',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'tax_query' => $taxQuery,
            'no_found_rows' => true,
            'suppress_filters' => false,
        ]);

        return !empty($ids);
    }

    private static function findProfileIdsForTaxonomyTerm(string $taxonomy, WP_Term $term, ?WP_Term $cityTerm, int $limit): array
    {
        $taxQuery = [
            'relation' => 'AND',
            [
                'taxonomy' => $taxonomy,
                'field' => 'term_id',
                'terms' => [(int) $term->term_id],
            ],
        ];

        if ($cityTerm instanceof WP_Term) {
            $taxQuery[] = [
                'taxonomy' => 'city',
                'field' => 'term_id',
                'terms' => [(int) $cityTerm->term_id],
            ];
        }

        $ids = get_posts([
            'post_type' => 'profile',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'fields' => 'ids',
            'tax_query' => $taxQuery,
            'no_found_rows' => true,
            'suppress_filters' => false,
        ]);

        return array_values(array_map('intval', (array) $ids));
    }

    private static function toPrepositionalCase(string $cityName): string
    {
        $cityName = trim($cityName);
        if ($cityName === '') {
            return 'городе';
        }

        $normalized = function_exists('mb_strtolower') ? mb_strtolower($cityName) : strtolower($cityName);
        $exceptions = [
            'москва' => 'Москве',
            'санкт-петербург' => 'Санкт-Петербурге',
            'нижний новгород' => 'Нижнем Новгороде',
            'ростов-на-дону' => 'Ростове-на-Дону',
        ];

        if (isset($exceptions[$normalized])) {
            return $exceptions[$normalized];
        }

        if (preg_match('/(о|е|ё|и|ы|у|ю|э)$/iu', $cityName)) {
            return $cityName;
        }

        if (preg_match('/а$/iu', $cityName)) {
            return (string) preg_replace('/а$/iu', 'е', $cityName);
        }

        if (preg_match('/я$/iu', $cityName)) {
            return (string) preg_replace('/я$/iu', 'е', $cityName);
        }

        if (preg_match('/ь$/iu', $cityName)) {
            return (string) preg_replace('/ь$/iu', 'и', $cityName);
        }

        return $cityName . 'е';
    }
}
