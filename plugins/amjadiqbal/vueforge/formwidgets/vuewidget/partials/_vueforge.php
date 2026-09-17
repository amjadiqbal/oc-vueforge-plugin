<?php if ($this->previewMode): ?>
    <div class="form-control">
        <pre><?= e($value) ?></pre>
    </div>
<?php else: ?>
    <div
        id="<?= $fieldId ?>"
        class="vueforge-widget"
        data-vueforge-widget="<?= e($component) ?>"
        data-vueforge-field="<?= e($fieldName) ?>"
        data-vueforge-props="<?= $props ?>"
    ></div>
    <input
        type="hidden"
        name="<?= $fieldName ?>"
        id="<?= $fieldId ?>-input"
        value="<?= $value ?>"
        data-vueforge-hidden-input
    />
<?php endif ?>
