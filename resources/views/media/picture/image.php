<div class="image-editor">
    <?php $form = $view->form(); ?>
    <?= $form->form([
        'action' => $view->routeUrl('media.picture.editor.update', [
            'template' => $template, 'storage' => $storage, 'path' => $file->path()
        ]),
        'data-image-editor' => [
            'id' => $editorId,
            'previewUrl' => (string)$view->routeUrl('media.picture.editor.preview', [
                'template' => $template, 'storage' => $storage, 'path' => $file->path()
            ]),
            'previewMethod' => 'POST',
            'actionOpen' => 'crop',
        ],
        'name' => $formName,
    ]) ?>
    <div class="image-editor-container">
        <div class="image-editor-image" data-image-loading="">
            <img src="<?= $view->esc($file->url()) ?>" alt="" data-image="">
        </div>
        <div class="image-editor-actions">
            <div class="image-editor-actions-head">
                <div class="buttons spaced">
                    <span class="button text-xs" data-image-action="reset" data-image-action-trigger="reset"><?= $view->etrans('Reset') ?></span>
                </div>
            </div>
            <div class="image-editor-actions-body">
                <?php
                foreach($actions->filters(false)->all() as $action) {
                    echo $view->render('media/image/'.$action, ['form' => $form, 'editorId' => $editorId]);
                }
                
                echo $view->render('media/image/filters', ['form' => $form, 'editorId' => $editorId]);
                ?>
            </div>
            <div class="image-editor-actions-foot">
                <div class="image-editor-attributes">
                    <span class="text-700" data-image-attr="mimetype"><?= $view->esc($file->mimeType()) ?></span>
                    <span>
                        <span class="text-700" data-image-attr="width"><?= $view->esc($file->width()) ?></span>x<span class="text-700" data-image-attr="height"><?= $view->esc($file->height()) ?></span>px
                    </span>
                    <span class="text-700" data-image-attr="size"><?= $view->esc($file->humanSize()) ?></span>
                </div>
            </div>
        </div>
    </div>
    <?= $form->close() ?>
</div>