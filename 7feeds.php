<?php
/*
Plugin Name: 7feeds ticker
Plugin URI: http://7feeds.com
Description: Flash based RSS ticker widget for WordPress. <a href="http://7feeds.com">Visit widget page</a> for more information.
Version: 1.10.6
Author: IOIX Ukraine
Author URI: http://ioix.com.ua

Copyright 2009, IOIX Ukraine

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/***************** DEFINE *****************/
//Get path
if(function_exists('plugins_url')){
  // 2.6 or better
  $movie = plugins_url('7feeds-news-ticker/rssinformer.swf');
  $path = plugins_url('7feeds-news-ticker/');
} else {
  // pre 2.6
  $movie = get_bloginfo('wpurl') . "/wp-content/plugins/7feeds-news-ticker/rssinformer.swf";
  $path = get_bloginfo('wpurl')."/wp-content/plugins/7feeds-news-ticker/";
}

define('_7FEEDS_PATH', $path);
define('_7FEEDS_MOVIE_PATH', $movie);
$GLOBALS['7FEEDS_ACTIVE'] = true;
$gaFONTS = array('Default','Arial','Courier','Simsun','Tahoma','Times new roman','Verdana');

/***************** DEFINE *****************/

_7feed_check_extensions();

function _7feed_check_extensions($main=true) {
  $GLOBALS['_7feeds_error_message'] = '';
  $show = false;

  if (!extension_loaded('curl') || !extension_loaded('mbstring')) {
    if ($main) {

      $GLOBALS['_7feeds_error_message'] = '7feeds warning: Server misconfiguration detected. Proceed to <a href="options-general.php?page=7feeds-news-ticker/7feeds.php">configuration page</a> | Dismiss';
      $show = true;

    }else {

      $aErr[] = 'cURL (<span style="color:'.(!extension_loaded('curl')?'#FF0000">false':'#00FF00">true').'</span>)';
      $aErr[] = 'MBString (<span style="color:'.(!extension_loaded('mbstring')?'#FF0000">false':'#00FF00">true').'</span>)';

      if (!empty($aErr)) {
        $GLOBALS['_7feeds_error_message'] = 'For correct functioning "7feeds" plugin requires following PHP Extensions:';
        foreach ($aErr as $key=>$val) {
          $GLOBALS['_7feeds_error_message'] .= '<BR>'.($key+1).') '.$val;
        }
        $GLOBALS['_7feeds_error_message'] .= '<BR>If you don\'t understand what it means, please contact your server administrator or hosting provider support staff and show then this error message!';
      }

      return $GLOBALS['_7feeds_error_message'];
    }
  }

  if ($show) {
    $GLOBALS['7FEEDS_ACTIVE'] = false;

    function _7feed_warning() {
      echo "<div id=\"_7feeds-warning\" style=\"background-color: #FE9090; border:1px solid #FFFFFF; color:#FFFFFF;\" class=\"updated\"><p><strong>".
      __($GLOBALS['_7feeds_error_message']).
      "</strong></p></div>";
    }

    add_action('admin_notices', '_7feed_warning');
    return;
  }

}

//initially set the options
function wp_7feeds_install () {
  $newoptions = get_option('wp7feeds_options');
  $newoptions['x_size'] = '180';
  $newoptions['y_size'] = '320';
  $newoptions['summary_length'] = '300';
  $newoptions['title_length'] = '100';
  $newoptions['scroll_speed'] = '50';
  $newoptions['num_of_entries'] = '5';
  $newoptions['pause_time'] = '3000';
  $newoptions['open_new_window'] = '1';
  $newoptions['feed_url'] = 'http://news.bbc.co.uk/';
  $newoptions['strip_tags'] = '0';
  $newoptions['theme'] = '0';
  $newoptions['widget_header'] = '1';
  $newoptions['news_content'] = '1';
  $newoptions['pub_time'] = '1';
  $newoptions['widget_title'] = '';
  $newoptions['widget_promote'] = '1';
  $newoptions['rounded_corners'] = '1';
  $newoptions['date_format'] = '';
  $newoptions['news_filter'] = '';
  $newoptions['news_filter_type'] = 0;
  $newoptions['news_filter_condition'] = 0;
  $newoptions['widget_font'] = 0;

  add_option('wp7feeds_options', $newoptions);
}

// add the admin page
function wp_7feeds_add_pages() {
  $page=add_options_page('7feeds ticker', '7feeds ticker', 8, __FILE__, 'wp_7feeds_options');
  add_action('admin_head', 'wp_7feeds_admin_head');
  add_action('admin_print_scripts-' . $page, 'wp_7feeds_admin_scripts');
}

// replace tag in content with tag cloud (non-shortcode version for WP 2.3.x)
function wp_7feeds_init($content){
  if( strpos($content, '[WP-7feeds]') === false ){
    return $content;
  } else {
    $code = wp_7feeds_createflashcode(false);
    $content = str_replace( '[WP-7feeds]', $code, $content );
    return $content;
  }
}

// template function
function wp_7feeds_insert( $atts=NULL ){
  echo wp_7feeds_createflashcode( false, $atts );
}

// shortcode function
function wp_7feeds_shortcode( $atts=NULL ){
 return wp_7feeds_createflashcode( false, $atts );
}

