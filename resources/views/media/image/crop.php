<?php if ($view->once(__FILE__)) { ?>
    <?php
    $view->asset('assets/js-cropper/cropper.css');
    $view->asset('assets/media/image-crop.js')->attr('type', 'module');
    
    $translations = [];
    $translator = $view->app()->get(\Tobento\Service\Translation\TranslatorInterface::class);
    
    if ($translator instanceof \Tobento\Service\Translation\ResourcesAware) {
        $translations = $translator->getResource(name: 'cropper', locale: $locale);
    }
    ?>
    <script id="cropper-translation" type="application/ld+json"><?= json_encode($translations) ?></script>
    <script type="module" nonce="<?= $view->esc($view->get('cspNonce', '')) ?>">
        import cropper from "<?= $view->assetPath('assets/js-cropper/cropper.js') ?>";
        const translator = cropper.translator;
        // specify the current locale:
        translator.locale('*');
        const trans = JSON.parse(document.querySelector('#cropper-translation').innerHTML);
        // add translations:
        translator.add('*', trans);
    </script>
<?php } ?>
<?php
/*$crop = [
    'target' => [700],
    'crop' => ['width' => 1400, 'height' => 700, 'x' => 0, 'y' => 0, 'scale' => 1],
    'disabled' => false,
];*/
?>
<div class="image-action" data-image-action="crop"<?= $view->tagAttributes('image.crop')->set('data-image-crop', $crop) ?>>
    <div class="image-action-head link" data-image-action-trigger="toggle-open"><?= $view->etrans('Crop') ?></div>
    <div class="image-action-body">
        <div class="mt-s cols nowrap middle">
            <div><?= $form->input(
                name: 'target_width',
                type: 'number',
                attributes: ['class' => 'small max-width-xs', 'data-image-update' => '0', 'id' => '']
            ) ?></div>
            <div class="px-xs">x</div>
            <div><?= $form->input(
                name: 'target_height',
                type: 'number',
                attributes: ['class' => 'small max-width-xs', 'data-image-update' => '0', 'id' => '']
            ) ?></div>
            <div class="pl-xs">px</div>
        </div>
        <div class="mt-s">
            <?= $form->input(
                name: 'actions.crop.background',
                type: 'color',
                attributes: [
                    'id' => $form->nameToId($editorId.'actions.crop.background'),
                    'class' => 'small fit',
                    'disabled',
                ]
            ) ?>
            <div class="mt-xs">            
                <?= $form->input(
                    name: 'apply.crop.background',
                    type: 'checkbox',
                    value: '0',
                    attributes: [
                        'data-image-action-trigger' => 'toggle-disabled-checkbox',
                        'data-disable-checkbox' => $form->nameToId($editorId.'actions.crop.background'),
                        'id' => '',
                    ]
                ) ?>
                <span><?= $view->etrans('Apply background color to image.') ?></span>
            </div>
        </div>
    </div>
</div>