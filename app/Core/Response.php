<?php

namespace App\Core;

class Response {
    public function setStatusCode(int $code) {
        http_response_code($code);
    }

    public function setHeader(string $name, string $value) {
        header("$name: $value");
    }

    public function json(array $data, int $statusCode = 200) {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'application/json');
        echo json_encode($data);
        exit;
    }
}