// piece together the flash code
function wp_7feeds_createflashcode( $widget=false, $atts=NULL, $widget_options = array(), $widgetId = '' ){
  static $aWidgetIds;
  global $gaFONTS;

  if ($GLOBALS['7FEEDS_ACTIVE'] === false) {
    return '';
  }

  if (!isset($aWidgetIds)) {
    $aWidgetIds = array();
  }

  //Gen id
  while (true) {
    srand(time());
    $num = rand(1,1000000);

    if (!in_array($num, $aWidgetIds)) {
      break;
    }
  }
  $aWidgetIds[] = $num;

  //Get options
  if ($widget && !empty($widget_options)) {
    $options = $widget_options;
  }elseif (!$widget && !empty($atts)) {
    $options = $atts;
  }

  if (!is_array($options['feed_url']) && unserialize($options['feed_url']) !== false) {
    $aTmp = unserialize($options['feed_url']);
    if (empty($aTmp)) {
      $options['feed_url'] = '';
    }
  }elseif (is_array($options['feed_url'])) {
    $options['feed_url'] = serialize($options['feed_url']);
  }else {
    $options['feed_url'] = serialize(array($options['feed_url']));
  }


  //Check box fields
  $aF = array('open_new_window','strip_tags','widget_header','news_content','pub_time','pause_time','rounded_corners');

  if (isset($options['widget_promote'])) {
    unset($options['widget_promote']);
  }

  if (!$widget) {
    foreach ($aF as $key=>$val) {
      if (!isset($options[$val])) {
        unset($aF[$key]);
      }
    }
    sort($aF);
  }

  $atOptions = get_option('wp7feeds_options');

  foreach ($atOptions as $key=>$val) {
    if (empty($options[$key])) {
      if (!in_array($key,$aF)) {
        $options[$key] = '';
      }else {
        $options[$key] = (int)$options[$key];
      }
    }

    if (!isset($options[$key]) || $options[$key] === '') {
      $options[$key] = $val;
    }
  }

  $aTmp = unserialize($options['feed_url']);
  if (!empty($widgetId) && is_array($aTmp) && !empty($aTmp)) {
    $options['feed_url'] = $widgetId;
  }elseif (is_array($aTmp) && !empty($aTmp)) {
    $options['feed_url'] = $aTmp[0];
  }

  $font = strtolower($gaFONTS[(int)$options['widget_font']]);

  $flashCode = '';

  $flashCode .= '<div id="wp-7feeds-flash_'.$num.'"></div>';

  $flashCode .= '<script type="text/javascript" src="'._7FEEDS_PATH.'js/swf_object.js"></script>';
  $flashCode .= '<script type="text/javascript">';
  $flashCode .= 'var so = new SWFObject("'._7FEEDS_MOVIE_PATH.'", "movie", "'.$options['x_size'].'", "'.$options['y_size'].'", "8", "#FFFFFF");';
  $flashCode .= 'so.addParam("wmode", "transparent");';
  $flashCode .= 'so.addVariable("version","1.4");';
  $flashCode .= 'so.addVariable("num_of_entries","'.$options['num_of_entries'].'");';
  $flashCode .= 'so.addVariable("title_text","7Feeds widget");';
  $flashCode .= 'so.addVariable("subtitle_text","subtitle");';
  $flashCode .= 'so.addVariable("title_format","center/'.$font.'/16/0x000000/b/ni/nu");';
  $flashCode .= 'so.addVariable("subtitle_format","center/'.$font.'/11/0x000000/b/ni/nu");';
  $flashCode .= 'so.addVariable("feed_title_format","'.wp_7feeds_get_theme_color($options['theme'], 'feed_title_format',0,$font).'");';
  $flashCode .= 'so.addVariable("feed_date_format","left/'.$font.'/12/0xC8BDBD/b/ni/nu");';
  $flashCode .= 'so.addVariable("feed_copy_format","left/'.$font.'/12/0xC8BDBD/b/i/nu");';
  $flashCode .= 'so.addVariable("feed_text_format","left/'.$font.'/12/0x000000/nb/ni/nu");';
  $flashCode .= 'so.addVariable("body_bgcolor","'.wp_7feeds_get_theme_color($options['theme'], 'body_bgcolor',0,$font).'");';
  $flashCode .= 'so.addVariable("title_bgcolor","0xEFEFEF");';
  $flashCode .= 'so.addVariable("subtitle_bgcolor","0xE3E3E3");';
  $flashCode .= 'so.addVariable("footer_bgcolor","'.wp_7feeds_get_theme_color($options['theme'], 'footer_bgcolor',0,$font).'");';
  $flashCode .= 'so.addVariable("feed_bgcolor","0xFFFFFF");';
  $flashCode .= 'so.addVariable("feed_highlight","'.wp_7feeds_get_theme_color($options['theme'], 'feed_highlight',0,$font).'");';
  $flashCode .= 'so.addVariable("border_color","'.wp_7feeds_get_theme_color($options['theme'], 'border_color',0,$font).'");';
  $flashCode .= 'so.addVariable("summary_length","'.$options['summary_length'].'");';
  $flashCode .= 'so.addVariable("title_length","'.$options['title_length'].'");';
  $flashCode .= 'so.addVariable("scroll_speed","'.$options['scroll_speed'].'");';
  $flashCode .= 'so.addVariable("item_spacing","12");';
  $flashCode .= 'so.addVariable("scroll_buttons","1");';
  $flashCode .= 'so.addVariable("pause_time","'.$options['pause_time'].'");';
  $flashCode .= 'so.addVariable("show_title","1");';
  $flashCode .= 'so.addVariable("show_subtitle","1");';
  $flashCode .= 'so.addVariable("show_publish_time","'.$options['pub_time'].'");';
  $flashCode .= 'so.addVariable("show_entry_summary","'.$options['news_content'].'");';
  $flashCode .= 'so.addVariable("widget_name","'.($options['widget_title'] == ''?'no name':$options['widget_title']).'");';
  $flashCode .= 'so.addVariable("show_body","'.$options['widget_header'].'");';
  $flashCode .= 'so.addVariable("show_entry_copyright","0");';
  $flashCode .= 'so.addVariable("show_footer","0");';
  $flashCode .= 'so.addVariable("use_rounded_corners","'.$options['rounded_corners'].'");';
  $flashCode .= 'so.addVariable("open_new_window","'.$options['open_new_window'].'");';
  $flashCode .= 'so.addVariable("show_url_field","0");';
  $flashCode .= 'so.addVariable("data_url","'._7FEEDS_PATH.'parser.php");';
  $flashCode .= 'so.addVariable("feed_url","'.$options['feed_url'].'");';
  if (isset($atts['news_filter'])) {
    $flashCode .= 'so.addVariable("news_filter","'.$atts['news_filter'].'");';
  }  
  if (isset($atts['news_filter_type'])) {
    $flashCode .= 'so.addVariable("news_filter_type","'.$atts['news_filter_type'].'");';
  }  
  if (isset($atts['news_filter_condition'])) {
    $flashCode .= 'so.addVariable("news_filter_condition","'.$atts['news_filter_condition'].'");';
  }  
  $flashCode .= 'so.addVariable("strip_tags","'.$options['strip_tags'].'");';
  $flashCode .= 'so.addVariable("elements_strcolor","0x777777");';
  $flashCode .= 'so.addVariable("elements_bgcols","0xFFF7E1");';
  $flashCode .= 'so.addVariable("footer_powered_format","right/'.$font.'/11/0x666666/nb/ni/nu");';
  $flashCode .= 'so.addVariable("footer_feedo_format","'.wp_7feeds_get_theme_color($options['theme'], 'footer_feedo_format',0,$font).'");';
  $flashCode .= 'so.addVariable("feed_back_color","'.wp_7feeds_get_theme_color($options['theme'], 'feed_back_color',0,$font).'");';
  $flashCode .= 'so.addVariable("feeds_num_color","'.wp_7feeds_get_theme_color($options['theme'], 'feeds_num_color',0,$font).'");';
  $flashCode .= 'so.addVariable("buttons_onpress_color","'.wp_7feeds_get_theme_color($options['theme'], 'buttons_onpress_color',0,$font).'");';
  $flashCode .= 'so.addVariable("x_size", "'.$options['x_size'].'");';
  $flashCode .= 'so.addVariable("y_size", "'.$options['y_size'].'");';

  $flashCode .= 'so.addVariable("lang1","Loading...");';
  $flashCode .= 'so.addVariable("lang2","Powered by");';

  $flashCode .= 'so.write("wp-7feeds-flash_'.$num.'");';

  $flashCode .= '</script>';
  if (isset($options['widget_promote']) && $options['widget_promote']) {
    $flashCode .= '<div align="center" style="width:'.$options['x_size'].'px; font-size: 7px;"><a href="http://7feeds.com" style="color: '.wp_7feeds_get_theme_color($options['theme'], 'border_color', 1).'">Powered by 7feeds</a></div>';
  }

  return $flashCode;
}

