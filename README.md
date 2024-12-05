# App Media

The app media provides features and services such as:

* [responsive images](#picture-feature) using the HTML picture element
* [file display](#file-display-feature) and [file download](#file-download-feature)
* [image editor](#image-editor-feature) to crop, resize and other actions to modify images
* [upload validator](#upload-validator) to validate uploaded files

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
        - [File Download Feature](#file-download-feature)
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
        - [Upload Validator](#upload-validator)
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

- PHP 8.0 or greater

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
$app = (new AppFactory())->createApp();

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

This feature may be used to retrieve files from a supported [file storage](https://github.com/tobento-ch/app-file-storage). The main aim of this feature is to retrieve file urls.
 
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
use Tobento\Media\Feature\File;
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
use Tobento\Media\Feature\File;

$file = $app->get(File::class)
    ->storage(storage: 'images')
    ->with('url', 'width', 'height')
    ->file(path: 'path/to/file.jpg');
```

If you only want to retrieve a file url, you may prefer to use the ```url``` method instead:

```php
use Tobento\Media\Feature\File;

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
        // define the supported storages:
        supportedStorages: ['images'],
        
        // you may change the route uri:
        routeUri: 'media/file/{storage}/{path*}', // default
        
        // you may define a route domain:
        routeDomain: 'media.example.com', // null is default
    ),
],
```

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

### File Download Feature

This feature may be used to force downloading a file from a supported [file storage](https://github.com/tobento-ch/app-file-storage) in the user's browser.
 
**Requirements**

This feature does not have any requirements.

**Install**

In the [media config file](#media-config) you can configure this feature:

```php
'features' => [
    new Feature\FileDownload(
        // define the supported storages:
        supportedStorages: ['images'],
        
        // you may change the route uri:
        routeUri: 'media/download/{storage}/{path*}', // default
        
        // you may define a route domain:
        routeDomain: 'media.example.com', // null is default
    ),
],
```

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

This feature may be used to edit images.

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

Use the ```media.image.editor``` route name to generate the url where you can edit the specified image.

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
        // Define the storage name where to store the generated picture data.
        pictureStorageName: 'picture-data',

        // Define the storage name where to store each created image. The storage must support urls.
        imageStorageName: 'images',
        
        // Define the queue to be used for generating the images:
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
)->imgAttr('alt', 'Alt Text') ?>
```

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

### Picture Editor Feature

This feature requires the [Picture Feature](#picture-feature) to be installed.

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
    new Feature\PictureEditor(
        // define different image editors templates:
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

The ```Tobento\App\Media\Event\PictureEdited``` will dispatch **after** the picture is edited.

## Services

### File Writer

The file writer class writes the given file to the defined [File Storage](https://github.com/tobento-ch/service-file-storage).

```php
use Tobento\App\Media\FileStorage\FileWriter;
use Tobento\App\Media\FileStorage\FileWriterInterface;
use Tobento\App\Media\Image\ImageProcessor;
use Tobento\App\Media\Image\Writer;
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

### Upload Validator

The upload validator validates the given uploaded file.

```php
use Tobento\App\Media\Upload\Validator;
use Tobento\App\Media\Upload\ValidatorInterface;

$validator = new Validator(
    // Define the allowed file extensions:
    allowedExtensions: ['jpg', 'png', 'gif', 'webp'],
    
    // Define if you want to allow only strict filename characters
    // which are alphanumeric characters, hyphen, spaces, and periods:
    strictFilenameCharacters: true, // default
    
    // Define the max. filename length:
    maxFilenameLength: 255, // default
    
    // You may define the max. file size in bytes or null (unlimited).
    maxFileSizeInKb: 2000,
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

The validator validates that

* the file extension and mime type detected by its content is allowed
* the client filename extension, client media type of the file is consistent with its content
* the filename consists only of alphanumeric characters, hyphen, spaces, and periods if ```strictFilenameCharacters``` is set to ```true``` (default)
* the filename length does not exceed the defined ```maxFilenameLength``` parameter
* the file size does not exceed the defined ```maxFileSizeInKb``` parameter. Default is ```null``` unlimited

Once the uploaded file is validated and valid, you can be sure that

* the ```$uploadedFile->getClientMediaType()``` is allowed, consistent with its file content and extension
* the ```$uploadedFile->getClientFilename()``` file extension is allowed and consistent with its file content

The only thing you have to take care of is the filename except the extension:

```php
$filename = $uploadedFile->getClientFilename();

$extension = pathinfo($filename, PATHINFO_EXTENSION);
// is valid as verified
```

If you use the [File Writer](#file-writer) to store files, make sure the ```filenames``` parameter is configured safely.

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

Always store uploaded files outside the webroot or on a different host. If using the [File Writer](#file-writer) to store files, make sure your defined ```storage``` is outside the webroot such as the default configured ```uploads``` storage.

**Resources**

You may read the [File Upload Cheatsheet - owasp.org](https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html).

### Uploaded File Factory

The upload file factory may be used to create uploaded files from different resources.

```php
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface as Psr17UploadedFileFactoryInterface;
use Tobento\App\Media\Upload\UploadedFileFactory;
use Tobento\App\Media\Upload\UploadedFileFactoryInterface;

$factory = new UploadedFileFactory(
    uploadedFileFactory: $uploadedFileFactory, // Psr17UploadedFileFactoryInterface
    streamFactory: $streamFactory, // StreamFactoryInterface
);

var_dump($factory instanceof UploadedFileFactoryInterface);
// bool(true)
```

**createFromRemoteUrl**

Use the ```createFromRemoteUrl``` method to create an uploaded file from a remote url:

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

**createFromStorageFile**

Use the ```createFromStorageFile``` method to create an uploaded file from a [Storage File](https://github.com/tobento-ch/service-file-storage#file-interface):

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