<div class="image-action" data-image-action="contrast">
    <div class="image-action-head link" data-image-action-trigger="toggle-open"><?= $view->etrans('Contrast') ?></div>
    <div class="image-action-body">
        <div class="mt-s">
            <div><span data-range="0">0</span></div>
            <?= $form->input(
                name: 'actions.contrast.contrast',
                type: 'range',
                value: '0',
                attributes: [
                    'id' => $form->nameToId($editorId.'actions.contrast.contrast'),
                    'step' => '1',
                    'min' => '-100',
                    'max' => '100',
                    'class' => 'small fit',
                    'data-image-action-trigger' => 'range'
                ]
            ) ?>
        </div>
    </div>
</div>