//Get theme color
function wp_7feeds_get_theme_color($index, $key, $hex=0, $font = 'arial') {

  $aData = array();
  //Default
  $aData[] = array("feed_title_format"=>"left/".$font."/14/0xfe9900/b/ni/nu","body_bgcolor"=>"0xffe0b2","footer_bgcolor"=>"0xffe0b2","feed_highlight"=>"0xffefd8","border_color"=>"0xfe9900","footer_feedo_format"=>"left/".$font."/11/0xfe9900/nb/ni/nu","feed_back_color"=>"0xfffbf2","feeds_num_color"=>"0xfe9900","buttons_onpress_color"=>"0xfe9900");

  $aData[] = array("feed_title_format"=>"left/".$font."/14/0xfe9900/b/ni/nu","body_bgcolor"=>"0xffe0b2","footer_bgcolor"=>"0xffe0b2","feed_highlight"=>"0xffefd8","border_color"=>"0xfe9900","footer_feedo_format"=>"left/".$font."/11/0xfe9900/nb/ni/nu","feed_back_color"=>"0xfffbf2","feeds_num_color"=>"0xfe9900","buttons_onpress_color"=>"0xfe9900");
  $aData[] = array("feed_title_format"=>"left/".$font."/14/0x6599ff/b/ni/nu","body_bgcolor"=>"0xd1e0ff","footer_bgcolor"=>"0xd1e0ff","feed_highlight"=>"0xe7f0ff","border_color"=>"0x6599ff","footer_feedo_format"=>"left/".$font."/11/0x6599ff/nb/ni/nu","feed_back_color"=>"0xf7faff","feeds_num_color"=>"0x6599ff","buttons_onpress_color"=>"0x6599ff");
  $aData[] = array("feed_title_format"=>"left/".$font."/14/0xcccb32/b/ni/nu","body_bgcolor"=>"0xf1f0c2","footer_bgcolor"=>"0xf1f0c2","feed_highlight"=>"0xf7f7df","border_color"=>"0xcccb32","footer_feedo_format"=>"left/".$font."/11/0xcccb32/nb/ni/nu","feed_back_color"=>"0xfcfbf6","feeds_num_color"=>"0xcccb32","buttons_onpress_color"=>"0xcccb32");
  $aData[] = array("feed_title_format"=>"left/".$font."/14/0xfe66cb/b/ni/nu","body_bgcolor"=>"0xffd0f0","footer_bgcolor"=>"0xffd0f0","feed_highlight"=>"0xffe8f7","border_color"=>"0xfe66cb","footer_feedo_format"=>"left/".$font."/11/0xfe66cb/nb/ni/nu","feed_back_color"=>"0xfff7fc","feeds_num_color"=>"0xfe66cb","buttons_onpress_color"=>"0xfe66cb");
  $aData[] = array("feed_title_format"=>"left/".$font."/14/0x676767/b/ni/nu","body_bgcolor"=>"0xd1d1d1","footer_bgcolor"=>"0xd1d1d1","feed_highlight"=>"0xe7e7e7","border_color"=>"0x676767","footer_feedo_format"=>"left/".$font."/11/0x676767/nb/ni/nu","feed_back_color"=>"0xf7f7f7","feeds_num_color"=>"0x676767","buttons_onpress_color"=>"0x676767");
  $aData[] = array("feed_title_format"=>"left/".$font."/14/0x339933/b/ni/nu","body_bgcolor"=>"0xc1e0c1","footer_bgcolor"=>"0xc1e0c1","feed_highlight"=>"0xe0efe0","border_color"=>"0x339933","footer_feedo_format"=>"left/".$font."/11/0x339933/nb/ni/nu","feed_back_color"=>"0xf5faf4","feeds_num_color"=>"0x339933","buttons_onpress_color"=>"0x339933");
  $aData[] = array("feed_title_format"=>"left/".$font."/14/0xcd3333/b/ni/nu","body_bgcolor"=>"0xf1c1c1","footer_bgcolor"=>"0xf1c1c1","feed_highlight"=>"0xf8dfdf","border_color"=>"0xcd3333","footer_feedo_format"=>"left/".$font."/11/0xcd3333/nb/ni/nu","feed_back_color"=>"0xfdf4f5","feeds_num_color"=>"0xcd3333","buttons_onpress_color"=>"0xcd3333");
  $aData[] = array("feed_title_format"=>"left/".$font."/14/0x9966ff/b/ni/nu","body_bgcolor"=>"0xe1d1ff","footer_bgcolor"=>"0xe1d1ff","feed_highlight"=>"0xefe7ff","border_color"=>"0x9966ff","footer_feedo_format"=>"left/".$font."/11/0x9966ff/nb/ni/nu","feed_back_color"=>"0xfaf7ff","feeds_num_color"=>"0x9966ff","buttons_onpress_color"=>"0x9966ff");
  $aData[] = array("feed_title_format"=>"left/".$font."/14/0x66cc68/b/ni/nu","body_bgcolor"=>"0xd1f0d1","footer_bgcolor"=>"0xd1f0d1","feed_highlight"=>"0xe8f7e7","border_color"=>"0x66cc68","footer_feedo_format"=>"left/".$font."/11/0x66cc68/nb/ni/nu","feed_back_color"=>"0xf7fcf6","feeds_num_color"=>"0x66cc68","buttons_onpress_color"=>"0x66cc68");

  $ret = isset($aData[$index][$key])?$aData[$index][$key]:$aData[0][$key];

  if ($hex) {
    $ret = str_replace('0x','#',$ret);
  }

  return $ret;
}

