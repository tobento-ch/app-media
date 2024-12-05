<div class="image-action" data-image-action="background">
    <div class="image-action-head link" data-image-action-trigger="toggle-open"><?= $view->etrans('Background') ?></div>
    <div class="image-action-body">
        <div class="mt-s">
            <?= $form->input(
                name: 'actions.background.color',
                type: 'color',
                attributes: [
                    'id' => $form->nameToId($editorId.'actions.background.color'),
                    'class' => 'small fit',
                    'disabled',
                ]
            ) ?>
            <div class="mt-xs">
                <?= $form->input(
                    name: 'apply.background.color',
                    type: 'checkbox',
                    value: '0',
                    attributes: [
                        'data-image-action-trigger' => 'toggle-disabled-checkbox',
                        'data-disable-checkbox' => $form->nameToId($editorId.'actions.background.color'),
                        'id' => '',
                    ]
                ) ?>
                <span><?= $view->etrans('Apply background color to transparent image.') ?></span>
            </div>
        </div>
    </div>
</div>