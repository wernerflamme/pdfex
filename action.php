<?php
/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Werner Flamme <w.flamme@web.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC'))
  die();

if (!defined('DOKU_PLUGIN'))
  define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once(DOKU_PLUGIN . 'action.php');

if (!defined('DOKU_PDFEXDIR'))
  define('DOKU_PDFEXDIR', DOKU_INC . 'data/pdfex/');

if ( !is_dir(DOKU_PDFEXDIR) ) {
    @mkdir(DOKU_PDFEXDIR, 0775);
    if ($fh = @fopen(DOKU_PDFEXDIR . '.htaccess', 'w') ) {
      @fwrite($fh, "order allow,deny\n");
      @fwrite($fh, "allow from all\n");
      @fclose($fh);
    } // if ($fh)
} // if ( !is_dir(DOKU_PDFEXDIR) )

if (!defined('NL'))
  define('NL',"\n");

class action_plugin_pdfex extends DokuWiki_Action_Plugin
{

  /**
   * Return some info
   */
  function getInfo(){
    return array(
      'author' => 'Werner Flamme',
      'email'  => 'w.flamme@web.de',
      'date'   => '2006-09-24',
      'name'   => 'PDF Export Plugin',
      'desc'   => 'Creates PDF files with the pages\' content',
      'url'    => 'http://wiki:splitbrain.org/user:wflamme:pdfex',
    );
  } // function getInfo

  /**
   * Register the eventhandlers
   */
  function register(&$contr){
    $contr->register_hook('TPL_CONTENT_DISPLAY',
                          'BEFORE',
                          $this,
                          'handle_act_pdfex',
                           array());
  } // function register


  /**
    function to fuss aaround a bit and prepare something for TCPDF
  **/
  function handle_act_pdfex(&$event, $param)
  {
    global $ACT;
    global $ID;
    global $conf;
    
    require_once('conf/default.php');

//print_r($conf);

    if (strtolower($ACT) != 'show')
      return; // nothing to do for us

    // well now, let's look at the data and convert it into a pdf file:
    $filewiki = wikiFN($ID);
    $pdfresultfile = DOKU_PDFEXDIR . 'PDF_' . str_replace(':', '_', $ID) . '.pdf';
    $create_pdffile = true;
    if ( @file_exists($pdfresultfile) ) {
      $create_pdffile = ( filectime($filewiki) > filectime($pdfresultfile) );
      // if the wiki page is newer than the PDF, we have to recreate the PDF
    } // if ( @file_exists($pdfresultfile) )
//echo "<hr />pdfresultfile=$pdfresultfile<br />\n";
//echo "TS PDF=", date('Y-m-d H:i:s', filectime($pdfresultfile)), "<br />\n";
//echo "PDFEX_METHOD={$conf['pdfex_method']}!<br />\n";
//echo "filewiki=$filewiki<br />\n";
//echo "TS Wiki=", date('Y-m-d H:i:s', filectime($filewiki)), "<hr /><br />\n";
    if ($create_pdffile === true) {
      $myhtml = $event->data;
      $paraArr = array( 'OUTPUT_NAME' => $pdfresultfile);
      $paraArr['PDF_AUTHOR'] = ($conf['pdfex_c_auth'] == 1) ? $conf['pdfex_author'] : $this->_getAuthorFromMeta($ID);
      switch ($conf['pdfex_method']) {
        case 'tcpdf':
          $paraArr['PDF_HEADER_STRING'] = "$ID";
          $paraArr['PDF_HEADER_TITLE']  = $conf['pdfex_title'];
          $myPDFsize = $this->_tcPDF($myhtml, $paraArr);
          break;
        case 'html2fpdf':
          $myhtml = utf8_decode($myhtml); // not needed for TCPDF
          $myPDFsize = $this->_html2fPDF($myhtml, $paraArr);
          break;
      } // switch (PDFEX_METHOD)
    } // if ($create_pdffile === true)
  } // function handle_act_pdfex

  /** 
    presently a dummy function
    @param $pageID page ID to get the author of
  **/
  function _getAuthorFromMeta($pageID)
  {
    return $conf['pdfex_author'];
  } // function _createtempfile

