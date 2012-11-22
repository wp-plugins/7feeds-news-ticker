<?php
//Dates
$gaLang['Jan'] = 'Jan';
$gaLang['Feb'] = 'Feb';
$gaLang['Mar'] = 'Mar';
$gaLang['Apr'] = 'Apr';
$gaLang['May'] = 'May';
$gaLang['Jun'] = 'Jun';
$gaLang['Jul'] = 'Jul';
$gaLang['Aug'] = 'Aug';
$gaLang['Sep'] = 'Sep';
$gaLang['Oct'] = 'Oct';
$gaLang['Nov'] = 'Nov';
$gaLang['Dec'] = 'Dec';

function basexml2array($contents, $get_attributes=1, $priority = 'tag') {
  if(!$contents) return array();

  if(!function_exists('xml_parser_create')) {
    //print "'xml_parser_create()' function not found!";
    return array();
  }

  //Get the XML parser of PHP - PHP must have this module for the parser to work
  $parser = xml_parser_create('');
  //xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
  xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
  xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, true);
  xml_parse_into_struct($parser, trim($contents), $xml_values, $arr_index);
  xml_parser_free($parser);

  if(!$xml_values) return;//Hmm...

  //Initializations
  $xml_array = array();
  $parents = array();
  $opened_tags = array();
  $arr = array();

  $current = &$xml_array; //Refference

  //Go through the tags.
  $repeated_tag_index = array();//Multiple tags with same name will be turned into an array
  foreach($xml_values as $data) {
    unset($attributes,$value);//Remove existing values, or there will be trouble

    //This command will extract these variables into the foreach scope
    // tag(string), type(string), level(int), attributes(array).
    extract($data);//We could use the array by itself, but this cooler.

    $result = array();
    $attributes_data = array();

    if(isset($value)) {
      if($priority == 'tag') $result = $value;
      else $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode
    }

    //Set the attributes too.
    if(isset($attributes) and $get_attributes) {
      foreach($attributes as $attr => $val) {
        if($priority == 'tag') $attributes_data[$attr] = $val;
        else $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
      }
    }

    //See tag status and do the needed.
    if($type == "open") {//The starting of the tag '<tag>'
      $parent[$level-1] = &$current;
      if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
        $current[$tag] = $result;
        if($attributes_data) $current[$tag. '_attr'] = $attributes_data;
        $repeated_tag_index[$tag.'_'.$level] = 1;

        $current = &$current[$tag];

      } else { //There was another element with the same tag name

        if(isset($current[$tag][0])) {//If there is a 0th element it is already an array
          $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
          $repeated_tag_index[$tag.'_'.$level]++;
        } else {//This section will make the value an array if multiple tags with the same name appear together
          $current[$tag] = array($current[$tag],$result);//This will combine the existing item and the new item together to make an array
          $repeated_tag_index[$tag.'_'.$level] = 2;

          if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
            $current[$tag]['0_attr'] = $current[$tag.'_attr'];
            unset($current[$tag.'_attr']);
          }

        }
        $last_item_index = $repeated_tag_index[$tag.'_'.$level]-1;
        $current = &$current[$tag][$last_item_index];
      }

    } elseif($type == "complete") { //Tags that ends in 1 line '<tag />'
      //See if the key is already taken.
      if(!isset($current[$tag])) { //New Key
        $current[$tag] = $result;
        $repeated_tag_index[$tag.'_'.$level] = 1;
        if($priority == 'tag' and $attributes_data) $current[$tag. '_attr'] = $attributes_data;

      } else { //If taken, put all things inside a list(array)
        if(isset($current[$tag][0]) and is_array($current[$tag])) {//If it is already an array...

          // ...push the new element into that array.
          $current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;

          if($priority == 'tag' and $get_attributes and $attributes_data) {
            $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
          }
          $repeated_tag_index[$tag.'_'.$level]++;

        } else { //If it is not an array...
          $current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value
          $repeated_tag_index[$tag.'_'.$level] = 1;
          if($priority == 'tag' and $get_attributes) {
            if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well

              $current[$tag]['0_attr'] = $current[$tag.'_attr'];
              unset($current[$tag.'_attr']);
            }

            if($attributes_data) {
              $current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
            }
          }
          $repeated_tag_index[$tag.'_'.$level]++; //0 and 1 index is already taken
        }
      }

    } elseif($type == 'close') { //End of tag '</tag>'
      $current = &$parent[$level-1];
    }
  }

  return($xml_array);
}
/*=========================================*/

function array2xml($array, $indent = 0, $parent_key = '') {
  $xml = '';
  foreach($array as $key => $value){
    if (strpos($key, '_attr') !== false) {
      continue;
    }

    $skipTag = false;
    if (is_numeric($key)) {
      $key = $parent_key;
    }

    if (is_array($value) && isset($value[0])) {
      $skipTag = true;
    }

    if (!$skipTag) {
      $xml .= str_repeat(' ', $indent);
      $xml .= "<$key";

      if (isset($array[$key.'_attr']) && !empty($array[$key.'_attr'])) {
        foreach ($array[$key.'_attr'] as $k=>$v) {
          $xml .= ' '.$k.' = "'.$v.'"';
        }
      }
      $xml .= ">";
    }

    if(is_array($value)){
      $xml .= "\n" . array2xml($value, $indent + 1, $key);
      $xml .= str_repeat(' ', $indent);
    } else{
      if (strtolower($key) == 'description') {
        $xml .= '<![CDATA[';
      }

      $xml .= $value;

      if (strtolower($key) == 'description') {
        $xml .= ']]>';
      }
    }

    if (!$skipTag) {
      $xml .= '</'.(($pos = strpos($key, ' ')) ? substr($key, 0, $pos) : $key).">\n";
    }
  }

  return $xml;
}
/*=========================================*/

function xml2array($contents) {
  $curArray = basexml2array($contents);
  return replaceItems($curArray);
  return $curArray;
}
/*=========================================*/

function replaceItems($data) {
  $index = 0;
  $resArray = array();
  foreach ($data as $key => $value) {
    if ($key == 'item') {
      $key = $index++;
    }
    $resArray[$key] = is_array($value) ? replaceItems($value) : $value;
  }
  return $resArray;
}
/*=========================================*/

