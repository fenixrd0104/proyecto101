<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: McDonald's <zuojiazi.cn@gmail.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace org\image\driver;

use think\Lang;

class Imagick
{
    /**
     * Image resource object
     *
     * @var resource
     */
    private $im;

    /**
     * Image information, including width, height, type, mime, size
     *
     * @var array
     */
    private $info;

    /**
     * Constructor, which can be used to open an image
     *
     * @param string $imgname image path
     */
    public function __construct($imgname = null)
    {
        if (!extension_loaded('Imagick')) {
            throw new \Exception(Lang::get('_NOT_SUPPERT_') . ':Imagick');
        }
        $imgname && $this->open($imgname);
    }

    /**
     * open an image
     *
     * @param string $imgname image path
     */
    public function open($imgname)
    {
        //detect image file
        if (!is_file($imgname)) {
            throw new \Exception('non-existent image file');
        }

        //destroy existing image
        empty($this->im) || $this->im->destroy();

        // load image
        $this->im = new \Imagick(realpath($imgname));

        //set image information
        $this->info = [
            'width'  => $this->im->getImageWidth(),
            'height' => $this->im->getImageHeight(),
            'type'   => strtolower($this->im->getImageFormat()),
            'mime'   => $this->im->getImageMimeType(),
        ];
    }

    /**
     * save image
     *
     * @param string $imgname image save name
     * @param string $type image type
     * @param boolean $interlace whether to set interlace for JPEG type images
     */
    public function save($imgname, $type = null, $interlace = true)
    {
        if (empty($this->im)) {
            throw new \Exception('No image resources can be saved');
        }

        //set image type
        if (is_null($type)) {
            $type = $this->info['type'];
        } else {
            $type = strtolower($type);
            $this->im->setImageFormat($type);
        }

        // JPEG image set interlace
        if ('jpeg' == $type || 'jpg' == $type) {
            $this->im->setImageInterlaceScheme(1);
        }

        //Remove image configuration information
        $this->im->stripImage();

        // save the image
        $imgname = realpath(dirname($imgname)) . '/' . basename($imgname); // force absolute path
        if ('gif' == $type) {
            $this->im->writeImages($imgname, true);
        } else {
            $this->im->writeImage($imgname);
        }
    }

    /**
     * return image width
     *
     * @return integer image width
     */
    public function width()
    {
        if (empty($this->im)) {
            throw new \Exception('No image resource specified');
        }

        return $this->info['width'];
    }

    /**
     * return image height
     *
     * @return integer image height
     */
    public function height()
    {
        if (empty($this->im)) {
            throw new \Exception('No image resource specified');
        }

        return $this->info['height'];
    }

    /**
     * return image type
     *
     * @return string image type
     */
    public function type()
    {
        if (empty($this->im)) {
            throw new \Exception('No image resource specified');
        }

        return $this->info['type'];
    }

    /**
     * Return image MIME type
     *
     * @return string image MIME type
     */
    public function mime()
    {
        if (empty($this->im)) {
            throw new \Exception('No image resource specified');
        }

        return $this->info['mime'];
    }

    /**
     * Returns an array of image dimensions 0 - image width, 1 - image height
     *
     * @return array image size
     */
    public function size()
    {
        if (empty($this->im)) {
            throw new \Exception('No image resource specified');
        }

        return [$this->info['width'], $this->info['height']];
    }

    /**
     * Crop image
     *
     * @param integer $w crop area width
     * @param integer $h crop area height
     * @param integer $x crop area x coordinate
     * @param integer $y The y coordinate of the clipping area
     * @param integer $width image save width
     * @param integer $height image save height
     */
    public function crop($w, $h, $x = 0, $y = 0, $width = null, $height = null)
    {
        if (empty($this->im)) {
            throw new \Exception('No image resource that can be cropped');
        }

        //set save size
        empty($width) && $width = $w;
        empty($height) && $height = $h;

        // crop the image
        if ('gif' == $this->info['type']) {
            $img = $this->im->coalesceImages();
            $this->im->destroy(); //Destroy the original image

            //loop through each frame
            do {
                $this->_crop($w, $h, $x, $y, $width, $height, $img);
            } while ($img->nextImage());

           //Compress picture
            $this->im = $img->deconstructImages();
            $img->destroy(); //Destroy the zero-hour picture
        } else {
            $this->_crop($w, $h, $x, $y, $width, $height);
        }
    }

