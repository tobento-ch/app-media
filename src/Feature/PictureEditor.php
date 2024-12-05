<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);
 
namespace Tobento\App\Media\Feature;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Tobento\App\AppInterface;
use Tobento\App\Boot;
use Tobento\App\Http\Exception\HttpException;
use Tobento\App\Http\Exception\NotFoundException;
use Tobento\App\Language\RouteLocalizerInterface;
use Tobento\App\Media\FeatureInterface;
use Tobento\App\Media\FeaturesInterface;
use Tobento\App\Media\Event;
use Tobento\App\Media\Exception\ImageProcessException;
use Tobento\App\Media\Image\ImageActionsInterface;
use Tobento\App\Media\Image\ImageProcessor;
use Tobento\App\Media\Image\ImageProcessorInterface;
use Tobento\App\Media\Image\MessagesFactory as ImagerMessagesFactory;
use Tobento\App\Media\Picture\PictureGeneratorInterface;
use Tobento\App\Migration\Boot\Migration;
use Tobento\App\User\Middleware\VerifyPermission;
use Tobento\Service\Collection\Collection;
use Tobento\Service\Imager\InterventionImage\ImagerFactory;
use Tobento\Service\FileStorage\FileNotFoundException;
use Tobento\Service\FileStorage\StorageInterface;
use Tobento\Service\FileStorage\StoragesInterface;
use Tobento\Service\Picture\Definition\PictureDefinition;
use Tobento\Service\Picture\DefinitionInterface;
use Tobento\Service\Picture\DefinitionsInterface;
use Tobento\Service\Picture\PictureCreator;
use Tobento\Service\Picture\Sources;
use Tobento\Service\Picture\Srcset;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Routing\RouteGroupInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Uri\PreviousUriInterface;

/**
 * PictureEditor
 */
class PictureEditor extends Boot implements FeatureInterface
{
    public const INFO = [
        'boot' => [
            'picture editor such as cropping, coloroize and more.',
        ],
    ];

    public const BOOT = [
        \Tobento\App\Message\Boot\Message::class,
        \Tobento\App\Media\Boot\Media::class,
        
        // HTTP:
        \Tobento\App\Http\Boot\ErrorHandler::class,
        \Tobento\App\Http\Boot\Routing::class,
        \Tobento\App\Http\Boot\RequesterResponser::class,
        
        // I18n:
        \Tobento\App\Language\Boot\Language::class,
        \Tobento\App\Translation\Boot\Translation::class,
        
        // USER:
        \Tobento\App\User\Boot\HttpUserErrorHandler::class,
        \Tobento\App\User\Boot\User::class,
        
        // MISC:
        \Tobento\App\FileStorage\Boot\FileStorage::class,
        \Tobento\App\Event\Boot\Event::class,
        
        // VIEW:
        \Tobento\App\View\Boot\View::class,
        \Tobento\App\View\Boot\Form::class,
        \Tobento\App\View\Boot\Messages::class,
    ];
    
    /**
     * Create a new PictureEditor instance.
     *
     * @param array<string, array<array-key, string>> $templates
     * @param array $supportedStorages
     * @param array $supportedMimeTypes
     * @param class-string $imageActions
     * @param null|string $userPermission
     * @param bool $localizeRoute
     * @param string $view
     * @param bool $queuePictureGeneration
     */
    final public function __construct(
        protected array $templates = [
            'default' => [
                'crop', 'resize', 'fit', // Used for cropping. You may uncomment all if you want to disable cropping.
                'background', 'blur', 'brightness', 'contrast', 'colorize', 'flip', 'gamma', 'pixelate', 'rotate',
                'greyscale', 'sepia', // Filters
                'quality',
            ],
        ],
        protected array $supportedStorages = ['images'],
        protected array $supportedMimeTypes = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'],
        protected string $imageActions = \Tobento\App\Media\Image\ImageActions::class,
        protected null|string $userPermission = 'media.picture.editor',
        protected bool $localizeRoute = false,
        protected string $view = 'media/picture/editor',
        protected bool $queuePictureGeneration = true,
    ) {}
    
    /**
     * Returns the feature name.
     *
     * @return string
     */
    public function featureName(): string
    {
        return 'picture-editor';
    }
    
