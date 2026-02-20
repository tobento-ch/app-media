# App Media

The App Media package provides a set of features and services for working with media files, including:

* [responsive images](#picture-feature) using the HTML `<picture>` element
* [file display](#file-display-feature) and [file download](#file-download-feature)
* [image editing](#image-editor-feature) for cropping, resizing, and transforming images
* [upload validation](#upload-validators) for validating uploaded files

and more ...

## Table of Contents

- [Getting Started](#getting-started)
    - [Requirements](#requirements)
- [Documentation](#documentation)
    - [App](#app)
    - [Media Boot](#media-boot)
        - [Media Config](#media-config)
    - [Features](#features)
        - [File Feature](#file-feature)
        - [File Display Feature](#file-display-feature)
        - [File Display Signed Feature](#file-display-signed-feature)
        - [File Download Feature](#file-download-feature)
        - [File Download Signed Feature](#file-download-signed-feature)
        - [Icons Feature](#icons-feature)
        - [Image Editor Feature](#image-editor-feature)
            - [Edit Image](#edit-image)
        - [Picture Feature](#picture-feature)
            - [Display Picture](#display-picture)
            - [Picture Definitions](#picture-definitions)
            - [Clearing Generated Picture](#clearing-generated-picture)
        - [Picture Editor Feature](#picture-editor-feature)
            - [Edit Picture](#edit-picture)
    - [Services](#services)
        - [File Storage Writer](#file-storage-writer)
        - [Copy Mode (CopyFileWrapper)](#copy-mode-copyfilewrapper)
        - [Upload Validators](#upload-validators)
        - [Uploaded File Factory](#uploaded-file-factory)
        - [Image Processor](#image-processor)
        - [Picture Generator](#picture-generator)
    - [Learn More](#learn-more)
        - [Display And Download Files Using Apps](#display-and-download-files-using-apps)
- [Credits](#credits)
___

# Getting Started

Add the latest version of the app media project running this command.

```
composer require tobento/app-media
```

## Requirements

- PHP 8.4 or greater

# Documentation

## App

Check out the [**App Skeleton**](https://github.com/tobento-ch/app-skeleton) if you are using the skeleton.

You may also check out the [**App**](https://github.com/tobento-ch/app) to learn more about the app in general.

## Media Boot

The media boot does the following:

* installs and loads the media config
* implements media interfaces
* boots features from media config

```php
use Tobento\App\AppFactory;
use Tobento\App\Media\FeaturesInterface;

// Create the app
$app = new AppFactory()->createApp();

// Add directories:
$app->dirs()
    ->dir(realpath(__DIR__.'/../'), 'root')
    ->dir(realpath(__DIR__.'/../app/'), 'app')
    ->dir($app->dir('app').'config', 'config', group: 'config')
    ->dir($app->dir('root').'public', 'public')
    ->dir($app->dir('root').'vendor', 'vendor');

// Adding boots
$app->boot(\Tobento\App\Media\Boot\Media::class);

// Implemented interfaces:
$features = $app->get(FeaturesInterface::class);

// Run the app
$app->run();
```

### Media Config

The configuration for the media is located in the ```app/config/media.php``` file at the default App Skeleton config location.

## Features

### File Feature

This feature provides convenient access to files and file URLs from any supported
[file storage](https://github.com/tobento-ch/app-file-storage).  
Its primary purpose is to retrieve file URLs or file objects for internal use within your application.

This feature works with both public and private storages.  
Private storages are not intended to generate public URLs.  
When using a private storage, the `File` feature is primarily meant for retrieving
file objects for further processing within your application, not for producing URLs.

**Requirements**

This feature does not have any requirements.

**Install**

In the [media config file](#media-config) you can configure this feature:

```php
'features' => [
    new Feature\File(
        // define the supported storages which have public urls:
        supportedStorages: ['images'],
        
        // you may throw exeptions if storage or file does not exist for debugging e.g.:
        throw: true, // false default
    ),
],
```

**Retrieve Files**

To retrieve files, use the ```storage``` method from the ```File::class``` returning a **read-only** [file storage](https://github.com/tobento-ch/service-file-storage#storage-interface). The file storage does not throw any exceptions when a file does not exists instead it returns an "empty" file.

```php
use Tobento\App\Media\Feature\File;
use Tobento\Service\FileStorage\FileInterface;
use Tobento\Service\FileStorage\StorageInterface;

$fileUrl = $app->get(File::class)
    ->storage(storage: 'images')
    ->file(path: 'path/to/file.jpg')
    ->url();

$storage = $app->get(File::class)->storage(storage: 'images');
// StorageInterface

$file = $storage->file(path: 'path/to/file.jpg');
// FileInterface
```

By default, the file storage will retrieve only file urls. If you wish to retrieve other [file attributes](https://github.com/tobento-ch/service-file-storage#available-file-attributes) use the file storage ```with``` method:

```php
use Tobento\App\Media\Feature\File;

$file = $app->get(File::class)
    ->storage(storage: 'images')
    ->with('url', 'width', 'height')
    ->file(path: 'path/to/file.jpg');
```

If you only want to retrieve a file url, you may prefer to use the ```url``` method instead:

```php
use Tobento\App\Media\Feature\File;

$file = $app->get(File::class)->url(storage: 'images', path: 'path/to/file.jpg');
```

**Retrieve Files Within Views**

Make sure you have booted the [View Boot](https://github.com/tobento-ch/app-view#view-boot).

Use the view ```fileStorage``` method to retrieve file(s) within your views:

```php
$fileUrl = $view->fileStorage(storage: 'images')->file(path: 'path/to/file.jpg')->url();
```

If you only want to retrieve a file url, you may prefer to use the ```fileUrl``` method instead:

```php
$fileUrl = $view->fileUrl(storage: 'images', path: 'path/to/file.jpg');
```

**Using File Display Feature For Urls**

If a storage does not support public urls you may use the [File Display Feature](#file-display-feature) and in the [File Storage Config](https://github.com/tobento-ch/app-file-storage#file-storage-config) set the ```public_url``` parameter as the route uri configured in the [File Display Feature](#file-display-feature):

```php
'storages' => [

    'files' => [
        'factory' => \Tobento\App\FileStorage\FilesystemStorageFactory::class,
        'config' => [
            // The location storing the files:
            'location' => directory('app').'storage/files/',
            
            // Point to the file display feature route uri:
            'public_url' => 'https://example.com/media/file/',
        ],
    ],
],
```

### File Display Feature

This feature may be used to display a file from a supported [file storage](https://github.com/tobento-ch/app-file-storage), such as an image or PDF, directly in the user's browser.

**Requirements**

This feature does not have any requirements.

**Install**

In the [media config file](#media-config) you can configure this feature:

```php
'features' => [
    new Feature\FileDisplay(
        // define the supported storages (public-only storages are allowed):
        supportedStorages: ['images'],
        
        // you may change the route uri:
        routeUri: 'media/file/{storage}/{path*}', // default
        
        // you may define a route domain:
        routeDomain: 'media.example.com', // null is default
    ),
],
```

> **Important**
>
> This feature only works with storages of type [**public**](https://github.com/tobento-ch/service-file-storage#public-storage).  
> Private storages will always result in a **404 Not Found** response.

**Display File**

Once installed, files will be publicly accessible by the defined route uri:

```
https://example.com/media/file/images/path/to/file.jpg
```

To generate a file url, use the router ```url``` method:

```php
use Tobento\Service\Routing\RouterInterface;

$router = $app->get(RouterInterface::class);

$router->url('media.file.display', ['storage' => 'images', 'path' => 'path/to/file.jpg']);
```

You may check out the [Display And Download Files Using Apps](#display-and-download-files-using-apps) if you want to serve files from a customized app.

### File Display Signed Feature

This feature may be used to securely display a file from a supported [file storage](https://github.com/tobento-ch/app-file-storage) using **signed URLs**.  
A signed URL ensures that the file can only be accessed when the URL contains a valid cryptographic signature, and optionally an expiration timestamp.

This is ideal for displaying private or protected files such as PDFs, images, or documents that should not be publicly accessible.

**Requirements**

This feature does not have any requirements.

**Install**

In the [media config file](#media-config) you can configure this feature:

```php
'features' => [
    new Feature\FileDisplaySigned(
        // define the supported storages (private-only storages are allowed):
        supportedStorages: ['uploads-private'],
        
        // you may change the route uri:
        routeUri: 'media/s/file/{storage}/{path*}', // default
        
        // you may define a route domain:
        routeDomain: 'media.example.com', // null is default
    ),
],
```

> **Important**
>
> This feature only works with storages of type [**private**](https://github.com/tobento-ch/service-file-storage#private-storage).  
> Public storages will always result in a **404 Not Found** response.

**Display File**

Once installed, files can only be accessed using a **signed URL**.
Unsigned URLs will always result in a **403 Forbidden** response.

A typical signed URL looks like:

```
https://example.com/media/s/file/uploads-private/path/to/file.pdf/{expires}/{signature}
```

To generate a signed file URL, use the router `url` method and call `sign`:

```php
use Tobento\Service\Routing\RouterInterface;

$router = $app->get(RouterInterface::class);

$url = $router->url('media.file.display.signed', [
    'storage' => 'private',
    'path' => 'path/to/file.pdf',
])->sign();
```
For additional information on signing options and behavior, visit the [Signed URL Generation](https://github.com/tobento-ch/service-routing#signed-url-generation) section.

You may check out the [Display And Download Files Using Apps](#display-and-download-files-using-apps) if you want to serve files from a customized app.

### File Download Feature

This feature may be used to force downloading a file from a supported [file storage](https://github.com/tobento-ch/app-file-storage) in the user's browser.
 
**Requirements**

This feature does not have any requirements.

**Install**

In the [media config file](#media-config) you can configure this feature:

```php
'features' => [
    new Feature\FileDownload(
        // define the supported storages (public-only storages are allowed):
        supportedStorages: ['images'],
        
        // you may change the route uri:
        routeUri: 'media/download/{storage}/{path*}', // default
        
        // you may define a route domain:
        routeDomain: 'media.example.com', // null is default
    ),
],
```

> **Important**
>
> This feature only works with storages of type [**public**](https://github.com/tobento-ch/service-file-storage#public-storage).  
> Private storages will always result in a **404 Not Found** response.

**Download File**

Once installed, files will be publicly accessible by the defined route uri:

```
https://example.com/media/download/images/path/to/file.jpg
```

To generate a file url, use the router ```url``` method:

```php
use Tobento\Service\Routing\RouterInterface;

$router = $app->get(RouterInterface::class);

$router->url('media.file.download', ['storage' => 'images', 'path' => 'path/to/file.jpg']);
```

You may check out the [Display And Download Files Using Apps](#display-and-download-files-using-apps) if you want to serve files from a customized app.

### File Download Signed Feature

This feature may be used to securely download a file from a supported [file storage](https://github.com/tobento-ch/app-file-storage) using **signed URLs**.  
A signed URL ensures that the file can only be accessed when the URL contains a valid cryptographic signature, and optionally an expiration timestamp.

This is ideal for downloading private or protected files such as PDFs, images, or documents that should not be publicly accessible.

**Requirements**

This feature does not have any requirements.

**Install**

In the [media config file](#media-config) you can configure this feature:

```php
'features' => [
    new Feature\FileDownloadSigned(
        // define the supported storages (private-only storages are allowed):
        supportedStorages: ['uploads-private'],
        
        // you may change the route uri:
        routeUri: 'media/s/download/{storage}/{path*}', // default
        
        // you may define a route domain:
        routeDomain: 'media.example.com', // null is default
    ),
],
```

> **Important**
>
> This feature only works with storages of type [**private**](https://github.com/tobento-ch/service-file-storage#private-storage).  
> Public storages will always result in a **404 Not Found** response.

**Download File**

Once installed, files can only be accessed using a **signed URL**.
Unsigned URLs will always result in a **403 Forbidden** response.

A typical signed URL looks like:

```
https://example.com/media/s/download/uploads-private/path/to/file.pdf/{expires}/{signature}
```

To generate a signed file URL, use the router `url` method and call `sign`:

```php
use Tobento\Service\Routing\RouterInterface;

$router = $app->get(RouterInterface::class);

$url = $router->url('media.file.download.signed', [
    'storage' => 'private',
    'path' => 'path/to/file.pdf',
])->sign();
```
For additional information on signing options and behavior, visit the [Signed URL Generation](https://github.com/tobento-ch/service-routing#signed-url-generation) section.

You may check out the [Display And Download Files Using Apps](#display-and-download-files-using-apps) if you want to serve files from a customized app.

### Icons Feature

This feature may be used to render SVG icons using the [Icon Service](https://github.com/tobento-ch/service-icon).

**Requirements**

This feature does not have any requirements.

**Install**

In the [media config file](#media-config) you can configure this feature:

```php
'features' => [
    new Feature\Icons(
        // Define the directory where to store cached icons:
        cacheDir: directory('app').'storage/icons/',
        
        // You may enable to throw an exception if an icon is not found.
        // This is useful during development, but in production, you may want to log a message instead (see below).
        throwIconNotFoundException: true, // default is false
    ),
],
```

**Render Icons Within Views**

To render icons within your views use the view ```icon``` method returning an icon implementing the [Icon Interface](https://github.com/tobento-ch/service-icon#icon-interface):

```php
<?= $view->icon('edit')->size('m')->label(text: 'Edit') ?>
```

**Access Icons**

In addition, to render icons you may just access them within any service:

```php
use Tobento\Service\Icon\IconInterface;
use Tobento\Service\Icon\IconsInterface;

final class SomeService
{
    public function __construct(
        private IconsInterface $icons,
    ) {
        $icon = $icons->get('edit');
        // IconInterface
    }
}
```

**Storing SVG icons**

Store your SVG icon files in the ```app/views/icons/``` directory:

```
app/
    views/
        icons/
            edit.svg
            ...
```

**Clear cached icons**

To clear cached icons you may delete the defined ```$cacheDir``` folder manually or run the following command:

```
php ap icons:clear
```

During development, if you store more SVG icons, you will need to clear the cache to see the changes!

**Log Not Found Icons**

Make sure you have booted the [App Logging Boot](https://github.com/tobento-ch/app-logging#logging-boot).

In the ```app/config/logging.php``` file you may define the logger to be used, otherwise the default logger will be used:

```php
'aliases' => [
    \Tobento\App\Media\Icon\FallbackIcons::class => 'daily',
    
    // or do not log at all:
    \Tobento\App\Media\Icon\FallbackIcons::class => 'null',
],
```

### Image Editor Feature

This feature offers a full image-editing interface, making it possible to crop, resize, transform, and adjust images within your application.

> **Technical Note**  
> The Image Editor uses the `ImageProcessor` from  
> https://github.com/tobento-ch/service-upload/#image-processor  
>
> app-media wraps this processor in  
> `Tobento\App\Media\Upload\ImageProcessor`  
> to integrate logging and application-level configuration.

> **Important**
>
> The Image Editor loads images in the browser and writes changes back to storage.  
> For security reasons, access to the editor is protected by a permission check.  
> By default, the required permission is **`media.image.editor`**.  
> You may override this permission, but disabling permission checks is not recommended.

**Requirements**

This features requires:

```
composer require tobento/app-language
composer require tobento/app-translation
composer require tobento/app-user
```

**Install**

In the [media config file](#media-config) you can configure this feature:

```php
'features' => [
    new Feature\ImageEditor(
        // define different image editors templates:
        templates: [
            'default' => [
                'crop', 'resize', 'fit', // Used for cropping. You may uncomment all if you want to disable cropping.
                'background', 'blur', 'brightness', 'contrast', 'colorize', 'flip', 'gamma', 'pixelate', 'rotate', 'sharpen',
                'greyscale', 'sepia', // Filters
                'quality', 'format',
            ],
        ],

        // define the supported storages:
        supportedStorages: ['uploads'],
        
        // define the supported mime types:
        supportedMimeTypes: ['image/png', 'image/jpeg', 'image/gif', 'image/webp'],
        
        // you may define a custom image actions class:
        imageActions: \Tobento\App\Media\Imager\ImageActions::class, // default
        
        // define the user permission or null if no permission is needed (not recommended):
        userPermission: 'media.image.editor', // default
        
        // you may localize routes:
        localizeRoute: true, // false (default)
    ),
],
```

See the vendor documentation for available actions:  
[Image Actions - tobento/service-imager](https://github.com/tobento-ch/service-imager#image-actions)

The class used here (`Tobento\App\Media\Imager\ImageActions`) extends the vendor
`ImageActions` and adds optional logging support via `LoggerTrait`.

**Logging**

Ensure the [App Logging Boot](https://github.com/tobento-ch/app-logging#logging-boot) is enabled.

In the ```app/config/logging.php``` file you may define the logger to be used, otherwise the default logger will be used:

```php
'aliases' => [
    // Logs if image processing fails:
    \Tobento\App\Media\Upload\ImageProcessor::class => 'daily',
    // or do not log at all:
    \Tobento\App\Media\Upload\ImageProcessor::class => 'null',
    
    // Logs if image action fails:
    \Tobento\App\Media\Imager\ImageActions::class => 'daily',
    // or do not log at all:
    \Tobento\App\Media\Imager\ImageActions::class => 'null',
],
```

#### Edit Image

Use the ```media.image.editor``` route name to generate the URL where you can edit the specified image.

```php
$url = $router->url('media.image.editor', ['template' => 'default', 'storage' => 'uploads', 'path' => 'image.jpg']);
```

**Events**

The ```Tobento\App\Media\Event\ImageEdited``` will dispatch **after** the image is edited.

### Picture Feature

This feature integrates the [tobento/service-picture-generator](https://github.com/tobento-ch/service-picture-generator) package into your application and provides a convenient way to generate responsive `<picture>` markup.

Images are generated in the background (via queue) when they are first requested.  
Until the generated variants exist, a fallback image is returned.  
Once generated, the responsive picture markup is served automatically.

For a detailed explanation of how picture generation works internally, see the  
[Workflow section of service-picture-generator](https://github.com/tobento-ch/service-picture-generator#workflow).


**Requirements**

This feature has no additional package requirements.

**Install**

Configure the feature in your [media config file](#media-config):

```php
'features' => [
    new Feature\Picture(
        // Define the storage name where the generated picture metadata is stored.
        // This storage should be private (not publicly accessible).
        pictureStorageName: 'picture-data',

        // Define the storage name where generated images are stored.
        // This storage must be public (i.e. support URLs) so the images can be displayed.
        imageStorageName: 'images',
        
        // Queue used for generating images in the background:
        queueName: 'file',
    ),
],
```

Make sure the storages exist in your  
[App File Storage Config](https://github.com/tobento-ch/app-file-storage#file-storage-config).

Make sure the queue exists in your  
[App Queue Config](https://github.com/tobento-ch/app-queue#queue-config).

**Logging**

Ensure the [App Logging Boot](https://github.com/tobento-ch/app-logging#logging-boot) is enabled.

In the ```app/config/logging.php``` file you may define the logger to be used, otherwise the default logger will be used:

```php
'aliases' => [
    // Logs if picture generation fails:
    \Tobento\App\Media\Picture\PictureGenerator::class => 'daily',
    // or do not log at all:
    \Tobento\App\Media\Picture\PictureGenerator::class => 'null',
],
```

#### Displaying Pictures

Use the `picture()` helper in your views:

```php
<?= $view->picture(
    // The path to the original image within the storage:
    path: 'path/to/image.jpg',

    // The storage name where the image is located:
    resource: 'storage-name',

    // The named picture definition to use:
    definition: 'name',

    // Whether to queue image generation (recommended):
    queue: true, // default

    // Whether private storages may be read:
    allowPrivateStorage: false, // default
)->imgAttr('alt', 'Alt Text') ?>
```

See [Basic Usage - Picture Generator](https://github.com/tobento-ch/service-picture-generator#basic-usage) section for more information.

#### Picture Definitions

Store JSON definitions in:

```
app/
    views/
        picture-definitions/
            product-main.json
            ...
```

See [Json Files Definitions](https://github.com/tobento-ch/service-picture#json-files-definitions) for more information.

#### Clearing Generated Picture

To clear generated pictures see [Clearing Generated Pictures - Picture Generator](https://github.com/tobento-ch/service-picture-generator#clearing-generated-pictures) section.

### Picture Editor Feature

This feature provides an interface for editing pictures and their defined variants.  
It displays all picture definitions along with their preview images, allowing you to crop, resize, transform, and adjust each variant before regenerating them.  
It requires the [Picture Feature](#picture-feature) to be installed.

**Technical Note**  
The Picture Editor relies on two underlying components:

* Image processing is performed by the `ImageProcessor` from  
  https://github.com/tobento-ch/service-upload/#image-processor  
  app-media extends this processor through  
  `Tobento\App\Media\Upload\ImageProcessor`  
  to add logging and application-level integration.

* Picture generation is handled by the `PictureGenerator` from  
  https://github.com/tobento-ch/service-picture-generator  
  app-media wraps this generator in  
  `Tobento\App\Media\Picture\PictureGenerator`  
  to provide logging support and seamless framework integration.

> **Important**
>
> The Picture Editor loads images in the browser and writes changes back to storage.  
> For security reasons, access to the editor is protected by a permission check.  
> By default, the required permission is **`media.picture.editor`**.  
> You may override this permission, but disabling permission checks is not recommended.

**Requirements**

This feature requires:

```
composer require tobento/app-language
composer require tobento/app-translation
composer require tobento/app-user
```

**Install**

In the [media config file](#media-config) you can configure this feature:

```php
'features' => [
    new Feature\PictureEditor(
        // define different image editor templates:
        templates: [
            'default' => [
                'crop', 'resize', 'fit', // Used for cropping. You may uncomment all if you want to disable cropping.
                'background', 'blur', 'brightness', 'contrast', 'colorize', 'flip', 'gamma', 'pixelate', 'rotate', 'sharpen',
                'greyscale', 'sepia', // Filters
                'quality',
            ],
        ],
        
        // define the supported storages:
        supportedStorages: ['uploads'],
        
        // define the supported mime types:
        supportedMimeTypes: ['image/png', 'image/jpeg', 'image/gif', 'image/webp'],
        
        // you may define a custom image actions class:
        imageActions: \Tobento\App\Media\Image\ImageActions::class, // default
        
        // define the user permission or null if no permission is needed (not recommended):
        userPermission: 'media.picture.editor', // default
        
        // you may localize routes:
        localizeRoute: true, // false (default)
        
        // Images will be generated in the background by the queue by default:
        queuePictureGeneration: true, // true (default)
    ),
],
```

See the vendor documentation for available actions:  
[Image Actions - tobento/service-imager](https://github.com/tobento-ch/service-imager#image-actions)

The class used here (`Tobento\App\Media\Imager\ImageActions`) extends the vendor
`ImageActions` and adds optional logging support via `LoggerTrait`.

**Logging**

Ensure the [App Logging Boot](https://github.com/tobento-ch/app-logging#logging-boot) is enabled.

In the ```app/config/logging.php``` file you may define the logger to be used, otherwise the default logger will be used:

```php
'aliases' => [
    // Logs if picture generation fails:
    \Tobento\App\Media\Picture\PictureGenerator::class => 'daily',
    // or do not log at all:
    \Tobento\App\Media\Picture\PictureGenerator::class => 'null',
    
    // Logs if image processing fails:
    \Tobento\App\Media\Upload\ImageProcessor::class => 'daily',
    // or do not log at all:
    \Tobento\App\Media\Upload\ImageProcessor::class => 'null',
    
    // Logs if image action fails:
    \Tobento\App\Media\Imager\ImageActions::class => 'daily',
    // or do not log at all:
    \Tobento\App\Media\Imager\ImageActions::class => 'null',
],
```

#### Edit Picture

Use the ```media.picture.editor``` route name to generate the url where you can edit the specified picture.

```php
$url = $router->url('media.picture.editor', [
    'template' => 'default',
    'storage' => 'uploads',
    'path' => 'image.jpg',
    'definitions' => ['product', 'product-list']
]);
```

**Events**

The ```Tobento\App\Media\Event\PictureEdited``` will be dispatched **after** the picture is edited.

## Services

`app-media` exposes several upload-related services from the
`tobento/service-upload` package.

For full documentation of upload workflows, validators, file writing,
and processing, see:
https://github.com/tobento-ch/service-upload

### File Storage Writer

Writes uploaded files to a configured storage location (e.g., local disk, cloud storage).
This service comes directly from `tobento/service-upload`

Documentation:
https://github.com/tobento-ch/service-upload#file-storage-writer

### Copy Mode (CopyFileWrapper)

Provides copy behavior for uploaded files.
https://github.com/tobento-ch/service-upload#copy-mode-copyfilewrapper

### Upload Validators

All validators from service-upload are available.  
See https://github.com/tobento-ch/service-upload#upload-validators

### Uploaded File Factory

Factory for creating `UploadedFile` instances.
See https://github.com/tobento-ch/service-upload#uploaded-file-factory

### Image Processor

`\Tobento\App\Media\Upload\ImageProcessor` is an app-media wrapper around the  
`tobento/service-upload` `ImageProcessor`, adding logging support and  
application-level integration.

Vendor documentation:  
https://github.com/tobento-ch/service-upload#image-processor

It is used by the [Image Editor Feature](#image-editor-feature) and the [Picture Editor Feature](#picture-editor-feature).

### Picture Generator

`\Tobento\App\Media\Picture\PictureGenerator` is an app-media wrapper around the  
`tobento/service-picture-generator` `PictureGenerator`, adding logging support and  
application-level integration.

Vendor documentation:  
https://github.com/tobento-ch/service-picture-generator

It is also used by the [Picture Feature](#picture-feature) and the [Picture Editor Feature](#picture-editor-feature).

## Learn More

### Display And Download Files Using Apps

You may use the [Apps](https://github.com/tobento-ch/apps) to create multiple apps, one for your main app and one for displaying and downloading files only:

Once you have created and configured your apps you may

**In Media File Display App**

```php
use Tobento\Apps\AppBoot;

class MediaDisplayApp extends AppBoot
{
    protected const APP_ID = 'media-display';

    protected const SLUG = 'app-media';
    
    protected const DOMAINS = ['media.example.com'];
}
```

```php
'features' => [
    new Feature\FileDisplay(
        // define the supported storages:
        supportedStorages: ['images'],
        
        // you may change the route uri:
        routeUri: '{storage}/{path*}',
        
        routeDomain: 'media.example.com',
    ),
],
```

**In Main App**

```php
use Tobento\Apps\AppBoot;

class MainApp extends AppBoot
{
    protected const APP_ID = 'main';

    protected const SLUG = '';
    
    protected const DOMAINS = ['example.com', 'media.example.com'];
}
```

```php
'features' => [
    new Feature\FileDisplay(
        // define the supported storages:
        supportedStorages: ['images'],
        
        // you may change the route uri:
        routeUri: '{storage}/{path*}',
        
        // you may define a route domain:
        routeDomain: 'media.example.com',
    ),
],
```

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)