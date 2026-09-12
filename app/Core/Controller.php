<?php

namespace App\Core;

abstract class Controller {
    protected function json(array $data, int $statusCode = 200) {
        $response = new Response();
        return $response->json($data, $statusCode);
    }

    protected function view(string $viewPath, array $data = []) {
        extract($data);
        
        $file = __DIR__ . '/../Views/' . $viewPath . '.php';
        
        if (file_exists($file)) {
            require_once $file;
        } else {
            die("View $viewPath not found");
        }
    }
}
