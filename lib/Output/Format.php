<?php
/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 * @author     mikespub
 */

namespace SebLucas\Cops\Output;

use SebLucas\Cops\Input\Config;
use SebLucas\Template\doT;
use DOMDocument;

class Format
{
    /**
     * This method is a direct copy-paste from
     * http://tmont.com/blargh/2010/1/string-format-in-php
     */
    public static function str_format($format)
    {
        $args = func_get_args();
        $format = array_shift($args);

        preg_match_all('/(?=\{)\{(\d+)\}(?!\})/', $format, $matches, PREG_OFFSET_CAPTURE);
        $offset = 0;
        foreach ($matches[1] as $data) {
            $i = $data[0];
            $format = substr_replace($format, @$args[(int)$i], $offset + $data[1] - 1, 2 + strlen($i));
            $offset += strlen(@$args[(int)$i]) - 2 - strlen($i);
        }

        return $format;
    }

    public static function addURLParam($urlParams, $paramName, $paramValue)
    {
        if (empty($urlParams)) {
            $urlParams = '';
        }
        $start = '';
        if (preg_match('#^\?(.*)#', $urlParams, $matches)) {
            $start = '?';
            $urlParams = $matches[1];
        }
        $params = [];
        parse_str($urlParams, $params);
        if (empty($paramValue) && strval($paramValue) !== "0") {
            unset($params[$paramName]);
        } else {
            $params[$paramName] = $paramValue;
        }
        return $start . http_build_query($params);
    }

    public static function addDatabaseParam($urlParams, $database)
    {
        if (!is_null($database)) {
            $urlParams = self::addURLParam($urlParams, 'db', $database);
        }
        return $urlParams;
    }

    public static function addVersion($url)
    {
        if (strpos($url, '?') !== false) {
            $url .= '&v=' . Config::VERSION;
        } else {
            $url .= '?v=' . Config::VERSION;
        }
        return $url;
    }

    public static function serverSideRender($data, $theme = 'default')
    {
        // Get the templates
        $header = file_get_contents('templates/' . $theme . '/header.html');
        $footer = file_get_contents('templates/' . $theme . '/footer.html');
        $main = file_get_contents('templates/' . $theme . '/main.html');
        $bookdetail = file_get_contents('templates/' . $theme . '/bookdetail.html');
        $page = file_get_contents('templates/' . $theme . '/page.html');

        // Generate the function for the template
        $template = new doT();
        $dot = $template->template($page, ['bookdetail' => $bookdetail,
                                                  'header' => $header,
                                                  'footer' => $footer,
                                                  'main' => $main]);
        // If there is a syntax error in the function created
        // $dot will be equal to FALSE
        if (!$dot) {
            return false;
        }
        // Execute the template
        if (!empty($data)) {
            return $dot($data);
        }

        return null;
    }

    public static function xml2xhtml($xml)
    {
        return preg_replace_callback('#<(\w+)([^>]*)\s*/>#s', function ($m) {
            $xhtml_tags = ['br', 'hr', 'input', 'frame', 'img', 'area', 'link', 'col', 'base', 'basefont', 'param'];
            if (in_array($m[1], $xhtml_tags)) {
                return '<' . $m[1] . $m[2] . ' />';
            } else {
                return '<' . $m[1] . $m[2] . '></' . $m[1] . '>';
            }
        }, $xml);
    }

    public static function display_xml_error($error)
    {
        $return = '';
        $return .= str_repeat('-', $error->column) . "^\n";

        switch ($error->level) {
            case LIBXML_ERR_WARNING:
                $return .= 'Warning ' . $error->code . ': ';
                break;
            case LIBXML_ERR_ERROR:
                $return .= 'Error ' . $error->code . ': ';
                break;
            case LIBXML_ERR_FATAL:
                $return .= 'Fatal Error ' . $error->code . ': ';
                break;
        }

        $return .= trim($error->message) .
                "\n  Line: " . $error->line .
                "\n  Column: " . $error->column;

        if ($error->file) {
            $return .= "\n  File: " . $error->file;
        }

        return "$return\n\n--------------------------------------------\n\n";
    }

    public static function are_libxml_errors_ok()
    {
        $errors = libxml_get_errors();

        foreach ($errors as $error) {
            if ($error->code == 801) {
                return false;
            }
        }
        return true;
    }

    public static function html2xhtml($html)
    {
        $doc = new DOMDocument();
        libxml_use_internal_errors(true);

        $doc->loadHTML('<html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"></head><body>' .
                            $html  . '</body></html>'); // Load the HTML
        $output = $doc->saveXML($doc->documentElement); // Transform to an Ansi xml stream
        $output = self::xml2xhtml($output);
        if (preg_match('#<html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"></meta></head><body>(.*)</body></html>#ms', $output, $matches)) {
            $output = $matches [1]; // Remove <html><body>
        }
        /*
        // In case of error with summary, use it to debug
        $errors = libxml_get_errors();

        foreach ($errors as $error) {
            $output .= self::display_xml_error($error);
        }
        */

        if (!self::are_libxml_errors_ok()) {
            $output = 'HTML code not valid.';
        }

        libxml_use_internal_errors(false);
        return $output;
    }
}
