<div class="image-action" data-image-action="sepia">
    <span class="image-action-btn link" data-image-action-trigger="toggle-open|toggle-disabled"><?= $view->etrans('Sepia') ?></span>
    <?= $form->input(
        name: 'actions.sepia.',
        type: 'hidden',
        attributes: ['disabled', 'data-disable' => '1']
    ) ?>
</div>