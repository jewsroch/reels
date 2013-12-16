<?php
if ( !class_exists('CategoryIconsLite') ) {
	
	define('CIL_OPTIONS','caticonslite_settings');
	define('CIL_GROUP','caticonslite-group');
	
	class CategoryIconsLite {
		
		private $meta_key = '_wp_attachment_category'; // hidden custom field
		protected $permalink = 0;
		protected $the_title = 0;
		private $caticons_array = array(); // cat_id => icon_id, icon_url, cat_slug, cat_name
		private $slugs = array(); // slug => cat_id
		private $sb_position = 'left';
		private $default_options = array (
			"caticonslite-sidebar-position"  =>	"left",
			"caticonslite-sidebar"   =>	"checked",
			"caticonslite-posttitle" =>	"checked"
			);
		private $options = array();
				
		function __construct() {
		    $this->set_cat_icons();
    		$options = get_option(CIL_OPTIONS);
    		if (!is_array($options) || count($options) == 0) {
                add_option(CIL_OPTIONS,$this->default_options);
                $options = $this->default_options;
            }
            $this->options = $options;
    		update_option(CIL_OPTIONS, $this->options);
    		$this->set_sidebar_icons_position($this->options['caticonslite-sidebar-position']);
		}
		
		function get_all() {
    		return $this->caticons_array;
		}
		
		function reset_settings($options) {
    		if(!empty($options['reset']))
    		  return $this->default_options;
    		else 
    		  return $options;
		}
		
		function set_options() {
    		register_setting(CIL_GROUP, CIL_OPTIONS, array($this, 'reset_settings'));
  	     }	
  	     
		function is_checked($option,$suboption='') {
    		$resultat = '';
    		$checked = ' checked="checked" ';
    		if (empty($suboption) AND $this->options[$option] == "checked") {
        		$resultat = $checked;
    		}
    		if (!empty($suboption)) {
        		if ( $this->options[$option] == $suboption) {
            		$resultat = $checked;
        		}
    		}
    		return $resultat;
		}
		
		function add_menu(){
    		$hook = add_options_page(__('Category Icons Lite Settings'),
                     __('Category Icons Lite'),
                     "manage_categories",
                     "caticonsliteoptions",
                     array($this,"panel")
                     );
		}
				
		function panel() {
    		echo '<div class="wrap"><h2>Category Icons Lite</h2><form action="options.php" method="post" id="caticonslite-form">';
            settings_fields(CIL_GROUP); 
            $html = '
              <div class="metabox-holder" id="caticonslite-metabox">
              <div id="postbox-container-1" class="postbox-container" style="width:50%;">
                  <div id="caticonslite-section-1" class="postbox" >
                    <h3 ><span>'.__('Settings').'</span></h3>
                    <div class="inside">
                      <table class="form-table">
                        <tr valign="top">
                          <th scope="row">'.__('Posts').'</th>
                          <td><label >
                              <input type="checkbox" value="checked" name="'.CIL_OPTIONS.'[caticonslite-posttitle]" '.$this->is_checked('caticonslite-posttitle').' />
                            </label>
                          </td>
                        </tr>
                        <tr valign="top">
                          <th scope="row">'.__('Sidebar').'</th>
                          <td><label >
                              <input type="checkbox" value="checked" name="'.CIL_OPTIONS.'[caticonslite-sidebar]" '.$this->is_checked('caticonslite-sidebar').' />
                            </label>
                          </td>
                        </tr>
                        <tr valign="top">
                          <th scope="row">'.__('Alignment').'</th>
                          <td><label>
                              <input type="radio" value="left" name="'.CIL_OPTIONS.'[caticonslite-sidebar-position]" '.$this->is_checked('caticonslite-sidebar-position','left').' />
                              '. __('Left').'</label>
                            <label >
                              <input type="radio" value="right" name="'.CIL_OPTIONS.'[caticonslite-sidebar-position]" '.$this->is_checked('caticonslite-sidebar-position','right').' />
                              '.__('Right').'</label>
                            <p class="description">('.__('Sidebar').')</p></td>
                        </tr>
                      </table>
                      <p>
                        <input class="button-primary"  type="submit" name="caticonslite_submit" value="Save Options" />
                        <input  class="button-secondary" type="submit" name="caticonslite_settings[reset]" value="Reset Options"  />
                      </p>
                    </div>
                  </div>
              </div>
            </form></div>';
            echo $html;
		}
		
		function are_icons_sidebar() {
    		$result = false;
    		if ($this->options['caticonslite-sidebar']=="checked") {
        		$result = true;
    		}
    		return $result;
		}
		
		function get_sidebar_icons_position() {    		
    		return $this->options['caticonslite-sidebar-position'];
		}
		
		function are_icons_post_title() {
    		$result = false;
    		if ($this->options['caticonslite-posttitle']=="checked") {
        		$result = true;
    		}
    		return $result;
		}
		
		/**
		* Sets the display of the icons on the right or on the left of the sidebar category name
		*/
		function set_sidebar_icons_position($position='left') {
    		if ($position=='left' OR $position=='right') {
        		$this->sb_position = $position;
    		}
		}
		
		/**
		* Filters the display of the icons on a particular page (by default : icons are displayed on all the pages)
		*/
		function page_filter($text) {//if (is_category())  // use this if you want to display icons in front of the post title only in the category page, for example
			if ($this->the_title == 1 && $this->permalink == 1) $text = $this->post_title($text);
			if  ($this->the_title == 0) $this->the_title = 1;			
			return $text;
		}
				
		/**
		* Adds the icon in front of the title
		*/
		function post_title($text) {
			$image = '';
			if (in_the_loop() && $GLOBALS['post']->post_title==$text) {// if in the loop & post title is the same than the one being processed
				$this->reset_flags();
				$icon=$this->get_icon();
				if (!empty($icon)) $image = $this->html_tag($icon,$text);
			}
			return $image.$text; // if you want to display it after the title : return $text.$image;
		}
		
		/**
		* Returns the html tag of the icon
		*/
		function html_tag($url,$title='') {
			$html = '';	
			$cat_object = array_pop(get_the_category($GLOBALS['post']->ID));
			$cat_name = esc_attr($cat_object->name);
			$alt = $cat_name;
			//$alt = 'caticonslite_bm_alt';			
			$alt = apply_filters('caticonslite_alt', $alt);
			//$title = esc_attr($title);
			$title = $cat_name;
			$title = apply_filters('caticonslite_title', $title);
			if (!empty($url)) $html = "<img class='caticonslite_bm' alt=\"$alt\" src=\"".esc_url($url)."\" title=\"$title\" />";
			$html = apply_filters('caticonslite_htmltag', $html);
			return $html;
		}
		
		/**
		* This function returns the icon url
		*/
		function get_icon($cat_id=0) {
			global $wpdb;
			$result = '';
			if ( 0 == $cat_id ) {	
				$catlist = get_the_category(get_the_ID());
				if (is_array($catlist)) {// If there are several categories,
					if (count($catlist) > 0) {
						$cat_id = (int) $catlist[0]->term_id; // I take only the first one
						$cat_id = apply_filters('caticonslite_cat_priority', $cat_id, $catlist);
					}	
					else {
    					return $result;
					} 	
				}
				else 
					$cat_id = (int) $catlist->term_id;
			}
			
			$cat_id = apply_filters('caticonslite_cat_id', $cat_id);
			
			if ($cat_id > 0 AND isset($this->caticons_array[$cat_id]['url'])) {			
				$result = $this->caticons_array[$cat_id]['url'];
			}
						
			return $result;
		}
		
		/**
		* This function loads all the urls of the icons in an array to access them later
		*/
		function set_cat_icons() {
			global $wpdb;
			// Create an array with cat_id => post_id, image_url
			$query = "SELECT guid AS image_url,{$wpdb->prefix}postmeta.meta_value AS cat_id, {$wpdb->prefix}postmeta.post_id AS icon_id, {$wpdb->prefix}terms.slug, {$wpdb->prefix}terms.name 
			FROM {$wpdb->prefix}posts 
			INNER JOIN {$wpdb->prefix}postmeta ON {$wpdb->prefix}postmeta.post_id = {$wpdb->prefix}posts.ID 
			INNER JOIN {$wpdb->prefix}terms ON {$wpdb->prefix}postmeta.meta_value = {$wpdb->prefix}terms.term_id
			WHERE meta_key = '".esc_sql($this->meta_key)."'";	
			
			$results = wp_cache_get( 'categoryiconslite_results');
			if ( false === $results ) { // put in cache the result
				$results = $wpdb->get_results( $wpdb->prepare($query ));
				wp_cache_add( 'categoryiconslite_results', $results );
			} 
			if (is_array($results) && count($results)>0) {				
				foreach($results as $result) {
					$this->caticons_array[$result->cat_id] = array( 
                                        					   'icon_id' => $result->icon_id, 
                                        					   'url' => esc_url($result->image_url),
                                        					   'slug' => $result->slug,
                                        					   'name' => $result->name
                                        					);
                    $this->slugs[$result->slug] = $result->cat_id;
				}
			}
		}
		
		/**
		* Returns the category icon
		*/
		function get_cat_icon($cat_id=0) {
			return $this->html_tag($this->get_icon($cat_id));
		}
		
		/**
		* Injects icons in the wp_list_categories result
		*/
		function list_cats( $output ) {			
			if (count($this->slugs)==0) return $output;
			if (!empty($output)) {
				$myarray = $this->url_extractor($output);
				foreach ($myarray as $child) {
					$cats = array();
					$array = preg_match('/.*?\\/.*?\\/.*?\\/\\?cat=(\\d+)/is', $child[0], $correspondances) ;
					if ($array == 1 && isset($correspondances[1]) && $correspondances[1] > 0) {
    					$cats[] = $correspondances[1];// standard permalinks 
					}
					else {// the last part is taken as the category name
					   $url = preg_replace('/\/$/','', $child[0]); // removes trailing comma
					   $nb = preg_match('/[^\/]+$/',$url,$result);
					   if (0 < $nb) {
    				       $name = $result[0];
    				       $cats[] = $this->slugs[trim($name)];	   
					   }
					}
					$img = '';
					if (0 < count($cats)) {
    					$img .= $this->get_cat_icon($cats[0]);
						if ( 'left' == $this->sb_position ) {// Before the category name
						    $toto = preg_match('/>(.*<)/i',$child[1],$result);
						    $cat_name = $result[1];
    						$output = str_replace('>'.$cat_name, '>'.$img.$cat_name, $output);
						}
						else {// After the category name
    					   $toto = preg_match('/(>.*)</i',$child[1],$result);
						   $cat_name = $result[1];
    					   $output = str_replace($cat_name.'<', $cat_name.$img.'<', $output);	
						}				
					}
				}
				$output = apply_filters('caticonslite_widget', $output);
			}
			return $output;
		}
		
		
		/**
		* Displays the category assigned to the image (icon) in the media library
		*/
		function catagory_name($actions, $post) {
			$cat_name = get_cat_name(get_post_meta($post->ID, $this->meta_key, true));
			if (!empty($cat_name)) echo '<i>'.esc_html(__('Category').' : '. $cat_name).'</i>';
			return $actions;
		}
				
		/**
		* Displays the category list in Edit Media panel
		*/
		function form_fields($form_fields,$post) {
			if ( current_user_can( 'manage_categories' )  ) {
				$array_categories = '';
				global $wpdb;
				$category_id = get_post_meta($post->ID, $this->meta_key, true);
				$categories = $wpdb->get_results( "SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE meta_key = '".esc_sql($this->meta_key).'\'');
				foreach ($categories as $category) {
					if ($category->meta_value != $category_id) {
						$array_categories .= $category->meta_value.':';
					}
				}
				$array_categories = substr($array_categories,0,-1);
				$array = wp_dropdown_categories(
				    array(
				        'hide_empty' => 0, 
				        'name' => "attachments[{$post->ID}][categoryiconlite_id]", 
				        'class' => 'categoryiconlite_id_class', 
				        'echo' => 0, 
				        'orderby' => 'name', 
				        'selected' => $category_id, 
				        'hierarchical' => true, 
				        'show_option_none' => __('None') // if 'None' is selected, the association with a category will be removed or not done
				    )
				);				
				$form_fields['category']['label'] = __('Category');
				$form_fields['category']['input'] = 'select';
				$form_fields['category']['select'] = $array."<input type='hidden' id='caticonslite_categories' name='caticonslite_categories' value='$array_categories' />";
				
			}
			return $form_fields;
		}
		
		/**
		* Assigns the category to the selected image
		*/
		function form_fields_save($post, $attachment) {
			if ( current_user_can( 'manage_categories' )) {
				global $wpdb;
				
				$post_id=0;
				$cat_id = -1;
				
				if ( isset($attachment['categoryiconlite_id']) ) {
				    $cat_id = $attachment['categoryiconlite_id'];
				}
				
				if (isset($post['ID'])) {
				    $post_id = (int) $post['ID'];
				}
				elseif (isset($_POST['attachments'])) {
					$post_id = (int) array_pop(array_keys($_POST['attachments']));
				}
				
				if ($cat_id > -1) { // If the selected category ID not -1, assign it
					$post_meta = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = '".esc_sql($this->meta_key)."' AND meta_value=$cat_id"));// search in postmeta table if the category exists
					$post_id2 = get_post_meta($post_id,$this->meta_key,true);
					if ( !is_null($post_meta) && $post_id != $post_meta ) { // tests if the image is different than the one already assigned to the category and throws an error if it's the case, does nothing otherwise
    					delete_post_meta($post_meta,$this->meta_key);
					}
					if (!is_null($post_id2)) {
						delete_post_meta($post_id,$this->meta_key);
					}
					add_post_meta($post_id,$this->meta_key,$cat_id,true);// if the category is not in the postmeta, adds it
				}
				else {// Delete the assigned category if 'none' is selected
					delete_post_meta($post_id,$this->meta_key);
				}
				// update the caticons_array
				wp_cache_delete( 'categoryiconslite_results');
				$this->set_cat_icons();
			}
			return $post;
		}

		/**
		* This function displays category icons in the categories panel
		*/
		function categories_custom_column( $data, $column, $id) {//$empty, $columnName, $id
			$row = '';
			if ($column == 'caticonslite_icon') {
				$icon = $this->get_icon($id);
				$icon_img = '';
				if (!empty($icon)) {
					$icon_img = '<img src="'.$icon.'" alt="&nbsp;" />';
				}
				if (isset($this->caticons_array[$id]['icon_id'])) {
				    $icon_id = $this->caticons_array[$id]['icon_id'];
				    $url = admin_url('media.php?attachment_id=');
				    $row .= '<a href="'.$url.$icon_id.'&action=edit">'.$icon_img.'</a>';
				}
				else {
    				$row = $icon_img;
				}
			}
			return $row;
		}

		/**
		* This function displays 'Icons' in the header columns of the categories table
		*/
		function categories_header($array) {
			$array['caticonslite_icon'] = '<img src="' .admin_url( 'images/media-button-image.gif' , __FILE__ ). '" alt="category icons" border="0" />';
			return $array;
		}
		
		/**
		* This function raises the_permalink flag
		*/
		function permalink_flag_on($permalink) {
			$this->permalink = 1;
			return $permalink;
		}
		
		/**
		* This function resets the_permalink flag & the the_title flag
		*/
		function reset_flags($content='') {
			$this->permalink = 0;
			$this->the_title = 0;
			return $content;
		}
		
		/**
		* This function removes the association of the icon with the removed category
		*/
		function category_removed($term_id) {  
			global $wpdb;
			if($_POST['taxonomy'] == 'category') {         
	            $query = "DELETE FROM {$wpdb->prefix}postmeta WHERE meta_key ='".esc_sql($this->meta_key)."' and meta_value = ".$term_id;
				$results = $wpdb->get_results( $wpdb->prepare($query ));
			}	
		}
				
		/**
		* This function loads various scripts
		*/
		function load_scripts() {
			 wp_enqueue_script('jquery-ui-dialog');
			 wp_enqueue_style('wp-jquery-ui-dialog');
		}
		
		/**
		* This function displays a warning if the icon is already assigned to a category
		*/
		function delete_warning(){	
		?> 
			<script type="text/javascript">
			var jq = jQuery.noConflict();			
			jq(document).ready(function() { 
			    var dlg = jq("<div id='caticons_warning_dialog' title='<?php echo __('Warning!'); ?>' />")
			             .html('')
			             .appendTo('body');
                dlg.dialog({
                    dialogClass : 'wp-dialog',
                    modal : true,
                    autoOpen : false,
                    closeOnEscape : true,
                    buttons : [
                        {
                            text : '<?php echo __('OK'); ?>',
                            class : 'button-primary',
                            click : function() {
                                jq(this).dialog('close');
                            }
                        }
                    ]
                });//.dialog('open');
				jq(".categoryiconlite_id_class").change(function() {
					jq("select option:selected").each(function () {
						category = jq(this).val();
						categories = (jq("#caticonslite_categories").val()).split(":");
						if ( (jq.inArray(category,categories)) != -1) {
						    var p = jq.parseJSON('<?php echo json_encode($this->caticons_array); ?>');
						    for (var key in p) {
                              if (p.hasOwnProperty(key)) {
                                //alert(key + " -> " + p[key]['url']);
                                if (key == category) {
                                    dlg.html('<div id="caticons_warning_content" style="text-align:center;line-height:8;vertical-align:middle;" >' + p[key]['name'] + ' = <img id="caticons_icon" style="vertical-align:middle;" src="' + p[key]['url'] + '" alt="tyty" /></div>')
                                }
                              }
                            }                            
							dlg.dialog('open');
						}
					});
				});
			});
			</script>
		<?php 
		}
		
		/**
		* This function modifies the query to display only the category icons in the media table
		*/
		function caticons_media_filter( $query ) {
		    global  $pagenow, $wp_query;
		    if ( is_admin() && $pagenow=='upload.php' && isset( $_GET['caticons'] ) && $_GET['caticons'] == '1' ) {
		        set_query_var( 'meta_key',  $this->meta_key ) ;
		    }
		}
		
		/**
		* This function displays the dropdown 'show category icons' in the media table
		*/
		function show_caticons_dropdown() {
			global $post_type, $post_mime_type, $pagenow;
			if ($post_type=='attachment' AND ($post_mime_type=='image' OR !isset($post_mime_type))) {
				$caticons = isset( $_GET['caticons'] ) ? (int) $_GET['caticons'] : 0;
				?>
				<select name='caticons'>
					<option <?php selected( $caticons, 0 ); ?> value='0'><?php _e( 'Show all' ); ?></option>
					<option <?php selected( $caticons, 1 ); ?> value='1'>Category Icons</option>
				</select>
				
				<?php
			}
		}

		/**
		* This function returns an array with the urls of the string 
		*/
		function url_extractor($string) {
			$myarray = array();
			if (preg_match_all('/<a\s+.*?href=[\"\']?([^\"\' >]*)[\"\']?[^>]*(>.*?<)\/a>/i', $string, $correspondances, PREG_SET_ORDER)) 
				foreach ($correspondances as $correspondance) 
					array_push($myarray, array(esc_url($correspondance[1]), trim($correspondance[2])));
			return $myarray;
		}
		        
	}// end class
}//endif