  /**
    transforms HTML-Code (without CSS) into a PDF file
    
    @param $htmlcode    HTML-Code to be transformed
    @param $aParam      Array with output parameters
    
  **/
  function _tcPDF($htmlcode, $aParam)
  {

    require_once(dirname(__FILE__) . '/tcpdf/tcpdf.php');
    include_once(dirname(__FILE__) . '/tcpdf/config/lang/eng.php');
    $defltParam = array(
      'K_PATH_MAIN'           => $_SERVER['SERVER_HOME'] . '/',
      'K_PATH_URL'            => 'http://' . $_SERVER['SERVER_NAME'] . '/',
      'K_PATH_TCPDF'          => dirname(__FILE__) . '/tcpdf/',
      'DOC_TITLE'             => 'Document Title',
      'DOC_SUBJECT'           => 'Document Description',
      'DOC_KEYWORDS'          => 'Document keywords',
      'HTML_IS_UNICODE'       => true,
      'HTML_HAS_ENCODING'     => 'UTF-8',
      'PDF_PAGE_ORIENTATION'  => 'portrait',
      'PDF_PAGE_FORMAT'       => 'A4',
      'PDF_CREATOR'           => 'TCPDF (http://tcpdf.sf.net)',
      'PDF_AUTHOR'            => 'anonymous',
      'PDF_HEADER_TITLE'      => 'Title in header line',
      'PDF_HEADER_STRING'     => "rest of\nheader line",
      'PDF_HEADER_LOGO'       => 'logo_example.png',
      'PDF_HEADER_LOGO_WIDTH' => 20,
      'PDF_UNIT'              => 'mm',
      'PDF_MARGIN_HEADER'     =>  5,
      'PDF_MARGIN_FOOTER'     => 10,
      'PDF_MARGIN_TOP'        => 27,
      'PDF_MARGIN_BOTTOM'     => 25,
      'PDF_MARGIN_LEFT'       => 15,
      'PDF_MARGIN_RIGHT'      => 15,
      'PDF_FONT_NAME_MAIN'    => 'FreeSans',
      'PDF_FONT_SIZE_MAIN'    => 10,
      'PDF_FONT_NAME_DATA'    => 'FreeSerif',
      'PDF_FONT_SIZE_DATA'    =>  8,
      'PDF_IMAGE_SCALE_RATIO' =>  4,
      'HEAD_MAGNIFICATION'    =>  1.1,
      'K_CELL_HEIGHT_RATIO'   =>  1.25,
      'K_TITLE_MAGNIFICATION' =>  1.3,
      'K_SMALL_RATIO'         =>  (2/3),
      'OUTPUT_NAME'           => 'default.pdf',
      'OUTPUT_DEST'           => 'F'
    );
    $defltParam['FPDF_FONTPATH']    = $defltParam['K_PATH_TCPDF']  . 'fonts/';
    $defltParam['K_PATH_CACHE']     = $defltParam['K_PATH_MAIN']   . 'cache/';
    $defltParam['K_PATH_URL_CACHE'] = $defltParam['K_PATH_URL']    . 'cache/';
    $defltParam['K_PATH_IMAGES']    = $defltParam['K_PATH_TCPDF']  . 'images/';
    $defltParam['K_BLANK_IMAGE']    = $defltParam['K_PATH_IMAGES'] . '_blank.jpg';
    $allowedParms = array_keys($defltParam);

    foreach ($aParam as $pName => $pValue) {
        if (in_array(strtoupper($pName), $allowedParms))
            $defltParam[$pName] = $pValue;
    } // foreach ($aParam as $pName => $pValue)

    define('FPDF_FONTPATH',       $defltParam['FPDF_FONTPATH']);
    define('K_PATH_IMAGES',       $defltParam['K_PATH_IMAGES']);
    define('K_BLANK_IMAGE',       $defltParam['K_BLANK_IMAGE']);
    define('K_CELL_HEIGHT_RATIO', $defltParam['K_CELL_HEIGHT_RATIO']);
    define('K_PATH_CACHE',        $defltParam['K_PATH_CACHE']);
    define('K_PATH_URL_CACHE',    $defltParam['K_PATH_URL_CACHE']);
    define('K_SMALL_RATIO',       $defltParam['K_SMALL_RATIO']);
    $pdf = new TCPDF(   $defltParam['PDF_PAGE_ORIENTATION'],
                        $defltParam['PDF_UNIT'],
                        $defltParam['PDF_PAGE_FORMAT'],
                        $defltParam['HTML_IS_UNICODE'],
                        $defltParam['HTML_HAS_ENCODING']
                    );
    // set document information
    $pdf->SetCreator(   $defltParam['PDF_CREATOR']);
    $pdf->SetAuthor(    $defltParam['PDF_AUTHOR']);
    $pdf->SetTitle(     $defltParam['DOC_TITLE']);
    $pdf->SetSubject(   $defltParam['DOC_SUBJECT']);
    $pdf->SetKeywords(  $defltParam['DOC_KEYWORDS']);
    $pdf->SetHeaderData($defltParam['PDF_HEADER_LOGO'],
                        $defltParam['PDF_HEADER_LOGO_WIDTH'],
                        $defltParam['PDF_HEADER_TITLE'],
                        $defltParam['PDF_HEADER_STRING']
                       );
    //set margins
    $pdf->SetMargins(   $defltParam['PDF_MARGIN_LEFT'],
                        $defltParam['PDF_MARGIN_TOP'],
                        $defltParam['PDF_MARGIN_RIGHT']
                    );
    //set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, $defltParam['PDF_MARGIN_BOTTOM']);
    $pdf->SetHeaderMargin($defltParam['PDF_MARGIN_HEADER']);
    $pdf->SetFooterMargin($defltParam['PDF_MARGIN_FOOTER']);
    //set image scale factor
    $pdf->setImageScale($defltParam['PDF_IMAGE_SCALE_RATIO']);

    $pdf->setHeaderFont( array( $defltParam['PDF_FONT_NAME_MAIN'],
                                '',
                                $defltParam['PDF_FONT_SIZE_MAIN']
                              )
                        );
    $pdf->setFooterFont( array( $defltParam['PDF_FONT_NAME_DATA'],
                                '',
                                $defltParam['PDF_FONT_SIZE_DATA']
                              )
                        );

    $pdf->setLanguageArray($l); //set language items

    //initialize document
    $pdf->AliasNbPages();

    // debug 1 start
    $f1 = fopen('dings1.txt', 'w');
    fputs($f1, $htmlcode);
    fclose($f1);
    // debug 1 end
    $repl_more = array('href', 'src', 'action');
    foreach ($repl_more as $to_replace) {
        $repl_searchfor = '|' . $to_replace . '="/~(.*?)/(.*?)"|';
        $repl_replwith  = $to_replace . '="file:///home/$1/public_html/$2"';
        $htmlcode = preg_replace($repl_searchfor, $repl_replwith, $htmlcode);
        $repl_searchfor = '|' . $to_replace . '="/(.*?)"|';
        $repl_replwith = $to_replace . '="' . DOKU_INC . '$1"';
        $htmlcode = preg_replace($repl_searchfor, $repl_replwith, $htmlcode);
    } // foreach ($repl_more as $to_replace)
    $htmlcode = str_replace('file:///', '/', $htmlcode);
    // debug 2 start
    $f2 = fopen('dings2.txt', 'w');
    fputs($f2, $htmlcode);
    fclose($f2);
    // debug 2 end
    $repl_searchfor = '/src="' . preg_quote(DOKU_INC, '/') . 'lib\/exe\/fetch.php?(.*?)media=(.+?)"/';
//    echo "<br />Suche nach $repl_searchfor<br />\n";
    $matches = array();
    while ( preg_match($repl_searchfor, $htmlcode, $matches) == 1 ) {
      $htmlcode = str_replace($matches[0], ('src="'. DOKU_INC . 'data/media/' .
                  str_replace(':', '/', $matches[2]) . '"'), $htmlcode);
    } // while ( preg_match($repl_searchfor, $htmlcode, $matches) )
    // debug 3 start
    $f3 = fopen('dings3.txt', 'w');
    fputs($f3, $htmlcode);
    fclose($f3);
    // debug 3 end

    $pdf->AddPage();
    $pdf->WriteHTML($htmlcode);
    $pdf->Output(   $defltParam['OUTPUT_NAME'],
                    $defltParam['OUTPUT_DEST']
                );
    chmod($defltParam['OUTPUT_NAME'], 0664);
    return filesize($defltParam['OUTPUT_NAME']);
  } // function _tcPDF

