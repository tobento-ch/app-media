<!DOCTYPE html>
<html lang="<?= $view->esc($view->get('htmlLang', 'en')) ?>">
	
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $view->etrans('Picture Editor') ?></title>
        <meta name="description" content="<?= $view->etrans('Picture Editor') ?>">
        
        <?= $view->render('inc/head') ?>
        <?= $view->assets()->render() ?>
        
        <?php
        $view->asset('assets/media/image-editors.css');
        $view->asset('assets/media/image-editors.js')->attr('type', 'module');
        $view->asset('assets/media/picture-editors.js')->attr('type', 'module');
        ?>
    </head>
    
    <body<?= $view->tagAttributes('body')->add('class', 'page')->render() ?>>

        <?= $view->render('inc/header') ?>
        <?= $view->render('inc/nav') ?>

        <main class="page-main">

            <?= $view->render('inc.breadcrumb') ?>
            <?= $view->render('inc.messages') ?>

            <h1 class="title text-xl"><?= $view->etrans('Picture Editor') ?></h1>
            
            <div class="picture-editor">
                <?php if (empty($definitions)) { ?>
                    <p class="text-body my-xs"><?= $view->etrans('There are no images available to edit.') ?></p>
                <?php } else { ?>
                    <?php $form = $view->form(); ?>
                    <?= $form->form([
                        'action' => $view->routeUrl('media.picture.editor.update', [
                            'template' => $template, 'storage' => $storage, 'path' => $file->path()]),
                        'data-picture-editor' => ['id' => $editorId],
                    ]) ?>

                    <div class="buttons spaced">
                        <?= $form->button(
                            text: $view->trans('Save'),
                            attributes: ['class' => 'button primary text-xs', 'data-picture-editor-action' => 'save']
                        ) ?>
                    </div>

                    <?= $form->close() ?>

                    <div data-picture-editor-images="<?= $view->esc($editorId) ?>">
                        <?php foreach($definitions as $definition) { ?>
                            <?= $view->render('media/picture/definition', ['definition' => $definition]) ?>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        </main>

        <?= $view->render('inc/footer') ?>
    </body>
</html>