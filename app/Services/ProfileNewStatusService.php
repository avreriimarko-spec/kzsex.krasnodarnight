<?php

namespace App\Services;

class ProfileNewStatusService
{
    private const CRON_HOOK = 'kzsex_sync_profile_new_status';
    private const NEW_TAXONOMY = 'new';
    private const NEW_TAXONOMY_TERM_SLUG = 'new';
    private const NEW_TAXONOMY_TERM_NAME = 'Новые';
    private const NEW_CATEGORY_SLUGS = ['new', 'novye', 'новые'];

    /**
     * Регистрирует hooks для автообновления статуса "Новая".
     */
    public static function registerHooks(): void
    {
        add_action('init', [self::class, 'scheduleSyncEvent']);
        add_action('save_post_profile', [self::class, 'syncSingleProfileOnSave'], 20, 3);
        add_action(self::CRON_HOOK, [self::class, 'syncAllPublishedProfiles']);
    }

    /**
     * Планирует периодическую синхронизацию.
     */
    public static function scheduleSyncEvent(): void
    {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'twicedaily', self::CRON_HOOK);
        }
    }

    /**
     * Синхронизация одной анкеты после сохранения.
     */
    public static function syncSingleProfileOnSave(int $postId, \WP_Post $post, bool $isUpdate): void
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
     * Пакетная синхронизация всех опубликованных анкет.
     */
    public static function syncAllPublishedProfiles(): void
    {
        $profileIds = get_posts([
            'post_type' => 'profile',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'orderby' => 'ID',
            'order' => 'ASC',
        ]);

        foreach ($profileIds as $profileId) {
            self::syncProfileTerms((int) $profileId);
        }
    }

    /**
     * Добавляет или убирает термины "новые" согласно правилу 7 дней.
     */
    private static function syncProfileTerms(int $profileId): void
    {
        $isNew = ProfileQuery::isProfileNew($profileId);

        self::syncNewTaxonomy($profileId, $isNew);
        self::syncCategoryTerms($profileId, $isNew);
    }

    /**
     * Синхронизация кастомной таксономии `new`.
     */
    private static function syncNewTaxonomy(int $profileId, bool $isNew): void
    {
        if (!taxonomy_exists(self::NEW_TAXONOMY)) {
            return;
        }

        $termId = self::ensureNewTaxonomyTermId();
        if ($termId <= 0) {
            return;
        }

        if ($isNew) {
            wp_add_object_terms($profileId, [$termId], self::NEW_TAXONOMY);
            return;
        }

        $termIds = wp_get_object_terms($profileId, self::NEW_TAXONOMY, ['fields' => 'ids']);
        if (is_wp_error($termIds) || empty($termIds)) {
            return;
        }

        wp_remove_object_terms($profileId, array_map('intval', $termIds), self::NEW_TAXONOMY);
    }

    /**
     * Синхронизация стандартной `category` (если она разрешена для profile).
     */
    private static function syncCategoryTerms(int $profileId, bool $isNew): void
    {
        if (!taxonomy_exists('category') || !is_object_in_taxonomy('profile', 'category')) {
            return;
        }

        $categoryTermIds = self::getExistingCategoryNewTermIds();

        if ($isNew) {
            if (empty($categoryTermIds)) {
                $created = wp_insert_term('Новые', 'category', ['slug' => 'new']);
                if (!is_wp_error($created) && isset($created['term_id'])) {
                    $categoryTermIds = [(int) $created['term_id']];
                }
            }

            if (!empty($categoryTermIds)) {
                wp_add_object_terms($profileId, [(int) $categoryTermIds[0]], 'category');
            }
            return;
        }

        if (!empty($categoryTermIds)) {
            wp_remove_object_terms($profileId, array_map('intval', $categoryTermIds), 'category');
        }
    }

    /**
     * Возвращает term_id для термина "new" в таксономии `new` (создаёт при отсутствии).
     */
    private static function ensureNewTaxonomyTermId(): int
    {
        $term = get_term_by('slug', self::NEW_TAXONOMY_TERM_SLUG, self::NEW_TAXONOMY);
        if ($term && !is_wp_error($term)) {
            return (int) $term->term_id;
        }

        $created = wp_insert_term(
            self::NEW_TAXONOMY_TERM_NAME,
            self::NEW_TAXONOMY,
            ['slug' => self::NEW_TAXONOMY_TERM_SLUG]
        );

        if (is_wp_error($created) || empty($created['term_id'])) {
            return 0;
        }

        return (int) $created['term_id'];
    }

    /**
     * Ищет существующие "новые" категории по известным slug.
     *
     * @return array<int>
     */
    private static function getExistingCategoryNewTermIds(): array
    {
        $terms = get_terms([
            'taxonomy' => 'category',
            'slug' => self::NEW_CATEGORY_SLUGS,
            'hide_empty' => false,
        ]);

        if (is_wp_error($terms) || empty($terms)) {
            return [];
        }

        return array_values(array_unique(array_map(static fn($term) => (int) $term->term_id, $terms)));
    }
}
