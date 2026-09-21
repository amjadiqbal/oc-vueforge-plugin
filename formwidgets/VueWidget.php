<?php

namespace AmjadIqbal\VueForge\FormWidgets;

use AmjadIqbal\VueForge\Classes\ViteResolver;
use Backend\Classes\FormWidgetBase;

/**
 * VueWidget is a generic FormWidget that hydrates an arbitrary Vue 3
 * single-file component inside an October CMS backend form.
 *
 * YAML usage:
 *
 *     my_field:
 *         label: My Field
 *         type: vueforge
 *         component: TagInput
 *         viteEntry: assets/vue/components/TagInput.vue
 *         props:
 *             placeholder: "Add a tag..."
 */
class VueWidget extends FormWidgetBase
{
    /**
     * @var string component is the name of the Vue component to mount
     * (must match the default export name used by vueforge.ts's dynamic
     * import map, or a path resolvable by the Vite entry below).
     */
    public $component;

    /**
     * @var array props are extra static props merged with the field's value
     * before being serialized to JSON and handed to the Vue component.
     */
    public $props = [];

    /**
     * @var string viteEntry is the plugin-relative path to the component's
     * source file, used to resolve its built/hashed asset via ViteResolver.
     */
    public $viteEntry;

    /**
     * @inheritDoc
     */
    protected $defaultAlias = 'vueforge';

    /**
     * @inheritDoc
     */
    public function init()
    {
        $this->fillFromConfig([
            'component',
            'props',
            'viteEntry',
        ]);

        if (!$this->component) {
            $this->component = 'JsonEditor';
        }

        if (!$this->viteEntry) {
            $this->viteEntry = 'assets/vue/components/' . $this->component . '.vue';
        }
    }

    /**
     * @inheritDoc
     */
    public function render()
    {
        $this->prepareVars();

        (new ViteResolver)->inject($this, 'assets/js/vueforge.ts');

        return $this->makePartial('vueforge');
    }

    /**
     * prepareVars for display. Serializes the current field value (falling
     * back to a component-appropriate empty default) plus any static props
     * into the JSON blob the frontend hydrator reads from the DOM.
     */
    public function prepareVars()
    {
        $value = $this->getLoadValue();

        if ($value === null || $value === '') {
            $value = $this->defaultValueFor($this->component);
        }

        $this->vars['fieldId'] = $this->getId();
        $this->vars['fieldName'] = $this->getFieldName();
        $this->vars['component'] = $this->component;
        $this->vars['props'] = $this->encodeProps(array_merge(
            $this->props,
            ['modelValue' => $value]
        ));
        $this->vars['value'] = $this->encodeValue($value);
    }

    /**
     * getSaveValue decodes and sanitizes the posted JSON payload before it is
     * persisted to the Eloquent model. Malformed input never reaches the
     * database - it degrades to a safe empty value instead of throwing.
     *
     * @param mixed $value
     * @return array|string
     */
    public function getSaveValue($value)
    {
        if ($this->formField->disabled || $this->formField->hidden) {
            return $this->previewMode ? $value : $this->getLoadValue();
        }

        if (is_array($value)) {
            return $this->sanitize($value);
        }

        if (!is_string($value) || trim($value) === '') {
            return $this->defaultValueFor($this->component);
        }

        $decoded = json_decode($value, true);

        // json_decode returns null both for the literal "null" and for
        // invalid JSON - json_last_error() disambiguates the two so a
        // legitimately-empty field isn't confused with a parse failure.
        if ($decoded === null) {
            // Covers both malformed JSON and the literal "null" - either way
            // there is no usable payload, so fall back to the safe default
            // rather than persisting a bare null a Vue component won't expect.
            return $this->defaultValueFor($this->component);
        }

        return $this->sanitize($decoded);
    }

    /**
     * sanitize strips anything that isn't plain data (arrays/scalars) from a
     * decoded payload, recursively, so a crafted JSON body can never inject
     * objects, resources, or otherwise unexpected structures into the model.
     *
     * @param mixed $data
     * @return mixed
     */
    protected function sanitize($data)
    {
        if (is_array($data)) {
            $clean = [];

            foreach ($data as $key => $item) {
                // Only allow scalar or string-castable array/object keys.
                if (!is_string($key) && !is_int($key)) {
                    continue;
                }

                $clean[$key] = $this->sanitize($item);
            }

            return $clean;
        }

        if (is_scalar($data) || $data === null) {
            return $data;
        }

        // Objects, resources, closures, etc. are never valid payload content.
        return null;
    }

    /**
     * defaultValueFor returns a safe, type-appropriate empty value for a
     * given component so save/load never has to deal with null vs missing.
     *
     * @param string|null $component
     * @return array
     */
    protected function defaultValueFor(?string $component)
    {
        return match ($component) {
            'TagInput' => [],
            default => [],
        };
    }

    /**
     * encodeProps JSON-encodes the props bag and HTML-escapes the result for
     * embedding as a double-quoted HTML attribute value. Note that PHP's
     * JSON_HEX_* flags only escape characters *inside* JSON string values -
     * they do not touch JSON's own structural quotes - so an explicit
     * htmlspecialchars() pass is required to make the whole blob attribute-safe.
     *
     * @param array $props
     * @return string
     */
    protected function encodeProps(array $props): string
    {
        return htmlspecialchars(
            json_encode($props, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ENT_QUOTES,
            'UTF-8'
        );
    }

    /**
     * encodeValue mirrors encodeProps for the hidden-input seed value.
     *
     * @param mixed $value
     * @return string
     */
    protected function encodeValue($value): string
    {
        return htmlspecialchars(
            json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ENT_QUOTES,
            'UTF-8'
        );
    }
}
