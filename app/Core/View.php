<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    private string $basePath;

    public function __construct(string $basePath = __DIR__ . '/../../resources/views')
    {
        $this->basePath = rtrim($basePath, '\\/');
    }

    /**
     * Render a view inside the shared layout by default.
     *
     * @param array<string, mixed> $data
     */
    public function render(string $view, array $data = [], ?string $layout = 'shared.layout'): void
    {
        $content = $this->capture($view, $data);

        if ($layout === null) {
            echo $content;

            return;
        }

        $this->include($layout, array_merge([
            'pageScript' => $this->pageScript($view),
        ], $data, [
            'content' => $content,
            'viewName' => $view,
        ]));
    }

    /**
     * Render a view and return it as a string.
     *
     * @param array<string, mixed> $data
     */
    public function fetch(string $view, array $data = []): string
    {
        return $this->capture($view, $data);
    }

    /**
     * Render a view directly (no layout capture).
     *
     * @param array<string, mixed> $data
     */
    public function partial(string $view, array $data = []): void
    {
        $this->include($view, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function include(string $view, array $data): void
    {
        extract($data, EXTR_SKIP);
        $path = $this->path($view);

        if (!file_exists($path)) {
            throw new \RuntimeException("View not found: {$view} at {$path}");
        }

        include $path;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function capture(string $view, array $data): string
    {
        ob_start();
        $this->include($view, $data);

        return ob_get_clean() ?: '';
    }

    private function path(string $view): string
    {
        return $this->basePath . '/' . str_replace('.', '/', $view) . '.php';
    }

    private function pageScript(string $view): string
    {
        $script = str_replace('.', '/', $view);

        return "assets/js/pages/{$script}.js";
    }
}
