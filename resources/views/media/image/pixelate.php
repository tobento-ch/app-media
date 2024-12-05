<div class="image-action" data-image-action="pixelate">
    <div class="image-action-head link" data-image-action-trigger="toggle-open"><?= $view->etrans('Pixelate') ?></div>
    <div class="image-action-body">
        <div class="mt-s">
            <div><span data-range="0">0</span></div>
            <?= $form->input(
                name: 'actions.pixelate.pixelate',
                type: 'range',
                value: '0',
                attributes: [
                    'id' => $form->nameToId($editorId.'actions.pixelate.pixelate'),
                    'step' => '1',
                    'min' => '0',
                    'max' => '1000',
                    'class' => 'small fit',
                    'data-image-action-trigger' => 'range'
                ]
            ) ?>
        </div>
    </div>
</div>