    /**
     * Returns the feature group.
     *
     * @return string
     */
    public function featureGroup(): string
    {
        return 'picture-editor';
    }
    
    /**
     * Boot application services.
     *
     * @param Migration $migration
     * @param AppInterface $app
     * @param RouterInterface $router
     * @return void
     */
    public function boot(
        Migration $migration,
        AppInterface $app,
        RouterInterface $router
    ): void {
        $this->app = $app;
        
        // install migration:
        $migration->install(\Tobento\App\Media\Migration\MediaExtended::class);
        
        // Add feature:
        $app->get(FeaturesInterface::class)->add($this);
        
        // Routes:
        $this->configureRoutes($router, $app);
    }
    
    /**
     * Display the crop view for the given storage and path.
     *
     * @param string $template
     * @param string $storage
     * @param string $path
     * @param StoragesInterface $storages
     * @param DefinitionsInterface $definitions
     * @param PictureGeneratorInterface $pictureGenerator
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @return ResponseInterface
     */
    public function show(
        string $template,
        string $storage,
        string $path,
        StoragesInterface $storages,
        DefinitionsInterface $definitions,
        PictureGeneratorInterface $pictureGenerator,
        RequesterInterface $requester,
        ResponserInterface $responser,
    ): ResponseInterface {
        if (!array_key_exists($template, $this->templates)) {
            throw new NotFoundException();
        }
        
        if (! $storages->has($storage)) {
            throw new NotFoundException();
        }
        
        $storage = $storages->get($storage);
        
        if (! $this->supportsStorage($storage->name())) {
            throw new NotFoundException();
        }
        
        if (! $storage->exists(path: $path)) {
            throw new NotFoundException();
        }
        
        try {
            $file = $storage->with('stream', 'mimeType', 'size', 'width')->file(path: $path);
        } catch (FileNotFoundException $e) {
            throw new NotFoundException();
        }
        
        if (! $file->isHtmlImage()) {
            throw new NotFoundException();
        }
        
        if (empty($file->url())) {
            $url = sprintf('data:%s;base64,%s', (string)$file->mimeType(), base64_encode((string)$file->content()));
            $file = $file->withUrl($url);
        }
        
        $definitionNames = $requester->input()->get('definitions', []);
        $defs = [];
        
        foreach($definitionNames as $definitionName) {
            if ($definitions->has($definitionName)) {
                // If a generated picture exists, we create the definition from that to get the crop data e.g.
                $picture = $pictureGenerator->pictureRepository()->findOne($path, $definitionName);
                
                if (!is_null($picture)) {
                    $definition = new PictureDefinition(name: $definitionName, picture: $picture);
                } else {
                    $definition = $definitions->get($definitionName);
                }
                
                $defs[] = $definition;
            }
        }
        
        $imageActions = $this->configureImageActions()->withActions($this->templates[$template]);
        
        return $responser->render(
            view: $this->view,
            data: [
                'template' => $template,
                'storage' => $storage->name(),
                'file' => $file,
                'definitions' => $defs,
                'actions' => $imageActions,
                'supportedMimeTypes' => $this->supportedMimeTypes,
                'editorId' => '1',
                'crop' => [],
            ]
        )->withHeader('X-Exclude-Previous-Uri', '1');
    }
    
