<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class AllReviews extends Composer
{
    protected static $views = [
        'template-all-reviews',
    ];

    public function with()
    {
        return [
            'allReviews' => $this->getAllReviews(),
        ];
    }

    public function getAllReviews()
    {
        $reviews = [];
        
        // Проверяем, доступна ли функция get_field
        if (!function_exists('get_field')) {
            error_log('AllReviews: function get_field() not available');
            return $reviews;
        }
        
        // Получаем анкеты у которых есть отзывы (через meta query)
        $profiles = get_posts([
            'post_type' => 'profile',
            'post_status' => 'publish',
            'posts_per_page' => 50, // Ограничиваем еще больше
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => 'reviews_list',
                    'compare' => 'EXISTS',
                ],
            ],
        ]);

        if (empty($profiles)) {
            error_log('AllReviews: No profiles with reviews found');
            return $reviews;
        }

        foreach ($profiles as $profileId) {
            try {
                // Получаем отзывы напрямую по ID
                $profileReviews = get_field('reviews_list', $profileId);
                
                if ($profileReviews && is_array($profileReviews)) {
                    foreach ($profileReviews as $review) {
                        // Фильтруем: выводим только отзывы без поля 'imported' или с imported = false
                        // Отзывы из JSON имеют поле 'imported' = true
                        $isImported = isset($review['imported']) && $review['imported'] === true;
                        
                        if (!$isImported && is_array($review) && (!empty($review['content']) || !empty($review['author']))) {
                            // Получаем город анкеты для формирования URL
                            $cityTerms = get_the_terms($profileId, 'city');
                            $citySlug = !empty($cityTerms) && !is_wp_error($cityTerms) ? $cityTerms[0]->slug : '';
                            
                            $reviews[] = [
                                'profile_id' => $profileId,
                                'profile_title' => get_the_title($profileId),
                                'profile_url' => $this->getProfileUrlWithCity($profileId, $citySlug),
                                'profile_photo' => get_the_post_thumbnail_url($profileId, 'thumbnail'),
                                'author' => $review['author'] ?? 'Аноним',
                                'rating' => $review['rating'] ?? 0,
                                'content' => $review['content'] ?? '',
                                'date' => $review['date'] ?? '',
                                'date_formatted' => $this->formatDate($review['date'] ?? ''),
                            ];
                        }
                    }
                }
            } catch (Exception $e) {
                error_log('AllReviews: Error processing profile ' . $profileId . ': ' . $e->getMessage());
                continue;
            }
        }

        // Сортируем отзывы по дате (новые первые)
        usort($reviews, function($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        error_log('AllReviews: Found ' . count($reviews) . ' reviews');
        return $reviews;
    }

    private function formatDate($date)
    {
        if (empty($date)) return '';
        
        $timestamp = strtotime($date);
        if ($timestamp === false) return '';
        
        return date('d.m.Y', $timestamp);
    }
    
    private function getProfileUrlWithCity($profileId, $citySlug)
    {
        // Используем нашу функцию profile_url() которая уже правильно генерирует URL
        if (!empty($citySlug)) {
            return profile_url($profileId, $citySlug);
        }
        
        // Если город не указан, используем автоматическое определение
        return profile_url($profileId);
    }
}
