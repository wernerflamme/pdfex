<?php

/**
    \defgroup commonPDF 	Umwandlung HTML zu PDF

    Beinhaltet eine Funktion zur Umwandlung HTML zu PDF mit Hilfe von HTML2FPDF
    (http://html2fpdf.sourceforge.net/, nicht von der Projekthomepage abschrecken lassen)

    **/

/**

	\file cPDF.php
	\brief Umwandlung von HTML-Code (mit separaten CSS-Angaben) in eine PDF-Datei

	Aktuell eine Funktion, weitere bei Bedarf

	\author Werner Flamme <werner.flamme@ufz.de>

	**/

/**

	\ingroup commonPDF

	Diese Funktion wandelt HTML-Code (einschliesslich CSS) in eine PDF-Datei um.
	Die PDF-Datei wird auf dem Dateisystem in DOKU_INC/tmp angelegt und
	steht also zum Download unter DOKU_BASE/tmp zur Verfuegung.
	
	Man kann die Funktion z. B. so einsetzen:
	
	$myhtml = ... ; \n
	$myPDFfile = 'minimax.pdf'; // besser: mit tempnam() erzeugen \n
	$zuPDF = HTMLzuPDF($myhtml, $myPDFfile); \n
	
	dann Direktausgabe: \n
	ini_set('zlib.output_compression', 'Off'); \n
	header("Pragma: public"); \n
	header("Content-Type: application/force-download"); \n
	if (preg_match('/MSIE 5.5/', $_SERVER['HTTP_USER_AGENT']) || 
		preg_match('/MSIE 6.0/', $_SERVER['HTTP_USER_AGENT'])) { \n
		header("Content-Disposition: filename=\"$myPDFfile\""); \n
	} \n
	else { \n
		header("Content-Disposition: attachment; filename=\"$myPDFfile\""); \n
	} \n
	header("Content-Length: $zuPDF"); \n
	readfile(DOKU_INC. "tmp/$myPDFfile"); \n
	
	oder man baut einen Link auf DOKU_BASE . "tmp/$myPDFfile" (zum Download):
	
	$myhtml .= '\<p class="text">\<a href="' . DOKU_BASE . "tmp/$myPDFfile" . '">Ausgabe als PDF-Datei\</a>\</p>';
	
	
	@param $daten		String, der den umzuwandelnden HTML-Code enthaelt
	@param $filename	Dateiname, unter dem die PDF-Datei abgelegt wird; Default: 'result_{$adm['sid']}.pdf'
	@param $css			Dateiname (der mit fopen erreicht werden kann) mit CSS-Anweisungen;
	default: 'webdev.css' im Verzeichnis dieser Funktion
	@param $orientation	Ausrichtung; 'P' oder 'portrait' Hochformat, 'L' oder 'landscape' Querformat
	(Gross- und Kleinschreibung unerheblich)
	@param $unit		Masseinheit fuer Groessenangaben; default 'mm' = Millimeter, weiterhin
	moeglich: 'pt' (Point, 1/72 Zoll), 'cm' (Zentimeter), 'in' (Inch, Zoll)
	@param $format		"Papier-"format der PDF-Datei; default 'A4'; weiterhin moeglich:
	'A3', 'A5', 'Letter', 'Legal' (Gross- und Kleinschreibung unerheblich)
	
	@return Zahl der geschriebenen Bytes (= Dateigroesse)
	
	**/
function HTMLzuPDF($Daten, $filename = 'default.pdf', $css = 'webdev.css', $orientation = 'P', $unit = 'mm', $format = 'A4')
{
	define('FPDF_FONTPATH', (dirname(__FILE__) . '/font/') );

	if (!class_exists('HTML2FPDF')) {
    	require_once(dirname(__FILE__) . '/html2fpdf.class.php');
	} // if (!class_exists('HTML2FPDF'))
	
	// Ausgabedatei ermitteln
	$wrkfilnam = strtolower($filename);
	if (substr($filename, -4) != '.pdf')
		$wrkfilnam .= '.pdf';
	if ($wrkfilnam == 'default.pdf')
		$wrkfilnam = tempnam(DOKU_INC . '/tmp', "PDF_");

	// CSS-Daten ermitteln (Datei in String einlesen)
	$myCSSfile = ($css == 'webdev.css') ? (dirname(__FILE__) . '/webdev.css') : $css;
	$myCSSdata = @file_get_contents($myCSSfile);
	$myCSSsize = strlen($myCSSdata);
	if ($myCSSsize == 0) {
		$myCSSfile = dirname(__FILE__) . '/webdev.css';
		$myCSSdata = @file_get_contents($myCSSfile);
	} // if ($myCSSsize == 0)

	// PDF erzeugen, Datei ablegen
	$myPDF = new HTML2FPDF($orientation, $unit, $format);
	$myPDF->Open();
	$myPDF->AddPage();
	$myPDF->ShowNOIMG_GIF(true);
	$myPDF->ReadCSS($myCSSdata);
	$myPDF->WriteHTML($Daten);
	$dummy = $myPDF->Output("$wrkfilnam", 'F');
	
	$fillen = @filesize("$wrkfilnam");
	return $fillen;
} // function HTMLzuPDF

?>
