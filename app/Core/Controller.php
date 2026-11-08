<?php
namespace App\Core;

abstract class Controller
{
    protected function render(string $view, array $data = [], ?string $layout = 'layouts/master'): void
    {
        View::render($view, $data, $layout);
    }

    protected function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    protected function redirect(string $url): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        header("Location: {$url}");
        exit;
    }
}
