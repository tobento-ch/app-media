<!DOCTYPE html>
<html lang="<?= $view->esc($view->get('htmlLang', 'en')) ?>">
	
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= $view->etrans('Image Editor') ?></title>
        <meta name="description" content="<?= $view->etrans('Image Editor') ?>">
        
        <?= $view->render('inc/head') ?>
        <?= $view->assets()->render() ?>
        
        <?php
        $view->asset('assets/media/image-editors.css');
        $view->asset('assets/media/image-editors.js')->attr('type', 'module');
        ?>
    </head>
    
    <body<?= $view->tagAttributes('body')->add('class', 'page')->render() ?>>

        <?= $view->render('inc/header') ?>
        <?= $view->render('inc/nav') ?>

        <main class="page-main">

            <?= $view->render('inc.breadcrumb') ?>
            <?= $view->render('inc.messages') ?>

            <h1 class="title text-xl mb-s"><?= $view->etrans('Image Editor') ?></h1>
            
            <?= $view->render('media/image/image', ['editorId' => $editorId]) ?>
        </main>

        <?= $view->render('inc/footer') ?>
    </body>
</html>