function xmGetTagValues($array) {
  global $gaLang;
  $aTags = array('title'=>array('title'),'description'=>array('content','description'),'link'=>array('id','link'),'pubDate'=>array('date','pubDate','pubdate','issued'));

  $gaLang = array();
  $gNoReturn = true;

  $aReturn = array();

  if (!empty($array) && is_array($array)) {
    foreach ($array as $key=>$val) {
      $ffTag = '';

      //Check value & set string from array
      if (is_array($val)) {
        $ret = '';
        $alter = false;

        foreach ($val as $_k=>$_v) {
          if (is_array($_v)) {
            foreach ($_v as $_k1=>$_v1) {
              if ($_k1 == 'href' && htValidURL($_v1)) {
                $ret = $_v1;
              }
              if ($_k1 == 'rel' && $_v1 == 'alternate') {
                $alter = true;
              }
            }
          }
          if ($alter) {
            break;
          }
        }

        $val = $ret;
      }

      $skip = true;
      foreach ($aTags as $tag_key=>$tag_val) {

        foreach ($tag_val as $tv) {
          if (strpos($key, $tv) === false || strpos($key,'_attr') !== false) {
            continue;
          }

          //if ($tag_key == 'link' && !htValidURL($val)) {
          if ($tag_key == 'link' && empty($val)) {
            continue;
          }
          
          if ($tag_key == 'description' && empty($val)) {
            continue;
          }

          $skip = false;
          $ffTag = $tag_key;
          break;
        }

        if (!$skip) break;
      }

      if ($skip || $ffTag == '' || !is_string($val)) continue;

      $aKeys = array_keys($aReturn);
      if (in_array($ffTag, $aKeys)) {
        continue;
      }

      switch ($ffTag) {
        case 'pubDate':
          $t = strtotime($val);
          $aReturn['pubDate1'] = $t;
          //$val = date('d',$t).' '.$gaLang[date('M',$t)].' '.date(' Y H:i',$t);
          $val = _7feedsParseDate($t);
          
          break;
                  

        case 'description':
          $i=0;
          while (true) {
            $aTmp = array();
            if (!preg_match_all('/(&[\S]{2,6};)/iUm', $val, $aTmp) || $i > 10) {
              break;
            }else {
              $val = html_entity_decode($val,ENT_NOQUOTES,'UTF-8');
              $i++;
              continue;
            }
          }
          //$val = strip_tags_attributes($val,'<a>,<b>,<br>,<font>,<i>,<li>,<p>,<span>,<textformat>,<u>',array('height','alt','width','clear','size','class','border','style'));
          $val = strip_tags_attributes($val,'<a>,<b>,<font>,<i>,<ul>,<ol>,<li>,<p>,<span>,<textformat>,<u>,<br>,<strong>',array('height','alt','width','clear','size','class','border','style'));
          break;
      }

      $aReturn[$ffTag] = $val;
    }
  }

  foreach ($aTags as $key=>$val) {
    if (!isset($aReturn[$key])) {
      switch ($key) {
        case 'pubDate':
          $t = time();
          //$aReturn[$key] = date('d',$t).' '.$gaLang[date('M',$t)].' '.date(' Y H:i',$t);
          $aReturn[$key] = _7feedsParseDate($t);
          break;
        default:
          $aReturn[$key] = '';
      }
    }
  }

  return $aReturn;
}
/*=========================================*/

function _7feedsParseDate($t) {
  global $gDateTemplate;

  return date($gDateTemplate, $t);

  $dateVal = $gDateTemplate;

  preg_match_all('/(\w+)/iU',$dateVal, $aTmp);
  foreach ($aTmp[1] as $key=>$val) {
    $dateVal = str_replace($val, '~'.$val.'~', $dateVal);
  }

  preg_match_all('/~(.+)~/iU',$dateVal, $aTmp);

  $aData = array();
  foreach ($aTmp[1] as $key=>$val) {
    $aData[$val] = date($val,$t);
    $dateVal = str_replace('~'.$val.'~', date($val,$t), $dateVal);
  }

  return $dateVal;
}

function strip_tags_attributes($sSource, $aAllowedTags = '', $aDisabledAttributes = array()) {
  if (empty($aDisabledAttributes)) return strip_tags($sSource, $aAllowedTags);

  /*$aSpecialSymbols = array('■','●');
  for ($i=0; $i < count($aSpecialSymbols); $i++) {
  $sSource = str_replace($aSpecialSymbols[$i], '', $sSource);
  }*/

  $sSource = strip_tags($sSource, $aAllowedTags);

  preg_match_all('/<(.*?)>/ie', $sSource, $aRes);
  if (!empty($aRes) && !empty($aRes[0])) {

    foreach ($aRes[0] as $key=>$val) {
      $tPattern = $val;

      $foundTag = false;
      $foundQuote = false;
      $startPos = 0;
      $endPos = 0;

      foreach ($aDisabledAttributes as $n=>$attr) {

        $pos = mb_strpos($val,$attr,0,'UTF-8');
        if ($pos === false) {
          continue;
        }
        $foundTag = true;
        $startPos = $pos;

        $pos = mb_strpos($val, '=', $pos,'UTF-8');
        if ($pos === false) {
          continue;
        }

        do{
          $pos ++;
          $s = mb_substr($val, $pos, 1, 'UTF-8');

          if ($s === '\'' || $s === '"') {
            $foundQuote = $s;
            break;
          }

        }while ($s === ' ' || $s === '\'' || $s === '"');

        if ($foundQuote === false) {
          $pos1 = mb_strpos($val, ' ',$pos,'UTF-8');
          $pos2 = mb_strpos($val, '>',$pos,'UTF-8');

          if ($pos1 === false && $pos2 !== false) {
            $pos = $pos2;
          }elseif ($pos2 === false && $pos1 !== false) {
            $pos = $pos1;
          }elseif ($pos2 !== false && $pos1 !== false) {
            if ($pos1 < $pos2) {
              $pos = $pos1;
            }elseif ($pos1 > $pos2) {
              $pos = $pos2;
            }
          }
        }else {
          $pos = mb_strpos($val, $foundQuote,$pos+1,'UTF-8')+1;
          $foundQuote = false;
        }

        if ($pos !== false) {
          $endPos = $pos;
        }

        $val = mb_substr($val,0,$startPos-1,'UTF-8').mb_substr($val,$endPos,(mb_strlen($val,'UTF-8')),'UTF-8');
        $foundTag = false;
      }

      $aTmp = split(' ',$val);

      if (!empty($aTmp)) {
        foreach ($aTmp as $k=>$v) {
          if (strpos($v,'=') !== false) {
            $aTmp2 = split('=',$v);

            $v = '';
            if (count($aTmp2) > 0) {

              $aTmp2[1] = str_replace('"','',$aTmp2[1]);
              $aTmp2[1] = str_replace("'",'',$aTmp2[1]);

              $aTmp2[1] = '"'.$aTmp2[1];

              $t = $aTmp2[count($aTmp2)-1];
              if (count($aTmp2)-1 > 1) {
                $t = str_replace('"','',$t);
              }
              $t = str_replace("'",'',$t);
              $t = str_replace(">",'',$t);
              $aTmp2[count($aTmp2)-1] = $t.'"';
            }

            $v .= implode('=',$aTmp2);
          }
          $aTmp[$k] = $v;
        }
        if (strpos($aTmp[count($aTmp)-1],'>') === false) {
          $aTmp[count($aTmp)-1] .= '>';
        }

        $val = implode(' ',$aTmp);
      }
      $sSource = str_replace($tPattern,$val,$sSource);
    }
  }
  return $sSource;
}
/*=========================================*/

