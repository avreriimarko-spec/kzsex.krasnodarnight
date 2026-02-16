<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\TelegramService; // <-- Подключаем сервис

class FormServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        add_shortcode('custom_work_form', function () {
            return view('components.work-form')->render();
        });
        add_action('wp_ajax_send_work_form', [$this, 'handleSubmission']);
        add_action('wp_ajax_nopriv_send_work_form', [$this, 'handleSubmission']);

        add_shortcode('custom_contact_form', function () {
            return view('components.contact-form')->render();
        });
        add_action('wp_ajax_send_contact_form', [$this, 'handleContactSubmission']);
        add_action('wp_ajax_nopriv_send_contact_form', [$this, 'handleContactSubmission']);
    }

    public function handleSubmission()
    {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'work_form_action')) {
            wp_send_json_error(['message' => 'Ошибка безопасности.'], 403);
        }

        $name = sanitize_text_field($_POST['name'] ?? '');
        $age = sanitize_text_field($_POST['age'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $about = sanitize_textarea_field($_POST['about'] ?? '');

        if (!$name || !$phone) {
            wp_send_json_error(['message' => 'Заполните обязательные поля.'], 400);
        }

        // Обработка файлов (загружаем во временную папку)
        $attachments = [];
        if (!empty($_FILES['photos'])) {
            // Перегруппировка массива $_FILES
            $files = [];
            foreach ($_FILES['photos'] as $key => $all) {
                foreach ($all as $i => $val) {
                    $files[$i][$key] = $val;
                }
            }

            foreach ($files as $file) {
                if ($file['error'] === 0) {
                    $upload = wp_handle_upload($file, ['test_form' => false]);
                    if (isset($upload['file'])) {
                        $attachments[] = $upload['file']; // Полный путь к файлу на диске
                    }
                }
            }
        }

        // Формируем красивое сообщение для ТГ (HTML)
        $message = "💼 <b>Новая заявка на работу</b>\n\n";
        $message .= "👤 <b>Имя:</b> $name\n";
        $message .= "🔞 <b>Возраст:</b> $age\n";
        $message .= "📱 <b>Телефон:</b> $phone\n";
        if ($about) {
            $message .= "\n📝 <b>О себе:</b>\n$about";
        }
        $message .= "\n\n🔗 <i>Отправлено с сайта " . get_bloginfo('name') . "</i>";

        // ОТПРАВКА В TELEGRAM
        $tg = new TelegramService();
        
        // Проверяем настройки Telegram
        if (!$tg->isConfigured()) {
            // Удаляем файлы если есть
            foreach ($attachments as $filePath) {
                @unlink($filePath);
            }
            wp_send_json_error(['message' => 'Ошибка отправки: ' . $tg->getConfigError() . '. Свяжитесь с администратором сайта.'], 500);
        }
        
        $sent = $tg->sendApplication($message, $attachments);

        // Удаляем файлы с сервера после отправки (чтобы не засорять диск)
        foreach ($attachments as $filePath) {
            @unlink($filePath);
        }

        if ($sent) {
            wp_send_json_success(['message' => 'Анкета успешно отправлена в Telegram!']);
        } else {
            // Если токен не задан или ошибка API
            wp_send_json_error(['message' => 'Ошибка отправки. Свяжитесь с нами через мессенджеры.'], 500);
        }
    }

    public function handleContactSubmission()
    {
        // Проверка nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'contact_form_action')) {
            wp_send_json_error(['message' => 'Ошибка безопасности.'], 403);
        }

        $name = sanitize_text_field($_POST['name'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $email = sanitize_text_field($_POST['email'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');

        if (!$name || !$phone) {
            wp_send_json_error(['message' => 'Заполните имя и номер телефона.'], 400);
        }

        // Формируем сообщение для Telegram
        $msg = "📬 <b>Новое сообщение (Контакты)</b>\n\n";
        $msg .= "👤 <b>Имя:</b> $name\n";
        $msg .= "📱 <b>Телефон:</b> $phone\n";
        if ($email) {
            $msg .= "📧 <b>Email:</b> $email\n";
        }
        if ($message) {
            $msg .= "\n💬 <b>Комментарий:</b>\n$message";
        }
        $msg .= "\n\n🔗 <i>" . get_bloginfo('name') . "</i>";

        // Отправка через наш сервис
        $tg = new TelegramService();
        
        // Проверяем настройки Telegram
        if (!$tg->isConfigured()) {
            wp_send_json_error(['message' => 'Ошибка отправки: ' . $tg->getConfigError() . '. Свяжитесь с администратором сайта.'], 500);
        }
        
        $sent = $tg->sendApplication($msg);

        if ($sent) {
            wp_send_json_success(['message' => 'Сообщение отправлено!']);
        } else {
            wp_send_json_error(['message' => 'Ошибка отправки в Telegram. Попробуйте позже или свяжитесь с нами напрямую.'], 500);
        }
    }
}
