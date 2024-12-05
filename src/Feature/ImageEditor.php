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
use Tobento\App\Migration\Boot\Migration;
use Tobento\App\User\Middleware\VerifyPermission;
use Tobento\Service\FileStorage\FileNotFoundException;
use Tobento\Service\FileStorage\FileWriteException;
use Tobento\Service\FileStorage\StorageInterface;
use Tobento\Service\FileStorage\StoragesInterface;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Routing\RouteGroupInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Uri\PreviousUriInterface;

/**
 * ImageEditor
 */
class ImageEditor extends Boot implements FeatureInterface
{
    public const INFO = [
        'boot' => [
            'image editor such as cropping, coloroize and more.',
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
     * Create a new ImageEditor instance.
     *
     * @param array<string, array<array-key, string>> $templates
     * @param array $supportedStorages
     * @param array $supportedMimeTypes
     * @param class-string $imageActions
     * @param null|string $userPermission
     * @param bool $localizeRoute
     * @param string $view
     */
    final public function __construct(
        protected array $templates = [
            'default' => [
                'crop', 'resize', 'fit', // Used for cropping. You may uncomment all if you want to disable cropping.
                'background', 'blur', 'brightness', 'contrast', 'colorize', 'flip', 'gamma', 'pixelate', 'rotate',
                'greyscale', 'sepia', // Filters
                'quality', 'format',
            ],
        ],
        protected array $supportedStorages = ['images'],
        protected array $supportedMimeTypes = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'],
        protected string $imageActions = \Tobento\App\Media\Image\ImageActions::class,
        protected null|string $userPermission = 'media.image.editor',
        protected bool $localizeRoute = false,
        protected string $view = 'media/image/editor',
    ) {}
    
    /**
     * Returns the feature name.
     *
     * @return string
     */
    public function featureName(): string
    {
        return 'image-editor';
    }
    
    /**
     * Returns the feature group.
     *
     * @return string
     */
    public function featureGroup(): string
    {
        return 'image-editor';
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
     * @param ResponserInterface $responser
     * @return ResponseInterface
     */
    public function show(
        string $template,
        string $storage,
        string $path,
        StoragesInterface $storages,
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
            $url = sprintf('data:%s;base64,%s', $file->mimeType(), base64_encode($file->content()));
            $file = $file->withUrl($url);
        }
        
        $imageActions = $this->configureImageActions()->withActions($this->templates[$template]);
        
        return $responser->render(
            view: $this->view,
            data: [
                'template' => $template,
                'storage' => $storage->name(),
                'file' => $file,
                'actions' => $imageActions,
                'supportedMimeTypes' => $this->supportedMimeTypes,
                'editorId' => '1',
                'crop' => [],
            ]
        )->withHeader('X-Exclude-Previous-Uri', '1');
    }
    
    /**
     * Update the image for the given storage and path.
     *
     * @param string $template
     * @param string $storage
     * @param string $path
     * @param StoragesInterface $storages
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
        
        // process image:
        try {
            $encoded = $this->configureImageProcessor($imageActions)
                // add actions from user input, they will validated on the image processor.
                ->withActions($requester->input()->get('actions', []))
                ->withQuality($requester->input()->get('quality', []))
                ->withConvert([$file->mimeType() ?: '' => $requester->input()->get('format')])
                ->processFromStream($file->stream());
        } catch (ImageProcessException $e) {
            throw new HttpException(
                statusCode: 422,
                message: 'Image processing failed.',
                code: (int)$e->getCode(),
                previous: $e,
            );
        }
        
        // store:
        try {
            // change path if another extension!
            $path = $file->path();
            
            if ($file->extension() !== $encoded->extension()) {
                $path = str_replace('.'.$file->extension(), '.'.$encoded->extension(), $file->path());
            }
            
            $storage->write(path: $path, content: $encoded->encoded());
        } catch (FileWriteException $e) {
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
        
        $eventDispatcher?->dispatch(new Event\ImageEdited(
            storageName: $storage->name(),
            file: $file,
            encoded: $encoded,
            messages: $messages,
        ));
        
        // create and return response:
        if ($requester->wantsJson()) {
            return $responser->json([
                'file' => [
                    'mimetype' => $encoded->mimeType(),
                    'width' => $encoded->width(),
                    'height' => $encoded->height(),
                    'size' => $encoded->size(),
                    'dataUrl' => $encoded->dataUrl(),
                ],
                'messages' => $messages->toArray(),
            ]);
        }
        
        $responser->messages()->push($messages);
        
        return $responser->redirect($previousUri);
    }
    
    /**
     * Preview the image for the given storage and path.
     *
     * @param string $template
     * @param string $storage
     * @param string $path
     * @param StoragesInterface $storages
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
                ->withConvert([$file->mimeType() ?: '' => $requester->input()->get('format')])
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
            quality: ['image/jpeg' => 90],
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
                ? '{?locale}/media/image-editor/{template}/{storage}/{path*}'
                : 'media/image-editor/{template}/{storage}/{path*}';
            
            $route->get($uri, [$this, 'show'])->name('media.image.editor');
            $route->post($uri, [$this, 'update'])->name('media.image.editor.update');
            
            $route->post('media/image-editor-preview/{template}/{storage}/{path*}', [$this, 'preview'])->name('media.image.editor.preview');
            
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