function xmParseFeedArray($aFeed) {
  $aParentTag = array('rss'=>array('rss','rdf'));
  $aFeedTags = array('channel'=>array('channel','feed'));
  $aItemParentTags = array('item'=>array('item','entry'));
  $aItemTags = array('title'=>array('title'),'description'=>array('content','description'),'link'=>array('id','link'),'pubDate'=>array('date','pubDate','pubdate','issued','modified'),'pubDate1'=>array('date','pubDate','pubdate','issued','modified'));

  $aChannel = array('title'=>'','description'=>'','link'=>'','pubDate'=>'','pubDate1'=>'');
  $aItems = array();

  if (empty($aFeed)) {
    return array($aChannel,$aItems);
  }

  foreach ($aFeed as $key=>$val) {
    if (_in_array($key, $aParentTag['rss'])) {
      $aFeed = $val;
      break;
    }
  }


  foreach ($aFeed as $key=>$val) {
    if (_in_array($key, $aFeedTags['channel'])) {
      $aChannel = $val;
      break;
    }
  }

  foreach ($aFeed as $key=>$val) {
    if (_in_array($key, $aItemParentTags['item'])) {
      $aItems = $val;
      break;
    }
  }
  if (empty($aItems)) {
    foreach ($aChannel as $key=>$val) {
      if (_in_array($key, $aItemParentTags['item'])) {
        $aItems = $val;
        break;
      }
    }
  }

  $aChannel = xmGetTagValues($aChannel);

  if (isset($aItems[0])) {
    foreach ($aItems as $key=>$val) {
      $aItems[$key] = xmGetTagValues($val);
    }
  }else {
    $aTmp = $aItems;
    $aItems = array();
    $aItems[0] = xmGetTagValues($aTmp);
  }

  return array($aChannel,$aItems);
}
/*=========================================*/

function _in_array($search, $array, $first_check = true) {

  $re_check = false;
  foreach ($array as $val) {
    if (strpos($search,$val) !== false) {

      if (strpos($search,$val) == 0 && $first_check) {
        return true;
      }else {
        $re_check = true;
      }

    }
  }

  if ($re_check && $first_check) {
    return _in_array($search, $array, false);
  }

  return false;
}
/*=========================================*/

function htFindInvObj($Content) {

  $t = preg_match_all('/\<(img\b[^>]*?\bsrc[\s]*='.
  '[\s]*([\'"`]?)'.
  '(([^>\'"`\s]+)\.(jpg|jpe|jpeg|gif|png))?'.
  '\2[\s]*'.
  '[^>]*?)\>/im', $Content, $a);

  if(!empty($a) && is_array($a)){
    foreach($a[1] AS $key => $value){
      if(empty($a[4][$key])){
        $Content = str_replace('<'.$value.'>', '&lt;'.$value.'&gt;', $Content);
      }
    }
  }

  return $Content;
}
/*=========================================*/

function htValidURL($url) {

  if(!preg_match("~^(?:(?:https?|ftp|telnet)://(?:[a-z0-9_-]{1,32}".
  "(?::[a-z0-9_-]{1,32})?@)?)?(?:(?:[a-z0-9-]{1,128}\.)+(?:com|net|".
  "org|mil|edu|arpa|gov|biz|info|aero|inc|name|[a-z]{2})|(?!0)(?:(?".
  "!0[^.]|255)[0-9]{1,3}\.){3}(?!0|255)[0-9]{1,3})(?:/[a-z0-9.,_@%&".
  "?+=\~/-]*)?(?:#[^ '\"&<>]*)?$~i", $url)){
    return false;
  }
  return true;
}
/*=========================================*/

function htCheckSslURL() {
  Global $gRootURL, $gRootCustURL, $gRootUrlSSL, $gSSLActive, $gAccessLevel, $gaEvSSL, $gCurEvent;

  //Init
  $Ret = false;
  $tSSLAct = (strtolower(vrGetSerVar('HTTPS')) == 'on') ? true : false;

  //Prepare url with correct proto
  $gRootUrlSSL = htSetSslURL($gRootURL);

  //Validation of url - prepare return value if redirection required
  if(!(!empty($gaEvSSL) && !empty($gaEvSSL[$gAccessLevel]))){
    $gRootURL = $gRootUrlSSL;
    if(empty($tSSLAct) ^ empty($gSSLActive)){
      $Ret = $gRootURL;
    }
  }elseif(in_array($gCurEvent, $gaEvSSL[$gAccessLevel])){
    $gRootURL = $gRootUrlSSL;
    if(empty($tSSLAct) ^ empty($gSSLActive)){
      $Ret = $gRootURL;
    }
  }elseif(!in_array($gCurEvent, $gaEvSSL[$gAccessLevel]) && !empty($tSSLAct)){
    $Ret = $gRootURL;
  }

  if(empty($Ret) && !empty($gSSLActive) && $gRootURL == $gRootUrlSSL){
    $gRootCustURL = htSetSslURL($gRootCustURL);
  }

  //Exit
  return $Ret;
}
/*=========================================*/