    /**
     * Update the picture for the given storage and path.
     *
     * @param string $template
     * @param string $storage
     * @param string $path
     * @param StoragesInterface $storages
     * @param DefinitionsInterface $definitions
     * @param RouterInterface $router
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @param PreviousUriInterface $previousUri
     * @param null|EventDispatcherInterface $eventDispatcher
     * @return ResponseInterface
     */
    public function update(
        string $template,
        string $storage,
        string $path,
        StoragesInterface $storages,
        DefinitionsInterface $definitions,
        RouterInterface $router,
        RequesterInterface $requester,
        ResponserInterface $responser,
        PreviousUriInterface $previousUri,
        null|EventDispatcherInterface $eventDispatcher
    ): ResponseInterface {
        if (!array_key_exists($template, $this->templates)) {
            throw new NotFoundException();
        }
        
        if (! $storages->has($storage)) {
            throw new NotFoundException();
        }
        
        $storage = $storages->get($storage);
        
        if (! $this->supportsStorage($storage->name())) {
            throw new NotFoundException();
        }
        
        if (! $storage->exists(path: $path)) {
            throw new NotFoundException();
        }
        
        try {
            $file = $storage->with('stream', 'mimeType')->file(path: $path);
        } catch (FileNotFoundException $e) {
            throw new NotFoundException();
        }
        
        if (! $file->isHtmlImage()) {
            throw new NotFoundException();
        }
        
        $imageActions = $this->configureImageActions()->withActions($this->templates[$template]);
        $pictureGenerator = $this->configurePictureGenerator($imageActions);
        $definitionNames = [];
        
        foreach($requester->input()->get('definitions', []) as $definitionName => $inputDef) {
            if (! $definitions->has($definitionName)) {
                continue;
            }
            
            $definitionNames[] = $definitionName;
            
            $this->regeneratePicture(
                imageActions: $imageActions,
                pictureGenerator: $pictureGenerator,
                definition: $definitions->get($definitionName),
                input: $inputDef,
                path: $path,
                storageName: $storage->name(),
            );
        }
        
        $eventDispatcher?->dispatch(new Event\PictureEdited(
            storageName: $storage->name(),
            file: $file,
            definitionNames: $definitionNames,
        ));
        
        // create and return response:
        if ($requester->wantsJson()) {
            return $responser->json([
                'template' => $template,
                'storage' => $storage->name(),
                'path' => $path,
                'definitionNames' => $definitionNames,
            ]);
        }

        return $responser->redirect($previousUri);
    }
    
    /**
     * Preview the image for the given storage and path.
     *
     * @param string $template
     * @param string $storage
     * @param string $path
     * @param StoragesInterface $storages
     * @param RouterInterface $router
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @return ResponseInterface
     */
    public function preview(
        string $template,
        string $storage,
        string $path,
        StoragesInterface $storages,
        RequesterInterface $requester,
        ResponserInterface $responser,
    ): ResponseInterface {
        if (!array_key_exists($template, $this->templates)) {
            throw new NotFoundException();
        }
        
        if (! $storages->has($storage)) {
            throw new NotFoundException();
        }
        
        $storage = $storages->get($storage);
        
        if (! $this->supportsStorage($storage->name())) {
            throw new NotFoundException();
        }
        
        if (! $storage->exists(path: $path)) {
            throw new NotFoundException();
        }
        
        try {
            $file = $storage->with('stream', 'mimeType', 'size', 'width')->file(path: $path);
        } catch (FileNotFoundException $e) {
            throw new NotFoundException();
        }
        
        if (! $file->isHtmlImage()) {
            throw new NotFoundException();
        }
        
        $imageActions = $this->configureImageActions()->withActions($this->templates[$template]);
        
        // process image:
        try {
            $encoded = $this->configureImageProcessor($imageActions)
                // add actions from user input, they will validated on the image processor.
                ->withActions($requester->input()->get('actions', []))
                ->withQuality($requester->input()->get('quality', []))
                ->processFromStream($file->stream());
        } catch (ImageProcessException $e) {
            throw new HttpException(
                statusCode: 422,
                message: 'Image processing failed.',
                code: (int)$e->getCode(),
                previous: $e,
            );
        }
        
        // Create messages from image actions:
        $messages = (new ImagerMessagesFactory())->createMessagesFromActions(
            actions: $encoded->actions()->withoutProcessedBy()
        );

        $responser->messages()->push($messages);
        
        // create and return response:
        return $responser->json([
            'file' => [
                'mimetype' => $encoded->mimeType(),
                'width' => $encoded->width(),
                'height' => $encoded->height(),
                'size' => $encoded->humanSize(),
                'dataUrl' => $encoded->dataUrl(),
            ],
            'messages' => $messages->toArray(),
        ]);
    }
    
