<?php
/** @global string $gRequestMethod */
$gRequestMethod=0;                              //HTTP Request Method 0-CURL/1-Socket

/** @global array $gaCrwValidCodes */
$gaCrwValidCodes=array('100','200','201','202','203','204','205','206','300','301','302','303','304','305','306','307'); //Valid Server Responce Codes

/** @global flag $gSetDayLghtSave */
$gSetDayLghtSave=true;                          //Determine Day Light Save Time [true] or don't [false]

/** @global integer $gCurUTC */
$gCurUTC=3600*2;                                //Current User's UTC

/** @ignore @global string $gUTC */
$gUTC=0;                                      //Server's UTC (estimated in secondes)

//Include functions
include('functions.inc.php');

//Limit items
$tLimit = 20;

$url = isset($HTTP_GET_VARS['link'])?$HTTP_GET_VARS['link']:'';
if(empty($url) || !htValidURL($url)){
  exit;
}

$enc = 'UTF-8';
$gTimeout = 10;
$gHCRLF = "\n";

$run=0;
$out = true;
$return = '';

if (!file_exists('data')) {
  while ($out===true && $run < 5) {

    $Cont = htGetPageContent($url, '', 'GET', true, 2, 2);
    
    $return = $Cont['Content'];
    $headers = htGetResponseHeaders($Cont['Header']);

    /*echo '<xmp>';
    echo print_r($Cont);
    echo '</xmp>';*/
    
    if (isset($headers['Location']) && !empty($headers['Location'])) {
      $url = trim($headers['Location']);
      //echo 'Location: '.$url.'<BR>';
      continue;
    }

    //Check feed tag
    preg_match("/\?>[\s\S]{0,10}<(rss|rdf|feed)[^>]*>/im", $return, $areg);

    if (strpos($return,'<?xml')===false || !isset($areg[1])) {
      preg_match('/^.*<link.*application\/(rss|rdf|atom)\+xml.*?>/imU',$return,$aRes);

      if (!empty($aRes) && !empty($aRes[0])) {
        preg_match('/href=(\'|"| )+(.*)(\'|"| )+/imU',$aRes[0],$aRes);

        $run++;
        if (isset($aRes[2]) && !empty($aRes[2])) {
          $url = $aRes[2];
          continue;
        }
      }

      break;
    }else {
      $out=false;
    }
  }

}else {
  $fp = fopen('data','r');
  while (!feof($fp)) {
    $return .= fread($fp, 4096);
  }
  fclose($fp);
}

//Remove <xml> tag
$format = '';
if(preg_match("/<(rss|rdf|feed)[^>]*>/i", $return, $areg)){
  if (isset($areg[1])) {
    $format = $areg[1];
  }

  $pos=strpos($return, '<'.$format);
  if($pos!==false){
    $return=substr($return, $pos);
  }
}

$return=preg_replace("/<img[^>]*>/i", '', $return);
list($aChannel, $aFeed) = xmParseFeedArray(basexml2array($return));

$tData = array();
$cnt = 0;
foreach ($aFeed as $key=>$value) {
  if ($cnt > $tLimit) {
    break;
  }

  if (!isset($value['description'])) {
    $value['description'] = '';
  }
  
  if (trim(strip_tags($value['description'])) == '' && trim(strip_tags($value['title'])) == '') {
    continue;
  }
  $tData[$cnt] = $value;

  $cnt++;
}

//Check channel
$arr['rss']['channel'] = $aChannel;
$arr['rss']['channel']['item'] = $tData;

$xml = array2xml($arr);

$xml = preg_replace('/[\r\n]/', '', $xml);
$xml = preg_replace('/[\s]{2,}/', ' ', $xml);
//$xml = preg_replace('/[<[\s]*br[\s\/]*>]{0,}/iU', ' ', $xml);

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo $xml;
?>