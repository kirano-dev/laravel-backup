<?php

namespace KiranoDev\LaravelBackup\Helpers;

use Illuminate\Support\Facades\Http;

class TG
{
    private string $token;
    private string $chat_id;

    public function __construct()
    {
        $this->token = config('backup.bot_token');
        $this->chat_id = config('backup.chat_id');
    }

    const EMPTY = 'empty';

    public function sendFile(string $filePath, array $data): void
    {
        $message = "";

        foreach ($data as $key => $value) {
            if($value) {
                $message .= ($value === self::EMPTY
                        ? $key
                        : "<b>" . $key . ":</b> " . $value) . "\n";
            }
        }

        try {
            $response = Http::
            attach('document', file_get_contents($filePath), basename($filePath))
                ->post("https://api.telegram.org/bot$this->token/sendDocument", [
                    'parse_mode' => 'HTML',
                    'chat_id' => $this->chat_id,
                    'caption' => $message,
                ]);

            info($response->json());
        } catch (\Exception $e) {
            info($e->getMessage());
        }
    }

    public function sendFormatMessage(array $data): void
    {
        $message = "";

        foreach ($data as $key => $value) {
            if($value) {
                $message .= ($key === 0
                        ? $value
                        : "<b>" . $key . ":</b> " . $value) . "\n";
            }
        }

        try {
            Http::withQueryParameters([
                'parse_mode' => 'HTML',
                'chat_id' => $this->chat_id,
                'text' => $message,
                'disable_notification' => true,
                'link_preview_options' => json_encode([
                    'is_disabled' => true
                ]),
            ])->get("https://api.telegram.org/bot$this->token/sendMessage");
        } catch (\Exception $e) {
            info($e->getMessage());
        }
    }
}