//Get theme select
function wp_7feeds_get_theme_select($name, $val) {

  $aData = array();
  $aData[] = 'Default';
  $aData[] = 'Active Orange';
  $aData[] = 'Sky Blue';
  $aData[] = 'Hot Mustard';
  $aData[] = 'Teen Pink';
  $aData[] = 'Just Grey';
  $aData[] = 'Deep Green';
  $aData[] = 'Sweet Cherry';
  $aData[] = 'Modern Violet';
  $aData[] = 'Spring Fresh';


  $options = '';
  for ($i=0;$i<count($aData); $i++) {
    $options .= '<option value="'.$i.'" '.($i==$val?'selected':'').' style="background: '.wp_7feeds_get_theme_color($i,'border_color',1).';">'.$aData[$i].'</option>';
  }

  return '<select name="'.$name.'">'.$options.'</select>';
}

// options page
function wp_7feeds_options() {

  if ($GLOBALS['7FEEDS_ACTIVE'] === false) {
    echo '<div id="_7feeds-warning" style="background-color: #FE9090; border:1px solid #FFFFFF; color:#FFFFFF;" class="updated"><p><strong>'._7feed_check_extensions(false).'</strong></p></div>';
    exit;
  }

  $options = $newoptions = get_option('wp7feeds_options');

  // if submitted, process results
  if ( $_POST["wp7feeds_submit"] ) {
    $newoptions['x_size'] = strip_tags(stripslashes($_POST["x_size"]));
    $newoptions['y_size'] = strip_tags(stripslashes($_POST["y_size"]));
    $newoptions['summary_length'] = strip_tags(stripslashes($_POST["summary_length"]));
    $newoptions['title_length'] = strip_tags(stripslashes($_POST["title_length"]));
    $newoptions['scroll_speed'] = strip_tags(stripslashes($_POST["scroll_speed"]));
    $newoptions['num_of_entries'] = strip_tags(stripslashes($_POST["num_of_entries"]));
    $newoptions['pause_time'] = strip_tags(stripslashes($_POST["pause_time"]));
    $newoptions['open_new_window'] = strip_tags(stripslashes($_POST["open_new_window"]));

    if (is_array($_POST["feed_url"])) {
      $aTmp = array();
      foreach ($_POST["feed_url"] as $val) {
        if (empty($val)) continue;
        $aTmp[] = strip_tags(stripslashes($val));
      }
      $newoptions['feed_url'] = serialize($aTmp);
    }else {
      $newoptions['feed_url'] = strip_tags(stripslashes($_POST["feed_url"]));
    }

    $newoptions['news_order'] = (int)$_POST["news_order"];
    $newoptions['strip_tags'] = strip_tags(stripslashes($_POST["strip_tags"]));
    $newoptions['theme'] = strip_tags(stripslashes($_POST["theme"]));
    $newoptions['widget_header'] = strip_tags(stripslashes($_POST["widget_header"]));
    $newoptions['news_content'] = strip_tags(stripslashes($_POST["news_content"]));
    $newoptions['pub_time'] = strip_tags(stripslashes($_POST["pub_time"]));
    $newoptions['widget_title'] = strip_tags(stripslashes($_POST["widget_title"]));
    $newoptions['widget_promote'] = strip_tags(stripslashes($_POST["widget_promote"]));
    $newoptions['rounded_corners'] = $_POST["rounded_corners"];
    $newoptions['date_format'] = strip_tags(stripslashes($_POST["date_format"]));
    
    $newoptions['news_filter'] = strip_tags(stripslashes($_POST["news_filter"]));
    $newoptions['news_filter_type'] = (int)$_POST["news_filter_type"];
    $newoptions['news_filter_condition'] = (int)$_POST["news_filter_condition"];
    $newoptions['widget_font'] = (int)$_POST["widget_font"];
  }
  // any changes? save!
  if ( $options != $newoptions ) {
    $options = $newoptions;
    update_option('wp7feeds_options', $options);
  }
  // options form
  echo _7feed_get_javaScript();

  if (isset($_POST['wp7feeds_submit'])) {
    echo '<div id="message" class="updated fade"><p><strong>7feeds: Settings updated!</strong></p></div>';
  }

  echo '<form method="post">';
  echo "<div class=\"wrap\"><h2>Default display options</h2>";

  ?>
  
  <script>
  function _7feedsCollapse() {

    var el = document.getElementById('_help');
    if(el != null) {
      var s = (el.style.display == 'none'?1:0);
      el.style.display = (s==1?'':'none');

      /*var el = document.getElementById('collapse');
      if(el != null) {
      el.innerHTML = (s==1?'-':'+');
      }*/
    }
  }
  </script>
  
  <span style="cursor: pointer;" onclick="_7feedsCollapse();">You can use wp shortcodes [wp-7feeds]. Click here to show shortcode parameters >></span><br>
  <div class="updated fade" style="display: none;" id="_help">
  <b>Shortcode:</b><br>
  [wp-7feeds parameters]<br><br>
  
  <b>Parameters:</b><br>
  [x_size = "180"]<br>
  [y_size = "320"]<br>
  [summary_length = "300"]<br>
  [title_length = "100"]<br>
  [scroll_speed = "50"]<br>
  [num_of_entries = "5"]<br>
  [pause_time = "3000"]<br>
  [open_new_window = "1"]<br>
  [feed_url = "http://news.bbc.co.uk/"]<br>
  [strip_tags = "0"]<br>
  [theme = "0"] - values {from 0 to 8}<br>
  [widget_header = "1"]<br>
  [news_content = "1"]<br>
  [pub_time = "1"]<br>
  [widget_title = 1]<br>
  [widget_promote = "1"]<br>
  [rounded_corners = "1"]<br>
  [news_filter = "news,relax"]<br>
  [news_filter_type = "1"]<br>
  [news_filter_condition = "0"]<br>
  
  <b>Example:</b><br>
  [wp-7feeds feed_url = "http://news.bbc.co.uk/" pub_time = "1" rounded_corners = "1" theme = "2"  news_filter = "news" news_filter_type = "1" news_filter_condition = "0"]<br><br>
  </div>
  <?php

  echo '<div id="web_invoice_settings_tab_pane" class="web_invoice_tab_pane">
	<ul>
		<li><a href="#7feeds_widget_settings"><span>Widget settings</span></a></li>
		<li><a href="#7feeds_items_settings"><span>News items settings</span></a></li>
		<li><a href="#7feeds_content"><span>Content</span></a></li>
	</ul>';

  //Widget settings
  echo '<div id="7feeds_widget_settings"><table class="form-table">';
  // Select theme
  echo '<tr valign="top"><th scope="row">Select theme</th>';
  echo '<td>'.wp_7feeds_get_theme_select('theme',$options['theme']).'</td></tr>';

  // width
  echo '<tr valign="top"><th scope="row">Width of the widget</th>';
  echo '<td><input type="text" name="x_size" value="'.$options['x_size'].'" size="5"></input> Width in pixels</td></tr>';

  // height
  echo '<tr valign="top"><th scope="row">Height of the widget</th>';
  echo '<td><input type="text" name="y_size" value="'.$options['y_size'].'" size="5"></input> Height in pixels</td></tr>';

  // Corners
  echo '<tr valign="top"><th scope="row">Rounded corners</th>';
  echo '<td><input type="checkbox" name="rounded_corners" value="1"';
  if( $options['rounded_corners'] == "1" ){ echo ' checked="checked"'; }
  echo '></input></td></tr>';

  //Widget font
  echo '<tr valign="top"><th scope="row">Widget font</th>';
  echo '<td>'.wp_7feeds_font_select('widget_font', $newoptions['widget_font']).'</td></tr>';

  // Help to promote
  echo '<tr valign="top"><th scope="row">Help to promote 7feeds</th>';
  echo '<td><input type="checkbox" name="widget_promote" value="1"';
  if( $options['widget_promote'] == "1" ){ echo ' checked="checked"'; }
  echo '></input></td></tr>';

  echo '</table></div>';

  //Items settings
  echo '<div id="7feeds_items_settings"><table class="form-table">';

  // News order
  echo '<tr valign="top"><th scope="row">News order</th>';
  echo '<td><select name="news_order"><option value="0">Consistent</option><option value="1" '.($options['news_order']==1?'selected':'').'>Random</option><option value="2" '.($options['news_order']==2?'selected':'').'>Order by date asc</option><option value="3" '.($options['news_order']==3?'selected':'').'>Order by date desc</option></select></td></tr>';

  // Number of entries
  echo '<tr valign="top"><th scope="row">Number of entries</th>';
  echo '<td><input type="text" name="num_of_entries" value="'.$options['num_of_entries'].'" size="3"></input></td></tr>';

  // title length
  echo '<tr valign="top"><th scope="row">News title length, chars</th>';
  echo '<td><input type="text" name="title_length" value="'.$options['title_length'].'" size="5"></input></td></tr>';

  // text length
  echo '<tr valign="top"><th scope="row">News content length, chars</th>';
  echo '<td><input type="text" name="summary_length" value="'.$options['summary_length'].'" size="5"></input></td></tr>';

  // Scroll Speed
  echo '<tr valign="top"><th scope="row">Scrolling speed</th>';
  echo '<td><input type="text" name="scroll_speed" value="'.$options['scroll_speed'].'" size="3"></input></td></tr>';

  // Pause time
  echo '<tr valign="top"><th scope="row">Pause time</th>';
  echo '<td><input type="text" name="pause_time" value="'.$options['pause_time'].'" size="6"></input> Set 0 to disable pausing</td></tr>';

  //Widget header
  echo '<tr valign="top"><th scope="row">Widget header</th>';
  echo '<td><input type="checkbox" name="widget_header" value="1"';
  if( $options['widget_header'] == "1" ){ echo ' checked="checked"'; }
  echo '></input> Check to show widget header from RSS feed title</td></tr>';

  // Custom widget title
  echo '<tr valign="top"><th scope="row">Custom widget title</th>';
  echo '<td><input type="text" name="widget_title" value="'.$options['widget_title'].'" size="60"></input></td></tr>';

  //News content
  echo '<tr valign="top"><th scope="row">News content</th>';
  echo '<td><input type="checkbox" name="news_content" value="1"';
  if( $options['news_content'] == "1" ){ echo ' checked="checked"'; }
  echo '></input> Check to show news content (unchecked - headlines only)</td></tr>';

  //pub time
  echo '<tr valign="top"><th scope="row">Pub time</th>';
  echo '<td><input type="checkbox" name="pub_time" value="1"';
  if( $options['pub_time'] == "1" ){ echo ' checked="checked"'; }
  echo '></input> Check to show Pub time of News Item</td></tr>';

  // Custom date format
  if (empty($options['date_format'])) {
    $options['date_format'] = 'l, d F, Y H:i';
  }

  echo '<tr valign="top"><th scope="row">Custom date format</th>';
  echo '<td><input type="text" name="date_format" value="'.$options['date_format'].'" size="20"></input><br>
  Example:<br>
  Y/m/d H:i:s ('.date('Y/m/d H:i:s').')<br>
  l, d F, Y H:i ('.date('l, d F, Y H:i').')<br>
  For more information of valid format of the outputted date/time check <a href="http://php.net/manual/en/function.date.php">PHP documentation.</a></td></tr>';

  // Strip tags
  echo '<tr valign="top"><th scope="row">Strip tags</th>';
  echo '<td><input type="checkbox" name="strip_tags" value="1"';
  if( $options['strip_tags'] == "1" ){ echo ' checked="checked"'; }
  echo '></input> Check to strip tags</td></tr>';

  // Open in new window
  echo '<tr valign="top"><th scope="row">Open links in new windows</th>';
  echo '<td><input type="checkbox" name="open_new_window" value="1"';
  if( $options['open_new_window'] == "1" ){ echo ' checked="checked"'; }
  echo '></input></td></tr>';

  echo '</table></div>';

  //Content
  echo '<div id="7feeds_content"><table class="form-table">';

  // Feed url
  echo '<tr valign="top"><th scope="row">Feed\'s URL</th>';
  //echo '<td><input type="text" name="feed_url" value="'.$options['feed_url'].'" size="60"></input></td></tr>';
  echo '<td>';
  echo _7feed_multi_fields('feed_url', $options['feed_url'], 'size="60"', 'feed_url_id', false);
  echo '</td></tr>';

  // News filter
  echo '<tr valign="top"><th scope="row">Filter type</th>';
  echo '<td><select name="news_filter_type"><option value="0">Contains</option><option value="1" '.($options['news_filter_type']==1?'selected':'').'>Does not contain</option></select></td></tr>';

  echo '<tr valign="top"><th scope="row">Filter condition</th>';
  echo '<td><select name="news_filter_condition"><option value="0">OR</option><option '.($options['news_filter_condition']==1?'selected':'').' value="1">AND</option></select></td></tr>';

  echo '<tr valign="top"><th scope="row">News filter</th>';
  echo '<td><textarea cols="60" rows="7" name="news_filter">'.addslashes($options['news_filter']).'</textarea><br>For enter several words, separate they with coma</td></tr>';

  echo '</table></div>';

  // end table
  echo '</table>';
  echo '<input type="hidden" name="wp7feeds_submit" value="true"></input>';
  echo '<p class="submit"><input type="submit" value="Update Options &raquo;"></input></p>';
  echo "</div>";
  echo '</form>';

}