    /**
     * Regenerate picture.
     *
     * @param ImageActionsInterface $imageActions
     * @param PictureGeneratorInterface $pictureGenerator
     * @param DefinitionInterface $definition
     * @param mixed $input
     * @param string $path
     * @param string $storageName
     * @return void
     */
    protected function regeneratePicture(
        ImageActionsInterface $imageActions,
        PictureGeneratorInterface $pictureGenerator,
        DefinitionInterface $definition,
        mixed $input,
        string $path,
        string $storageName,
    ): void {
        if (!is_array($input)) {
            return;
        }
        
        $input = new Collection($input);
        $picture = $definition->toPicture();
        
        // img src:
        $src = $picture->img()->src();
        $src = $src->withOptions([
            'actions' => $imageActions->verifyInputActions($input->get('img.src.actions'))
        ]);
        $picture = $picture->withImg($picture->img()->withSrc($src));
        
        // img srcset:
        if ($picture->img()->srcset()) {
            $srces = [];
            
            foreach($picture->img()->srcset() as $i => $src) {
                $srces[] = $src->withOptions([
                    'actions' => $imageActions->verifyInputActions($input->get('img.srcset.'.$i.'.actions'))
                ]);
            }
            
            $img = $picture->img()->withSrcset(new Srcset(...$srces));
            $picture = $picture->withImg($img);
        }
        
        // sources:
        $sources = [];
        
        foreach($picture->sources() as $is => $source) {
            $srces = [];
                        
            foreach($source->srcset() as $i => $src) {
                $srces[] = $src->withOptions([
                    'actions' => $imageActions->verifyInputActions($input->get('sources.'.$is.'.srcset.'.$i.'.actions'))
                ]);
            }
            
            $sources[] = $source->withSrcset(new Srcset(...$srces));
        }
        
        $picture = $picture->withSources(new Sources(...$sources));
        
        // create definition:
        $definition = new PictureDefinition(name: $definition->name(), picture: $picture);
        
        // regenerate picture:
        $pictureGenerator->regenerate(
            path: $path,
            resource: $storageName,
            definition: $definition,
            queue: $this->queuePictureGeneration,
        );
    }
    
    /**
     * Returns the configured image processor
     *
     * @param ImageActionsInterface $imageActions
     * @return ImageProcessorInterface
     */
    protected function configureImageProcessor(ImageActionsInterface $imageActions): ImageProcessorInterface
    {
        return new ImageProcessor(
            actions: [
                'orientate' => [],
                'resize' => ['width' => 300],
            ],
            allowedActions: $imageActions->getAllowedActions(),
            supportedMimeTypes: $this->supportedMimeTypes,
            actionFactory: $imageActions,
        );
    }
    
    /**
     * Configure image actions.
     *
     * @return ImageActionsInterface
     */
    protected function configureImageActions(): ImageActionsInterface
    {
        return $this->app->make($this->imageActions);
    }
    
    /**
     * Returns the configured picture generator.
     *
     * @param ImageActionsInterface $imageActions
     * @return PictureGeneratorInterface
     */
    protected function configurePictureGenerator(ImageActionsInterface $imageActions): PictureGeneratorInterface
    {
        return $this->app->get(PictureGeneratorInterface::class);
    }
    
    /**
     * Configure middlewares for the route(s).
     *
     * @param AppInterface $app
     * @return array
     */
    protected function configureMiddlewares(AppInterface $app): array
    {
        if (is_null($this->userPermission)) {
            return [];
        }
        
        return [
            [
                VerifyPermission::class,
                'permission' => $this->userPermission,
            ],
        ];
    }
    
    /**
     * Configure routes
     *
     * @param RouterInterface $router
     * @param AppInterface $app
     * @return void
     */
    protected function configureRoutes(RouterInterface $router, AppInterface $app): void
    {
        $route = $router->group('', function(RouteGroupInterface $route) {
            $uri = $this->localizeRoute
                ? '{?locale}/media/picture-editor/{template}/{storage}/{path*}'
                : 'media/picture-editor/{template}/{storage}/{path*}';
            
            $route->get($uri, [$this, 'show'])->name('media.picture.editor');
            $route->post($uri, [$this, 'update'])->name('media.picture.editor.update');
            
            $route->post('media/picture-editor-preview/{template}/{storage}/{path*}', [$this, 'preview'])->name('media.picture.editor.preview');
            
        })->middleware(...$this->configureMiddlewares($app));
        
        if ($this->localizeRoute) {
            $app->get(RouteLocalizerInterface::class)->localizeRoute($route);
        }
    }
    
    /**
     * Returns true if the given storage is supported, otherwise false.
     *
     * @param string $storage
     * @return bool
     */
    protected function supportsStorage(string $storage): bool
    {
        return in_array($storage, $this->supportedStorages);
    }
}