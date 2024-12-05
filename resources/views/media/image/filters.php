<div class="image-action" data-image-action="filters">
    <div class="image-action-head link" data-image-action-trigger="toggle-open"><?= $view->etrans('Filters') ?></div>
    <div class="image-action-body mt-s ml-s">
        <?php
        foreach($actions->filters(true)->all() as $action) {
            echo $view->render('media/image/'.$action, ['form' => $form, 'editorId' => $editorId]);
        }
        ?>
    </div>
</div>