    /**
     * Crop the picture, called internally
     *
     */
    private function _crop($w, $h, $x, $y, $width, $height, $img = null)
    {
        is_null($img) && $img = $this->im;

        // crop
        $info = $this->info;
        if (0 != $x || 0 != $y || $w != $info['width'] || $h != $info['height']) {
            $img->cropImage($w, $h, $x, $y);
            $img->setImagePage($w, $h, 0, 0); //Adjust the canvas to match the image
        }

        //adjust size
        if ($w != $width || $h != $height) {
            $img->scaleImage($width, $height);
        }

        //set cache size
        $this->info['width'] = $w;
        $this->info['height'] = $h;
    }

    /**
     * Generate thumbnails
     *
     * @param integer $width Thumbnail maximum width
     * @param integer $height Thumbnail maximum height
     * @param integer $type Thumbnail crop type
     */
    public function thumb($width, $height, $type = THINKIMAGE_THUMB_SCALE)
    {
        if (empty($this->im)) {
            throw new \Exception('No image resource that can be abbreviated');
        }

        //Original image width and height
        $w = $this->info['width'];
        $h = $this->info['height'];

        /* Calculate the necessary parameters for thumbnail generation */
        switch ($type) {
            /* Equal scaling */
            case THINKIMAGE_THUMB_SCALING:
                //If the original image size is smaller than the thumbnail size, it will not be thumbnailed
                if ($w < $width && $h < $height) {
                    return;
                }

                //Calculate the zoom ratio
                $scale = min($width / $w, $height / $h);

                //Set the coordinates and width and height of the thumbnail
                $x = $y = 0;
                $width = $w * $scale;
                $height = $h * $scale;
                break;

            /* Center crop */
            case THINKIMAGE_THUMB_CENTER:
                //Calculate the zoom ratio
                $scale = max($width / $w, $height / $h);

                //Set the coordinates and width and height of the thumbnail
                $w = $width / $scale;
                $h = $height / $scale;
                $x = ($this->info['width'] - $w) / 2;
                $y = ($this->info['height'] - $h) / 2;
                break;

            /* top left corner crop */
            case THINKIMAGE_THUMB_NORTHWEST:
                //Calculate the zoom ratio
                $scale = max($width / $w, $height / $h);

                //Set the coordinates and width and height of the thumbnail
                $x = $y = 0;
                $w = $width / $scale;
                $h = $height / $scale;
                break;

            /* lower right corner crop */
            case THINKIMAGE_THUMB_SOUTHEAST:
                //Calculate the zoom ratio
                $scale = max($width / $w, $height / $h);

                //Set the coordinates and width and height of the thumbnail
                $w = $width / $scale;
                $h = $height / $scale;
                $x = $this->info['width'] - $w;
                $y = $this->info['height'] - $h;
                break;

            /* padding */
            case THINKIMAGE_THUMB_FILLED:
                //Calculate the zoom ratio
                if ($w < $width && $h < $height) {
                    $scale = 1;
                } else {
                    $scale = min($width / $w, $height / $h);
                }

                //Set the coordinates and width and height of the thumbnail
                $neww = $w * $scale;
                $newh = $h * $scale;
                $posx = ($width - $w * $scale) / 2;
                $posy = ($height - $h * $scale) / 2;

                //create a new image
                $newimg = new Imagick();
                $newimg->newImage($width, $height, 'white', $this->info['type']);

                if ('gif' == $this->info['type']) {
                    $imgs = $this->im->coalesceImages();
                    $img = new Imagick();
                    $this->im->destroy(); //Destroy the original image

                    // loop to fill each frame
                    do {
                        //fill image
                        $image = $this->_fill($newimg, $posx, $posy, $neww, $newh, $imgs);

                        $img->addImage($image);
                        $img->setImageDelay($imgs->getImageDelay());
                        $img->setImagePage($width, $height, 0, 0);

                        $image->destroy(); //Destroy the zero-time image

                    } while ($imgs->nextImage());

                    //Compress picture
                    $this->im->destroy();
                    $this->im = $img->deconstructImages();
                    $imgs->destroy(); //Destroy the zero-hour picture
                    $img->destroy(); //Destroy the zero-hour picture

                } else {
                   //fill image
                    $img = $this->_fill($newimg, $posx, $posy, $neww, $newh);
                    //Destroy the original image
                    $this->im->destroy();
                    $this->im = $img;
                }

                //set new image properties
                $this->info['width'] = $width;
                $this->info['height'] = $height;
                return;

            /* fixed */
            case THINKIMAGE_THUMB_FIXED:
                $x = $y = 0;
                break;

            default:
                throw new \Exception('Unsupported thumbnail crop type');
        }

        /* crop image */
        $this->crop($w, $h, $x, $y, $width, $height);
    }

