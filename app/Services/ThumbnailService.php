<?php

namespace App\Services;

use Intervention\Image\ImageManager;

class ThumbnailService
{
    /**
     * @var Intervention\Image\ImageManager
     */
    private $imageManager;

    /**
     * @var string
     */
    private $imagePath;

    /**
     * @var double
     */
    private $thumbRate;

    /**
     * @var integer
     */
    private $thumbWidth;

    /**
     * @var integer
     */
    private $thumbHeight;

    /**
     * @var string
     */
    private $destPath;

    /**
     * @var integer
     */
    private $xCoordinate;

    /**
     * @var integer
     */
    private $yCoordinate;

    /**
     * @var string
     */
    private $fitPosition;

    /**
     * @var string
     */
    private $fileName;

    public function __construct()
    {
        $this->imageManager = new ImageManager([
            'driver' => 'gd'
        ]);

        // Default rate is 3/4 (1024*768, ...)
        $this->thumbRate = 0.75;
        $this->xCoordinate = null;
        $this->yCoordinate = null;
        $this->fitPosition = 'center';
    }

    /**
     * Set image to resize
     *
     * @param string $imagePath Path to resize image
     * @return App\Services\ThumbnailService
     */
    public function setImage($imagePath)
    {
        $this->imagePath = $imagePath;

        return $this;
    }

    /**
     * Get resize image path
     *
     * @return string Resize image path
     */
    public function getImage()
    {
        return $this->imagePath;
    }

    /**
     * Set thumb size rate (height/width)
     *
     * @param double Thumbnail heigh rate (if image height is null)
     * @return App\Services\ThumbnailService
     */
    public function setRate($rate)
    {
        $this->thumbRate = $rate;

        return $this;
    }

    /**
     * Get thumb size rate
     *
     * @return double Image resize rate
     */
    public function getRate()
    {
        return $this->thumbRate;
    }

    /**
     * Set thumb size
     *
     * @param integer Thumbnail width (pixel)
     * @param integer Thumbnail height (pixel)
     * @return App\Services\ThumbnailService
     */
    public function setSize($width, $height = null)
    {
        $this->thumbWidth = $width;
        $this->thumbHeight = $height;

        if (is_null($height)) {
            $this->thumbHeight = ($this->thumbWidth * $this->thumbRate);
        }

        return $this;
    }

    /**
     * Get thumb size
     *
     * @return array Array width and height of thumb
     */
    public function getSize()
    {
        return [$this->thumbWidth, $this->thumbHeight];
    }

    /**
     * Set path to save thumb
     *
     * @param string $destPath Destination path to save thumb
     * @return App\Services\ThumbnailService
     */
    public function setDestPath($destPath)
    {
        $this->destPath = $destPath;

        return $this;
    }

    /**
     * Get path to save thumb
     *
     * @return string Path to save thumb
     */
    public function getDestPath()
    {
        return $this->destPath;
    }

    /**
     * Set X-Y coordinates for crop
     *
     * @param integer $xCoord X-Coordinate of the top-left corner
     * @param integer $yCoord Y-Coordinate of the top-left corner
     */
    public function setCoordinates($xCoord, $yCoord)
    {
        $this->xCoordinate = $xCoord;
        $this->yCoordinate = $yCoord;

        return $this;
    }

    /**
     * Get X-Y coordinates
     *
     * @return array X-Y coordniates
     */
    public function getCoordinates()
    {
        return [$this->xCoordinate, $this->yCoordinate];
    }

    /**
     * @param string Fit position:
     *          top-left,
     *          top,
     *          top-right,
     *          left,
     *          center (default)
     *          right
     *          bottom-right
     *          bottom
     *          bottom-left
     * @return App\Services\ThumbnailService
     */
    public function setFitPosition($position)
    {
        $this->fitPosition = $position;

        return $this;
    }

    /**
     * Get fit position
     *
     * @return string Fit position
     */
    public function getFitPosition()
    {
        return $this->fitPosition;
    }

    /**
     * Set new file name to save. If filename is not set,
     * we'll use original file name
     *
     * @param string $fileName New file name to save
     * @return App\Services\ThumbnailService
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * Get new filename will save
     *
     * @return string New filename
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Resize image (save to disk)
     *
     * @param string $type Type of resize: fit, crop and resize
     * @param integer $quality Thumb quality
     * @return mixed String to thumb file or false
     */
    public function save($type = 'resize', $quality = 80)
    {
        $pathInfo = pathinfo($this->imagePath);
        $fileName = $pathInfo['basename'];

        if ($this->fileName) {
            $fileName = $this->fileName;
        }

        $destPath = sprintf('%s/%s', trim($this->destPath, '/'), $fileName);

        $thumbImage = $this->imageManager->make($this->imagePath);

        switch ($type) {
            case 'fit':
                $thumbImage->fit($this->thumbWidth, $this->thumbHeight, null, $this->fitPosition);
                break;
            case 'crop':
                $thumbImage->crop($this->thumbWidth, $this->thumbHeight, $this->xCoordinate, $this->yCoordinate);
                break;
            default:
                $thumbImage->resize($this->thumbWidth, $this->thumbHeight);
        }

        try {
            $thumbImage->save($destPath, $quality);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());

            return false;
        }

        return $destPath;
    }
}
