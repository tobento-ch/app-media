<div class="image-action" data-image-action="gamma">
    <div class="image-action-head link" data-image-action-trigger="toggle-open"><?= $view->etrans('Gamma') ?></div>
    <div class="image-action-body">
        <div class="mt-s">
            <div><span data-range="0">0</span></div>
            <?= $form->input(
                name: 'actions.gamma.gamma',
                type: 'range',
                value: '1',
                attributes: [
                    'id' => $form->nameToId($editorId.'actions.gamma.gamma'),
                    'step' => '0.05',
                    'min' => '0.01',
                    'max' => '9.99',
                    'class' => 'small fit',
                    'data-image-action-trigger' => 'range'
                ]
            ) ?>
        </div>
    </div>
</div>