function htSetSslURL($Url) {
  Global $gSSLActive;

  //Parse url
  $tUrl = htParseURL($Url);

  //Prepare new proto
  if(!empty($gSSLActive)){
    $tUrl['scheme'] = 'https';
  }else{
    $tUrl['scheme'] = 'http';
  }

  //Prepare new url
  $Url = $tUrl['scheme'].'://'.$tUrl['host'].(!empty($tUrl['port']) ? ':'.$tUrl['port'] : '').$tUrl['path'].(!empty($tUrl['query']) ? '?'.$tUrl['query'] : '');

  //Exit
  return $Url;
}
/*=========================================*/

function htParseURL($value) {

  $aurl=array('scheme'=>'', 'host'=>'', 'port'=>'', 'useport'=>'', 'path'=>'', 'query'=>'');
  $value=trim($value);
  if(empty($value)){
    return '';
  }
  if(preg_match("/^(https?:\/\/)?(.+)$/i", $value, $regs)){
    $url=$regs[2];
  }
  $atmp=parse_url('http://'.$url);
  //Host
  if(isset($atmp['host'])){
    $aurl['host']=$atmp['host'];
  }else{
    return $aurl;
  }
  //Proto
  if((strpos($value, '://')) !== false){
    $aurl['scheme']=substr($value, 0, strpos($value, '://'));
  }else{
    $aurl['scheme']='http';
  }
  //Port
  if(isset($atmp['port'])){
    $aurl['port']=$atmp['port'];
  }else{
    $aurl['port']='';
  }
  //Actual Port
  if(!empty($atmp['port'])){
    $aurl['useport']=$atmp['port'];
  }elseif(strtolower($aurl['scheme']) == 'https'){
    $aurl['useport']='443';
  }elseif(strtolower($aurl['scheme']) == 'http'){
    $aurl['useport']='80';
  }
  //Path after domain
  if(isset($atmp['path'])){
    $aurl['path']=$atmp['path'];
  }else{
    $aurl['path']='/';
  }
  //Query after path
  if(isset($atmp['query'])){
    $aurl['query']=$atmp['query'];
  }else{
    $aurl['query']='';
  }

  //Exit
  return $aurl;
}
/*=========================================*/

function htCanonicalizeURL($URL) {

  //Init, get proto, split to array
  $URL=str_replace("\\", '/', $URL);
  $url_up=strtoupper($URL);
  if((strpos($url_up, 'HTTP://')===false || strpos($url_up, 'HTTP://')!=0) && (strpos($url_up, 'HTTPS://')===false || strpos($url_up, 'HTTPS://')!=0)){
    $URL='http://'.$URL;
  }
  $URLa=explode('/', $URL);

  //Canonicalize
  foreach($URLa AS $key => $value){
    if($key <= 2){continue;}
    switch($value){
      case '.' : {
        unset($URLa[$key]);
        break;
      }
      case '..' : {
        unset($URLa[$key]);
        for($i=$key; $i > 2; $i--){
          if(isset($URLa[$i-1]) && ($i-1) > 2){
            unset($URLa[$i-1]);
            break;
          }
        }
        break;
      }
    }
  }

  //Formatting
  $URL=implode('/', $URLa);
  $URL=preg_replace('/\s/s', '+', $URL);
  $URL=str_replace('&amp;', '&', $URL);

  //Exit
  return $URL;
}
/*=========================================*/

