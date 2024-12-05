<div class="image-action" data-image-action="sharpen">
    <div class="image-action-head link" data-image-action-trigger="toggle-open"><?= $view->etrans('Sharpen') ?></div>
    <div class="image-action-body">
        <div class="mt-s">
            <div><span data-range="0">0</span></div>
            <?= $form->input(
                name: 'actions.sharpen.sharpen',
                type: 'range',
                value: '0',
                attributes: [
                    'id' => $form->nameToId($editorId.'actions.sharpen.sharpen'),
                    'step' => '1',
                    'min' => '0',
                    'max' => '100',
                    'class' => 'small fit',
                    'data-image-action-trigger' => 'range'
                ]
            ) ?>
        </div>
    </div>
</div>