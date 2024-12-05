<div class="image-action" data-image-action="flip">
    <div class="image-action-head link" data-image-action-trigger="toggle-open"><?= $view->etrans('Mirror') ?></div>
    <div class="image-action-body">
        <div class="mt-s field">
            <?= $form->radios(
                name: 'actions.flip.flip',
                items: [
                    'none' => $view->trans('Unmirrored'),
                    'horizontal' => $view->trans('Horizontal'),
                    'vertical' => $view->trans('Vertical'),
                ],
                selected: 'none',
                attributes: [
                    'id' => $form->nameToId($editorId.'actions.flip.flip'),
                ],
                labelAttributes: [],
                withInput: true,
                wrapClass: 'wrap-v'
            ) ?>
        </div>
    </div>
</div>