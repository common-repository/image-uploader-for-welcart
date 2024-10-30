<?php
/*
Plugin Name: Image Uploader for Welcart
Plugin URI: http://web.contempo.jp/weblog/tips/p636
Description: Create metabox with image uploader for Welcart. It allows uploading and sorting images directory from each edit page.
Author: Mizuho Ogino
Author URI: http://web.contempo.jp/
Version: 1.4.6
License: http://www.gnu.org/licenses/gpl.html GPL v2 or later
Text Domain: image-uploader-for-welcart
Domain Path: /languages
*/


if ( !class_exists( 'iu4w' ) ) {

class iu4w {



	public function __construct() {

		load_plugin_textdomain( 'image-uploader-for-welcart', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		add_action( 'add_meta_boxes', array( $this,'iu4w_add_metabox' ) );
		add_action( 'save_post', array( $this,'iu4w_save_images'), 11, 1 );
		add_filter( 'attachment_fields_to_edit', array( $this,'iu4w_attachment_fields_to_edit' ), 11, 2 );

	}



	/////////////////////// ADD META BOX WITH UPLOADER ///////////////////////
	
	public function iu4w_add_metabox(){
		$post = isset($_GET['post']) ? $_GET['post'] : '';
		$page = isset($_GET['page']) ? $_GET['page'] : '';
		$item_code = $post ? get_post_meta( absint($post), '_itemCode', true ) : '';

		if ( $page == 'usces_itemedit' || $page == 'usces_itemnew' || $item_code ) {
			add_meta_box( 'iu4w_images', __('Product Images', 'image-uploader-for-welcart' ), array( $this, 'image_uploader_for_welcart'), 'post', 'side', 'high' ); // Add new metabox
			remove_meta_box( 'item-main-pict', 'post', 'side' ); // Remove default metabox
		}
	}


	public function image_uploader_for_welcart( $post, $metabox ){

		$iu4w_li = array();
		$item_code = get_post_meta( $post->ID, '_itemCode', true );
		if ( $item_code ):
			$attached_ids = array();
			$get_children = get_children( '&post_type=attachment&post_mime_type=image&post_parent=' . $post->ID );
			if ( $get_children ) : foreach ( $get_children as $child ) :
				$attached_ids[] = $child->ID;
			endforeach; endif;
			global $usces;
			$img_ids = $this->iu4w_get_attaches( $item_code );
			if ( $img_ids ): foreach ($img_ids as $img_id ):
				if ( !in_array( $img_id, $attached_ids ) ) { // If WELCART product images are not attached.
					$atpost = array();
					$atpost['ID'] = $img_id;
					$atpost['post_parent'] = $post->ID;
					wp_update_post( $atpost ); // Attach to the post
				}
				$thumb_src = wp_get_attachment_image_src ($img_id,'medium');
				$iu4w_li[] = 
					"\t".'<li class="iu4w-li" id="iu4w-li-'.$img_id.'" title="'.__('Sort it in any order', 'image-uploader-for-welcart' ).'">'."\n".
					"\t\t".'<div class="iu4w-wrap">'."\n".
					"\t\t\t".'<a href="#" class="iu4w-remove button" title="'.__( 'Remove this image from the list', 'image-uploader-for-welcart' ).'"></a>'."\n".
					"\t\t\t".'<div class="iu4w-img" style="background-image:url(\''.$thumb_src[0].'\')"><img src="'.$thumb_src[0].'" /></div>' ."\n".
					"\t\t\t".'<div class="iu4w-editor"><label for="at' .$img_id. '_alt">'.__('Alt Text').'</label><input id="at' .$img_id. '_alt" type="text" placeholder="'.__('Alt Text').'" name="iu4w_attr['.$img_id. '][alt]" value="'.get_post_meta( $img_id, '_wp_attachment_image_alt', true).'" /><label for="at' .$img_id. '_cap">'.__('Caption').'</label><textarea id="at' .$img_id. '_cap" placeholder="'.__('Caption').'" name="iu4w_attr[' .$img_id. '][caption]">'.get_post_field('post_excerpt', $img_id).'</textarea></div>'."\n".
					"\t\t\t".'<input type="hidden" name="_iu4w_images[]" value="'.$img_id.'" />'."\n".
					"\t\t".'</div>'."\n".
					"\t".'</li>'."\n";
			endforeach; endif;
		endif;
		$setting = get_post_meta( $post->ID, '_iu4w_meta', true );
		echo '<ul id="iu4w-ul"'.( isset( $setting['view'] ) && $setting['view'] == 1 ? ' class="editor"' : '' ).'>'.join( $iu4w_li ).'</ul>'."\n";

	?>
	<div id="iu4w-media">
		<a class="button iu4w-open" title="<?php __('Add Images', 'image-uploader-for-welcart'); ?>"><?php _e( 'Add Images', 'image-uploader-for-welcart' ); ?></a>
		<a class="button iu4w-editor-open" title="<?php _e('Editor View', 'image-uploader-for-welcart'); ?>"></a>
		<a class="button iu4w-editor-close" title="<?php _e('Block View', 'image-uploader-for-welcart'); ?>"></a>
	</div>
	<input type="hidden" id="iu4w_view" name="iu4w_view" value="<?php echo ( isset( $setting['view'] ) ? $setting['view'] : 0 ); ?>" />
	<input type="hidden" id="iu4w_tempo_ids" name="iu4w_tempo_ids" value="<?php echo ( $img_ids ? join($img_ids,',') : '' ); ?>" />
	<input type="hidden" name="iu4w_nonce" value="<?php echo wp_create_nonce(basename(__FILE__)); ?>" />

	<style type="text/css">
		#iu4w_images *, #iu4w_images *::before, #iu4w_images *::after { -webkit-box-sizing:border-box; box-sizing:border-box; }
		#iu4w_after_title-sortables { margin-top:20px; }
		#iu4w_images .inside { padding-top:8px; padding-bottom:13px; }
		#iu4w-ul { display:block; list-style:none; margin:0 -7px; padding:0; display:-webkit-box; display:-ms-flexbox; display:flex; -webkit-flex-wrap:wrap; -ms-flex-wrap:wrap; flex-wrap:wrap; }
		#iu4w-ul::after { content:' '; display:block; height:0; clear:both; visibility:hidden; }
		#iu4w-ul > * { display:block; margin:0; padding:4px 6px; position:relative; width:20%; }
		#iu4w-ul > *:first-child { width:100%; max-width:100%; text-align:center; }
		#iu4w-ul > *:first-child .iu4w-wrap { display:inline-block; }
		#iu4w-ul:not(.editor) > *:first-child .iu4w-img { height:200px; display:block; background-size:contain; }
		#iu4w-ul:not(.editor) > *:first-child .iu4w-img img { display:block; }

		.iu4w-wrap { display:block; margin:0; padding:5px; position:relative; overflow:visible; background:#f8f8f8; border:1px solid #e7e7e7; -webkit-border-radius:2px; border-radius:2px; }
		.iu4w-editor { display:none; padding:2px 0 0 }
		.iu4w-editor label { display:none; }
		#iu4w_images input, #iu4w_images textarea { display:block; width:100%; margin:3px 0 0!important; padding:4px; }
		#iu4w_images input::-webkit-input-placeholder, #iu4w_images textarea::-webkit-input-placeholder { color:#e7d998; }
		#iu4w_images input::-moz-placeholder, #iu4w_images textarea::-moz-placeholder { color:#e7d998; }
		#iu4w_images input::-ms-input-placeholder, #iu4w_images textarea::-ms-input-placeholder { color:#e7d998; }
		#iu4w_images input::placeholder-shown, #iu4w_images textarea::placeholder-shown { color:#e7d998; }
		.iu4w-img { padding:0; margin:0; display:block; position:relative; vertical-align:middle; overflow:hidden; background-position:center center; background-repeat:no-repeat; background-size:contain; }
		.iu4w-wrap:hover { background:#ffff92; border-color:#cdc28d; cursor:move; }

		.iu4w-img { height:auto; display:block; background-size:cover; }
		.iu4w-img::before { content:' '; display:block; padding:100% 0 0 0; }
		.iu4w-img img { display:none; margin:0; padding:0; max-height:100%; max-width:100%; height:auto; width:auto; opacity:0; }
		
		#iu4w-ul.editor > * { width:100%; max-width:100%; }
		#iu4w-ul.editor .iu4w-img { height:120px; text-align:center; background-size:contain; }
		/*#iu4w-ul.editor .iu4w-img img { display:inline-block; height:100%; }*/
		#iu4w-ul.editor .iu4w-wrap { display:block; width:100%; }
		#iu4w-ul.editor .iu4w-editor { display:block; }
		#iu4w-ul:not(.editor) + #iu4w-media .iu4w-editor-close, #iu4w-ul.editor + #iu4w-media .iu4w-editor-open { background-color:#ffff92; }
		.iu4w-wrap input[type="hidden"] { display:none; }
		#iu4w-ul a.iu4w-remove { height:28px; width:28px; text-align:center; position:absolute; right:-5px; text-decoration:none; padding:0; -webkit-border-radius:2px; border-radius:2px; z-index:20; }
		#iu4w-ul a.iu4w-remove { top:-4px; }
		#iu4w-ul a.iu4w-remove::before { font-family:"dashicons"; display:block; text-align:center; vertical-align:middle; font-size:20px; line-height:20px; height:28px; padding:4px 0; }
		#iu4w-ul a.iu4w-remove::before { content:"\f158"; }
		#iu4w-media { padding:5px 108px 0 0; position:relative; }
		#iu4w-media a.button { height:30px; padding:8px; font-size:13px; line-height:20px; width:100%; height:auto; font-weight:normal; text-align:center; display:block; vertical-align:baseline;}
		#iu4w-media a.button::before { font-family:"dashicons"; font-size:20px; line-height:20px; display:inline; vertical-align:middle; }
		#iu4w-media a.iu4w-open::before { content:"\f128"; margin-right:.3em; }
		#iu4w-media a.iu4w-open { margin-right: 70px; }
		#iu4w-media a.iu4w-editor-open { position:absolute; top:5px; right:54px; width:50px; }
		#iu4w-media a.iu4w-editor-close { position:absolute; top:5px; right:0; width:50px; }
		#iu4w-media a.iu4w-editor-open::before { content:"\f163"; }
		#iu4w-media a.iu4w-editor-close::before { content:"\f509"; }
		#iu4w-ul .sortable-placeholder { position:relative; border:none; background:none; min-width:100px; min-height:100px; }
		#iu4w-ul:not(.editor) .sortable-placeholder:first-child { height:222px; }
		#iu4w-ul .sortable-placeholder::after { position:absolute; left:0; right:0; top:0; bottom:0; content:''; display:block; margin:5px; opacity:.5; border:1px dashed #cdc28d; background-color:#ffff92; }

		@media screen and (min-width : 1540px){
			#normal-sortables #iu4w-ul.editor > * { width:50%; }
		}
		@media screen and (min-width : 1280px){
			#iu4w-ul > * { width:16.66666%; max-width:180px; }
		}
		@media screen and (min-width : 851px){
			#side-sortables #iu4w_images .inside { padding-top:4px; }
			#side-sortables #iu4w-ul { text-align:center; }
			/*#side-sortables #iu4w-ul.editor > * { float:none; display:block; height:auto; }*/
			#side-sortables #iu4w-media { padding:5px 72px 0 0; }
			#side-sortables #iu4w-media a.button::before { font-size:18px; }
			#side-sortables #iu4w-media a.iu4w-editor-open { right:36px; width:36px; }
			#side-sortables #iu4w-media a.iu4w-editor-close { right:0; width:36px; }
			#side-sortables #iu4w-ul.editor .iu4w-img { display:block; text-align:center; width:auto; margin:0 auto; }
			#side-sortables #iu4w-ul.editor .iu4w-editor { padding:4px 0 0; display:block; width:auto; }
			#side-sortables .iu4w-editor label { display:none; }
	
			#side-sortables #iu4w-ul:not(.editor) { margin:0 -6px; }
			#side-sortables #iu4w-ul:not(.editor) > * { width:50%; padding:5px; }
			#side-sortables #iu4w-ul:not(.editor) > *:first-child { width:100%; }
			#side-sortables #iu4w-ul:not(.editor) > *:first-child .iu4w-img { height:auto; max-height:200px; display:block; background-size:contain; }
		}

		@media screen and ( min-width : 479px){
			.iu4w-editor label { width:80px; margin-left:-80px; display:inline-block; text-align:right; float:left; font-size:10px; padding:3px 4px 0 0; }
			.iu4w-editor label::after { display:inline-block; content:':'; }
			.iu4w-editor { padding:0 5px 0 84px; }
			#iu4w-ul.editor .iu4w-wrap { display:table; width:100%; }
			#iu4w-ul.editor .iu4w-img { width:24%; min-width:140px; max-width:240px; }
			#iu4w-ul.editor .iu4w-img, #iu4w-ul.editor .iu4w-editor { display:table-cell; vertical-align:middle; }
		}
		@media screen and ( min-width : 479px) and ( max-width : 850px){
			#iu4w_images input::-webkit-input-placeholder, #iu4w_images textarea::-webkit-input-placeholder { color:transparent; }
			#iu4w_images input::-moz-placeholder, #iu4w_images textarea::-moz-placeholder { color:transparent; }
			#iu4w_images input::-ms-input-placeholder, #iu4w_images textarea::-ms-input-placeholder { color:transparent; }
			#iu4w_images input::placeholder-shown, #iu4w_images textarea::placeholder-shown { color:transparent; }
		}
		@media screen and (max-width : 600px){
			#iu4w-ul > * { width:25%; }
		}
		@media screen and (max-width : 478px){
			#iu4w-ul > * { width:33.3333%; }
			#iu4w-ul:not(.editor) > *:first-child .iu4w-img { height:160px; }
			#iu4w-ul:not(.editor) .sortable-placeholder:first-child { height:182px; }
			.iu4w-wrap { padding:3px; }
		}
	</style>
	<script type="text/javascript">
	jQuery( function( $ ){

		var iu4w = $( '#iu4w_images' ), ex_ul = iu4w.find( '#iu4w-ul' ), ex_ids = [];

		iu4w.on( 'click', 'a.iu4w-open', function( e ) {
			e.preventDefault();

			var iu4w_uploader = wp.media({
				state: 'iu4w_state',
				multiple: true
			});
			iu4w_uploader.states.add([
				new wp.media.controller.Library({
					id: 'iu4w_state',
					library: wp.media.query( { type: 'image' } ),
					title: <?php echo '\''.__('Upload Product Images', 'image-uploader-for-welcart' ).'\''; ?>,
					priority: 70,
					toolbar: 'select',
					menu: false,
					filterable: 'uploaded',
					multiple: 'add'
				})
			]);
			setTimeout( function(){ if ( !$( '.media-frame' ).length ) iu4w_uploader.open(); }, 100 ); // The parameter "menu:false" disturbs open() event.		
			iu4w_uploader.open();

			iu4w_uploader.on( 'open', function(){
				$( 'select.attachment-filters [value="uploaded"]' ).attr( 'selected', true ).parent().trigger( 'change' ); // Change the default view to "Uploaded to this post".
			}).on( 'select', function( ){ 
				var this_id = 0, this_url = '', new_li = '';
					selection = iu4w_uploader.state().get( 'selection' );
				ex_ul.children( 'li' ).each( function(){
					this_id = Number( $(this).attr( 'id' ).slice(8) );
					if ( this_id ){ 
						ex_ids.push( this_id );
					} 
				});
				selection.each( function( file ){
					this_id = file.toJSON().id;
					if ( file.attributes.sizes.medium ) this_url = file.attributes.sizes.medium.url;
					else if ( file.attributes.sizes.large ) this_url = file.attributes.sizes.large.url;
					else this_url = file.attributes.url;
					if ( $.inArray( this_id, ex_ids ) > -1 ){ // Remove the ID duplicate in the list
						ex_ul.find( '#iu4w-li-' + this_id ).remove();
					}
					new_li =
						new_li +
						'<li class="iu4w-li" id="iu4w-li-' + this_id + '">' +
						'<div class="iu4w-wrap">' + 
						'<a href="#" class="iu4w-remove button" title="' + <?php echo '\''.__('Remove this image from the list', 'image-uploader-for-welcart' ).'\''; ?> + '"></a>' +
						'<div class="iu4w-img" style="background-image:url(\'' + this_url + '\')"><img src="' + this_url + '" /></div>' +
						'<div class="iu4w-editor"><label for="at' + this_id + '_alt">' + <?php echo '\''.__( 'Alt Text' ).'\''; ?> + '</label><input id="at' + this_id + '_alt" type="text" placeholder="' + <?php echo '\''.__('Alt Text').'\''; ?> + '" name="iu4w_attr[' + this_id + '][alt]" value="' + file.toJSON().alt + '" /><label for="at' + this_id + '_cap">' + <?php echo '\''.__('Caption').'\''; ?> + '</label><textarea id="at' + this_id + '_cap" placeholder="' + <?php echo '\''.__('Caption').'\''; ?> + '" name="iu4w_attr[' + this_id + '][caption]">' + file.toJSON().caption + '</textarea></div>' + 
						'<input type="hidden" name="_iu4w_images[]" value="'+ this_id +'" />' + 
						'</div>'+
						'</li>';
				});
				if ( ex_ul.find( 'li' ).length ) {
					ex_ul.append( new_li );
				} else {
					ex_ul.prepend( new_li );
				}
			});
		});


		iu4w.on( 'click', '.iu4w-remove', function() {
			img_obj = $(this).parents( 'li.iu4w-li' ).remove();
			return false;
		});

		ex_ul.sortable({
			cursor : 'move',
			tolerance : 'pointer',
			placeholder : "sortable-placeholder",
			opacity: 0.6
		});

		iu4w.on( 'click', 'a.iu4w-editor-open', function( e ) {
			$( '#iu4w-ul' ).addClass('editor');	
			$( '#iu4w_view').val( 1 );
		});
		iu4w.on( 'click', 'a.iu4w-editor-close', function( e ) {
			$( '#iu4w-ul' ).removeClass('editor');	
			$( '#iu4w_view').val( 0 );		
		});

	});
	</script>
	<?php 
	}



	public function iu4w_attachment_fields_to_edit( $form_fields, $post ) { // hide title input field

		$codestr = '-';
		$op = get_option('usces');
		if ( isset( $op['system']['subimage_rule'] ) && $op['system']['subimage_rule'] ){ // check setting of "subimage_rule"
			$codestr = defined('USCES_VERSION') && version_compare(USCES_VERSION, '1.8.9') < 0 ? '--' : '__';
		}
		$meta_value = '';
		if ( preg_match( '/^(.*?)'.$codestr.'([0-9]+)\z/', $post->post_title, $match ) ){
			$meta_value = mb_strtolower( $match[1] );
		} elseif ( preg_match( '/^(.*?)\z/', $post->post_title, $match ) ){
			$meta_value = mb_strtolower( $match[1] );
		}

		if ( $meta_value ){
			global $wpdb;
			$results = $wpdb->get_results( "SELECT ID FROM $wpdb->posts, $wpdb->postmeta WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id AND $wpdb->posts.post_type = 'post' AND $wpdb->postmeta.meta_key = '_itemCode' AND $wpdb->postmeta.meta_value = '$meta_value' LIMIT 1" );

			if ( $results && get_post_mime_type( $results[0]->ID ) == 'item' ){
				$form_fields[ 'iu4w_readonly_title' ][ 'label' ] = __('Title');
				$form_fields[ 'iu4w_readonly_title' ][ 'input' ] = 'html';
				$form_fields[ 'iu4w_readonly_title' ][ 'html' ] = '<input value="'.$post->post_title.'" type="text" readonly="readonly"/>';
				$form_fields[ 'iu4w_hide_input' ][ 'label' ] = '';
				$form_fields[ 'iu4w_hide_input' ][ 'input' ] = 'html';
				$form_fields[ 'iu4w_hide_input' ][ 'html' ] = 
					$pagenow.'<style type="text/css"> label.setting[data-setting="title"], .media-types.media-types-required-info { display:none!important; }</style>'."\n";
			}
		}
		return $form_fields;
	}



	/////////////////////// SAVE AND RENAME IMAGES ///////////////////////

	public function iu4w_save_images( $post_id ){
		if ( 
			defined('DOING_AUTOSAVE') && DOING_AUTOSAVE || 
			false !== wp_is_post_revision( $post_id ) || 
			!current_user_can( 'edit_post', $post_id ) ||
			!isset($_POST['iu4w_nonce']) || 
			!wp_verify_nonce($_POST['iu4w_nonce'], basename(__FILE__)) 
		) return $post_id; 

		$item_code = get_post_meta( $post_id, '_itemCode', true );
		if ( !$item_code ) return $post_id;
	
		$view = isset($_POST['iu4w_view']) ? $_POST['iu4w_view'] : 0;
		$attr = isset($_POST['iu4w_attr']) ? $_POST['iu4w_attr']: array();
		$setting = get_post_meta( $post_id, '_iu4w_meta', true );
		$ex_attr = isset($setting['attr']) ? $setting['attr'] : array();
		if ( $attr !== $ex_attr ){
			foreach( $attr as $key => $val ):
				$title = $caption = $alt = $dddd = '';
				foreach ( $val as $key2 => $val2 ){
					if ( trim($key2) == 'title' ) $title = $val2;
					elseif ( trim($key2) == 'caption' ) $caption = $val2;
					elseif ( trim($key2) == 'alt' ) $alt = $val2;
				}
				wp_update_post( array( 'ID' => $key, 'post_title' => $title, 'post_excerpt' => $caption ) );
				update_post_meta( $key, '_wp_attachment_image_alt', $alt );
			endforeach; 
		}

		$newvalue = array( 'attr' => $attr, 'view' => $view );
		update_post_meta( $post_id, '_iu4w_meta', $newvalue );

		$ex_ids = $this->iu4w_get_attaches( $item_code );
		$new_ids = isset($_POST['_iu4w_images']) ? $_POST['_iu4w_images']: array(); //POST
		$this->iu4w_rename_images( $post_id, $new_ids, $ex_ids, $item_code ); 

		return $post_id;

	}


	public function iu4w_rename_images( $post_id, $new_ids, $ex_ids, $item_code ) { // Rename images to welcart format
		
		$codestr = '-';
		$op = get_option('usces');
		if ( isset( $op['system']['subimage_rule'] ) && $op['system']['subimage_rule'] ){ // check setting of "subimage_rule"
			$codestr = defined('USCES_VERSION') && version_compare(USCES_VERSION, '1.8.9') < 0 ? '--' : '__';
		}
		if ( $new_ids ): foreach ( $new_ids as $key => $img_id ):
			if ( $key === 0 ) { // main image
				wp_update_post( array( "ID" => $img_id, "post_title" => $item_code )); // Change the title of a main image to SKU
				$thumb_id = get_post_meta( $post_id, '_thumbnail_id', true );
				$autothumb = apply_filters('iu4w_auto_thumbnail', '__return_true'); 
				if ( $autothumb || !$thumb_id && $_POST['_thumbnail_id'] == -1 ){
					update_post_meta( $post_id, '_thumbnail_id', $img_id ); // The main image register to the featured image
				} 
			} else { // sub images
				$key = sprintf( '%02d', $key );
				wp_update_post( array( "ID" => $img_id, "post_title" => $item_code.$codestr.$key )); // Change the title of a sub image to SKU with numbers
			} 
		endforeach; endif;
		if ( !empty( $ex_ids[0] ) ): foreach ( $ex_ids as $img_id ):
			if ( empty( $new_ids ) || !in_array ($img_id, $new_ids ) ) { // The ID not listed
				wp_update_post( array( "ID" => $img_id, "post_title" => 'img_'.$img_id )); // Remove SKU from the title of the image
			}
		endforeach; endif;

	}



	/////////////////////// GET ALL PRODUCT IMAGES /////////////////////// 

	public function iu4w_get_attaches ( $item_code ) { 
		// The function instead of get_mainpictid() and get_pictids(). It returns an array of images even if post_titles of images are duplicated.
		if( empty($item_code) ) return 0;
		$output = array();
		global $wpdb; global $usces;

		$results = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND post_title = '$item_code'" );
		if ( $results ): foreach ( $results as $val ):
			$output[] = $val->ID;
		endforeach; endif;
		$pictids = $usces->get_pictids( $item_code );
		if ( $pictids ){
		 	$output = array_merge( $output, $pictids );
		}
		if ( empty( $output ) ) {
			return false;
		} else {
			return $output;
		}

	}



}
new iu4w();
}