function _7feed_get_javaScript() {
  $cnt = '';

  $cnt .= '<script>';

  $cnt .= 'var _7feed_gen_id_number = 1;
  function _7feeds_add_field(id){
  var el = document.getElementById(id);
  
  var insBF = true;
  var parent = document.getElementById(id+"_last_br");
  if(parent == null) {
    insBF = false;
    parent = el;
  }
  
  var clone = el.cloneNode(true);
  clone.value="";
  clone.id+="_script_"+_7feed_gen_id_number;
  _7feed_gen_id_number++;
  
  var br_el = document.createElement("BR");
  br_el.id = clone.id+"_br";
  if(insBF) {
    parent.parentNode.insertBefore(br_el, parent);
    parent.parentNode.insertBefore(clone, parent);
  }else {
    parent.parentNode.appendChild(br_el);
    parent.parentNode.appendChild(clone);
  }
  
  var span_el = document.createElement("SPAN");
  span_el.innerHTML = "&nbsp;";
  span_el.id = clone.id+"_x";
  
  if(insBF) {
    parent.parentNode.insertBefore(span_el, parent);
  }else {
    parent.parentNode.appendChild(span_el);
  }
  
  var a_el = document.createElement("A");
  a_el.innerHTML = "X";  
  a_el.href = "javascript:_7feeds_rem_field(\'"+clone.id+"\'); ";
  span_el.appendChild(a_el);
  }';

  $cnt .= 'function _7feeds_rem_field(id){
  var el = document.getElementById(id);
  if(el!=null)el.parentNode.removeChild(el);
  
  var el = document.getElementById(id+\'_x\');
  if(el!=null)el.parentNode.removeChild(el);
  
  var el = document.getElementById(id+\'_br\');
  if(el!=null)el.parentNode.removeChild(el);
  }';

  $cnt .= '</script>';

  return $cnt;
}

function _7feed_multi_fields($name, $value, $action, $id, $br=true) {

  $aTmp = array();
  if (!empty($value)) {
    $aTmp = unserialize($value);
  }

  if (empty($aTmp) && !is_array($aTmp)){
    $aTmp[] = $value;
  }elseif (empty($aTmp)) {
    $aTmp[] = '';
  }

  $cnt = '';
  $i=0;
  $c = count($aTmp);
  for ($i=0; $i<$c; $i++) {
    if ($i == 0) {
      $el_id = $id;
      //$action .= ' id="'.$id.'"';
    }else {
      $el_id = $id.'_'.$i;
      //$action .= ' id="'.$id.'_'.'"';
    }

    if ($br) {
      $cnt .= '<BR  id="'.$el_id.'_br" />';
    }
    $br = true;

    $cnt .= '<input type="text" name="'.$name.'[]" value="'.$aTmp[$i].'" id="'.$el_id.'" />'.($i > 0?' <a href="javascript:_7feeds_rem_field(\''.$el_id.'\'); "  id="'.$el_id.'_x">X</a>':'');
  }

  //Add link
  $cnt .= '<BR id="'.$id.'_last_br"><a href="javascript: _7feeds_add_field(\''.$id.'\');">Add more feeds</a>';

  return $cnt;
}

function wp_7feeds_font_select($name, $sel) {
  global $gaFONTS;

  $str = '<select name="'.$name.'">';

  $c = count($gaFONTS);
  for ($i=0; $i<$c;$i++) {
    $str .= '<option value="'.$i.'"'.($i==$sel?' selected':'').'>'.$gaFONTS[$i].'</option>';
  }

  return $str.'</select>';

}

//uninstall all options
function wp_7feeds_uninstall () {
  //delete_option('wp7feeds_options');
  //delete_option('widget_7feeds-widget');
}


// widget
/*** CLASS ***/
### Class: WP-7feeds ticket
class WP_Widget_7feeds extends WP_Widget {
  // Constructor
  function WP_Widget_7feeds() {
    $widget_ops = array('description' => __('Customized RSS news ticker/scroller for sidebar', 'wp-7feeds'));
    $this->WP_Widget('7feeds-widget', __('7feeds', 'wp-7feeds'), $widget_ops);
  }

  // Display Widget
  function widget($args, $options) {
    extract($args);

    echo $before_widget;
    if( !empty($options['title']) ):echo $before_title . $options['title'] . $after_title;
    endif;

    if( !stristr( $_SERVER['PHP_SELF'], 'widgets.php' ) ){
      echo wp_7feeds_createflashcode(true, NULL, $options, $this->number);
    }
    echo $after_widget;
  }

  // When Widget Control Form Is Posted
  function update($new_instance, $newoptions) {

    if (!isset($_POST['wp7feeds_widget_submit'])) {
      return false;
    }

    $newoptions['x_size'] = strip_tags(stripslashes($_POST["wp7feeds_widget_x_size"]));
    $newoptions['y_size'] = strip_tags(stripslashes($_POST["wp7feeds_widget_y_size"]));
    $newoptions['summary_length'] = strip_tags(stripslashes($_POST["wp7feeds_widget_summary_length"]));
    $newoptions['title_length'] = strip_tags(stripslashes($_POST["wp7feeds_widget_title_length"]));
    $newoptions['scroll_speed'] = strip_tags(stripslashes($_POST["wp7feeds_widget_scroll_speed"]));
    $newoptions['num_of_entries'] = strip_tags(stripslashes($_POST["wp7feeds_widget_num_of_entries"]));
    $newoptions['pause_time'] = strip_tags(stripslashes($_POST["wp7feeds_widget_pause_time"]));
    $newoptions['open_new_window'] = strip_tags(stripslashes($_POST["wp7feeds_widget_open_new_window"]));

    //$newoptions['feed_url'] = strip_tags(stripslashes($_POST["wp7feeds_widget_feed_url"]));
    if (is_array($_POST["wp7feeds_widget_feed_url"])) {
      $aTmp = array();
      foreach ($_POST["wp7feeds_widget_feed_url"] as $val) {
        if (empty($val)) continue;
        $aTmp[] = strip_tags(stripslashes($val));
      }
      $newoptions['feed_url'] = serialize($aTmp);
    }else {
      $newoptions['feed_url'] = strip_tags(stripslashes($_POST["wp7feeds_widget_feed_url"]));
    }

    $newoptions['news_order'] = (int)$_POST["wp7feeds_widget_news_order"];

    $newoptions['strip_tags'] = strip_tags(stripslashes($_POST["wp7feeds_widget_strip_tags"]));
    $newoptions['theme'] = strip_tags(stripslashes($_POST["wp7feeds_theme"]));
    $newoptions['widget_header'] = strip_tags(stripslashes($_POST["wp7feeds_widget_header"]));
    $newoptions['news_content'] = strip_tags(stripslashes($_POST["wp7feeds_news_content"]));
    $newoptions['pub_time'] = strip_tags(stripslashes($_POST["wp7feeds_pub_time"]));
    $newoptions['widget_title'] = strip_tags(stripslashes($_POST["wp7feeds_widget_title"]));
    $newoptions['rounded_corners'] = strip_tags(stripslashes($_POST["wp7feeds_rounded_corners"]));
    $newoptions['title'] = strip_tags(stripslashes($_POST["wp7feeds_title"]));

    $newoptions['news_filter'] = strip_tags(stripslashes($_POST["wp7feeds_news_filter"]));
    $newoptions['news_filter_type'] = (int)$_POST["wp7feeds_news_filter_type"];
    $newoptions['news_filter_condition'] = (int)$_POST["wp7feeds_news_filter_condition"];
    $newoptions['widget_font'] = (int)$_POST["wp7feeds_widget_font"];

    return $newoptions;
  }

  // DIsplay Widget Control Form
  function form($options) {

    if (empty($options)) {
      $options = get_option('wp7feeds_options');
      foreach ($options as $key=>$val) {
        if ($val != '1' && $val != '0' && !is_numeric($val)) {
          $options[$key] = '';
        }
      }
    }

    $x_size = attribute_escape($options['x_size']);
    $y_size = attribute_escape($options['y_size']);
    $summary_length = attribute_escape($options['summary_length']);
    $title_length = attribute_escape($options['title_length']);
    $scroll_speed = attribute_escape($options['scroll_speed']);
    $num_of_entries = attribute_escape($options['num_of_entries']);
    $pause_time = attribute_escape($options['pause_time']);
    $open_new_window = attribute_escape($options['open_new_window']);

    //$feed_url = attribute_escape($options['feed_url']);
    $feed_url = $options['feed_url'];
    $news_order = attribute_escape($options['news_order']);

    $strip_tags = attribute_escape($options['strip_tags']);
    $theme = attribute_escape($options['theme']);
    $widget_header = attribute_escape($options['widget_header']);
    $news_content = attribute_escape($options['news_content']);
    $pub_time = attribute_escape($options['pub_time']);
    $widget_title = attribute_escape($options['widget_title']);
    $rounded_corners = attribute_escape($options['rounded_corners']);
    $title = attribute_escape($options['title']);

    $news_filter = $options['news_filter'];
    $news_filter_type = $options['news_filter_type'];
    $news_filter_condition = $options['news_filter_condition'];
    $widget_font = $options['widget_font'];

    echo _7feed_get_javaScript();
		?>
			<p><label for="wp7feeds_title"><?php _e('Title (optional):'); ?> <input class="widefat" id="wp7feeds_title" name="wp7feeds_title" type="text" value="<?php echo $title; ?>" /></label></p>
			<p><label for="wp7feeds_widget_x_size"><?php _e('Width (optional):'); ?> <input class="widefat" id="wp7feeds_widget_x_size" name="wp7feeds_widget_x_size" type="text" value="<?php echo $x_size; ?>" /></label></p>
			<p><label for="wp7feeds_widget_y_size"><?php _e('Height (optional):'); ?> <input class="widefat" id="wp7feeds_widget_y_size" name="wp7feeds_widget_y_size" type="text" value="<?php echo $y_size; ?>" /></label></p>
			<p><label for="wp7feeds_widget_summary_length"><?php _e('Item length (optional):'); ?> <input class="widefat" id="wp7feeds_widget_summary_length" name="wp7feeds_widget_summary_length" type="text" value="<?php echo $summary_length; ?>" /></label></p>
			<p><label for="wp7feeds_widget_title_length"><?php _e('Title length (optional):'); ?> <input class="widefat" id="wp7feeds_widget_title_length" name="wp7feeds_widget_title_length" type="text" value="<?php echo $title_length; ?>" /></label></p>
			<p><label for="wp7feeds_widget_scroll_speed"><?php _e('Scrolling speed (optional):'); ?> <input class="widefat" id="wp7feeds_widget_scroll_speed" name="wp7feeds_widget_scroll_speed" type="text" value="<?php echo $scroll_speed; ?>" /></label></p>
			<p><label for="wp7feeds_widget_num_of_entries"><?php _e('Number of items (optional):'); ?> <input class="widefat" id="wp7feeds_widget_num_of_entries" name="wp7feeds_widget_num_of_entries" type="text" value="<?php echo $num_of_entries; ?>" /></label></p>
			<p><label for="wp7feeds_widget_pause_time"><?php _e('Pause time (optional):'); ?> <input class="widefat" id="wp7feeds_widget_pause_time" name="wp7feeds_widget_pause_time" type="text" value="<?php echo $pause_time; ?>" /></label></p>
			<p><label for="wp7feeds_widget_open_new_window"><input class="checkbox" id="wp7feeds_widget_open_new_window" name="wp7feeds_widget_open_new_window" type="checkbox" value="1" <?php if( $open_new_window == "1" ){ echo ' checked="checked"'; } ?> > Open link in new window</label></p>
			
			<p><?php _e('Feed\'s URL (optional):'); echo _7feed_multi_fields('wp7feeds_widget_feed_url', $feed_url, ' class="widefat"', $this->id.'feed_url');?></p>
			<p><?php _e('News order (optional):');?>
			 <select name="wp7feeds_widget_news_order">
			   <option value="0">Consistent</option>
			   <option value="1" <? if( $news_order == 1 ){ echo 'selected'; } ?>>Random</option>
         <option value="2" <? if( $news_order == 2 ){ echo 'selected'; } ?> >Order by date asc</option>
         <option value="3" <? if( $news_order == 3 ){ echo 'selected'; } ?> >Order by date desc</option>			   
			 </select>
			</p>
			
			<p><?php _e('Select theme:');  echo wp_7feeds_get_theme_select('wp7feeds_theme',$theme)?></p>
			
			<p><label for="wp7feeds_rounded_corners"><input class="checkbox" id="wp7feeds_rounded_corners" name="wp7feeds_rounded_corners" type="checkbox" value="1" <?php if( $rounded_corners == "1" ){ echo ' checked="checked"'; } ?> > Rounded corners</label></p>
			
			<p><label for="wp7feeds_widget_header"><input class="checkbox" id="wp7feeds_widget_header" name="wp7feeds_widget_header" type="checkbox" value="1" <?php if( $widget_header == "1" ){ echo ' checked="checked"'; } ?> > Widget header</label></p>
			<p><label for="wp7feeds_news_content"><input class="checkbox" id="wp7feeds_news_content" name="wp7feeds_news_content" type="checkbox" value="1" <?php if( $news_content == "1" ){ echo ' checked="checked"'; } ?> > News content</label></p>
			<p><label for="wp7feeds_pub_time"><input class="checkbox" id="wp7feeds_pub_time" name="wp7feeds_pub_time" type="checkbox" value="1" <?php if( $pub_time == "1" ){ echo ' checked="checked"'; } ?> > Pub time</label></p>    
      			
			<p><label for="wp7feeds_news_filter"><?php _e('News filter (optional):'); ?> <textarea class="widefat" rows="7" name="wp7feeds_news_filter"><?php echo addslashes($news_filter)?></textarea><br>For enter several words, separate they with coma</label></p>
			<p><label for="wp7feeds_news_filter_type"><?php _e('Filter type:'); ?> <select name="wp7feeds_news_filter_type"><option value="0">Contains</option><option value="1" <?php echo ($news_filter_type==1?'selected':'')?>>Does not contain</option></select></label></p>
			<p><label for="wp7feeds_news_filter_condition"><?php _e('Filter condition:'); ?> <select name="wp7feeds_news_filter_condition"><option value="0">OR</option><option <?php echo ($news_filter_condition==1?'selected':'')?> value="1">AND</option></select></label></p>
			
			<p><label for="wp7feeds_widget_font"><?php _e('Widget font:'); echo wp_7feeds_font_select('wp7feeds_widget_font', $widget_font);?> </label></p>
			
			<p><label for="wp7feeds_widget_title"><?php _e('Widget title (optional):'); ?> <input class="widefat" id="wp7feeds_widget_title" name="wp7feeds_widget_title" type="text" value="<?php echo $widget_title; ?>" /></label></p>
			
			<p><label for="wp7feeds_widget_strip_tags"><input class="checkbox" id="wp7feeds_widget_strip_tags" name="wp7feeds_widget_strip_tags" type="checkbox" value="1" <?php if( $strip_tags == "1" ){ echo ' checked="checked"'; } ?> > Strip tags</label></p>
			
			<input type="hidden" id="wp7feeds_widget_submit" name="wp7feeds_widget_submit" value="1" />
		<?php

  }
}

### Function: Init 7feeds Widget
add_action('widgets_init', 'widget_7feeds_init');
function widget_7feeds_init() {

  if(is_admin()) {
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('jquery-ui-tabs');
    
    wp_enqueue_script('jquery-cookie',_7FEEDS_PATH.'js/jquery.cookie.js', array('jquery'));    
    wp_register_script('7feeds_ticker',_7FEEDS_PATH.'js/7feeds.js');

  }
  
  if ($GLOBALS['7FEEDS_ACTIVE'] === true) {
    register_widget('WP_Widget_7feeds');
  }
}

function wp_7feeds_admin_head() {
  echo "<link rel='stylesheet' href='"._7FEEDS_PATH."wp_admin.css' type='text/css'type='text/css' media='all' />";
}

function wp_7feeds_admin_scripts() {
  wp_enqueue_script('7feeds_ticker');
}
/*** CLASS ***/

// add the actions
add_action('admin_menu', 'wp_7feeds_add_pages');
register_activation_hook( __FILE__, 'wp_7feeds_install' );
register_deactivation_hook( __FILE__, 'wp_7feeds_uninstall' );
if( function_exists('add_shortcode') ){
  add_shortcode('wp-7feeds', 'wp_7feeds_shortcode');
  add_shortcode('WP-7feeds', 'wp_7feeds_shortcode');
} else {
  add_filter('the_content','wp_7feeds_init');
}

?>