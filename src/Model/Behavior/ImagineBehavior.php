<?php
declare(strict_types=1);
/**
 * Copyright 2011-2017, Florian Krämer
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * Copyright 2011-2017, Florian Krämer
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
namespace Burzum\Imagine\Model\Behavior;

use BadMethodCallException;
use Burzum\Imagine\Lib\ImageProcessor;
use Burzum\Imagine\Lib\ImagineUtility;
use Cake\ORM\Behavior;
use Cake\ORM\Table;
use Imagine\Image\AbstractImage;
use InvalidArgumentException;

/**
 * CakePHP Imagine Plugin
 */
class ImagineBehavior extends Behavior
{
    public const BEFORE_APPLY_OPERATIONS = 'ImagineBehavior.beforeApplyOperations';
    public const APPLY_OPERATIONS = 'ImagineBehavior.applyOperation';
    public const AFTER_APPLY_OPERATIONS = 'ImagineBehavior.afterApplyOperations';

    /**
     * Default settings array
     *
     * @var array
     */
    protected $_defaultConfig = [
        'engine' => 'Gd',
        'processorClass' => '\Burzum\Imagine\Lib\ImageProcessor',
    ];

    /**
     * Class name of the image processor to use.
     *
     * @var string
     */
    protected $_processorClass;

    /**
     * Image processor instance
     *
     * @var \Burzum\Imagine\Lib\ImageProcessor|null
     */
    protected $_processor;

    /**
     * Imagine engine
     */
    protected $Imagine;

    /**
     * Constructor
     *
     * @param \Cake\ORM\Table $table The table this behavior is attached to.
     * @param array $settings The settings for this behavior.
     */
    public function __construct(Table $table, array $settings = [])
    {
        parent::__construct($table, $settings);

        $class = '\Imagine\\' . $this->getConfig('engine') . '\Imagine';
        $this->Imagine = new $class();
        $this->_table = $table;
        $processorClass = $this->getConfig('processorClass');
        $this->_processor = new $processorClass($this->getConfig());
    }

    /**
     * Returns the image processor object.
     *
     * @return \Burzum\Imagine\Lib\ImageProcessor
     */
    public function getImageProcessor(): ?ImageProcessor
    {
        return $this->_processor;
    }

    /**
     * Delegate the calls to the image processor lib.
     *
     * @param string $method Method name
     * @param array $args Arguments
     * @return mixed
     */
    public function __call($method, $args)
    {
        if (method_exists($this->_processor, $method)) {
            return call_user_func_array([$this->_processor, $method], $args);
        }

        return null;
    }

    /**
     * Loads an image and applies operations on it.
     *
     * Caching and taking care of the file storage is NOT the purpose of this method!
     *
     * @param \Imagine\Image\AbstractImage|string $image Image instance or a file to open
     * @param string|null $output File to write
     * @param array $imagineOptions Image Options
     * @param array $operations Image operations
     * @throws \InvalidArgumentException
     * @return bool|\Imagine\Image\AbstractImage
     */
    public function processImage($image, $output = null, $imagineOptions = [], $operations = [])
    {
        if (is_string($image)) {
            $this->_processor->open($image);
            $image = $this->_processor->image();
        }
        if (!$image instanceof AbstractImage) {
            $message = 'An instance of `\Imagine\Image\AbstractImage` is required, you passed `%s`!';
            throw new InvalidArgumentException(sprintf($message, get_class($image)));
        }

        $event = $this->getTable()->dispatchEvent(self::BEFORE_APPLY_OPERATIONS, compact('image', 'operations'));
        if ($event->isStopped()) {
            return $event->getResult();
        }

        $data = $event->getData();
        $this->_applyOperations(
            $data['operations'],
            $data['image']
        );

        $event = $this->getTable()->dispatchEvent(self::AFTER_APPLY_OPERATIONS, $data);
        if ($event->isStopped()) {
            return $event->getResult();
        }

        if ($output === null) {
            return $image;
        }

        return $this->_processor->save($output, $imagineOptions);
    }

    /**
     * Applies the actual image operations to the image.
     *
     * @param array $operations Operations
     * @param array $image Image
     * @throws \BadMethodCallException
     * @return void
     */
    protected function _applyOperations($operations, $image)
    {
        foreach ($operations as $operation => $params) {
            $event = $this->getTable()->dispatchEvent(self::APPLY_OPERATIONS, compact('image', 'operations'));
            if ($event->isStopped()) {
                continue;
            }

            if (method_exists($this->_table, $operation)) {
                $this->getTable()->{$operation}($image, $params);
            } elseif (method_exists($this->_processor, $operation)) {
                $this->_processor->{$operation}($params);
            } else {
                throw new BadMethodCallException(sprintf(
                    'Unsupported image operation `%s`!',
                    $operation
                ));
            }
        }
    }

    /**
     * Turns the operations and their params into a string that can be used in a file name to cache an image.
     *
     * Suffix your image with the string generated by this method to be able to batch delete a file that has versions of it cached.
     * The intended usage of this is to store the files as my_horse.thumbnail+width-100-height+100.jpg for example.
     *
     * So after upload store your image meta data in a db, give the filename the id of the record and suffix it
     * with this string and store the string also in the db. In the views, if no further control over the image access is needed,
     * you can simply direct-link the image like $this->Html->image('/images/05/04/61/my_horse.thumbnail+width-100-height+100.jpg');
     *
     * @param array $operations Imagine image operations
     * @param array $separators Optional
     * @param string $hash Has the operations to a string, default is false
     * @return string Filename compatible String representation of the operations
     * @link http://support.microsoft.com/kb/177506
     */
    public function operationsToString($operations, $separators = [], ?string $hash = null)
    {
        return ImagineUtility::operationsToString($operations, $separators, $hash);
    }

    /**
     * hashImageOperations
     *
     * @param array $imageSizes Array of image versions
     * @param int $hashLength Hash length, default is 8
     * @return array
     */
    public function hashImageOperations($imageSizes, $hashLength = 8): array
    {
        return ImagineUtility::hashImageOperations($imageSizes, $hashLength);
    }

    /**
     * Gets the image size of an image
     *
     * @param string $image Image file
     * @return array
     */
    public function getImageSize($image)
    {
        return $this->_processor->getImageSize($image);
    }
}
