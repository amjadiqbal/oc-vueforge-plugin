<?php

use Amjad\VueForge\FormWidgets\VueWidget;
use Backend\Classes\FormField;
use Illuminate\Database\Eloquent\Model;

/**
 * VueWidgetTest exercises VueWidget directly against October's real
 * Backend\Classes\FormField / FormWidgetBase machinery (not mocks), covering:
 *  - prop/value serialization in prepareVars()
 *  - getSaveValue() JSON parsing
 *  - getSaveValue() sanitization against malformed/malicious payloads
 *
 * A bare FormField (rather than a full Backend\Widgets\Form) is used
 * deliberately: constructing a Form here would additionally require a real
 * Backend\Classes\Controller for its Larajax component-container wiring,
 * which is unrelated to what VueWidget itself is responsible for.
 *
 * Run from an October CMS application root that has this plugin installed
 * under plugins/amjad/vueforge, e.g.:
 *
 *     vendor/bin/phpunit -c phpunit.xml --testsuite "VueForge"
 */
class VueWidgetFixtureModel extends Model
{
    public $table = 'vueforge_fixture';

    protected $guarded = [];
}

class VueWidgetTest extends PluginTestCase
{
    protected function makeWidget(array $fieldConfig = [], $model = null): VueWidget
    {
        $model = $model ?: new VueWidgetFixtureModel;

        $field = new FormField(array_merge([
            'fieldName' => 'payload',
        ], array_intersect_key($fieldConfig, array_flip(['valueFrom', 'value']))));

        $widgetConfig = array_diff_key($fieldConfig, array_flip(['valueFrom', 'value']));
        $widgetConfig['model'] = $model;

        return new VueWidget(null, $field, $widgetConfig);
    }

    public function testDefaultComponentAndViteEntry()
    {
        $widget = $this->makeWidget();

        $this->assertSame('JsonEditor', $widget->component);
        $this->assertSame('assets/vue/components/JsonEditor.vue', $widget->viteEntry);
    }

    public function testConfiguredComponentAndViteEntry()
    {
        $widget = $this->makeWidget(['component' => 'TagInput']);

        $this->assertSame('TagInput', $widget->component);
        $this->assertSame('assets/vue/components/TagInput.vue', $widget->viteEntry);
    }

    public function testExplicitViteEntryIsNotOverridden()
    {
        $widget = $this->makeWidget([
            'component' => 'TagInput',
            'viteEntry' => 'assets/vue/custom/Wherever.vue',
        ]);

        $this->assertSame('assets/vue/custom/Wherever.vue', $widget->viteEntry);
    }

    public function testPrepareVarsSerializesModelValueAndStaticProps()
    {
        $model = new VueWidgetFixtureModel;
        $model->payload = ['a' => 1, 'b' => 'two'];

        $widget = $this->makeWidget([
            'component' => 'JsonEditor',
            'props' => ['placeholder' => 'hi'],
            'valueFrom' => 'payload',
        ], $model);

        $widget->prepareVars();

        // vars['props']/['value'] are HTML-attribute-escaped (see
        // VueWidget::encodeProps) - htmlspecialchars_decode() mirrors what
        // the browser's Element.getAttribute() does before vueforge.ts
        // hands the string to JSON.parse().
        $props = json_decode(htmlspecialchars_decode($widget->vars['props'], ENT_QUOTES), true);

        $this->assertSame('hi', $props['placeholder']);
        $this->assertSame(['a' => 1, 'b' => 'two'], $props['modelValue']);
        $this->assertSame(['a' => 1, 'b' => 'two'], json_decode(htmlspecialchars_decode($widget->vars['value'], ENT_QUOTES), true));
    }

    public function testPrepareVarsFallsBackToEmptyDefaultWhenValueIsMissing()
    {
        $widget = $this->makeWidget();

        $widget->prepareVars();

        $this->assertSame([], json_decode(htmlspecialchars_decode($widget->vars['value'], ENT_QUOTES), true));
    }

    public function testPrepareVarsEscapesValuesUnsafeForAnHtmlAttribute()
    {
        $model = new VueWidgetFixtureModel;
        $model->payload = ['xss' => '"><script>alert(1)</script>'];

        $widget = $this->makeWidget(['component' => 'JsonEditor', 'valueFrom' => 'payload'], $model);
        $widget->prepareVars();

        $this->assertStringNotContainsString('<script>', $widget->vars['props']);
        $this->assertStringNotContainsString('"', $widget->vars['props']);
    }

    public function testGetSaveValueDecodesValidJsonArray()
    {
        $widget = $this->makeWidget();

        $result = $widget->getSaveValue('{"key":"value","nested":{"a":1}}');

        $this->assertSame(['key' => 'value', 'nested' => ['a' => 1]], $result);
    }

    public function testGetSaveValueAcceptsAlreadyDecodedArray()
    {
        $widget = $this->makeWidget();

        $result = $widget->getSaveValue(['already' => 'array']);

        $this->assertSame(['already' => 'array'], $result);
    }

    public function testGetSaveValueReturnsDefaultOnMalformedJson()
    {
        $widget = $this->makeWidget();

        $result = $widget->getSaveValue('{not valid json');

        $this->assertSame([], $result);
    }

    public function testGetSaveValueReturnsDefaultOnEmptyString()
    {
        $widget = $this->makeWidget();

        $this->assertSame([], $widget->getSaveValue(''));
        $this->assertSame([], $widget->getSaveValue(null));
    }

    public function testGetSaveValuePreservesValidNestedArray()
    {
        $widget = $this->makeWidget();

        $valid = ['valid' => 'ok', 'nested' => ['deep' => 'value']];

        $result = $widget->getSaveValue($valid);

        $this->assertSame($valid, $result);
    }

    public function testGetSaveValueDropsObjectsAndResourcesFromDecodedArrays()
    {
        $widget = $this->makeWidget();

        $resource = fopen('php://memory', 'r');
        $malicious = [
            'safe' => 'value',
            'object' => new stdClass,
            'resource' => $resource,
            'nested' => ['also_object' => new stdClass, 'ok' => 1],
        ];

        $result = $widget->getSaveValue($malicious);
        fclose($resource);

        $this->assertSame('value', $result['safe']);
        $this->assertNull($result['object']);
        $this->assertNull($result['resource']);
        $this->assertSame(1, $result['nested']['ok']);
        $this->assertNull($result['nested']['also_object']);
    }

    public function testGetSaveValueHandlesJsonNullLiteralAsValidEmptyValue()
    {
        $widget = $this->makeWidget();

        // The literal string "null" is valid JSON for a null value; VueWidget
        // treats it the same as malformed JSON since a bare null is not a
        // usable payload for any VueForge component.
        $result = $widget->getSaveValue('null');

        $this->assertSame([], $result);
    }

    public function testGetSaveValueRespectsDisabledFieldByReturningLoadedValue()
    {
        $model = new VueWidgetFixtureModel;
        $model->payload = ['existing' => 'value'];

        $field = new FormField(['fieldName' => 'payload', 'valueFrom' => 'payload']);
        $field->disabled(true);

        $widget = new VueWidget(null, $field, ['model' => $model]);

        $result = $widget->getSaveValue('{"attempted":"override"}');

        $this->assertSame(['existing' => 'value'], $result);
    }
}
