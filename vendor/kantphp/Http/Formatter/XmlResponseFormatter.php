<?php

/**
 * @package KantPHP
 * @author  Zhenqiang Zhang <zhenqiang.zhang@hotmail.com>
 * @copyright (c) KantPHP Studio, All rights reserved.
 * @license http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 */

namespace Kant\Http\Formatter;

use DOMDocument;
use DOMElement;
use DOMText;
use Kant\Foundation\Arrayable;
use Kant\Foundation\Component;
use Kant\Helper\StringHelper;

/**
 * XmlResponseFormatter formats the given data into an XML response content.
 *
 * It is used by [[Response]] to format response data.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class XmlResponseFormatter extends Component implements ResponseFormatterInterface {

    /**
     * @var string the Content-Type header for the response
     */
    public $contentType = 'application/xml';

    /**
     * @var string the XML version
     */
    public $version = '1.0';

    /**
     * @var string the XML encoding. If not set, it will use the value of [[Response::charset]].
     */
    public $encoding;

    /**
     * @var string the name of the root element.
     */
    public $rootTag = 'response';

    /**
     * @var string the name of the elements that represent the array elements with numeric keys.
     */
    public $itemTag = 'item';

    /**
     * @var boolean whether to interpret objects implementing the [[\Traversable]] interface as arrays.
     * Defaults to `true`.
     * @since 2.0.7
     */
    public $useTraversableAsArray = true;

    /**
     * Formats the specified response.
     * @param Response $response the response to be formatted.
     */
    public function format($response) {
        $charset = $this->encoding === null ? $response->charset : $this->encoding;
        if (stripos($this->contentType, 'charset') === false) {
            $response->headers->set('charset', $response->charset);
        }
        $response->headers->set('Content-Type', $this->contentType);
        if ($response->data !== null) {
            $dom = new DOMDocument($this->version, $charset);
            $root = new DOMElement($this->rootTag);
            $dom->appendChild($root);
            $this->buildXml($root, $response->data);
            $response->content = $dom->saveXML();
        }
    }

    /**
     * @param DOMElement $element
     * @param mixed $data
     */
    protected function buildXml($element, $data) {
        if (is_array($data) ||
                ($data instanceof \Traversable && $this->useTraversableAsArray && !$data instanceof Arrayable)
        ) {
            foreach ($data as $name => $value) {
                if (is_int($name) && is_object($value)) {
                    $this->buildXml($element, $value);
                } elseif (is_array($value) || is_object($value)) {
                    $child = new DOMElement(is_int($name) ? $this->itemTag : $name);
                    $element->appendChild($child);
                    $this->buildXml($child, $value);
                } else {
                    $child = new DOMElement(is_int($name) ? $this->itemTag : $name);
                    $element->appendChild($child);
                    $child->appendChild(new DOMText((string) $value));
                }
            }
        } elseif (is_object($data)) {
            $child = new DOMElement(StringHelper::basename(get_class($data)));
            $element->appendChild($child);
            if ($data instanceof Arrayable) {
                $this->buildXml($child, $data->toArray());
            } else {
                $array = [];
                foreach ($data as $name => $value) {
                    $array[$name] = $value;
                }
                $this->buildXml($child, $array);
            }
        } else {
            $element->appendChild(new DOMText((string) $data));
        }
    }

}
