<div class="image-action" data-image-action="quality">
    <div class="image-action-head link" data-image-action-trigger="toggle-open"><?= $view->etrans('Quality') ?></div>
    <div class="image-action-body">
        <?php if (in_array('image/jpeg', $supportedMimeTypes)) { ?>
        <div class="mt-s">
            <div>
                <?= $form->label(
                    text: 'image/jpeg:',
                    for: $form->nameToId($editorId.'quality.jpeg')
                ) ?>
                <span data-range="70">70</span>
            </div>
            <?= $form->input(
                name: 'quality[image/jpeg]',
                type: 'range',
                value: '70',
                attributes: [
                    'id' => $form->nameToId($editorId.'quality'),
                    'step' => '1',
                    'min' => '1',
                    'max' => '100',
                    'class' => 'small fit',
                    'data-image-action-trigger' => 'range'
                ]
            ) ?>
        </div>
        <?php } ?>
        <?php if (in_array('image/webp', $supportedMimeTypes)) { ?>
        <div class="mt-s">
            <div>
                <?= $form->label(
                    text: 'image/webp:',
                    for: $form->nameToId($editorId.'quality')
                ) ?>
                <span data-range="70">70</span>
            </div>
            <?= $form->input(
                name: 'quality[image/webp]',
                type: 'range',
                value: '70',
                attributes: [
                    'id' => $form->nameToId($editorId.'quality.webp'),
                    'step' => '1',
                    'min' => '1',
                    'max' => '100',
                    'class' => 'small fit',
                    'data-image-action-trigger' => 'range'
                ]
            ) ?>
        </div>
        <?php } ?>
    </div>
</div>