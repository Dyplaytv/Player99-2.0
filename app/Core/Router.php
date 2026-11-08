<?php
namespace App\Core;

use Exception;

class Router
{
    protected array $routes = [];
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function get(string $path, $handler, array $middlewares = []): self
    {
        $this->addRoute('GET', $path, $handler, $middlewares);
        return $this;
    }

    public function post(string $path, $handler, array $middlewares = []): self
    {
        $this->addRoute('POST', $path, $handler, $middlewares);
        return $this;
    }

    protected function addRoute(string $method, string $path, $handler, array $middlewares = []): void
    {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . rtrim($pattern, '/') . '/?$#i';

        $this->routes[] = [
            'method' => $method,
            'path' => '/' . trim($path, '/'),
            'pattern' => $pattern,
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }

    public function dispatch()
    {
        $method = $this->request->method();
        $uri = $this->request->uri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Executa Middlewares
                foreach ($route['middlewares'] as $middlewareClass) {
                    $middleware = new $middlewareClass();
                    $middleware->handle($this->request);
                }

                // Executa Handler
                $handler = $route['handler'];
                if (is_callable($handler)) {
                    return call_user_func_array($handler, array_merge([$this->request], $params));
                }

                if (is_array($handler) && count($handler) === 2) {
                    [$class, $action] = $handler;
                    if (class_exists($class)) {
                        $controller = new $class();
                        if (method_exists($controller, $action)) {
                            return call_user_func_array([$controller, $action], array_merge([$this->request], $params));
                        }
                    }
                }
            }
        }

        // Fallback gracioso para scripts legados em transição (em legacy_archive/)
        $cleanUri = ltrim($uri, '/');
        $legacyFile = dirname(__DIR__, 2) . '/legacy_archive/' . $cleanUri;
        if (file_exists($legacyFile) && is_file($legacyFile)) {
            // Ajusta o diretório de execução para a raiz para manter includes relativos funcionais
            chdir(dirname(__DIR__, 2));
            require $legacyFile;
            exit;
        }

        // 404 Not Found
        http_response_code(404);
        echo "<div style='text-align:center; padding:50px; font-family:sans-serif;'><h1>404 - Página Não Encontrada</h1><p>A rota solicitada não existe.</p><a href='/'>Voltar para o Início</a></div>";
        exit;
    }
}
