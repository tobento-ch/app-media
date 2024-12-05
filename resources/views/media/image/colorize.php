<div class="image-action" data-image-action="colorize">
    <div class="image-action-head link" data-image-action-trigger="toggle-open"><?= $view->etrans('Colorize') ?></div>
    <div class="image-action-body">
        <div class="mt-s">
            <div>
                <?= $form->label(
                    text: $view->trans('red').':',
                    for: $form->nameToId($editorId.'actions.colorize.red')
                ) ?>
                <span data-range="0">0</span>
            </div>
            <?= $form->input(
                name: 'actions.colorize.red',
                type: 'range',
                attributes: [
                    'id' => $form->nameToId($editorId.'actions.colorize.red'),
                    'step' => '1',
                    'min' => '-100',
                    'max' => '100',
                    'class' => 'small fit',
                    'data-image-action-trigger' => 'range'
                ]
            ) ?>
        </div>
        <div class="mt-s">
            <div>
                <?= $form->label(
                    text: $view->trans('green').':',
                    for: $form->nameToId($editorId.'actions.colorize.green')
                ) ?>
                <span data-range="0">0</span>
            </div>
            <?= $form->input(
                name: 'actions.colorize.green',
                type: 'range',
                attributes: [
                    'id' => $form->nameToId($editorId.'actions.colorize.green'),
                    'step' => '1',
                    'min' => '-100',
                    'max' => '100',
                    'class' => 'small fit',
                    'data-image-action-trigger' => 'range'
                ]
            ) ?>
        </div>
        <div class="mt-s">
            <div>
                <?= $form->label(
                    text: $view->trans('blue').':',
                    for: $form->nameToId($editorId.'actions.colorize.blue')
                ) ?>
                <span data-range="0">0</span>
            </div>
            <?= $form->input(
                name: 'actions.colorize.blue',
                type: 'range',
                attributes: [
                    'id' => $form->nameToId($editorId.'actions.colorize.blue'),
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