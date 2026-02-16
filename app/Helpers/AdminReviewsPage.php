<?php

namespace App\Helpers;

class AdminReviewsPage
{
    public static function renderReviewsPage()
    {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Все отзывы моделей</h1>
            
            <?php self::renderFilters(); ?>
            
            <?php self::renderReviewsTable(); ?>
        </div>
        <?php
    }

    protected static function renderFilters()
    {
        $current_profile = isset($_GET['profile_id']) ? intval($_GET['profile_id']) : 0;
        $current_rating = isset($_GET['rating']) ? $_GET['rating'] : '';
        $current_status = isset($_GET['status']) ? $_GET['status'] : 'published';
        
        ?>
        <div class="tablenav top">
            <div class="alignleft actions">
                <form method="get" action="">
                    <input type="hidden" name="page" value="all-reviews">
                    
                    <select name="status" class="regular-text">
                        <option value="published" <?php echo $current_status === 'published' ? 'selected' : ''; ?>>Опубликовано</option>
                        <option value="pending" <?php echo $current_status === 'pending' ? 'selected' : ''; ?>>На модерации</option>
                        <option value="all" <?php echo $current_status === 'all' ? 'selected' : ''; ?>>Все</option>
                    </select>
                    
                    <select name="profile_id" class="regular-text">
                        <option value="">Все модели</option>
                        <?php
                        $profiles = get_posts([
                            'post_type' => 'profile',
                            'post_status' => 'publish',
                            'posts_per_page' => -1,
                            'orderby' => 'title',
                            'order' => 'ASC'
                        ]);
                        
                        foreach ($profiles as $profile) {
                            $selected = $current_profile == $profile->ID ? 'selected' : '';
                            echo "<option value='{$profile->ID}' {$selected}>{$profile->post_title}</option>";
                        }
                        ?>
                    </select>
                    
                    <select name="rating" class="regular-text">
                        <option value="">Все рейтинги</option>
                        <option value="5" <?php echo $current_rating === '5' ? 'selected' : ''; ?>>5 звезд</option>
                        <option value="4" <?php echo $current_rating === '4' ? 'selected' : ''; ?>>4 звезды</option>
                        <option value="3" <?php echo $current_rating === '3' ? 'selected' : ''; ?>>3 звезды</option>
                        <option value="2" <?php echo $current_rating === '2' ? 'selected' : ''; ?>>2 звезды</option>
                        <option value="1" <?php echo $current_rating === '1' ? 'selected' : ''; ?>>1 звезда</option>
                    </select>
                    
                    <input type="submit" class="button" value="Фильтровать">
                    <a href="?page=all-reviews" class="button">Сбросить</a>
                </form>
            </div>
            
            <div class="alignright">
                <button type="button" class="button button-link-delete" onclick="deleteAllReviews()">
                    <span class="dashicons dashicons-trash"></span> Удалить все отзывы
                </button>
            </div>
            
            <br class="clear">
        </div>
        <?php
    }

