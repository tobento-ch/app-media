# App Media

The App Media package provides a set of features and services for working with media files, including:

* [responsive images](#picture-feature) using the HTML `<picture>` element
* [file display](#file-display-feature) and [file download](#file-download-feature)
* [image editing](#image-editor-feature) for cropping, resizing, and transforming images
* [upload validation](#upload-validator) for validating uploaded files

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
        - [File Writer](#file-writer)
        - [Copy Mode (CopyFileWrapper)](#copy-mode-copyfilewrapper)
        - [Upload Validators](#upload-validators)
            - [Upload Validator](#upload-validator)
            - [Upload CSV Validator](#upload-csv-validator)
            - [Upload NDJSON Validator](#upload-ndjson-validator)
            - [Upload PDF Validator](#upload-pdf-validator)
            - [Upload ZIP Validator](#upload-zip-validator)
            - [Upload Combine Validator](#upload-combine-validator)
        - [Uploaded File Factory](#uploaded-file-factory)
        - [Image Processor](#image-processor)
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
        imageActions: \Tobento\App\Media\Image\ImageActions::class, // default
        
        // define the user permission or null if no permission is needed (not recommended):
        userPermission: 'media.image.editor', // default
        
        // you may localize routes:
        localizeRoute: true, // false (default)
    ),
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

This feature may be used to generate HTML markup for responsive images using the HTML picture element.

**Workflow**

If images are not created yet when you [display a picture](#creating-picture), a picture job will be sent to the defined queue, generating the images in the background and returning a "fallback" picture from the defined resource. Once, the images are generated, the picture will be displayed with the images generated.

**Requirements**

This feature does not have any requirements.

**Install**

In the [media config file](#media-config) you can configure this feature:

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

Make sure you have configured the defined storages in the [App File Storage Config](https://github.com/tobento-ch/app-file-storage#file-storage-config).

Make sure you have configured the defined queue in the [App Queue Config](https://github.com/tobento-ch/app-queue#queue-config).

**Logging**

Make sure you have booted the [App Logging Boot](https://github.com/tobento-ch/app-logging#logging-boot).

In the ```app/config/logging.php``` file you may define the logger to be used, otherwise the default logger will be used:

```php
'aliases' => [
    // Logs if picture generation fails:
    \Tobento\App\Media\Picture\PictureGenerator::class => 'daily',
    // or do not log at all:
    \Tobento\App\Media\Picture\PictureGenerator::class => 'null',
    
    // Logs if image action fails:
    \Tobento\App\Media\Image\ImageActions::class => 'daily',
    // or do not log at all:
    \Tobento\App\Media\Image\ImageActions::class => 'null',
],
```

#### Display Picture

Within your view file, use the ```picture``` method to display a picture based on the given ```path```, ```resource``` and ```definition``` parameter:

**Example Using A Named Definition**

You may use named definitions to generate images from. Check out the [Picture Definitions](#picture-definitions) section to learn how to add named definitions.

```php
<?= $view->picture(
    path: 'path/to/image.jpg',
    resource: 'storage-name',
    definition: 'name',
    allowPrivateStorage: false, // default
)->imgAttr('alt', 'Alt Text') ?>
```

About `allowPrivateStorage`

Use this when the image source is a *private* storage.  
(For most cases, using a public storage as the image source is recommended.)

- `false` (default): private storages are not read and a `NullPictureTag` is returned.
- `true`: allow reading from private storage (useful for backend-only sources such as uploads or protected files).

Depending on your definition this will output:

```html
<picture>
  <source srcset="https://example.com/path/to/image.webp" type="image/webp">
  <source srcset="https://example.com/path/to/image.jpg" type="image/jpeg">
  <img src="https://example.com/path/to/image.jpg" alt="Alt Text">
</picture>
```

Using named definitions have the following advantages:

* you can crop images based on the named definition
* you can have different definitions per view theme

**Example Using A Definition**

```php
use Tobento\Service\Picture\Definition\ArrayDefinition;

<?= $view->picture(
    path: 'path/to/image.jpg',
    resource: 'storage-name',
    definition: new ArrayDefinition('product-main', [
        'img' => [
            'src' => [600],
            'alt' => 'Alternative Text',
            'loading' => 'lazy',
        ],
        // You may define any sources:
        'sources' => [
            [
                'media' => '(min-width: 800px)',
                'srcset' => [
                    '' => [1200, 500],
                ],
                'type' => 'image/webp',
            ],
            [
                'media' => '(max-width: 600px)',
                'srcset' => [
                    '' => [600, 400],
                ],
                'type' => 'image/webp',
            ],
        ],
    ]),
) ?>
```

Check out the [Picture Definition](https://github.com/tobento-ch/service-picture#definition) section to learn more about definitions in general.

**Example Using An Imager Resource**

```php
use Tobento\Service\Imager\ResourceInterface;

<?= $view->picture(
    path: 'path/to/image.jpg',
    resource: $resource, // ResourceInterface
    definition: 'name',
)->imgAttr('alt', 'Alt Text') ?>
```

Check out the [Picture Creating - Create Picture From Resource](https://github.com/tobento-ch/service-picture#picture-creating) section to learn more about.

#### Picture Definitions

Store your picture definition JSON files in the ```app/views/picture-definitions/``` directory:

```
app/
    views/
        picture-definitions/
            product-main.json
            ...
```

You may check out the [Json Files Definitions](https://github.com/tobento-ch/service-picture#json-files-definitions) for more information.

#### Clearing Generated Picture

**Using Console Command**

To clear all generated pictures run the following command:

```
php ap picture:clear
```

Or clear generated pictures of specific definitions only:

```
php ap picture:clear --def=product-main --def=post
```

**Using Picture Generator**

```php
use Tobento\App\Media\Picture\PictureGeneratorInterface;

class SomeService
{
    public function __construct(
        protected PictureGeneratorInterface $pictureGenerator,
    ) {}
    
    private function deleteGeneratedPictures()
    {
        // First, get the picture repository from the generator:
        $pictureRepository = $this->pictureGenerator->pictureRepository();
        
        // Deletes the created picture with all its created images for the specified path and definition:
        $pictureRepository->delete(
            path: 'foo/image.jpg',
            definition: 'product-main',
        );
        
        // Deletes all created pictures with all its created images for the specified definition:
        $pictureRepository->deleteAll(
            definition: 'product-main',
        );
        
        // Deletes all created pictures with all its created images for the specified path:
        $pictureRepository->deleteAllByPath(
            path: 'foo/image.jpg',
        );
    }
}
```

### Picture Editor Feature

This feature provides an interface for editing pictures and their defined variants.  
It displays all picture definitions along with their preview images, allowing you to crop, resize, transform, and adjust each variant before regenerating them.  
It requires the [Picture Feature](#picture-feature) to be installed.

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

### File Writer

The file writer class writes the given file to the defined [File Storage](https://github.com/tobento-ch/service-file-storage).

```php
use Tobento\App\Media\FileStorage\FileWriter;
use Tobento\App\Media\FileStorage\FileWriterInterface;
use Tobento\App\Media\FileStorage\Writer;
use Tobento\App\Media\Image\ImageProcessor;
use Tobento\Service\FileStorage\StorageInterface;

$fileWriter = new FileWriter(
    // Define the file storage where to write the files to:
    storage: $storage, // StorageInterface
    
    // Define how filenames should be handled:
    filenames: FileWriter::ALNUM, // RENAME, ALNUM, KEEP
    
    // Or using a closure for customized filenames:
    filenames: function (string $filename): string {
        // customize
        return $filename;
    },
    
    // Define how dublicates should be handled:
    duplicates: FileWriter::RENAME, // RENAME, OVERWRITE, DENY
    
    // Define how folders should be handled:
    folders: FileWriter::ALNUM, // or KEEP
    
    // Or using a closure for customized folders:
    folders: function (string $path): string {
        // customize
        return $path;
    },
    
    // Define the max folder depth limit:
    folderDepthLimit: 5,

    // You may add writers handling specific files:
    writers: [
        new Writer\ImageWriter(
            imageProcessor: new ImageProcessor(
                actions: [
                    'orientate' => [],
                    'resize' => ['width' => 2000],
                ],
            ),
        ),
        new Writer\SvgSanitizerWriter(),
    ],
);
```

You may check out the [Image Processor](#image-processor) section to learn more about it.

**writeFromStream**

Use the ```writeFromStream``` method to write the given stream to the file storage:

```php
use Psr\Http\Message\StreamInterface;
use Tobento\App\Media\Exception\WriteException;
use Tobento\App\Media\FileStorage\WriteResponseInterface;

$writeResponse = $fileWriter->writeFromStream(
    stream: $stream, // StreamInterface
    filename: 'file.txt',
    folderPath: 'path/to', // or an empty string if no path at all
);

var_dump($writeResponse instanceof WriteResponseInterface);
// bool(true)

// throws WriteException if writing failed!
```

**writeUploadedFile**

Use the ```writeUploadedFile``` method to write the given uploaded file to the file storage:

```php
use Psr\Http\Message\UploadedFileInterface;
use Tobento\App\Media\Exception\WriteException;
use Tobento\App\Media\FileStorage\WriteResponseInterface;

$writeResponse = $fileWriter->writeUploadedFile(
    file: $uploadedFile, // UploadedFileInterface
    folderPath: 'path/to', // or an empty string if no path at all
);

var_dump($writeResponse instanceof WriteResponseInterface);
// bool(true)

// throws WriteException if writing failed!
```

It is highly recommended to use the [Upload Validator](#upload-validator) before writing the uploaded file to the file storage.

**copyFile**

Use the `copyFile` method to copy an existing file inside the same file storage to a new folder.
This is useful when selecting files from a file manager or when you want to duplicate files without re-uploading or re-processing them.

```php
use Tobento\App\Media\Exception\WriteException;
use Tobento\App\Media\FileStorage\WriteResponseInterface;

use Tobento\App\Media\Exception\WriteException;
use Tobento\App\Media\FileStorage\WriteResponseInterface;

$writeResponse = $fileWriter->copyFile(
    path: 'foo/image.jpg', // existing file path inside the storage
    folderPath: 'path/to', // target folder, or an empty string for root
);

// Result: 'path/to/image.jpg'
// Note: copyFile() does NOT preserve the source folder structure.

var_dump($writeResponse instanceof WriteResponseInterface);
// bool(true)

// throws WriteException if copying failed!
```

This method performs a storage-level copy (e.g. local to local, S3 to S3) without reading streams or applying any image processing.
It is ideal for file-manager selections or fast, lossless duplication.

**writeResponse**

```php
use Psr\Http\Message\UploadedFileInterface;
use Tobento\App\Media\FileStorage\WriteResponseInterface;
use Tobento\Service\Message\MessagesInterface;

$writeResponse = $fileWriter->writeUploadedFile(file: $uploadedFile, folderPath: '');

var_dump($writeResponse instanceof WriteResponseInterface);
// bool(true)

// Get the path (string) e.g. path/to/file.txt
$path = $writeResponse->path();

// Get the content (string|\Stringable):
$content = $writeResponse->content();

// Get the original filename (unmodified). Might come from client.
$originalFilename = $writeResponse->originalFilename();

// Get the messages:
$messages = $writeResponse->messages();
// MessagesInterface
```

### Copy Mode (CopyFileWrapper)

Copy mode can be used when you want to copy an existing file inside the same [file storage](https://github.com/tobento-ch/app-file-storage) instead of uploading a new one.  
A `CopyFileWrapper` contains:

- the original `UploadedFileInterface` (metadata only)
- the storage name where the file currently exists
- the path of the file inside that storage
    
```php
use Tobento\App\Media\Upload\CopyFileWrapper;

if ($inputFile instanceof CopyFileWrapper) {
    $writeResponse = $writer->copyFile(
        sourcePath: $inputFile->path(),
        folderPath: $folderPath,
    );
} else {
    $writeResponse = $writer->writeUploadedFile($inputFile, $folderPath);
}
```

### Upload Validators

#### Upload Validator

The upload validator validates a given uploaded file against a set of configurable security and consistency rules.

```php
use Tobento\App\Media\Upload\Validator;
use Tobento\App\Media\Upload\ValidatorInterface;

$validator = new Validator(
    // Allowed file extensions:
    allowedExtensions: ['jpg', 'png', 'gif', 'webp'],
    
    // Whether to restrict filenames to alphanumeric characters,
    // hyphens, underscores, spaces, and periods:
    strictFilenameCharacters: true, // default
    
    // Maximum allowed filename length:
    maxFilenameLength: 255, // default
    
    // Maximum file size in kilobytes (null = unlimited):
    maxFileSizeInKb: 2000,
    
    // Whether to validate the client-provided media type
    // against the detected mime type (disabled by default):
    validateClientMediaType: true,
);

var_dump($validator instanceof ValidatorInterface);
// bool(true)
```

**validateUploadedFile**

Use the ```validateUploadedFile``` method to validate the given uploaded file:

```php
use Psr\Http\Message\UploadedFileInterface;
use Tobento\App\Media\Exception\UploadedFileException;

try {
    $validator->validateUploadedFile(
        file: $uploadedFile, // UploadedFileInterface
    );
} catch (UploadedFileException $e) {
    // validation failed.
}
```

#### Security

The validator ensures that:

- the file extension is allowed
- the mime type detected from the file's content is allowed
- the client filename extension is consistent with the file's content
- the client media type is consistent with the detected mime type  
  (only if `validateClientMediaType` is enabled)
- the filename contains only alphanumeric characters, hyphens, underscores, spaces, and periods  
  (if `strictFilenameCharacters` is `true`)
- the filename length does not exceed the configured `maxFilenameLength`
- the file size does not exceed the configured `maxFileSizeInKb`  
  (default: `null` = unlimited)

Once the uploaded file is validated and accepted, you can rely on:

- `$uploadedFile->getClientMediaType()` being allowed and consistent with the file content  
  (if strict client media type validation is enabled)
- `$uploadedFile->getClientFilename()` having a valid and consistent extension

The only remaining responsibility is verifying the filename itself, excluding the extension:

```php
$filename = $uploadedFile->getClientFilename();

$extension = pathinfo($filename, PATHINFO_EXTENSION);
// is valid as verified
```

If you use the [File Writer](#file-writer) to store files, ensure the ```filenames``` parameter is configured safely.

```php
use Tobento\App\Media\FileStorage\FileWriter;

$fileWriter = new FileWriter(
    filenames: FileWriter::ALNUM,
    
    // or
    filenames: FileWriter::RENAME,
    
    // or
    filenames: function (string $filename): string {
        // verify filename!
        return $verifiedFilename;
    },
);
```

**File Storage Location**

Always store uploaded files outside the webroot or on a separate host.  
If you use the [File Writer](#file-writer), ensure the configured `storage` location is outside the webroot - such as the default ```uploads-private``` or ```uploads-public``` storage.

**Resources**

For further guidance on secure file uploads, refer to:  
[File Upload Cheatsheet - owasp.org](https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html).

#### Upload CSV Validator

The CSV validator extends the base [upload validator](#upload-validator) with additional CSV-specific security checks.  
It ensures that uploaded CSV files are structurally valid, safe to process, and free from spreadsheet-formula injection.

```php
use Tobento\App\Media\Exception\UploadedFileException;
use Tobento\App\Media\Upload\CsvValidator;
use Tobento\App\Media\Upload\ValidatorInterface;

$validator = new CsvValidator(
    allowedExtensions: ['csv'],
);

// Disable deep CSV content validation if needed returning a new instance:
$validator = $validator->withValidateCsvContent(false);

var_dump($validator instanceof ValidatorInterface);
// bool(true)

try {
    $validator->validateUploadedFile($uploadedFile);
} catch (UploadedFileException $e) {
    // CSV validation failed.
}
```

**CSV-Specific Security**

The CSV validator ensures:
- the file extension is csv
- the detected mime type is one of the allowed CSV mime types: text/csv, text/plain, application/csv, application/vnd.ms-excel
- the CSV can be parsed line-by-line
- all rows have a consistent number of columns
- no cell begins with =, +, -, or @ (prevents spreadsheet formula injection)
- UTF-8 BOM is handled correctly
- empty lines are ignored safely

#### Upload NDJSON Validator

The NDJSON validator extends the base [upload validator](#upload-validator) with line-by-line JSON validation.  
It ensures that uploaded NDJSON files contain **one valid JSON object per line**, ignore empty lines, and safely reject malformed entries.

```php
use Tobento\App\Media\Exception\UploadedFileException;
use Tobento\App\Media\Upload\NdjsonValidator;
use Tobento\App\Media\Upload\ValidatorInterface;

$validator = new NdjsonValidator(
    allowedExtensions: ['ndjson'],
);

var_dump($validator instanceof ValidatorInterface);
// bool(true)

try {
    $validator->validateUploadedFile($uploadedFile);
} catch (UploadedFileException $e) {
    // NDJSON validation failed.
}
```

#### Upload PDF Validator

The PDF validator extends the base [upload validator](#upload-validator) with additional PDF-specific security checks.  
It ensures that uploaded PDF files are structurally safe by detecting features commonly used for malicious behavior, such as JavaScript, embedded files, encryption, and auto-execution actions.

```php
use Tobento\App\Media\Exception\UploadedFileException;
use Tobento\App\Media\Upload\PdfValidator;
use Tobento\App\Media\Upload\ValidatorInterface;

$validator = new PdfValidator(
    allowedExtensions: ['pdf'],
);

var_dump($validator instanceof ValidatorInterface);
// bool(true)

try {
    $validator->validateUploadedFile($uploadedFile);
} catch (UploadedFileException $e) {
    // PDF validation failed.
}
```

#### Upload ZIP Validator

The ZIP validator extends the base [upload validator](#upload-validator) with archive-specific security checks.  
It ensures that uploaded ZIP files are safe to extract, structurally valid, and free from common archive-based attack vectors such as ZIP bombs, directory traversal, and excessive nesting.

```php
use Tobento\App\Media\Exception\UploadedFileException;
use Tobento\App\Media\Upload\ZipValidator;
use Tobento\App\Media\Upload\ValidatorInterface;

$validator = new ZipValidator(
    allowedExtensions: ['zip'],
);

// Configure optional ZIP-specific limits returning a new instance:
$validator = $validator
    ->withMaxEntries(1000) // Maximum number of files inside the ZIP (default: 2000)
    ->withMaxTotalUncompressedBytes(10_000) // Total uncompressed size limit (default: 50_000_000 (50 MB))
    ->withMaxCompressionRatio(20) // Prevent ZIP bombs (default: 200)
    ->withMaxDepth(1); // Maximum nested ZIP depth (default: 3)

var_dump($validator instanceof ValidatorInterface);
// bool(true)

try {
    $validator->validateUploadedFile($uploadedFile);
} catch (UploadedFileException $e) {
    // ZIP validation failed.
}
```

**ZIP-Specific Security Features**

The `ZipValidator` performs several safety checks to ensure uploaded archives are safe to process:

- **Maximum entry count**  
  Prevents ZIP files containing thousands of entries, which can overwhelm extraction routines.

- **Maximum total uncompressed size**  
  Protects against ZIP bombs that expand to massive sizes when extracted.

- **Maximum compression ratio**  
  Detects malicious archives with extreme compression ratios.

- **Directory traversal protection**  
  Blocks unsafe paths such as:
```text
../evil.txt
../../etc/passwd
```

- **Nested ZIP depth**  
  Controls how many layers of ZIP-within-ZIP are allowed. Useful for preventing recursive archive bombs.

- **In-memory nested ZIP validation**  
  Nested ZIPs are validated using an internal in‑memory uploaded file implementation, without writing to disk.

#### Upload Combine Validator

The combine validator allows you to register multiple validators and automatically dispatches validation to the first validator that supports the file's extension.

This is ideal when your application accepts multiple file types, each with its own specialized validator.

```php
use Tobento\App\Media\Exception\UploadedFileException;
use Tobento\App\Media\Upload\CombineValidator;
use Tobento\App\Media\Upload\CsvValidator;
use Tobento\App\Media\Upload\Validator;
use Tobento\App\Media\Upload\ValidatorInterface;

$validator = new CombineValidator(
    new CsvValidator(allowedExtensions: ['csv']), // handles .csv
    new Validator(), // fallback for all other extensions
);

var_dump($validator instanceof ValidatorInterface);
// bool(true)

try {
    $validator->validateUploadedFile($uploadedFile);
} catch (UploadedFileException $e) {
    // no matching validator or validation failed
}
```

### Uploaded File Factory

The uploaded file factory creates PSR-7 `UploadedFileInterface` instances from different resources such as remote URLs or storage files.

```php
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface as Psr17UploadedFileFactoryInterface;
use Tobento\App\Media\Upload\UploadedFileFactory;
use Tobento\App\Media\Upload\UploadedFileFactoryInterface;

$factory = new UploadedFileFactory(
    uploadedFileFactory: $uploadedFileFactory, // Psr17UploadedFileFactoryInterface
    streamFactory: $streamFactory, // StreamFactoryInterface
    client: $client, // ClientInterface (PSR-18)
    requestFactory: $requestFactory, // RequestFactoryInterface (PSR-17)
);

var_dump($factory instanceof UploadedFileFactoryInterface);
// bool(true)
```

**createFromRemoteUrl**

Creates an uploaded file by downloading the content from a remote URL using a PSR-18 HTTP client.

```php
use Psr\Http\Message\UploadedFileInterface;
use Tobento\App\Media\Exception\CreateUploadedFileException;

try {
    $uploadedFile = $factory->createFromRemoteUrl(
        url: 'https://example.com/image.jpg' // string
    );
    
    var_dump($uploadedFile instanceof UploadedFileInterface);
    // bool(true)
} catch (CreateUploadedFileException $e) {
    // creating uploaded file failed.
}
```

If the remote request fails or the response status code is not 200, a `CreateUploadedFileException` is thrown.

**createFromStorageFile**

Creates an uploaded file from a [Storage File](https://github.com/tobento-ch/service-file-storage#file-interface).

```php
use Psr\Http\Message\UploadedFileInterface;
use Tobento\App\Media\Exception\CreateUploadedFileException;
use Tobento\Service\FileStorage\FileInterface;

try {
    $uploadedFile = $factory->createFromStorageFile(
        file: $file // FileInterface
    );
    
    var_dump($uploadedFile instanceof UploadedFileInterface);
    // bool(true)
} catch (CreateUploadedFileException $e) {
    // creating uploaded file failed.
}
```

If the storage file does not provide a stream, a `CreateUploadedFileException` is thrown.

### Image Processor

The image processor class processes the given image with the defined actions using the [Imager Service](https://github.com/tobento-ch/service-imager).

```php
use Tobento\App\Media\Image\ImageProcessor;
use Tobento\App\Media\Image\ImageProcessorInterface;
use Tobento\Service\Imager\Action;
use Tobento\Service\Imager\ActionFactoryInterface;

$imageProcessor = new ImageProcessor(
    // Define the imager actions to be processed:
    actions: [
        'orientate' => [],
        'resize' => ['width' => 300],
        new Action\Contrast(20),
    ],
    
    // You may define imager actions which are allowed only.
    // If empty array all are allowed if not in disallowedActions.
    allowedActions: [
        Action\Greyscale::class,
    ],
    
    // You may define imager actions which are not allowed and will be skipped:
    disallowedActions: [
        Action\Colorize::class,
    ],
    
    // You may convert certain images e.g. png to jpeg:
    convert: ['image/png' => 'image/jpeg'],
    
    // You may adjust the image quality:
    quality: ['image/jpeg' => 90, 'image/webp' => 90],
    
    // You may adjust the supported mime types:
    supportedMimeTypes: ['image/png', 'image/jpeg', 'image/gif'], // default
    
    // You may define a custom imager actions class:
    //actionFactory: $customActionFactory, // ActionFactoryInterface
);

var_dump($imageProcessor instanceof ImageProcessorInterface);
// bool(true)

// Use the following methods to modify the image processor returning a new instance:
$imageProcessor = $imageProcessor->withActions([
    'resize' => ['width' => 300],
]);

$imageProcessor = $imageProcessor->withConvert([
    'image/png' => 'image/jpeg',
]);

$imageProcessor = $imageProcessor->withQuality([
    'image/jpeg' => 90,
    'image/webp' => 90,
]);
```

**processFromResource**

Use the ```processFromResource``` method to process the given resource:

```php
use Tobento\App\Media\Exception\ImageProcessException;
use Tobento\Service\Imager\ResourceInterface;
use Tobento\Service\Imager\Response\Encoded;

$encoded = $imageProcessor->processFromResource(
    resource: $resource, // ResourceInterface
);

var_dump($encoded instanceof Encoded);
// bool(true)

// throws ImageProcessException if image cannot get processed!
```

Check out the [Resource](https://github.com/tobento-ch/service-imager#resource) and [Encoded](https://github.com/tobento-ch/service-imager#encoded-response) documentation to learn more.

**processFromStream**

Use the ```processFromStream``` method to process the given stream:

```php
use Psr\Http\Message\StreamInterface;
use Tobento\App\Media\Exception\ImageProcessException;
use Tobento\Service\Imager\Response\Encoded;

$encoded = $imageProcessor->processFromStream(
    stream: $stream, // StreamInterface
);

var_dump($encoded instanceof Encoded);
// bool(true)

// throws ImageProcessException if image cannot get processed!
```

Check out the [Encoded](https://github.com/tobento-ch/service-imager#encoded-response) documentation to learn more.

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