function htGetPageContent($URL, $Content, $HttpMethod, $GetBody, $ConvCharset=1, $ParseContent=0) {
  Global $gRequestMethod, $gaCrwValidCodes, $gtsHost;
  Global $gResponceContent, $gtiWrongHeader, $gtRespTime; //Internal

  $PC=array();
  $PC['URL']=$URL;
  $PC['Size']=0;
  $PC['UTime']=0;
  $PC['RTime']=0;
  $PC['Code']='';
  $PC['Error']='';
  $PC['Cache']='';
  $PC['Title']='';
  $PC['Header']='';
  $PC['Content']='';
  $PC['BaseURL']='';
  $PC['Charset']='';
  $PC['Keywords']='';
  $PC['Description']='';
  $PC['Content-Type']='';
  $PC['Last-Modified']='';

  $gResponceContent = Array('Header'=>'','Body'=>'','Error'=>'','BaseURL'=>'');
  $gtiWrongHeader = 0;
  $gtRespTime = 0;

  if(empty($gtsHost)){
    $gtsHost = htParseURL($URL);
    $gtsHost = $gtsHost['host'];
  }

  $TimeStart = erGetMicroTime();
  $Content=htHTTPRequest($URL, $Content, $gRequestMethod, $HttpMethod, $GetBody);
  $TimeEnd = erGetMicroTime();
  $PC['UTime'] = $TimeEnd - $TimeStart;
  if(empty($gtRespTime)){
    $gtRespTime = $TimeEnd;
  }
  $PC['RTime'] = $gtRespTime - $TimeStart;
  unset($TimeStart, $TimeEnd, $gtRespTime);

  //Get header and body
  $PC['Header']   =$Content['Header'];
  $PC['Content']  =$Content['Body'];
  $PC['Error']    =$Content['Error'];

  //Page size
  $PC['Size']=strlen($PC['Content']);

  //Get responce code
  $array1=array();
  if(!preg_match("/^[^\s]+ (\d{3})/i", $PC['Header'], $array1)){
    $PC['Code']='';
  }else{
    $PC['Code']=trim($array1[1]);
  }

  if(!in_array($PC['Code'], $gaCrwValidCodes)){
    $PC['Content']='';
    $PC['Size']=0;
    return $PC;
  }

  //Base URL
  $array1=array();
  if(!preg_match('/<base[^>]+href[\s]*=[\s]*[\'"`]?([^>\'"`\s]+)[\'"`]?[\s]*[^>]*>/i', $PC['Content'], $array1)){
    $PC['BaseURL'] = $Content['BaseURL'];
  }else{
    $PC['BaseURL'] = rtrim($array1[1], '/');
  }

  if(!empty($GetBody) && !empty($ConvCharset)){
    //Get charset type from body
    $array1=array();
    $text=preg_replace("/\>/", " >", $PC['Content']);
    if(!preg_match('/(<meta.*charset.*>)/smi', $text, $array1)){
      if(!preg_match('/(<?xml .*encoding.*?>)/smi', $text, $array1)){
        $PC['Charset']='';
      }else{
        if(!preg_match('/encoding=([\'"`]?)([^>\'"`\s]+)\1/', $array1[1], $array2)){
          $PC['Charset']='';
        }else{
          $PC['Charset']=strtoupper(trim($array2[2]));
        }
      }
    }else{
      if(!preg_match('/charset=([^"\';]+)/', $array1[1], $array2)){
        $PC['Charset']='';
      }else{
        $PC['Charset']=strtoupper(trim($array2[1]));
      }
    }
    //If charset not found in the body try to get it from the head
    if($PC['Charset']==''){
      //Get charset and content type from header
      $text=preg_replace("/\>/", " >", $PC['Header']);
      $array1=array();
      if(!preg_match('/(Content-Type:.*charset.*\r?\n)/smi', $text, $array1)){
        $PC['Charset']='';
      }else{
        $array2=array();
        if(!preg_match('/charset=([^\r\n]+)/', trim($array1[1]), $array2)){
          $PC['Charset']='';
        }else{
          $PC['Charset']=strtoupper(trim($array2[1]));
        }
      }
    }
  }

  if(!empty($GetBody) && !empty($ParseContent)){
    //Get data of last modification
    $array1=array();
    if(!preg_match("/\r?\nLast\-Modified[\s]*\: ([a-z]{3}, \d{1,2} [a-z]{3} \d{4} \d{1,2}\:\d{1,2}\:\d{1,2}[a-z ]+)\r?\n/i", $PC['Header'], $array1)){
      $array1=array();
      if(!preg_match("/\r?\nDate[\s]*\: ([a-z]{3}, \d{1,2} [a-z]{3} \d{4} \d{1,2}\:\d{1,2}\:\d{1,2}[a-z ]+)\r?\n/i", $PC['Header'], $array1)){
        $PC['Last-Modified']='';
      }else{
        $PC['Last-Modified']=trim($array1[1]);
      }
    }else{
      $PC['Last-Modified']=trim($array1[1]);
    }
    //----
    //Get Cache
    $array1=array();
    if(!preg_match("/\r?\nCache\-Control[\s]*\:([^\r\n]+)\r?\n/i", $PC['Header'], $array1)){
      $array1=array();
      if(!preg_match("/\r?\nPragma[\s]*\:([^\r\n]+)\r?\n/i", $PC['Header'], $array1)){
        $PC['Cache']='';
      }else{
        $PC['Cache']=trim($array1[1]);
      }
    }else{
      $PC['Cache']=trim($array1[1]);
    }
    //----
    //Remove comments
    if($ParseContent == 2){
      $PC['Content'] = preg_replace('/<!--((?!-->).)*-->/m', '', $PC['Content']);
      $PC['Content'] = preg_replace('/(<script\b[^>]*>)((?!<\/script>).)+(<\/script>)/is', '$1$3', $PC['Content']);
    }
    //----
    //Get title
    $array1=array();
    if(!preg_match('|<Title>(.+)</Title>|Usi', $PC['Content'], $array1)){
      $PC['Title']='';
    }else{
      $PC['Title']=trim($array1[1]);
    }
    //----
    //Get description
    $array1=array();
    if(!preg_match('|<meta[^>]*description[^>]*content="([^>]+)"[^>]*>|Usi', $PC['Content'], $array1)){
      $PC['Description']='';
    }else{
      $PC['Description']=trim($array1[1]);
    }
    //----
    //Get keywords
    $array1=array();
    if(!preg_match('|<meta[^>]*keywords[^>]*content="([^>]+)"[^>]*>|Usi', $PC['Content'], $array1)){
      $PC['Keywords']='';
    }else{
      $PC['Keywords']=trim($array1[1]);
    }
    //----
    //Get content type from body
    $array1=array();
    if(!preg_match('/<meta[^>]*content="([^>]+);/', $PC['Content'], $array1)){
      $PC['Content-Type']='';
    }else{
      $PC['Content-Type']=$array1[1];
    }
    //----
    //If Content-Type not found in the body...
    if($PC['Content-Type']==''){
      if(!preg_match("/Content-Type: ([\/-\w]+)/", $PC['Header'], $mtch)){
        $PC['Content-Type']='';
      }else{
        $PC['Content-Type']=trim($mtch[1]);
      }
    }
  }

  //Remove <xml> tag
  if(!empty($PC['Content']) && preg_match("/<(rss|rdf|feed)[^>]*>/i", $PC['Content'], $areg)) {
    $format = '';
    if (isset($areg[1])) {
      $format = $areg[1];
    }

    $pos=strpos($PC['Content'], '<'.$format);
    if($pos!==false){
      $PC['Content']=substr($PC['Content'], $pos);
    }
  }


  //Encode fields to UTF-8
  if(!empty($PC['Charset'])){
    $EncodeFields=array('Content','Header','Title');
    foreach($EncodeFields as $field){
      if(isset($PC[$field]) && !empty($PC[$field])){
        if(!empty($ConvCharset) && $ConvCharset == 2){
          $str = mb_convert_encoding($PC[$field], 'UTF-8', strtolower($PC['Charset']));
        }else{
          $str = mb_convert_encoding($PC[$field], 'UTF-8', $PC['Charset'].', ISO-8859-1, auto');
        }
        if(empty($str)){
          continue;
        }
        $PC[$field]=$str;
      }
    }
  }

  return $PC;
}
/*=========================================*/

function htMakeBaseURL($URL) {

  $tLP = htParseURL($URL);

  $pos = strrpos($tLP['path'], '/');
  $tLP['path'] = rtrim(substr($tLP['path'], 0, $pos), '/');
  $BaseURL = $tLP['scheme'].'://'.$tLP['host'].(!empty($tLP['port']) ? ':'.$tLP['port']:'').$tLP['path'];

  return $BaseURL;
}
/*=========================================*/