  function _html2fPDF($htmlcode, $aParam)
  {
    define('FPDF_FONTPATH', (dirname(__FILE__) . '/html2fpdf/font/') );
    require_once(dirname(__FILE__) . '/html2fpdf/html2fpdf.class.php');

    $defltParam = array(
      'PDF_PAGE_ORIENTATION'  => 'portrait',
      'PDF_PAGE_FORMAT'       => 'A4',
      'PDF_UNIT'              => 'mm',
      'PDF_AUTHOR'            => 'anonymous',
      'OUTPUT_NAME'           => 'default.pdf',
      'OUTPUT_DEST'           => 'F'
                       );
    $allowedParms = array_keys($defltParam);

    foreach ($aParam as $pName => $pValue) {
        if (in_array(strtoupper($pName), $allowedParms))
            $defltParam[$pName] = $pValue;
    } // foreach ($aParam as $pName => $pValue)
    $pdf = new HTML2FPDF( $defltParam['PDF_PAGE_ORIENTATION'],
                          $defltParam['PDF_UNIT'],
                          $defltParam['PDF_PAGE_FORMAT']
                        );
    $pdf->Open();
    $pdf->AddPage();
    $pdf->ShowNOIMG_GIF(false);
    $pdf->WriteHTML($htmlcode);
    $pdf->Output(   $defltParam['OUTPUT_NAME'],
                    $defltParam['OUTPUT_DEST']
                );
    chmod($defltParam['OUTPUT_NAME'], 0664);
    return filesize($defltParam['OUTPUT_NAME']);
  } // function _html2fPDF($htmlcode, $aParam)

} // class action_plugin_pdfex

//Setup VIM: ex: et ts=4 enc=utf-8 :
