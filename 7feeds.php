<?php
/*
Plugin Name: 7feeds ticker
Plugin URI: http://7feeds.com
Description: Flash based RSS ticker widget for WordPress. <a href="http://7feeds.com">Visit widget page</a> for more information.
Version: 1.02
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

//initially set the options
function wp_7feeds_install () {
  $newoptions = get_option('wp7feeds_options');
  $newoptions['x_size'] = '180';
  $newoptions['y_size'] = '320';
  $newoptions['summary_length'] = '300';
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

  add_option('wp7feeds_options', $newoptions);
}

// add the admin page
function wp_7feeds_add_pages() {
  add_options_page('7feeds ticker', '7feeds ticker', 8, __FILE__, 'wp_7feeds_options');
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
function wp_7feeds_createflashcode( $widget=false, $atts=NULL, $widget_options = array() ){
  static $aWidgetIds;

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
  }

  //Check box fields
  $aF = array('open_new_window','strip_tags','widget_header','news_content','pub_time');
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

  // get some paths
  if( function_exists('plugins_url') ){
    // 2.6 or better
    $movie = plugins_url('7feeds-news-ticker/rssinformer.swf');
    $path = plugins_url('7feeds-news-ticker/');
  } else {
    // pre 2.6
    $movie = get_bloginfo('wpurl') . "/wp-content/plugins/7feeds-news-ticker/rssinformer.swf";
    $path = get_bloginfo('wpurl')."/wp-content/plugins/7feeds-news-ticker/";
  }

  $flashCode = '';

  $flashCode .= '<div id="wp-7feeds-flash_'.$num.'"></div>';

  $flashCode .= '<script type="text/javascript" src="'.$path.'swf_object.js"></script>';
  $flashCode .= '<script type="text/javascript">';
  $flashCode .= 'var so = new SWFObject("'.$movie.'", "movie", "'.$options['x_size'].'", "'.$options['y_size'].'", "8", "#FFFFFF");';
  $flashCode .= 'so.addVariable("version","1.4");';
  $flashCode .= 'so.addVariable("num_of_entries","'.$options['num_of_entries'].'");';
  $flashCode .= 'so.addVariable("title_text","7Feeds widget");';
  $flashCode .= 'so.addVariable("subtitle_text","subtitle");';
  $flashCode .= 'so.addVariable("title_format","center/_sans/16/0x000000/b/ni/nu");';
  $flashCode .= 'so.addVariable("subtitle_format","center/verdana/11/0x000000/b/ni/nu");';
  $flashCode .= 'so.addVariable("feed_title_format","'.wp_7feeds_get_theme_color($options['theme'], 'feed_title_format').'");';
  $flashCode .= 'so.addVariable("feed_date_format","left/arial/12/0xC8BDBD/b/ni/nu");';
  $flashCode .= 'so.addVariable("feed_copy_format","left/arial/12/0xC8BDBD/b/i/nu");';
  $flashCode .= 'so.addVariable("feed_text_format","left/arial/12/0x000000/nb/ni/nu");';
  $flashCode .= 'so.addVariable("body_bgcolor","'.wp_7feeds_get_theme_color($options['theme'], 'body_bgcolor').'");';
  $flashCode .= 'so.addVariable("title_bgcolor","0xEFEFEF");';
  $flashCode .= 'so.addVariable("subtitle_bgcolor","0xE3E3E3");';
  $flashCode .= 'so.addVariable("footer_bgcolor","'.wp_7feeds_get_theme_color($options['theme'], 'footer_bgcolor').'");';
  $flashCode .= 'so.addVariable("feed_bgcolor","0xFFFFFF");';
  $flashCode .= 'so.addVariable("feed_highlight","'.wp_7feeds_get_theme_color($options['theme'], 'feed_highlight').'");';
  $flashCode .= 'so.addVariable("border_color","'.wp_7feeds_get_theme_color($options['theme'], 'border_color').'");';
  $flashCode .= 'so.addVariable("summary_length","'.$options['summary_length'].'");';
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
  $flashCode .= 'so.addVariable("use_rounded_corners","1");';
  $flashCode .= 'so.addVariable("open_new_window","'.$options['open_new_window'].'");';
  $flashCode .= 'so.addVariable("show_url_field","0");';
  $flashCode .= 'so.addVariable("data_url","'.$path.'parser.php");';
  $flashCode .= 'so.addVariable("feed_url","'.$options['feed_url'].'");';
  $flashCode .= 'so.addVariable("strip_tags","'.$options['strip_tags'].'");';
  $flashCode .= 'so.addVariable("elements_strcolor","0x777777");';
  $flashCode .= 'so.addVariable("elements_bgcols","0xFFF7E1");';
  $flashCode .= 'so.addVariable("footer_powered_format","right/_sans/11/0x666666/nb/ni/nu");';
  $flashCode .= 'so.addVariable("footer_feedo_format","'.wp_7feeds_get_theme_color($options['theme'], 'footer_feedo_format').'");';
  $flashCode .= 'so.addVariable("feed_back_color","'.wp_7feeds_get_theme_color($options['theme'], 'feed_back_color').'");';
  $flashCode .= 'so.addVariable("feeds_num_color","'.wp_7feeds_get_theme_color($options['theme'], 'feeds_num_color').'");';
  $flashCode .= 'so.addVariable("buttons_onpress_color","'.wp_7feeds_get_theme_color($options['theme'], 'buttons_onpress_color').'");';
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
function wp_7feeds_get_theme_color($index, $key, $hex=0) {

  $aData = array();
  $aData[] = array("feed_title_format"=>"left/arial/14/0xfe9900/b/ni/nu","body_bgcolor"=>"0xffe0b2","footer_bgcolor"=>"0xffe0b2","feed_highlight"=>"0xffefd8","border_color"=>"0xfe9900","footer_feedo_format"=>"left/_sans/11/0xfe9900/nb/ni/nu","feed_back_color"=>"0xfffbf2","feeds_num_color"=>"0xfe9900","buttons_onpress_color"=>"0xfe9900");
  $aData[] = array("feed_title_format"=>"left/arial/14/0x6599ff/b/ni/nu","body_bgcolor"=>"0xd1e0ff","footer_bgcolor"=>"0xd1e0ff","feed_highlight"=>"0xe7f0ff","border_color"=>"0x6599ff","footer_feedo_format"=>"left/_sans/11/0x6599ff/nb/ni/nu","feed_back_color"=>"0xf7faff","feeds_num_color"=>"0x6599ff","buttons_onpress_color"=>"0x6599ff");
  $aData[] = array("feed_title_format"=>"left/arial/14/0xcccb32/b/ni/nu","body_bgcolor"=>"0xf1f0c2","footer_bgcolor"=>"0xf1f0c2","feed_highlight"=>"0xf7f7df","border_color"=>"0xcccb32","footer_feedo_format"=>"left/_sans/11/0xcccb32/nb/ni/nu","feed_back_color"=>"0xfcfbf6","feeds_num_color"=>"0xcccb32","buttons_onpress_color"=>"0xcccb32");
  $aData[] = array("feed_title_format"=>"left/arial/14/0xfe66cb/b/ni/nu","body_bgcolor"=>"0xffd0f0","footer_bgcolor"=>"0xffd0f0","feed_highlight"=>"0xffe8f7","border_color"=>"0xfe66cb","footer_feedo_format"=>"left/_sans/11/0xfe66cb/nb/ni/nu","feed_back_color"=>"0xfff7fc","feeds_num_color"=>"0xfe66cb","buttons_onpress_color"=>"0xfe66cb");
  $aData[] = array("feed_title_format"=>"left/arial/14/0x676767/b/ni/nu","body_bgcolor"=>"0xd1d1d1","footer_bgcolor"=>"0xd1d1d1","feed_highlight"=>"0xe7e7e7","border_color"=>"0x676767","footer_feedo_format"=>"left/_sans/11/0x676767/nb/ni/nu","feed_back_color"=>"0xf7f7f7","feeds_num_color"=>"0x676767","buttons_onpress_color"=>"0x676767");
  $aData[] = array("feed_title_format"=>"left/arial/14/0x339933/b/ni/nu","body_bgcolor"=>"0xc1e0c1","footer_bgcolor"=>"0xc1e0c1","feed_highlight"=>"0xe0efe0","border_color"=>"0x339933","footer_feedo_format"=>"left/_sans/11/0x339933/nb/ni/nu","feed_back_color"=>"0xf5faf4","feeds_num_color"=>"0x339933","buttons_onpress_color"=>"0x339933");
  $aData[] = array("feed_title_format"=>"left/arial/14/0xcd3333/b/ni/nu","body_bgcolor"=>"0xf1c1c1","footer_bgcolor"=>"0xf1c1c1","feed_highlight"=>"0xf8dfdf","border_color"=>"0xcd3333","footer_feedo_format"=>"left/_sans/11/0xcd3333/nb/ni/nu","feed_back_color"=>"0xfdf4f5","feeds_num_color"=>"0xcd3333","buttons_onpress_color"=>"0xcd3333");
  $aData[] = array("feed_title_format"=>"left/arial/14/0x9966ff/b/ni/nu","body_bgcolor"=>"0xe1d1ff","footer_bgcolor"=>"0xe1d1ff","feed_highlight"=>"0xefe7ff","border_color"=>"0x9966ff","footer_feedo_format"=>"left/_sans/11/0x9966ff/nb/ni/nu","feed_back_color"=>"0xfaf7ff","feeds_num_color"=>"0x9966ff","buttons_onpress_color"=>"0x9966ff");
  $aData[] = array("feed_title_format"=>"left/arial/14/0x66cc68/b/ni/nu","body_bgcolor"=>"0xd1f0d1","footer_bgcolor"=>"0xd1f0d1","feed_highlight"=>"0xe8f7e7","border_color"=>"0x66cc68","footer_feedo_format"=>"left/_sans/11/0x66cc68/nb/ni/nu","feed_back_color"=>"0xf7fcf6","feeds_num_color"=>"0x66cc68","buttons_onpress_color"=>"0x66cc68");

  $ret = isset($aData[$index][$key])?$aData[$index][$key]:$aData[0][$key];

  if ($hex) {
    $ret = str_replace('0x','#',$ret);
  }

  return $ret;
}

//Get theme select
function wp_7feeds_get_theme_select($name, $val) {

  $aData = array();
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
    $options .= '<option value="'.$i.'" '.($i==$val?'selected':'').'>'.$aData[$i].'</option>';
  }

  return '<select name="'.$name.'">'.$options.'</select>';
}

// options page
function wp_7feeds_options() {

  $options = $newoptions = get_option('wp7feeds_options');

  // if submitted, process results
  if ( $_POST["wp7feeds_submit"] ) {
    $newoptions['x_size'] = strip_tags(stripslashes($_POST["x_size"]));
    $newoptions['y_size'] = strip_tags(stripslashes($_POST["y_size"]));
    $newoptions['summary_length'] = strip_tags(stripslashes($_POST["summary_length"]));
    $newoptions['scroll_speed'] = strip_tags(stripslashes($_POST["scroll_speed"]));
    $newoptions['num_of_entries'] = strip_tags(stripslashes($_POST["num_of_entries"]));
    $newoptions['pause_time'] = strip_tags(stripslashes($_POST["pause_time"]));
    $newoptions['open_new_window'] = strip_tags(stripslashes($_POST["open_new_window"]));
    $newoptions['feed_url'] = strip_tags(stripslashes($_POST["feed_url"]));
    $newoptions['strip_tags'] = strip_tags(stripslashes($_POST["strip_tags"]));
    $newoptions['theme'] = strip_tags(stripslashes($_POST["theme"]));
    $newoptions['widget_header'] = strip_tags(stripslashes($_POST["widget_header"]));
    $newoptions['news_content'] = strip_tags(stripslashes($_POST["news_content"]));
    $newoptions['pub_time'] = strip_tags(stripslashes($_POST["pub_time"]));
    $newoptions['widget_title'] = strip_tags(stripslashes($_POST["widget_title"]));
    $newoptions['widget_promote'] = strip_tags(stripslashes($_POST["widget_promote"]));
  }
  // any changes? save!
  if ( $options != $newoptions ) {
    $options = $newoptions;
    update_option('wp7feeds_options', $options);
  }
  // options form
  echo '<form method="post">';
  echo "<div class=\"wrap\"><h2>Default display options</h2>";
  echo '<table class="form-table">';
  // width
  echo '<tr valign="top"><th scope="row">Width of the widget</th>';
  echo '<td><input type="text" name="x_size" value="'.$options['x_size'].'" size="5"></input> Width in pixels</td></tr>';
  // height
  echo '<tr valign="top"><th scope="row">Height of the widget</th>';
  echo '<td><input type="text" name="y_size" value="'.$options['y_size'].'" size="5"></input> Height in pixels</td></tr>';
  // text length
  echo '<tr valign="top"><th scope="row">News item length, chars</th>';
  echo '<td><input type="text" name="summary_length" value="'.$options['summary_length'].'" size="5"></input></td></tr>';
  // Scroll Speed
  echo '<tr valign="top"><th scope="row">Scrolling speed</th>';
  echo '<td><input type="text" name="scroll_speed" value="'.$options['scroll_speed'].'" size="3"></input></td></tr>';
  // Number of entries
  echo '<tr valign="top"><th scope="row">Number of entries</th>';
  echo '<td><input type="text" name="num_of_entries" value="'.$options['num_of_entries'].'" size="3"></input></td></tr>';
  // Pause time
  echo '<tr valign="top"><th scope="row">Pause time</th>';
  echo '<td><input type="text" name="pause_time" value="'.$options['pause_time'].'" size="6"></input></td></tr>';
  // Open in new window
  echo '<tr valign="top"><th scope="row">Open links in new windows</th>';
  echo '<td><input type="checkbox" name="open_new_window" value="1"';
  if( $options['open_new_window'] == "1" ){ echo ' checked="checked"'; }
  echo '></input></td></tr>';
  // Feed url
  echo '<tr valign="top"><th scope="row">Feed URL</th>';
  echo '<td><input type="text" name="feed_url" value="'.$options['feed_url'].'" size="60"></input></td></tr>';

  // Select theme
  echo '<tr valign="top"><th scope="row">Select theme</th>';
  echo '<td>'.wp_7feeds_get_theme_select('theme',$options['theme']).'</td></tr>';

  //widget header
  echo '<tr valign="top"><th scope="row">Widget header</th>';
  echo '<td><input type="checkbox" name="widget_header" value="1"';
  if( $options['widget_header'] == "1" ){ echo ' checked="checked"'; }
  echo '></input> Check to show widget header from RSS feed title</td></tr>';

  //news content
  echo '<tr valign="top"><th scope="row">News content</th>';
  echo '<td><input type="checkbox" name="news_content" value="1"';
  if( $options['news_content'] == "1" ){ echo ' checked="checked"'; }
  echo '></input> Check to show news content (unchecked - headlines only)</td></tr>';

  //pub time
  echo '<tr valign="top"><th scope="row">Pub time</th>';
  echo '<td><input type="checkbox" name="pub_time" value="1"';
  if( $options['pub_time'] == "1" ){ echo ' checked="checked"'; }
  echo '></input> Check to show Pub time of News Item</td></tr>';

  // Custom widget title
  echo '<tr valign="top"><th scope="row">Custom widget title</th>';
  echo '<td><input type="text" name="widget_title" value="'.$options['widget_title'].'" size="60"></input></td></tr>';

  // Open in new window
  echo '<tr valign="top"><th scope="row">Strip tags</th>';
  echo '<td><input type="checkbox" name="strip_tags" value="1"';
  if( $options['strip_tags'] == "1" ){ echo ' checked="checked"'; }
  echo '></input> Check to strip tags</td></tr>';

  // Promote
  echo '<tr valign="top"><th scope="row">Help to promote 7feeds</th>';
  echo '<td><input type="checkbox" name="widget_promote" value="1"';
  if( $options['widget_promote'] == "1" ){ echo ' checked="checked"'; }
  echo '></input></td></tr>';
  // end table
  echo '</table>';
  echo '<input type="hidden" name="wp7feeds_submit" value="true"></input>';
  echo '<p class="submit"><input type="submit" value="Update Options &raquo;"></input></p>';
  echo "</div>";
  echo '</form>';

}

//uninstall all options
function wp_7feeds_uninstall () {
  delete_option('wp7feeds_options');
  delete_option('widget_7feeds-widget');
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
      echo wp_7feeds_createflashcode(true,NULL,$options);
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
    $newoptions['scroll_speed'] = strip_tags(stripslashes($_POST["wp7feeds_widget_scroll_speed"]));
    $newoptions['num_of_entries'] = strip_tags(stripslashes($_POST["wp7feeds_widget_num_of_entries"]));
    $newoptions['pause_time'] = strip_tags(stripslashes($_POST["wp7feeds_widget_pause_time"]));
    $newoptions['open_new_window'] = strip_tags(stripslashes($_POST["wp7feeds_widget_open_new_window"]));
    $newoptions['feed_url'] = strip_tags(stripslashes($_POST["wp7feeds_widget_feed_url"]));
    $newoptions['strip_tags'] = strip_tags(stripslashes($_POST["wp7feeds_widget_strip_tags"]));
    $newoptions['theme'] = strip_tags(stripslashes($_POST["wp7feeds_theme"]));
    $newoptions['widget_header'] = strip_tags(stripslashes($_POST["wp7feeds_widget_header"]));
    $newoptions['news_content'] = strip_tags(stripslashes($_POST["wp7feeds_news_content"]));
    $newoptions['pub_time'] = strip_tags(stripslashes($_POST["wp7feeds_pub_time"]));
    $newoptions['widget_title'] = strip_tags(stripslashes($_POST["wp7feeds_widget_title"]));

    return $newoptions;
  }

  // DIsplay Widget Control Form
  function form($options) {
    if (empty($options)) {
      $options = get_option('wp7feeds_options');
      foreach ($options as $key=>$val) {
        if ($val != '1' && $val != '0') {
          $options[$key] = '';
        }
      }
    }

    $x_size = attribute_escape($options['x_size']);
    $y_size = attribute_escape($options['y_size']);
    $summary_length = attribute_escape($options['summary_length']);
    $scroll_speed = attribute_escape($options['scroll_speed']);
    $num_of_entries = attribute_escape($options['num_of_entries']);
    $pause_time = attribute_escape($options['pause_time']);
    $open_new_window = attribute_escape($options['open_new_window']);
    $feed_url = attribute_escape($options['feed_url']);
    $strip_tags = attribute_escape($options['strip_tags']);
    $theme = attribute_escape($options['theme']);
    $widget_header = attribute_escape($options['widget_header']);
    $news_content = attribute_escape($options['news_content']);
    $pub_time = attribute_escape($options['pub_time']);
    $widget_title = attribute_escape($options['widget_title']);

		?>
			<p><label for="wp7feeds_widget_x_size"><?php _e('Width (optional):'); ?> <input class="widefat" id="wp7feeds_widget_x_size" name="wp7feeds_widget_x_size" type="text" value="<?php echo $x_size; ?>" /></label></p>
			<p><label for="wp7feeds_widget_y_size"><?php _e('Height (optional):'); ?> <input class="widefat" id="wp7feeds_widget_y_size" name="wp7feeds_widget_y_size" type="text" value="<?php echo $y_size; ?>" /></label></p>
			<p><label for="wp7feeds_widget_summary_length"><?php _e('Item length (optional):'); ?> <input class="widefat" id="wp7feeds_widget_summary_length" name="wp7feeds_widget_summary_length" type="text" value="<?php echo $summary_length; ?>" /></label></p>
			<p><label for="wp7feeds_widget_scroll_speed"><?php _e('Scrolling speed (optional):'); ?> <input class="widefat" id="wp7feeds_widget_scroll_speed" name="wp7feeds_widget_scroll_speed" type="text" value="<?php echo $scroll_speed; ?>" /></label></p>
			<p><label for="wp7feeds_widget_num_of_entries"><?php _e('Number of items (optional):'); ?> <input class="widefat" id="wp7feeds_widget_num_of_entries" name="wp7feeds_widget_num_of_entries" type="text" value="<?php echo $num_of_entries; ?>" /></label></p>
			<p><label for="wp7feeds_widget_pause_time"><?php _e('Pause time (optional):'); ?> <input class="widefat" id="wp7feeds_widget_pause_time" name="wp7feeds_widget_pause_time" type="text" value="<?php echo $pause_time; ?>" /></label></p>
			<p><label for="wp7feeds_widget_open_new_window"><input class="checkbox" id="wp7feeds_widget_open_new_window" name="wp7feeds_widget_open_new_window" type="checkbox" value="1" <?php if( $open_new_window == "1" ){ echo ' checked="checked"'; } ?> > Open link in new window</label></p>
			<p><label for="wp7feeds_widget_feed_url"><?php _e('Feed URL (optional):'); ?> <input class="widefat" id="wp7feeds_widget_feed_url" name="wp7feeds_widget_feed_url" type="text" value="<?php echo $feed_url; ?>" /></label></p>
			
			<p><?php _e('Select theme:');  echo wp_7feeds_get_theme_select('wp7feeds_theme',$theme)?></p>
			
			<p><label for="wp7feeds_widget_header"><input class="checkbox" id="wp7feeds_widget_header" name="wp7feeds_widget_header" type="checkbox" value="1" <?php if( $widget_header == "1" ){ echo ' checked="checked"'; } ?> > Widget header</label></p>
			<p><label for="wp7feeds_news_content"><input class="checkbox" id="wp7feeds_news_content" name="wp7feeds_news_content" type="checkbox" value="1" <?php if( $news_content == "1" ){ echo ' checked="checked"'; } ?> > News content</label></p>
			<p><label for="wp7feeds_pub_time"><input class="checkbox" id="wp7feeds_pub_time" name="wp7feeds_pub_time" type="checkbox" value="1" <?php if( $pub_time == "1" ){ echo ' checked="checked"'; } ?> > Pub time</label></p>

			<p><label for="wp7feeds_widget_title"><?php _e('Widget title (optional):'); ?> <input class="widefat" id="wp7feeds_widget_title" name="wp7feeds_widget_title" type="text" value="<?php echo $widget_title; ?>" /></label></p>
			
			<p><label for="wp7feeds_widget_strip_tags"><input class="checkbox" id="wp7feeds_widget_strip_tags" name="wp7feeds_widget_strip_tags" type="checkbox" value="1" <?php if( $strip_tags == "1" ){ echo ' checked="checked"'; } ?> > Strip tags</label></p>
			
			<input type="hidden" id="wp7feeds_widget_submit" name="wp7feeds_widget_submit" value="1" />
		<?php

  }
}

### Function: Init WP-Polls Widget
add_action('widgets_init', 'widget_7feeds_init');
function widget_7feeds_init() {
  register_widget('WP_Widget_7feeds');
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