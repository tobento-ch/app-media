<div class="image-action" data-image-action="greyscale">
    <span class="image-action-btn link" data-image-action-trigger="toggle-open|toggle-disabled"><?= $view->etrans('Greyscale') ?></span>
    <?= $form->input(
        name: 'actions.greyscale.',
        type: 'hidden',
        attributes: ['disabled', 'data-disable' => '1']
    ) ?>
</div>