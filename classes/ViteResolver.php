<?php

namespace Amjad\VueForge\Classes;

use Backend\Classes\FormWidgetBase;
use RuntimeException;

/**
 * Resolves Vite asset URLs for a VueForge entry point, transparently
 * switching between the local Vite HMR dev server and the production
 * manifest.json produced by `npm run build`.
 */
class ViteResolver
{
    /**
     * Relative path (from the plugin root) to the dev server's ping file.
     * When this responds, we know the HMR server is up.
     */
    protected string $devServerUrl = 'http://localhost:5173';

    protected string $buildDir = 'assets/dist';

    protected string $manifestPath = 'assets/dist/.vite/manifest.json';

    protected ?array $manifestCache = null;

    /**
     * Inject the JS/CSS needed to load $entry (e.g. "assets/js/vueforge.ts")
     * onto the given form widget, in either dev or production mode.
     */
    public function inject(FormWidgetBase $widget, string $entry): void
    {
        if ($this->isDevServerRunning()) {
            $this->injectDev($widget, $entry);

            return;
        }

        $this->injectProduction($widget, $entry);
    }

    /**
     * Detect whether the Vite dev server is reachable. We deliberately use a
     * very short timeout so a cold/absent dev server never stalls page loads
     * in production - this should only ever be true on a developer machine.
     */
    public function isDevServerRunning(): bool
    {
        if (!app()->environment('local', 'testing')) {
            return false;
        }

        $context = stream_context_create(['http' => ['timeout' => 0.2, 'ignore_errors' => true]]);
        $result = @file_get_contents($this->devServerUrl . '/@vite/client', false, $context);

        return $result !== false;
    }

    protected function injectDev(FormWidgetBase $widget, string $entry): void
    {
        // Vite's client enables HMR; it must be loaded before the entry module.
        $widget->addJs($this->devServerUrl . '/@vite/client', ['type' => 'module']);
        $widget->addJs($this->devServerUrl . '/' . ltrim($entry, '/'), ['type' => 'module']);
    }

    protected function injectProduction(FormWidgetBase $widget, string $entry): void
    {
        $manifest = $this->loadManifest();
        $chunk = $manifest[$entry] ?? null;

        if (!$chunk) {
            throw new RuntimeException(sprintf(
                'VueForge: entry "%s" was not found in the Vite manifest (%s). Run `npm run build` in the vueforge plugin directory.',
                $entry,
                $this->manifestPath
            ));
        }

        $baseUrl = '/plugins/amjad/vueforge/' . $this->buildDir . '/';

        $widget->addJs($baseUrl . $chunk['file'], ['type' => 'module']);

        foreach ($chunk['css'] ?? [] as $cssFile) {
            $widget->addCss($baseUrl . $cssFile);
        }

        foreach ($chunk['imports'] ?? [] as $importKey) {
            $importedChunk = $manifest[$importKey] ?? null;
            if ($importedChunk) {
                $widget->addJs($baseUrl . $importedChunk['file'], ['type' => 'modulepreload']);
            }
        }
    }

    protected function loadManifest(): array
    {
        if ($this->manifestCache !== null) {
            return $this->manifestCache;
        }

        $path = plugins_path('amjad/vueforge/' . $this->manifestPath);

        if (!file_exists($path)) {
            throw new RuntimeException(sprintf(
                'VueForge: Vite manifest not found at "%s". Run `npm run build` in the vueforge plugin directory.',
                $path
            ));
        }

        $contents = json_decode(file_get_contents($path), true);

        return $this->manifestCache = is_array($contents) ? $contents : [];
    }
}
