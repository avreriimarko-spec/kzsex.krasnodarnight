<?php

namespace App\Services;

class ProfileLocationConsistencyService
{
    private const LOCATION_TAXONOMIES = ['metro', 'district'];
    private const CITY_TAXONOMY = 'city';

    private static bool $isSyncing = false;

    public static function registerHooks(): void
    {
        add_filter('use_block_editor_for_post_type', [self::class, 'disableBlockEditorForProfile'], 10, 2);

        add_action('save_post_profile', [self::class, 'syncLocationTermsWithCity'], 30, 3);

        // Важно для Gutenberg/REST и любых прямых операций с терминами.
        add_action('set_object_terms', [self::class, 'syncAfterObjectTermsChanged'], 20, 6);
    }

    public static function disableBlockEditorForProfile(bool $useBlockEditor, string $postType): bool
    {
        if ($postType === 'profile') {
            return false;
        }

        return $useBlockEditor;
    }

    public static function syncLocationTermsWithCity(int $postId, \WP_Post $post, bool $isUpdate): void
    {
        if (wp_is_post_revision($postId) || wp_is_post_autosave($postId)) {
            return;
        }

        if ($post->post_type !== 'profile') {
            return;
        }

        self::syncProfileTerms($postId);
    }

    /**
     * @param int|string $objectId
     * @param array<int|string>|string $terms
     * @param array<int> $ttIds
     * @param string $taxonomy
     * @param bool $append
     * @param array<int> $oldTtIds
     */
    public static function syncAfterObjectTermsChanged($objectId, $terms, array $ttIds, string $taxonomy, bool $append, array $oldTtIds): void
    {
        $postId = (int) $objectId;
        if ($postId <= 0) {
            return;
        }

        if (!in_array($taxonomy, array_merge([self::CITY_TAXONOMY], self::LOCATION_TAXONOMIES), true)) {
            return;
        }

        if (get_post_type($postId) !== 'profile') {
            return;
        }

        self::syncProfileTerms($postId);
    }

    private static function syncProfileTerms(int $postId): void
    {
        if (self::$isSyncing) {
            return;
        }

        self::$isSyncing = true;

        try {
            $existingCityIds = self::getProfileCityIds($postId);
            $selectedCityId = self::resolveSelectedCityId($existingCityIds);

            if ($selectedCityId <= 0) {
                self::setSingleCity($postId, 0);
                self::clearLocationTerms($postId);
                return;
            }

            self::setSingleCity($postId, $selectedCityId);
            self::syncLocationTermsForCity($postId, $selectedCityId);
        } finally {
            self::$isSyncing = false;
        }
    }

    private static function setSingleCity(int $postId, int $cityId): void
    {
        if ($cityId <= 0) {
            wp_set_object_terms($postId, [], self::CITY_TAXONOMY, false);
            return;
        }

        wp_set_object_terms($postId, [$cityId], self::CITY_TAXONOMY, false);
    }

    private static function syncLocationTermsForCity(int $postId, int $cityId): void
    {
        foreach (self::LOCATION_TAXONOMIES as $taxonomy) {
            if (!taxonomy_exists($taxonomy)) {
                continue;
            }

            $locationTerms = wp_get_object_terms($postId, $taxonomy, ['fields' => 'all']);
            if (is_wp_error($locationTerms) || empty($locationTerms)) {
                continue;
            }

            $validTermIds = [];

            foreach ($locationTerms as $locationTerm) {
                $relatedCityId = function_exists('get_related_city_id_for_location_term')
                    ? \get_related_city_id_for_location_term($taxonomy, $locationTerm)
                    : 0;

                if ((int) $relatedCityId === $cityId) {
                    $validTermIds[] = (int) $locationTerm->term_id;
                }
            }

            wp_set_object_terms($postId, $validTermIds, $taxonomy, false);
        }
    }

    private static function clearLocationTerms(int $postId): void
    {
        foreach (self::LOCATION_TAXONOMIES as $taxonomy) {
            if (!taxonomy_exists($taxonomy)) {
                continue;
            }

            wp_set_object_terms($postId, [], $taxonomy, false);
        }
    }

    /**
     * @param int[] $existingCityIds
     */
    private static function resolveSelectedCityId(array $existingCityIds): int
    {
        if (isset($_POST['tax_input'][self::CITY_TAXONOMY])) {
            $rawValue = wp_unslash($_POST['tax_input'][self::CITY_TAXONOMY]);

            if (is_array($rawValue)) {
                foreach ($rawValue as $cityId) {
                    $cityId = (int) $cityId;
                    if ($cityId > 0) {
                        return $cityId;
                    }
                }
            }

            $singleCityId = (int) $rawValue;
            if ($singleCityId > 0) {
                return $singleCityId;
            }
        }

        return !empty($existingCityIds) ? (int) $existingCityIds[0] : 0;
    }

    /**
     * @return int[]
     */
    private static function getProfileCityIds(int $postId): array
    {
        $cityIds = wp_get_object_terms($postId, self::CITY_TAXONOMY, ['fields' => 'ids']);

        if (is_wp_error($cityIds) || empty($cityIds)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $cityIds)));
    }
}
