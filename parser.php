<?php
/** @global string $gRequestMethod */
$gRequestMethod=0;                              //HTTP Request Method 0-CURL/1-Socket

/** @global array $gaCrwValidCodes */
$gaCrwValidCodes=array('100','200','201','202','203','204','205','206','300','301','302','303','304','305','306','307'); //Valid Server Responce Codes

/** @global flag $gSetDayLghtSave */
$gSetDayLghtSave=true;                          //Determine Day Light Save Time [true] or don't [false]

/** @global integer $gCurUTC */
$gCurUTC=3600*2;                                //Current User's UTC

/** @ignore @global integer $gUTC */
$gUTC=0;                                        //Server's UTC (estimated in secondes)

/** @global integer $gNewsOrder */
$gNewsOrder=1;                                  //1 - random, 0 - Consistent

//Include functions
include('functions.inc.php');

//Limit items
$tLimit = 20;

$url = isset($HTTP_GET_VARS['link'])?$HTTP_GET_VARS['link']:isset($_GET['link'])?$_GET['link']:'';

if ((int)$url != 0) {

  //Include wp config
  require("../../../wp-config.php");
  $aTmp = get_option('widget_7feeds-widget');
  
  if (isset($aTmp[$url])) {
    $gNewsOrder = $aTmp[$url]['news_order'];
    $url = unserialize($aTmp[$url]['feed_url']);
  }
  
  //Default value
  if (empty($url)) {
    $aTmp = get_option('wp7feeds_options');
    $gNewsOrder = $aTmp['news_order'];
    $url = unserialize($aTmp['feed_url']);
  }
}

$aFeedUrls = array();
if (!is_array($url)) {
  if(!empty($url) && htValidURL($url)){
    $aFeedUrls[] = $url;
  }
}else {

  foreach ($url as $key=>$val) {
    if(!empty($val) && htValidURL($val)){
      $aFeedUrls[] = $val;
    }
  }
}

if (empty($aFeedUrls)) {
  exit;
}

$enc = 'UTF-8';
$gTimeout = 10;
$gHCRLF = "\n";

$aFeedChanel = array();
$aFeedItems = array();

foreach ($aFeedUrls as $url) {

  $run=0;
  $out = true;
  $return = '';


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

  if (empty($aFeedChanel)) {
    $aFeedChanel = $aChannel;
  }

  if (empty($aFeedItems)) {
    $aFeedItems = $aFeed;
  }else {
    foreach ($aFeed as $fi) {
      $aFeedItems[] = $fi;
    }
  }
}

$tData = array();
$cnt = 0;
$tArrayKeys = array_keys($aFeedItems);

if ($gNewsOrder == 1) {
  shuffle($tArrayKeys);
}

$c = count($tArrayKeys);

for ($i=0; $i < $c; $i++) {
  $value = $aFeedItems[$tArrayKeys[$i]];
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
$arr['rss']['channel'] = $aFeedChanel;
$arr['rss']['channel']['item'] = $tData;

$xml = array2xml($arr);

$xml = preg_replace('/[\r\n]/', '', $xml);
$xml = preg_replace('/[\s]{2,}/', ' ', $xml);
//$xml = preg_replace('/[<[\s]*br[\s\/]*>]{0,}/iU', ' ', $xml);

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo $xml;
?>