<div class="image-action" data-image-action="format">
    <div class="image-action-head link" data-image-action-trigger="toggle-open"><?= $view->etrans('Format') ?></div>
    <div class="image-action-body">
        <div class="mt-s field">
            <?= $form->radios(
                name: 'format',
                items: array_combine($supportedMimeTypes, $supportedMimeTypes),
                selected: $file->mimeType(),
                attributes: [
                    'id' => $form->nameToId($editorId.'format'),
                ],
                labelAttributes: [],
                withInput: true,
                wrapClass: 'wrap-v'
            ) ?>
        </div>
    </div>
</div>