function htHTTPRequest($URL, $Content, $RequestMethod=0, $HTTPMethod='POST', $IncludeBody=true) {
  Global $gAccessTimeOut, $gAuth, $gCRLF, $gHCRLF;
  Global $gtiWrongHeader, $gtDoRedirect, $gtRedirected, $gResponceContent, $gtMaxWrited;//Internal
  /** Some servers do not allow HEAD requests */
  Global $gbIncludeBody;//Internal

  //Check url is xml or web page with xml url
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $URL);
  curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1) Gecko/20061010 Firefox/2.0");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $content = curl_exec($ch);
  curl_close($ch);

  //Get rss url
  if (strpos($content, '<?xml') === false) {
    preg_match_all("|<link(.*)alternate.*>|U", $content, $aurls);
    $rss_url = '';
    foreach ($aurls[0] as $value) {
      if (strpos($value,'application/rss+xml') != 0 || strpos($value,'application/rdf+xml') != 0 || strpos($value,'application/atom+xml') != 0) {
        $rss_url = substr($value, (strpos($value, 'href="')+6));
        $URL=substr($rss_url, 0, (strpos($rss_url, '"')) - strlen($rss_url));
      }
    }
  }

  //Init
  $gtiWrongHeader = 0;
  $gbIncludeBody = $IncludeBody;
  $buff_in = Array('Header'=>'','Body'=>'','Error'=>'','BaseURL'=>'');
  if(!isset($gResponceContent)){$gResponceContent  = Array('Header'=>'','Body'=>'','Error'=>'','BaseURL'=>'');}
  if(!isset($gAccessTimeOut)){$gAccessTimeOut=60;}
  $Cookie = htProcCookieGet($URL);
  $url_data=htParseURL($URL);

  switch($RequestMethod){
    case 0 : {
      //Do CURL Request
      $buff_out=$Content;
      $ch=curl_init();
      curl_setopt($ch, CURLOPT_URL, $URL);
      curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1" );
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_TIMEOUT, $gAccessTimeOut);
      curl_setopt($ch, CURLOPT_WRITEFUNCTION, 'htReadBody');
      curl_setopt($ch, CURLOPT_HEADERFUNCTION, 'htReadHeader');
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);# required for https urls
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);   # required for https urls 
      curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $gAccessTimeOut );
      curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
      
      if(!empty($Cookie)){
        curl_setopt($ch, CURLOPT_COOKIE, $Cookie);
      }
      if(!empty($gAuth)){
        curl_setopt($ch, CURLOPT_USERPWD, $gAuth['B']);
      }
      if($HTTPMethod=='POST'){
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $buff_out);
      }
      curl_exec($ch);
      if(empty($gtMaxWrited)){
        $buff_in['Error'] = curl_error($ch);
      }
      curl_close($ch);
      break;
    }
    case 1 : {
      //Open socket for request
      ini_set('default_socket_timeout', $gAccessTimeOut);
      ini_set('max_input_time', $gAccessTimeOut);
      /** Some servers do not allow HEAD requests
*        if($HTTPMethod == 'GET' && !$IncludeBody){
*          $HTTPMethod = 'HEAD';
*        }
*/
      if(empty($url_data['useport'])){
        $url_data['useport']='80';
      }elseif($url_data['useport'] == '443'){
        $url_data['host'] = 'ssl://'.$url_data['host'];
      }
      $buff_out=$HTTPMethod.' '.$url_data['path'].' HTTP/1.1'.$gHCRLF;
      $buff_out.='Host: '.$url_data['host'].$gHCRLF;
      $buff_out.='Connection: Close'.$gHCRLF;
      $buff_out.='User-Agent: HTTPRequest() by php lib (c) eTek'.$gHCRLF;
      if(!empty($Cookie)){
        $buff_out.='Cookie: '.$Cookie.$gHCRLF;
      }
      if(!empty($gAuth)){
        $buff_out.='Authorization: Basic '.base64_encode($gAuth['B']).$gHCRLF;
      }
      if($HTTPMethod=='POST'){
        $buff_out.='Content-Type: application/x-www-form-urlencoded'.$gHCRLF;
        $buff_out.='Content-Length: '.strlen($Content).$gHCRLF;
      }
      $buff_out.=$gHCRLF;
      if($HTTPMethod=='POST'){
        $buff_out.=$Content.$gCRLF;
      }
      if(($fp=fsockopen($url_data['host'], $url_data['useport'], $tSockErrNum, $tSockErrStr, $gAccessTimeOut))){
        stream_set_timeout($fp, $gAccessTimeOut);
        stream_set_blocking($fp, false);
        fwrite($fp, $buff_out);
        while(!feof($fp)){
          $tState = htProcSockCont(fgets($fp, 4096), $IncludeBody);
          if(!$tState){
            break;
          }
        }
        fclose($fp);
      }
      $buff_in['Error'] = $tSockErrStr;
      break;
    }
  }

  //Make Redirect (Header->Location:)
  if(isset($gtDoRedirect) && !empty($gtDoRedirect) && empty($gtRedirected)){
    $gtRedirected = 1;
    $tURL = $gtDoRedirect;
    $gResponceContent['BaseURL'] = htMakeBaseURL($tURL);
    htReadHeader('', '');
    $gResponceContent = htHTTPRequest($tURL, '', $RequestMethod, 'GET', $IncludeBody);
  }

  //Prepare return value
  $buff_in['Header'] = $gResponceContent['Header'];
  $buff_in['Body']   = $gResponceContent['Body'];
  $buff_in['BaseURL']= !empty($gResponceContent['BaseURL']) ? $gResponceContent['BaseURL'] : htMakeBaseURL($URL);

  //Exit
  return $buff_in;
}
/*=========================================*/