    protected static function renderReviewsTable()
    {
        $current_page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        $per_page = 20;
        $offset = ($current_page - 1) * $per_page;
        $current_status = isset($_GET['status']) ? $_GET['status'] : 'published';
        
        $args = [
            'post_type' => 'profile',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ];
        
        if (isset($_GET['profile_id']) && !empty($_GET['profile_id'])) {
            $args['include'] = [intval($_GET['profile_id'])];
        }
        
        $profiles = get_posts($args);
        $all_reviews = [];
        
        foreach ($profiles as $profile_id) {
            // Получаем опубликованные отзывы
            if ($current_status === 'published' || $current_status === 'all') {
                $published_reviews = get_field('reviews_list', $profile_id);
                if ($published_reviews && is_array($published_reviews)) {
                    foreach ($published_reviews as $review_index => $review) {
                        // Фильтруем: выводим только отзывы без поля 'imported' или с imported = false
                        $isImported = isset($review['imported']) && $review['imported'] === true;
                        
                        if (!$isImported && is_array($review) && (!empty($review['content']) || !empty($review['author']))) {
                            $rating = isset($review['rating']) ? floatval($review['rating']) : 0;
                            
                            // Фильтр по рейтингу
                            if (isset($_GET['rating']) && !empty($_GET['rating'])) {
                                $filter_rating = floatval($_GET['rating']);
                                if ($rating <= 1) {
                                    $rating = $rating * 5; // Конвертируем если это коэффициент
                                }
                                if (round($rating) != $filter_rating) {
                                    continue;
                                }
                            }
                            
                            $all_reviews[] = [
                                'profile_id' => $profile_id,
                                'profile_title' => get_the_title($profile_id),
                                'profile_url' => get_permalink($profile_id),
                                'author' => $review['author'] ?? 'Аноним',
                                'content' => $review['content'] ?? '',
                                'rating' => $rating,
                                'date' => $review['date'] ?? '',
                                'status' => 'published',
                                'review_index' => $review_index // Сохраняем реальный индекс
                            ];
                        }
                    }
                }
            }
            
            // Получаем отзывы на модерации
            if ($current_status === 'pending' || $current_status === 'all') {
                $pending_reviews = get_field('pending_reviews', $profile_id);
                error_log('Profile ID: ' . $profile_id . ', Pending reviews count: ' . ($pending_reviews ? count($pending_reviews) : 0));
                
                if ($pending_reviews && is_array($pending_reviews)) {
                    foreach ($pending_reviews as $review_index => $review) {
                        if (is_array($review) && (!empty($review['content']) || !empty($review['author']))) {
                            $rating = isset($review['rating']) ? floatval($review['rating']) : 0;
                            
                            // Фильтр по рейтингу
                            if (isset($_GET['rating']) && !empty($_GET['rating'])) {
                                $filter_rating = floatval($_GET['rating']);
                                if ($rating <= 1) {
                                    $rating = $rating * 5; // Конвертируем если это коэффициент
                                }
                                if (round($rating) != $filter_rating) {
                                    continue;
                                }
                            }
                            
                            $all_reviews[] = [
                                'profile_id' => $profile_id,
                                'profile_title' => get_the_title($profile_id),
                                'profile_url' => get_permalink($profile_id),
                                'author' => $review['author'] ?? 'Аноним',
                                'content' => $review['content'] ?? '',
                                'rating' => $rating,
                                'date' => $review['date'] ?? '',
                                'status' => 'pending',
                                'created_at' => $review['created_at'] ?? '',
                                'review_index' => $review_index // Сохраняем реальный индекс
                            ];
                        }
                    }
                }
            }
        }
        
        // Сортировка по дате (новые первые)
        usort($all_reviews, function($a, $b) {
            $date_a = $a['status'] === 'pending' ? ($a['created_at'] ?? '') : $a['date'];
            $date_b = $b['status'] === 'pending' ? ($b['created_at'] ?? '') : $b['date'];
            return strcmp($date_b, $date_a);
        });
        
        $total_reviews = count($all_reviews);
        $reviews = array_slice($all_reviews, $offset, $per_page);
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Модель</th>
                    <th>Автор</th>
                    <th>Рейтинг</th>
                    <th>Дата</th>
                    <th>Статус</th>
                    <th>Текст отзыва</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reviews)): ?>
                    <tr>
                        <td colspan="7">Отзывы не найдены</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <tr class="<?php echo $review['status'] === 'pending' ? 'status-pending' : ''; ?>">
                            <td>
                                <strong>
                                    <a href="<?php echo $review['profile_url']; ?>" target="_blank">
                                        <?php echo $review['profile_title']; ?>
                                    </a>
                                </strong>
                                <br>
                                <small>ID: <?php echo $review['profile_id']; ?></small>
                            </td>
                            <td><?php echo esc_html($review['author']); ?></td>
                            <td>
                                <?php
                                $stars = '';
                                $display_rating = $review['rating'];
                                if ($display_rating <= 1) {
                                    $display_rating = $display_rating * 5;
                                }
                                for ($i = 1; $i <= 5; $i++) {
                                    $stars .= $i <= round($display_rating) ? '★' : '☆';
                                }
                                echo $stars . ' (' . number_format($display_rating, 1) . '/5)';
                                ?>
                            </td>
                            <td>
                                <?php 
                                if ($review['status'] === 'pending') {
                                    echo !empty($review['created_at']) ? date('d.m.Y H:i', strtotime($review['created_at'])) : 'Не указана';
                                } else {
                                    echo !empty($review['date']) ? date('d.m.Y', strtotime($review['date'])) : 'Не указана';
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                if ($review['status'] === 'pending') {
                                    echo '<span class="status-pending"><span class="dashicons dashicons-clock"></span> На модерации</span>';
                                } else {
                                    echo '<span class="status-approved"><span class="dashicons dashicons-yes-alt"></span> Опубликовано</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                $content = strip_tags($review['content']);
                                echo strlen($content) > 100 ? substr($content, 0, 100) . '...' : $content;
                                ?>
                            </td>
                            <td>
                                <div class="flex gap-2">
                                    <button class="button" onclick="showReviewDetails(<?php echo $review['review_index']; ?>)">
                                        Подробнее
                                    </button>
                                    <?php if ($review['status'] === 'pending'): ?>
                                        <button class="button button-primary" onclick="approveReview(<?php echo $review['profile_id']; ?>, <?php echo $review['review_index']; ?>)">
                                            Одобрить
                                        </button>
                                        <button class="button button-link-delete" onclick="rejectReview(<?php echo $review['profile_id']; ?>, <?php echo $review['review_index']; ?>)">
                                            Отклонить
                                        </button>
                                        <button class="button button-link-delete" onclick="deleteReview(<?php echo $review['profile_id']; ?>, <?php echo $review['review_index']; ?>, 'pending')">
                                            Удалить
                                        </button>
                                    <?php else: ?>
                                        <button class="button button-primary" onclick="editReview(<?php echo $review['profile_id']; ?>, <?php echo $review['review_index']; ?>)">
                                            Редактировать
                                        </button>
                                        <button class="button button-link-delete" onclick="deleteReview(<?php echo $review['profile_id']; ?>, <?php echo $review['review_index']; ?>, 'published')">
                                            Удалить
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <style>
        .status-pending {
            color: #d63638;
            font-weight: bold;
        }
        .status-approved {
            color: #00a32a;
            font-weight: bold;
        }
        tr.status-pending {
            background-color: #fef7f7;
        }
        </style>
        
        <?php
        // Пагинация
        $total_pages = ceil($total_reviews / $per_page);
        if ($total_pages > 1) {
            echo '<div class="tablenav bottom">';
            echo '<div class="tablenav-pages">';
            
            // Сохраняем текущие GET параметры
            $current_url = remove_query_arg('paged', $_SERVER['REQUEST_URI']);
            
            // Кнопка "Назад"
            if ($current_page > 1) {
                $prev_url = add_query_arg('paged', $current_page - 1, $current_url);
                echo '<a class="prev-page" href="' . esc_url($prev_url) . '">«</a>';
            }
            
            // Номера страниц
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);
            
            if ($start_page > 1) {
                echo '<a class="page-numbers" href="' . esc_url(add_query_arg('paged', 1, $current_url)) . '">1</a>';
                if ($start_page > 2) {
                    echo '<span class="page-numbers dots">…</span>';
                }
            }
            
            for ($i = $start_page; $i <= $end_page; $i++) {
                $class = $i == $current_page ? 'current' : '';
                $url = add_query_arg('paged', $i, $current_url);
                echo "<a class='$class page-numbers' href='" . esc_url($url) . "'>$i</a>";
            }
            
            if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) {
                    echo '<span class="page-numbers dots">…</span>';
                }
                echo '<a class="page-numbers" href="' . esc_url(add_query_arg('paged', $total_pages, $current_url)) . '">' . $total_pages . '</a>';
            }
            
            // Кнопка "Вперед"
            if ($current_page < $total_pages) {
                $next_url = add_query_arg('paged', $current_page + 1, $current_url);
                echo '<a class="next-page" href="' . esc_url($next_url) . '">»</a>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        
        // Скрытые данные для JavaScript
        ?>
        <script type="text/javascript">
        var reviewsData = <?php echo json_encode($all_reviews); ?>;
        
        function showReviewDetails(index) {
            var review = reviewsData[index];
            alert(
                'Модель: ' + review.profile_title + '\n' +
                'Автор: ' + review.author + '\n' +
                'Рейтинг: ' + review.rating + '\n' +
                'Дата: ' + review.date + '\n\n' +
                'Текст:\n' + review.content
            );
        }
        
        function editReview(profileId, reviewIndex) {
            var review = reviewsData[reviewIndex];
            var editUrl = '<?php echo admin_url('post.php'); ?>?post=' + profileId + '&action=edit&review_index=' + reviewIndex + '#reviews_list';
            window.open(editUrl, '_blank');
        }
        
        function deleteReview(profileId, reviewIndex, status) {
            if (!confirm('Вы уверены, что хотите удалить этот отзыв?')) {
                return;
            }
            
            // Отправляем AJAX запрос на удаление
            jQuery.post(ajaxurl, {
                action: 'delete_review',
                profile_id: profileId,
                review_index: reviewIndex,
                review_status: status || 'published',
                _ajax_nonce: '<?php echo wp_create_nonce('delete_review_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    alert('Отзыв успешно удален');
                    location.reload();
                } else {
                    alert('Ошибка при удалении отзыва: ' + response.data);
                }
            });
        }
        
        function savePendingReview() {
            var profileId = document.getElementById('editProfileId').value;
            var reviewIndex = document.getElementById('editReviewIndex').value;
            var author = document.getElementById('editAuthor').value;
            var rating = document.getElementById('editRating').value;
            var content = document.getElementById('editContent').value;
            
            if (!author || !content) {
                alert('Пожалуйста, заполните все поля');
                return;
            }
            
            // Отправляем AJAX запрос на сохранение
            jQuery.post(ajaxurl, {
                action: 'update_pending_review',
                profile_id: profileId,
                review_index: reviewIndex,
                author: author,
                rating: rating,
                content: content,
                _ajax_nonce: '<?php echo wp_create_nonce('update_pending_review_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    alert('Отзыв успешно обновлен');
                    closeEditModal();
                    location.reload();
                } else {
                    alert('Ошибка при обновлении отзыва: ' + response.data);
                }
            });
        }
        
        function deleteAllReviews() {
            var currentStatus = '<?php echo isset($_GET['status']) ? $_GET['status'] : 'published'; ?>';
            var statusText = currentStatus === 'pending' ? 'на модерации' : 'опубликованных';
            
            if (!confirm('ВНИМАНИЕ! Вы уверены, что хотите удалить ВСЕ ' + statusText + ' отзывы? Это действие необратимо!')) {
                return;
            }
            
            // Дополнительное подтверждение
            var confirmText = prompt('Для подтверждения введите "УДАЛИТЬ ВСЕ" (без кавычек):');
            if (confirmText !== 'УДАЛИТЬ ВСЕ') {
                alert('Неправильный текст подтверждения. Действие отменено.');
                return;
            }
            
            // Отправляем AJAX запрос на массовое удаление
            jQuery.post(ajaxurl, {
                action: 'delete_all_reviews',
                status: currentStatus,
                _ajax_nonce: '<?php echo wp_create_nonce('delete_all_reviews_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    alert(response.data);
                    location.reload();
                } else {
                    alert('Ошибка при удалении отзывов: ' + response.data);
                }
            });
        }
        
        function approveReview(profileId, reviewIndex) {
            if (!confirm('Вы уверены, что хотите одобрить этот отзыв?')) {
                return;
            }
            
            // Отправляем AJAX запрос на одобрение
            jQuery.post(ajaxurl, {
                action: 'approve_review',
                profile_id: profileId,
                review_index: reviewIndex,
                _ajax_nonce: '<?php echo wp_create_nonce('moderate_review_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    alert('Отзыв одобрен и опубликован');
                    location.reload();
                } else {
                    alert('Ошибка при одобрении отзыва: ' + response.data);
                }
            });
        }
        
        function rejectReview(profileId, reviewIndex) {
            if (!confirm('Вы уверены, что хотите отклонить этот отзыв?')) {
                return;
            }
            
            // Отправляем AJAX запрос на отклонение
            jQuery.post(ajaxurl, {
                action: 'reject_review',
                profile_id: profileId,
                review_index: reviewIndex,
                _ajax_nonce: '<?php echo wp_create_nonce('moderate_review_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    alert('Отзыв отклонен');
                    location.reload();
                } else {
                    alert('Ошибка при отклонении отзыва: ' + response.data);
                }
            });
        }
        </script>
        <?php
    }
}
