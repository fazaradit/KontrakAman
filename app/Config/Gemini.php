<?php

namespace App\Config;

class Gemini {
    public static function getApiKey(): string {
        return getenv('GEMINI_API_KEY') ?: '';
    }

    public static function getModel(): string {
        return getenv('GEMINI_MODEL') ?: 'gemini-1.5-pro';
    }
}
