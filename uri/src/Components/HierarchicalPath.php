<?php
/**
 * League.Uri (http://uri.thephpleague.com)
 *
 * @package   League.uri
 * @author    Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @copyright 2013-2015 Ignace Nyamagana Butera
 * @license   https://github.com/thephpleague/uri/blob/master/LICENSE (MIT License)
 * @version   4.2.0
 * @link      https://github.com/thephpleague/uri/
 */
namespace League\Uri\Components;

use InvalidArgumentException;
use League\Uri\Interfaces\HierarchicalComponent;
use League\Uri\Interfaces\HierarchicalPath as HierarchicalPathInterface;

/**
 * Value object representing a URI path component.
 *
 * @package League.uri
 * @author  Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since   1.0.0
 */
class HierarchicalPath extends AbstractHierarchicalComponent implements HierarchicalPathInterface
{
    use PathTrait;

    /**
     * @inheritdoc
     */
    protected static $separator = '/';

    /**
     * @inheritdoc
     */
    protected static $invalidCharactersRegex = ',[?#],';

    /**
     * New Instance
     *
     * @param string $path
     */
    public function __construct($path = '')
    {
        $path = $this->validateString($path);
        $this->assertValidComponent($path);
        $this->isAbsolute = self::IS_RELATIVE;
        if (static::$separator == mb_substr($path, 0, 1, 'UTF-8')) {
            $this->isAbsolute = self::IS_ABSOLUTE;
            $path = mb_substr($path, 1, mb_strlen($path), 'UTF-8');
        }

        $append_delimiter = false;
        if (static::$separator === mb_substr($path, -1, 1, 'UTF-8')) {
            $path = mb_substr($path, 0, -1, 'UTF-8');
            $append_delimiter = true;
        }

        $this->data = $this->validate($path);
        if ($append_delimiter) {
            $this->data[] = '';
        }
    }

    /**
     * validate the submitted data
     *
     * @param string $data
     *
     * @return array
     */
    protected function validate($data)
    {
        $filterSegment = function ($segment) {
            return isset($segment);
        };

        $data = $this->decodePath($data);

        return array_filter(explode(static::$separator, $data), $filterSegment);
    }

    /**
     * Retrieves a single path segment.
     *
     * Retrieves a single path segment. If the segment offset has not been set,
     * returns the default value provided.
     *
     * @param string $offset  the segment offset
     * @param mixed  $default Default value to return if the offset does not exist.
     *
     * @return mixed
     */
    public function getSegment($offset, $default = null)
    {
        if (isset($this->data[$offset])) {
            return $this->data[$offset];
        }

        return $default;
    }

    /**
     * @inheritdoc
     */
    public static function __set_state(array $properties)
    {
        return static::createFromArray($properties['data'], $properties['isAbsolute']);
    }

    /**
     * @inheritdoc
     */
    public function getContent()
    {
        $front_delimiter = '';
        if ($this->isAbsolute == self::IS_ABSOLUTE) {
            $front_delimiter = static::$separator;
        }

        return $front_delimiter.$this->encodePath(implode(static::$separator, $this->data));
    }

    /**
     * Returns an instance with the specified component appended
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the modified component with the appended data
     *
     * @param HierarchicalComponent|string $component the component to append
     *
     * @return static
     */
    public function append($component)
    {
        $source = $this->toArray();
        if (!empty($source) && '' === end($source)) {
            array_pop($source);
        }

        return $this->newCollectionInstance(array_merge(
            $source,
            $this->validateComponent($component)->toArray()
        ));
    }

    /**
     * Returns the path basename
     *
     * @return string
     */
    public function getBasename()
    {
        $data = $this->data;

        return (string) array_pop($data);
    }

    /**
     * Returns parent directory's path
     *
     * @return string
     */
    public function getDirname()
    {
        return str_replace(
            ['\\', "\0"],
            [static::$separator, '\\'],
            dirname(str_replace('\\', "\0", $this->__toString()))
        );
    }

    /**
     * Returns the basename extension
     *
     * @return string
     */
    public function getExtension()
    {
        list($basename, ) = explode(';', $this->getBasename(), 2);

        return pathinfo($basename, PATHINFO_EXTENSION);
    }

    /**
     * Returns an instance with the specified basename extension
     *
     * This method MUST retain the state of the current instance, and return
     * an instance that contains the extension basename modified.
     *
     * @param string $extension the new extension
     *                          can preceeded with or without the dot (.) character
     *
     * @throws InvalidArgumentException If the extension is invalid
     *
     * @return static
     */
    public function withExtension($extension)
    {
        $extension = $this->formatExtension($extension);
        $segments = $this->toArray();
        $basename = array_pop($segments);
        $parts = explode(';', $basename, 2);
        $basenamePart = array_shift($parts);
        if ('' === $basenamePart || is_null($basenamePart)) {
            return $this;
        }

        $newBasename = $this->buildBasename($basenamePart, $extension, array_shift($parts));
        if ($basename === $newBasename) {
            return $this;
        }
        $segments[] = $newBasename;

        return $this->newCollectionInstance($segments);
    }

    /**
     * create a new basename with a new extension
     *
     * @param string $basenamePart  the basename file part
     * @param string $extension     the new extension to add
     * @param string $parameterPart the basename parameter part
     *
     * @return string
     */
    protected function buildBasename($basenamePart, $extension, $parameterPart)
    {
        $length = mb_strrpos($basenamePart, '.'.pathinfo($basenamePart, PATHINFO_EXTENSION), 'UTF-8');
        if (false !== $length) {
            $basenamePart = mb_substr($basenamePart, 0, $length, 'UTF-8');
        }

        $parameterPart = trim($parameterPart);
        if ('' !== $parameterPart) {
            $parameterPart = ";$parameterPart";
        }

        $extension = trim($extension);
        if ('' !== $extension) {
            $extension = ".$extension";
        }

        return $basenamePart.$extension.$parameterPart;
    }

    /**
     * validate and format the given extension
     *
     * @param string $extension the new extension to use
     *
     * @throws InvalidArgumentException If the extension is not valid
     *
     * @return string
     */
    protected function formatExtension($extension)
    {
        if (0 === strpos($extension, '.')) {
            throw new InvalidArgumentException('an extension sequence can not contain a leading `.` character');
        }

        if (strpos($extension, static::$separator)) {
            throw new InvalidArgumentException('an extension sequence can not contain a path delimiter');
        }

        return implode(static::$separator, $this->validate($extension));
    }
}