    /**
     * Fill the specified image, used internally
     *
     */
    private function _fill($newimg, $posx, $posy, $neww, $newh, $img = null)
    {
        is_null($img) && $img = $this->im;

        /* Draw the specified image into a blank image */
        $draw = new ImagickDraw();
        $draw->composite($img->getImageCompose(), $posx, $posy, $neww, $newh, $img);
        $image = $newimg->clone();
        $image->drawImage($draw);
        $draw->destroy();

        return $image;
    }

    /**
     * Add watermark
     *
     * @param string $source watermark image path
     * @param integer $locate watermark location
     * @param integer $alpha watermark transparency
     */
    public function water($source, $locate = THINKIMAGE_WATER_SOUTHEAST)
    {
        //Resource detection
        if (empty($this->im)) {
            throw new \Exception('There is no image resource that can be watermarked');
        }

        if (!is_file($source)) {
            throw new \Exception('The watermark image does not exist');
        }

        //Create a watermark image resource
        $water = new Imagick(realpath($source));
        $info  = [$water->getImageWidth(), $water->getImageHeight()];

       /* set the watermark position */
        switch ($locate) {
            /* bottom right watermark */
            case THINKIMAGE_WATER_SOUTHEAST:
                $x = $this->info['width'] - $info[0];
                $y = $this->info['height'] - $info[1];
                break;

            /* bottom left watermark */
            case THINKIMAGE_WATER_SOUTHWEST:
                $x = 0;
                $y = $this->info['height'] - $info[1];
                break;

            /* Top left watermark */
            case THINKIMAGE_WATER_NORTHWEST:
                $x = $y = 0;
                break;

            /* top right watermark */
            case THINKIMAGE_WATER_NORTHEAST:
                $x = $this->info['width'] - $info[0];
                $y = 0;
                break;

            /* Center the watermark */
            case THINKIMAGE_WATER_CENTER:
                $x = ($this->info['width'] - $info[0]) / 2;
                $y = ($this->info['height'] - $info[1]) / 2;
                break;

           /* Bottom center watermark */
            case THINKIMAGE_WATER_SOUTH:
                $x = ($this->info['width'] - $info[0]) / 2;
                $y = $this->info['height'] - $info[1];
                break;

            /* center right watermark */
            case THINKIMAGE_WATER_EAST:
                $x = $this->info['width'] - $info[0];
                $y = ($this->info['height'] - $info[1]) / 2;
                break;

            /* Top center watermark */
            case THINKIMAGE_WATER_NORTH:
                $x = ($this->info['width'] - $info[0]) / 2;
                $y = 0;
                break;

            /* center left watermark */
            case THINKIMAGE_WATER_WEST:
                $x = 0;
                $y = ($this->info['height'] - $info[1]) / 2;
                break;

            default:
               /* custom watermark coordinates */
                if (is_array($locate)) {
                    list($x, $y) = $locate;
                } else {
                    throw new \Exception('Unsupported watermark position type');
                }
        }

        //create drawing resource
        $draw = new ImagickDraw();
        $draw->composite($water->getImageCompose(), $x, $y, $info[0], $info[1], $water);

        if ('gif' == $this->info['type']) {
            $img = $this->im->coalesceImages();
            $this->im->destroy(); //Destroy the original image

            do {
                //add watermark
                $img->drawImage($draw);
            } while ($img->nextImage());

            //Compress picture
            $this->im = $img->deconstructImages();
            $img->destroy(); //Destroy the zero-hour picture

        } else {
            //add watermark
            $this->im->drawImage($draw);
        }

        //Destroy the watermark resource
        $draw->destroy();
        $water->destroy();
    }

