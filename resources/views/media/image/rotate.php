<div class="image-action" data-image-action="rotate">
    <div class="image-action-head link" data-image-action-trigger="toggle-open"><?= $view->etrans('Rotate') ?></div>
    <div class="image-action-body">
        <div class="mt-s">
            <div>
                <?= $form->label(
                    text: $view->trans('Degrees').':',
                    for: $form->nameToId($editorId.'actions.rotate.degrees')
                ) ?>
                <span data-range="0">0</span>
            </div>
            <?= $form->input(
                name: 'actions.rotate.degrees',
                type: 'range',
                attributes: [
                    'id' => $form->nameToId($editorId.'actions.rotate.degrees'),
                    'step' => '1',
                    'min' => '-360',
                    'max' => '360',
                    'class' => 'small fit',
                    'data-image-action-trigger' => 'range'
                ]
            ) ?>
        </div>
        <div class="mt-s">
            <div class="mb-xs">
                <?= $form->label(
                    text: $view->trans('Background color').':',
                    for: $form->nameToId($editorId.'actions.rotate.bgcolor')
                ) ?>
            </div>
            <?= $form->input(
                name: 'actions.rotate.bgcolor',
                type: 'color',
                attributes: [
                    'id' => $form->nameToId($editorId.'actions.rotate.bgcolor'),
                    'class' => 'small fit',
                    'disabled',
                ]
            ) ?>
            <div class="mt-xs">
                <?= $form->input(
                    name: 'apply.rotate.bgcolor',
                    type: 'checkbox',
                    value: '0',
                    attributes: [
                        'data-image-action-trigger' => 'toggle-disabled-checkbox',
                        'data-disable-checkbox' => $form->nameToId($editorId.'actions.rotate.bgcolor'),
                        'id' => '',
                    ]
                ) ?>
                <span><?= $view->etrans('Apply background color to image.') ?></span>
            </div>
        </div>
    </div>
</div>