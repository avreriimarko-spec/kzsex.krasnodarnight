<?php

namespace App\Services;

class TelegramService
{
    private string $token;
    private string $chatId;

    public function __construct()
    {
        $this->token = get_field('tg_bot_token', 'option');
        $this->chatId = get_field('tg_chat_id', 'option');
    }

    /**
     * Проверка настроек
     */
    public function isConfigured(): bool
    {
        return !empty($this->token) && !empty($this->chatId);
    }

    /**
     * Получение ошибки конфигурации
     */
    public function getConfigError(): string
    {
        if (empty($this->token) && empty($this->chatId)) {
            return 'Токен бота и Chat ID не настроены';
        }
        if (empty($this->token)) {
            return 'Токен бота не настроен';
        }
        if (empty($this->chatId)) {
            return 'Chat ID не настроен';
        }
        return '';
    }

    /**
     * Основной метод отправки
     */
    public function sendApplication(string $text, array $files = []): bool
    {
        if (!$this->token || !$this->chatId) {
            return false;
        }

        if (empty($files)) {
            return $this->sendMessage($text);
        }

        return $this->sendMediaGroup($text, $files);
    }

    /**
     * Отправка простого текста
     */
    private function sendMessage(string $text): bool
    {
        $url = "https://api.telegram.org/bot{$this->token}/sendMessage";

        $data = [
            'chat_id' => $this->chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        $response = wp_remote_post($url, [
            'body' => $data,
            'timeout' => 15
        ]);

        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }

    /**
     * Отправка альбома фото с текстом
     */
    private function sendMediaGroup(string $caption, array $files): bool
    {
        $url = "https://api.telegram.org/bot{$this->token}/sendMediaGroup";

        $media = [];
        $postFields = ['chat_id' => $this->chatId];

        foreach ($files as $index => $filePath) {
            $key = "photo_$index";

            // Формируем структуру MediaGroup
            $mediaItem = [
                'type' => 'photo',
                'media' => "attach://$key",
            ];

            // Подпись добавляем только к первой фотографии
            if ($index === 0) {
                $mediaItem['caption'] = $caption;
                $mediaItem['parse_mode'] = 'HTML';
            }

            $media[] = $mediaItem;

            // Добавляем сам файл (CURLFile для PHP 8+)
            $postFields[$key] = new \CURLFile($filePath);
        }

        $postFields['media'] = json_encode($media);

        // Используем нативный curl, так как wp_remote_post плохо дружит с multipart/form-data и CURLFile
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }
}
