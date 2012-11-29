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
$gNewsOrder=0;                                  //1 - random, 0 - Consistent, 2 - Order by Public Date ascending, 3 - Order by Public Date descending

$gDateTemplate = 'l, d F, Y H:i';

//Include functions
include('functions.inc.php');

//Limit items
$tLimit = 20;

$url = $tUrl = isset($HTTP_GET_VARS['link'])?$HTTP_GET_VARS['link']:isset($_GET['link'])?$_GET['link']:'';

//Include wp config
require("../../../wp-config.php");

if ((isset($url))&&(trim($url) != '')) {
  $aTmp = get_option('widget_7feeds-widget');
  
  if (isset($aTmp[$tUrl])) {
    $gNewsOrder = $aTmp[$tUrl]['news_order'];
    $url = unserialize($aTmp[$tUrl]['feed_url']);
  }
 
  if (isset($aTmp[$tUrl])) {
    $gNewsOrder = $aTmp[$tUrl]['news_order'];
    $url = unserialize($aTmp[$tUrl]['feed_url']);
  }  
  
  if (isset($aTmp['news_order'])&&($gNewsOrder==0)){
    $gNewsOrder = $aTmp['news_order'];
  }
 
  //Default value
  if (empty($url)) {
    unset($aTmp);
    $aTmp = get_option('wp7feeds_options');
    $gNewsOrder = $aTmp['news_order'];
    $url = unserialize($aTmp['feed_url']);
  }

  $a_Tmp = get_option('wp7feeds_options');
  if (isset($a_Tmp['date_format'])) {
    $gDateTemplate = $a_Tmp['date_format'];
  } 
}else{
  $aTmp = get_option('wp7feeds_options');
  $gNewsOrder = $aTmp['news_order'];
  $url = unserialize($aTmp['feed_url']);
}

$a_Tmp["news_filter"]=isset($HTTP_GET_VARS['nf'])?$HTTP_GET_VARS['nf']:isset($_GET['nf'])?$_GET['nf']:$a_Tmp["news_filter"];
$a_Tmp["news_filter_type"]=isset($HTTP_GET_VARS['nft'])?$HTTP_GET_VARS['nft']:isset($_GET['nft'])?$_GET['nft']:$a_Tmp["news_filter_type"];
$a_Tmp["news_filter_condition"]=isset($HTTP_GET_VARS['nfc'])?$HTTP_GET_VARS['nfc']:isset($_GET['nfc'])?$_GET['nfc']:$a_Tmp["news_filter_condition"];


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

  /*12109*/
  //Convert htmlspecialchars
  //$return = utf8_html_entity_decode($return);
  /*12109*/

  $return=preg_replace("/<img[^>]*>/i", '', $return);
  
  list($aChannel, $aFeed) = xmParseFeedArray(basexml2array($return));

  if (empty($aFeedChanel)) {
    $aFeedChanel = $aChannel;
  }

  /*12109*/
  foreach ($aFeed as $key=>$val) {
    $aFeed[$key]['title'] = utf8_html_entity_decode($val['title']);
    $aFeed[$key]['description'] = utf8_html_entity_decode($val['description']);
  }
  /*12109*/
  
  /*if (empty($aFeedItems)) {
  $aFeedItems = $aFeed;
  }else {*/
  $aFTmp = (isset($aTmp[$tUrl])?$aTmp[$tUrl]:$a_Tmp);

  $filterI = '';
  $filterO = '';

  //$aFTmp['news_filter']=correct_encoding(trim($aFTmp['news_filter']));
 
  if (!empty($aFTmp['news_filter'])&&($aFTmp['news_filter']!='')) {
    if ((int)$aFTmp['news_filter_type']) {
      $filterO['words'] = mb_split(',',$aFTmp['news_filter']);
      $filterO['condition'] = $aFTmp['news_filter_condition'];
    }else {
      $filterI['words'] = mb_split(',',$aFTmp['news_filter']);
      $filterI['condition'] = $aFTmp['news_filter_condition'];
    }
  

    foreach ($aFeed as $fi) { 
      if (findWords($fi['description'].$fi['title'], $filterI, $filterO)) {
        $aFeedItems[] = $fi;
      }
    }
    
  }
  $aFeedItems=$aFeed;
  /*}*/ 
}

if (($gNewsOrder==2)&&(!empty($aFeedItems))){
  $aFeedItems=sort_arr($aFeedItems, 'pubDate1', 'desc');
}
if (($gNewsOrder==3)&&(!empty($aFeedItems))){
  $aFeedItems=sort_arr($aFeedItems, 'pubDate1', 'asc');
}  
  

function sort_arr($array, $sortby, $direction='asc') {
    
    $sortedArr = array();
    $tmp_Array = array();
    
    foreach($array as $k => $v) {
        $tmp_Array[] = strtolower($v[$sortby]);
    }
    
    if($direction=='asc'){
        asort($tmp_Array);
    }else{
        arsort($tmp_Array);
    }
    
    foreach($tmp_Array as $k=>$tmp){
        $sortedArr[] = $array[$k];
    }
    
    return $sortedArr;
 
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
  /*if ($cnt > $tLimit) {
    break;
  }*/

  if (!isset($value['description'])) {
    $value['description'] = '';
  }

  if (trim(strip_tags($value['description'])) == '' && trim(strip_tags($value['title'])) == '') {
    continue;
  }
  
  unset($value['pubDate1']);
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