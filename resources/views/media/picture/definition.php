<?php
use Tobento\Service\Picture\SrcInterface;

$srcToTitle = function (string $name, SrcInterface $src) use ($file): string {
    $dimension = sprintf('%sx%spx', $src->width() ?: '?', $src->height() ?: '?');
    $mimeType = $src->mimeType() ?: $file->mimeType();
    return sprintf('%s | %s | %s', $name, $dimension, $mimeType);
};

$picture = $definition->toPicture();
?>
<div class="picture-editor-definition">
    <div class="title text-l"><?= $view->esc($definition->name()) ?></div>
    <div class="picture-editor-src">
        <?php $imgSrc = $picture->img()->src(); ?>
        <div class="title text-s mb-xs"><?= $view->esc($srcToTitle('Img Src', $imgSrc)) ?></div>
        <?= $view->render('media/picture/image', [
            'editorId' => $definition->name().'.img.src',
            'formName' => 'definitions.'.$definition->name().'.img.src',
            'crop' => [
                'target' => [$imgSrc->width(), $imgSrc->height()],
                'crop' => $imgSrc->options()['actions']['crop'] ?? [],
                'disabled' => true, // disables width and height
            ]
        ]) ?>
    </div>
    <?php if ($picture->img()->srcset()) { ?>
        <?php foreach($picture->img()->srcset() as $i => $src) { ?>
            <div class="picture-editor-src mt-m">
                <div class="title text-s mb-xs"><?= $view->esc($srcToTitle('Img Srcset', $src)) ?></div>
                <?= $view->render('media/picture/image', [
                    'editorId' => $definition->name().'.img.srcset.'.$i,
                    'formName' => 'definitions.'.$definition->name().'.img.srcset.'.$i,
                    'crop' => [
                        'target' => [$src->width(), $src->height()],
                        'crop' => $src->options()['actions']['crop'] ?? [],
                        'disabled' => true, // disables width and height
                    ]
                ]) ?>
            </div>
        <?php } ?>
    <?php } ?>
    <?php foreach($picture->sources() as $is => $source) { ?>
        <?php foreach($source->srcset() as $i => $src) { ?>
            <div class="picture-editor-src mt-m">
                <div class="title text-s mb-xs"><?= $view->esc($srcToTitle('Source '.$is+1, $src)) ?></div>
                <?= $view->render('media/picture/image', [
                    'editorId' => $definition->name().'.sources.'.$is.'.srcset.'.$i,
                    'formName' => 'definitions.'.$definition->name().'.sources.'.$is.'.srcset.'.$i,
                    'crop' => [
                        'target' => [$src->width(), $src->height()],
                        'crop' => $src->options()['actions']['crop'] ?? [],
                        'disabled' => true, // disables width and height
                    ]
                ]) ?>
            </div>
        <?php } ?>
    <?php } ?>
</div>