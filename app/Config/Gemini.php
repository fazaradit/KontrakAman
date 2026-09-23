<?php

namespace App\Config;

class Gemini {
    public static function getApiKey(): string {
        return $_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY') ?: '';
    }

    public static function getModel(): string {
        return $_ENV['GEMINI_MODEL'] ?? getenv('GEMINI_MODEL') ?: 'gemini-1.5-flash';
    }
}
