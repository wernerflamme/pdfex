<?php
/**
 * Options for the pdfex plugin
 */

// the first parameter is set to 1 what means that $conf['pdfex_author'] is used inside the PDF
// as the author's name. As soon as I understand how to access the pages' metadata, there will be
// another valid option to use the "real" author of that page; $conf['pdfex_author'] will be used
// as a fallback solution
$conf['pdfex_c_auth'] = 1;               // use constant author?
$conf['pdfex_author'] = 'Werner Flamme'; // Name of author of current page - e. g. YOUR name :-)

// what do we want as the page title?
$conf['pdfex_title']  = $conf['title'];  // Title line of the PDF

// do we want to use tcpdf or html2fpdf?
$conf['pdfex_method'] = 'html2fpdf';     // at the moment, you may use 'tcpdf' or 'html2fpdf'

//Setup VIM: ex: et ts=2 enc=utf-8 :