    /**
     * Image added text
     *
     * @param string $text added text
     * @param string $font font path
     * @param integer $size font size
     * @param string $color text color
     * @param integer $locate text write location
     * @param integer $offset The offset of the text relative to the current position
     * @param integer $angle text angle
     */
    public function text($text, $font, $size, $color = '#00000000',
        $locate = THINKIMAGE_WATER_SOUTHEAST, $offset = 0, $angle = 0) {
        //Resource detection
        if (empty($this->im)) {
            throw new \Exception('No image resource that can be written to text');
        }

        if (!is_file($font)) {
            throw new \Exception("Non-existent font file: {$font}");
        }

        // get color and transparency
        if (is_array($color)) {
            $color = array_map('dechex', $color);
            foreach ($color as &$value) {
                $value = str_pad($value, 2, '0', STR_PAD_LEFT);
            }
            $color = '#' . implode('', $color);
        } elseif (!is_string($color) || 0 !== strpos($color, '#')) {
            throw new \Exception('Wrong color value');
        }
        $col = substr($color, 0, 7);
        $alp = strlen($color) == 9 ? substr($color, -2) : 0;

        //get text information
        $draw = new ImagickDraw();
        $draw->setFont(realpath($font));
        $draw->setFontSize($size);
        $draw->setFillColor($col);
        $draw->setFillAlpha(1 - hexdec($alp) / 127);
        $draw->setTextAntialias(true);
        $draw->setStrokeAntialias(true);

        $metrics = $this->im->queryFontMetrics($draw, $text);

        /* Calculate the initial coordinates and size of the text */
        $x = 0;
        $y = $metrics['ascender'];
        $w = $metrics['textWidth'];
        $h = $metrics['textHeight'];

        /* set the text position */
        switch ($locate) {
            /* Bottom right text */
            case THINKIMAGE_WATER_SOUTHEAST:
                $x += $this->info['width'] - $w;
                $y += $this->info['height'] - $h;
                break;

            /* Bottom left text */
            case THINKIMAGE_WATER_SOUTHWEST:
                $y += $this->info['height'] - $h;
                break;

            /* Top left text */
            case THINKIMAGE_WATER_NORTHWEST:
                // The starting coordinate is the upper left corner coordinate, no need to adjust
                break;

            /* upper right corner text */
            case THINKIMAGE_WATER_NORTHEAST:
                $x += $this->info['width'] - $w;
                break;

            /* Center text */
            case THINKIMAGE_WATER_CENTER:
                $x += ($this->info['width'] - $w) / 2;
                $y += ($this->info['height'] - $h) / 2;
                break;

            /* Bottom center text */
            case THINKIMAGE_WATER_SOUTH:
                $x += ($this->info['width'] - $w) / 2;
                $y += $this->info['height'] - $h;
                break;

            /* Center right text */
            case THINKIMAGE_WATER_EAST:
                $x += $this->info['width'] - $w;
                $y += ($this->info['height'] - $h) / 2;
                break;

            /* Center text on top */
            case THINKIMAGE_WATER_NORTH:
                $x += ($this->info['width'] - $w) / 2;
                break;

            /* Center left text */
            case THINKIMAGE_WATER_WEST:
                $y += ($this->info['height'] - $h) / 2;
                break;

            default:
               /* custom text coordinates */
                if (is_array($locate)) {
                    list($posx, $posy) = $locate;
                    $x += $posx;
                    $y += $posy;
                } else {
                    throw new \Exception('Unsupported literal position type');
                }
        }

        /* set offset */
        if (is_array($offset)) {
            $offset = array_map('intval', $offset);
            list($ox, $oy) = $offset;
        } else {
            $offset = intval($offset);
            $ox = $oy = $offset;
        }

        /* write text */
        if ('gif' == $this->info['type']) {
            $img = $this->im->coalesceImages();
            $this->im->destroy(); //Destroy the original image
            do {
                $img->annotateImage($draw, $x + $ox, $y + $oy, $angle, $text);
            } while ($img->nextImage());

            //Compress picture
            $this->im = $img->deconstructImages();
            $img->destroy(); //Destroy the zero-hour picture

        } else {
            $this->im->annotateImage($draw, $x + $ox, $y + $oy, $angle, $text);
        }
        $draw->destroy();
    }

    /**
     * Destructor method for destroying image resources
     *
     */
    public function __destruct()
    {
        empty($this->im) || $this->im->destroy();
    }

}