function htReadHeader($ch, $string) {
  Global $gMaxRedirects, $gtaAlwHdrCT;
  Global $gResponceContent, $gtsHost, $gtiWrongHeader, $gHeaderWrited;//Internal
  Global $gtDoRedirect, $gtRedirected, $gtRespTime;//Internal
  Static $RedirectsDone=0;

  //Determine New Analyze
  if(empty($gResponceContent['Header']) && empty($gResponceContent['Body'])){
    $RedirectsDone=0;
    $gHeaderWrited=0;
    $gtRespTime = erGetMicroTime();
  }
  if(!isset($gMaxRedirects)){$gMaxRedirects=10;}
  if(!isset($gtaAlwHdrCT) || !is_array($gtaAlwHdrCT)){$gtaAlwHdrCT=array();}

  if(isset($gtRedirected) && !empty($gtRedirected)){
    $gHeaderWrited=0;
    $gtRedirected=0;
    $gtDoRedirect='';
  }

  $length = strlen($string);

  $h = $gResponceContent['Header'].$string;
  //Parse headers
  $ahead = array();
  $atmp=explode("\n", $h);
  foreach($atmp as $key=>$value){
    if($key==0){
      if(preg_match("/HTTP\/1\.(0|1) ([\d]{3})/i", $h, $areg)){
        $ahead['Status']=$areg[2];
      }else{//error
        return 0;
      }
    }else{
      $afield=array();
      $afield=explode(': ', $value);
      if(isset($afield[1])){
        $ahead[$afield[0]]=$afield[1];
      }
    }
  }

  //Reject Pages With Content-Type Differ From "text/*"
  /*if(isset($ahead['Content-Type'])){
  $tCT = $ahead['Content-Type'];
  if (strpos($tCT,';') !== false) {
  $tCT = trim(substr($tCT, 0, (strpos($tCT, ';'))));
  }
  $pos = strpos($tCT, 'text/');

  if(($pos === false || $pos != 0) && !in_array($tCT, $gtaAlwHdrCT)){
  $gtiWrongHeader=1;
  }
  }*/

  //Determine Header Redirection
  if((isset($ahead['Location']) || isset($ahead['Content-Location'])) && empty($gtiWrongHeader) && $RedirectsDone<$gMaxRedirects){
    //Prepare Redirection URL
    $loc = isset($ahead['Location'])?$ahead['Location']:$ahead['Content-Location'];
    if((strpos($loc, '://'))===false){
      $loc = $gtsHost.'/'.ltrim($loc, '/');
    }
    $location = htCanonicalizeURL($loc);
    $locparsed = htParseURL($location);
    if($locparsed['host'] != $gtsHost){
      //Reject Redirections To Foreign Hosts
      $gtiWrongHeader=2;
    }else{
      //Mark to Redirect
      $gtDoRedirect = $location.(($loc{strlen($loc)-1}=='/') ? '/':'');
      $RedirectsDone++;
    }
  }

  //Set Cookie
  htProcCookieSet($string);

  //Determine End of Header
  if(preg_match("/^\r?\n$/", $string)){
    $gHeaderWrited = 1;
    if(!empty($gtiWrongHeader)){
      $length = 0;
    }
  }

  $gResponceContent['Header'] .= $string;

  return $length;
}
/*=========================================*/

function htReadBody($ch, $string) {
  Global $gResponceContent, $gtiMaxSize, $gtMaxWrited, $gtiWrongHeader, $gtDoRedirect, $gbIncludeBody;//Internal

  $length = strlen($string);

  $gtMaxWrited = 0;

  //Reject Pages Which Headers Don't Satisfy General Conditions
  if(isset($gtiWrongHeader) && ($gtiWrongHeader == 1 || $gtiWrongHeader == 2)){
    $length = 0;
  }

  //Return on Header-Redirection Event
  if(!empty($gtDoRedirect)){
    $length = 0;
  }

  /** Some servers do not allow HEAD requests
    * Return on not required body
    */
  if(empty($gbIncludeBody)){
    $length = 0;
  }

  if(!empty($length)){
    $gResponceContent['Body'] .= $string;
  }

  //Reject Pages With Size Greater Than MaxSize
  if(!empty($gtiMaxSize) && $gtiMaxSize < strlen($gResponceContent['Body'])){
    $gResponceContent['Body'] = substr($gResponceContent['Body'], 0, $gtiMaxSize);
    $gtMaxWrited = 1;
    $length = 0;
  }

  return $length;
}
/*=========================================*/

function htProcSockCont($string, $IncludeBody) {
  Global $gResponceContent, $gtiWrongHeader, $gHeaderWrited;//Internal

  $tReturn = 1;
  $tLength = 0;
  //Determine New Analyze
  if(empty($gResponceContent['Header']) && empty($gResponceContent['Body'])){
    $gHeaderWrited=0;
  }

  if(empty($gHeaderWrited)){
    //Process Header Content
    $tLength = htReadHeader('', $string);
  }else{
    //Process Body Content
    if($IncludeBody != false && !(isset($gtiWrongHeader) && ($gtiWrongHeader == 1 || $gtiWrongHeader == 2))){
      $tLength = htReadBody('', $string);
    }
  }

  //Make Comparison For Determine Header or Body Process Error
  if(strlen($string) != $tLength){
    $tReturn = 0;
  }

  return $tReturn;
}
/*=========================================*/

function htProcCookieSet($HeadStr) {
  Global $gCookie;

  if(!isset($gCookie)){$gCookie = array();}

  $pos = strpos(trim(strtolower($HeadStr)), 'set-cookie');
  if($pos !== false && $pos == 0){
    $Coo = explode(';', trim(substr($HeadStr, (strpos($HeadStr, ':')+1))));
    if(sizeof($Coo) > 0 && strlen(trim($Coo[0])) > 0 && strpos(trim($Coo[0]), '=') > 0){
      foreach($Coo AS $key => $value){
        $pos = strpos($value, '=');
        if($pos > 0){
          $key1 = trim(substr($value, 0, $pos));
          $val1 = trim(substr($value, ($pos+1), strlen($value)));
          if($key == 0){
            if(isset($gCookie[$key1])){
              unset($gCookie[$key1]);
            }
            if($val1 != ''){
              $CooName = $key1;
              $gCookie[$CooName]['value'] = $val1;
            }else{
              continue;
            }
          }elseif(!empty($val1) && !empty($CooName)){
            if($key1 == 'expires'){
              $val1 = dtStrToTime($val1);
            }
            $gCookie[$CooName][$key1] = $val1;
          }
        }
      }
    }
  }

  return;
}
/*=========================================*/

function htProcCookieGet($URL) {
  Global $gCookie;

  if(!isset($gCookie)){$gCookie = array();}
  $tReturn = '';

  if(!empty($gCookie)){
    $Prsd = '';
    $Coo = array();
    foreach($gCookie AS $key => $value){
      if(!empty($gCookie[$key]['expires'])){
        if($gCookie[$key]['expires'] <= time()){
          unset($gCookie[$key]);
          continue;
        }
      }
      if(!empty($gCookie[$key]['domain'])){
        if(empty($Prsd)){$Prsd = htParseURL($URL);}
        if(empty($Prsd) || !preg_match('/'.preg_quote($gCookie[$key]['domain'], '/').'$/i', $Prsd['host'])){
          continue;
        }
      }
      if(!empty($gCookie[$key]['path'])){
        if(empty($Prsd)){$Prsd = htParseURL($URL);}
        if(empty($Prsd) || !preg_match('/^'.preg_quote($gCookie[$key]['path'], '/').'/', $Prsd['path'])){
          continue;
        }
      }
      $Coo[] = $key.'='.$gCookie[$key]['value'];
    }
    if(!empty($Coo)){
      $tReturn = implode('; ', $Coo);
    }
  }

  return $tReturn;
}
/*=========================================*/

