<?php
/**
 * COPS (Calibre OPDS PHP Server) class file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Sébastien Lucas <sebastien@slucas.fr>
 */

use SebLucas\Cops\Input\Request;
use SebLucas\Cops\Output\EPubReader;

require_once __DIR__ . '/config.php';

if (php_sapi_name() === 'cli') {
    return;
}

$request = new Request();
$idData = (int) $request->get('data', null);
if (empty($idData)) {
    $request->notFound();
    return;
}
$component = $request->get('comp', null);
if (empty($component)) {
    $request->notFound();
    return;
}

try {
    $data = EPubReader::getContent($idData, $component, $request);

    $expires = 60*60*24*14;
    header('Pragma: public');
    header('Cache-Control: maxage='.$expires);
    header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');

    echo $data;
} catch (Exception $e) {
    error_log($e);
    $request->notFound();
}
