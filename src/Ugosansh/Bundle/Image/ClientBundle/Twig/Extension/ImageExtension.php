<?php

namespace Ugosansh\Bundle\Image\ClientBundle\Twig\Extension;

use Twig_Extension;
use Ugosansh\Component\Image\ImageInterface;
use Ugosansh\Bundle\Image\ClientBundle\Manager\ImageManager;

/**
 * Image twig extension
 */
class ImageExtension extends Twig_Extension
{
    /**
     * @var ImageManager
     */
    protected $manager;

    /**
     * @var string
     */
    protected $default;

    /**
     * __construct
     *
     * @param ImageManager
     */
    public function __construct(ImageManager $manager = null, $default = '')
    {
        $this->manager = $manager;
        $this->default = $default;
    }

    /**
     * Get twig extension name
     *
     * @return string
     */
    public function getName()
    {
        return 'ugosansh_image';
    }

    /**
     * getFunctions
     *
     * @return array
     */
    public function getFunctions()
    {
        return array(
            'image_path' => new \Twig_Function_Method($this, 'getUrl',
                array('is_safe' => array('js', 'html'))
            ),
            'image_render' => new \Twig_Function_Method($this, 'getImageTag',
                array('is_safe' => array('js', 'html'))
            ),
            'image_render_no_specific_size' => new \Twig_Function_Method($this, 'getImageTagNoSpecificSize',
                array('is_safe' => array('js', 'html'))
            ),
            'image_default' => new \Twig_Function_Method($this, 'getDefaultImageTag',
                array('is_safe' => array('js', 'html'))
            )
        );
    }

    /**
     * Generate img html tag
     *
     * @param array $attributes
     *
     * @return string
     */
    public function generateTag(array $attributes)
    {
        $render = '';

        foreach ($attributes as $name => $value) {
            $render .= sprintf('%s="%s"', $name, $value);
        }

        return sprintf('<img %s />', $render);
    }

    /**
     * render
     *
     * @param ImageInterface
     *
     * @return string
     */
    public function render(ImageInterface $image, array $attr = [])
    {
        $attr = array_merge([
            'src'    => $image->getLink('url'),
            'alt'    => $image->getTitle(),
            'width'  => $image->getWidth(),
            'height' => $image->getHeight()
        ], $attr);

        return $this->generateTag($attr);
    }

    /**
     * render without specific size
     *
     * @param ImageInterface
     *
     * @return string
     */
    public function renderWithouSize(ImageInterface $image, array $attr = [])
    {
        $attr = array_merge([
            'src'    => $image->getLink('url'),
            'alt'    => $image->getTitle(),
        ], $attr);

        return $this->generateTag($attr);
    }

    /**
     * Create img html tag
     *
     * @param integer $id
     * @param mixed   $width  integer|null
     * @param mixed   $height integer|null
     * @param mixed   $crop   integer|null
     * @param array   $attr   Optional attributes
     *
     * @return string
     */
    public function getImageTag($id, $width = null, $height = null, $crop = null, array $attr = [])
    {
        if ($image = $this->manager->getInfo($id, $width, $height, $crop)) {
            return $this->render($image, $attr);
        }

        $attr['src'] = $this->default;

        if (!is_null($width) && !is_null($height)) {
            $attr['width'] = $width;
            $attr['height'] = $height;
        }

        return $this->generateTag($attr);
    }

    /**
     * Create img html tag without size 
     *
     * @param integer $id
     *
     * @return string
     */
    public function getImageTagNoSpecificSize($id)
    {
        if ($image = $this->manager->getInfo($id)) {
            return $this->renderWithouSize($image);
        }

        $attr['src'] = $this->default;

        return $this->generateTag($attr);
    }

    /**
     * Create img html tag
     *
     * @param array $attr Optional attributes
     *
     * @return string
     */
    public function getDefaultImageTag(array $attr = [])
    {
        $attr['src'] = $this->default;

        return $this->generateTag($attr);
    }

    /**
     * Get image url
     *
     * @param integer $id
     * @param mixed   $width     integer|null
     * @param mixed   $height    integer|null
     * @param mixed   $crop      integer|null
     * @param mixed   $extension string|null
     *
     * @return string
     */
    public function getUrl($id, $width = null, $height = null, $crop = null, $extension = null)
    {
        if (!is_null($width) && !is_null($height) && !is_null($crop) && !is_null($extension)) {
            return $this->manager->generateUrl($id, $width, $height, $crop, $extension);
        }

        if ($url = $this->manager->getUrl($id, $width, $height, $crop)) {
            return $url;
        }

        return $this->default;
    }

    /**
     * Set image manager
     *
     * @param ImageManager $manager
     *
     * @return ImageExtension
     */
    public function setManager(ImageManager $manager)
    {
        $this->manager = $manager;

        return $this;
    }

    /**
     * Set default image path
     *
     * @param string $default
     *
     * @return ImageExtension
     */
    public function setDefault($default)
    {
        $this->default = $default;

        return $this;
    }

}
