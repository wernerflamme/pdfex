<?php
/**
 * Metadata for configuration manager plugin
 * Additions for the pdfex plugin
 *
 * @author    Werner Flamme <w.flamme@web.de>
 */

$meta['pdfex_c_auth'] = array('onoff');
$meta['pdfex_author'] = array('string');
$meta['pdfex_title']  = array('string');
$meta['pdfex_method'] = array('multichoice', '_choices' => array('html2fpdf', 'tcpdf') );

//Setup VIM: ex: et ts=2 enc=utf-8 :