//Get Response Headers
function htGetResponseHeaders($text) {
  //text - text of response (STR)

  $ahead=array();
  $repl=strpos($text, "\r\n\r\n");
  if($repl!==false){
    $text=substr($text, 0, $repl);
  }else{
    $repl=strpos($text, "\n\n");
    if($repl!==false){
      $text=substr($text, 0, $repl);
    }
  }
  $atmp=explode("\n", $text);
  foreach($atmp as $key=>$value){
    if($key==0){
      if(preg_match("/HTTP\/1\.(0|1) ([\d]{3})/i", $text, $areg)){
        $ahead['Status']=$areg[2];
      }else{//error
        return 0;
      }
    }else{
      $afield=array();
      $afield=explode(': ', $value);
      if(isset($afield[1])){
        $ahead[$afield[0]]=$afield[1];
      }
    }
  }
  return $ahead;
}
/*=========================================*/

function erGetMicroTime(){
  list($usec, $sec)=explode(' ', microtime());
  return ((float)$usec+(float)$sec);
}
/*=========================================*/

function dtStrToTime($str, $mode=0, $action=0){
  global $gUTC, $gCurUTC;

  //Check Date
  if(preg_match("/(\d{4}\-\d{2}\-\d{2})T(\d{2}\:\d{2}\:\d{2})Z/i", $str, $ar)){
    $tstamp=strtotime($ar[1].' '.$ar[2]);
  }elseif(preg_match("/(\d{4})\-(\d{2})\-(\d{2})T(\d{2})\:(\d{2})\:(\d{2})(\+|\-)(\d{2})\:(\d{2})/i", $str, $ar)){
    if($ar[7]=='+'){
      $ar[8]='-'.$ar[8];
      $ar[9]='-'.$ar[9];
    }
    $tstamp=gmmktime($ar[4]+$ar[8], $ar[5]+$ar[9], $ar[6], $ar[2], $ar[3], $ar[1]);
  }else{
    if($str=='now'){
      $tstamp=time();
      $mode=0;
    }else{
      $tstamp=strtotime($str);
    }
  }

  if($mode==1){
    $tstamp=$tstamp-$gUTC;
  }elseif($mode==2){
    $tstamp=$tstamp+$gUTC;
  }

  if(!empty($action) && $action == 1){
    $tstamp = $tstamp - $gCurUTC;
    $tstamp = $tstamp - dtGetDayLightSave($tstamp);
  }elseif(!empty($action) && $action == 2){
    $tstamp = $tstamp + $gCurUTC;
    $tstamp = $tstamp + dtGetDayLightSave($tstamp);
  }

  return $tstamp;
}
/*=========================================*/

function dtGetDayLightSave($tunix=0){
  Global $gSetDayLghtSave;

  $DLST = 0;
  if(empty($gSetDayLghtSave)){
    return $DLST;
  }

  if(empty($tunix)){
    $tunix = time();
  }

  $DLST = gmdate('I', $tunix);

  if(empty($DLST)){
    $tY = gmdate('Y', $tunix);
    $tM = array(3,10);
    foreach($tM AS $key => $value){
      for($i=31; $i>0; $i--){
        $tT = gmmktime(3, 0, 1, $value, $i, $tY);
        if(gmdate('w', $tT) == 0){
          $tM[$key]=$tT;
          unset($tT);
          break;
        }
      }
    }
    //$tT = time();
    $tT = $tunix;
    if($tT > $tM[0] && $tT < $tM[1]){
      $DLST=1;
    }
    unset($tM, $tT, $tY);
  }
  $DLST = $DLST*3600;

  return $DLST;
}
/*=========================================*/

function utf8_replaceEntity($result){
  $value = (int)$result[1];
  $string = '';
  $len = round(pow($value,1/8));
  for($i=$len;$i>0;$i--){
    $part = ($value & (255>>2)) | pow(2,7);
    if ( $i == 1 ) $part |= 255<<(8-$len);
    $string = chr($part) . $string;
    $value >>= 6;
  }
  return $string;
}
/*=========================================*/

function utf8_html_entity_decode($string){
  return preg_replace_callback('/&#([0-9]+);/u','utf8_replaceEntity',$string);
}
/*=========================================*/

/*Find words*/
function findWords($tDescr, $regularI = '', $regularO = '') {

  if ($regularI!='' && $regularO!='') {
    return 1;
  }

  $tDescr = mb_strtolower($tDescr, 'UTF-8');
  

 // $tReg_1 = $tReg_2 = 1;
  if ($regularI!='') {
    $tReg_1 = 0;
    $tMatch = 0;
    $tWords = array_count_values($regularI['words']);
    $regularI['words']=split(',',$regularI['words'][0]);
    //Check words
    foreach ($regularI['words'] as $word) {    
      $search_word = mb_strtolower(trim(correct_encoding($word)), 'UTF-8');    
      if (!empty($search_word) && mb_strpos(mb_strtolower($tDescr), $search_word) !== false) {
        $tMatch++;
      }
      $tWords++;
    }    
    
   //Check condition not
    if (($regularI['condition'] == 1 && $tMatch == $tWords) || ($regularI['condition'] == 0 && $tMatch > 0)) {
      $tReg_1=1;
    }
  }
  
 /* Second condition */

  if ($regularO!='') {
    $tMatch = 0;
    $tReg_2 = 1;
    $tWords = 0;
    $regularO['words']=split(',',$regularO['words'][0]);
   
    //Check words
    foreach ($regularO['words'] as $word) {    
      $search_word = mb_strtolower(trim(correct_encoding($word)), 'UTF-8');   
    //  var_dump($search_word,$tDescr); 
      if (!empty($search_word) && mb_strpos(mb_strtolower($tDescr), $search_word) !== false) {
        $tMatch++;
      }
      $tWords++;
    }

    //Check condition
    if (($regularO['condition'] == 1 && $tMatch == $tWords) || ($regularO['condition'] == 0 && $tMatch > 0)) {
      $tReg_2=0;
    }
  }
  
  /* Parse array */
  if ($tReg_1 || $tReg_2) {
    return 1;
  }
  /* Parse array */
  return 0;
}
/*=========================================*/

function detect_encoding($string) { 
  static $list = array('utf-8', 'windows-1251','ASCII');
 
  foreach ($list as $item) {
    $sample = iconv($item, $item, $string);
    if (md5($sample) == md5($string))
      return $item;
  }
  return null;
}

function correct_encoding($text) {
    $current_encoding = detect_encoding($text);
    $text = iconv($current_encoding, 'UTF-8', $text);
    return $text;
}
?>