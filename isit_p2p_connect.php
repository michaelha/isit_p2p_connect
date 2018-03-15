<?php
/*
Plugin Name: ISIT P2P Connections
Plugin URI: http://isit.arts.ubc.ca
Description: Create Website P2P connections
Version: 1.0
Author: Shaffiq Rahemtulla
Author URI: http://isit.arts.ubc.ca
*/
define( 'ISIT_P2P_CONNECTIONS_URI', plugins_url( '', __FILE__ ) );

add_filter("gform_column_input_32_7_1", "set_column", 10, 5);
function set_column($input_info, $field, $column, $value, $form_id){
    return array("type" => "select", "choices" => "First,Second,Third,Additional");
}

add_filter( 'gform_column_input_content_32_7_2', 'change_column2_content', 10, 6 );
function change_column2_content( $input, $input_info, $field, $text, $value, $form_id ) {
    //build field name, must match List field syntax to be processed correctly
    $input_field_name = 'input_' . $field->id . '[]';
    $tabindex = GFCommon::get_tabindex();
    $new_input = '<textarea name="' . $input_field_name . '" ' . $tabindex . ' class="textarea small" cols="50" rows="10">' . $value . '</textarea>';
    return $new_input;
}


function isit_p2p_register_scripts() {

	wp_register_style( 'owl-styles', ISIT_P2P_CONNECTIONS_URI . '/assets/owl.carousel.css', '', '1.0', 'all' );
	wp_register_script( 'owl-js', ISIT_P2P_CONNECTIONS_URI . '/assets/owl.carousel.min.js', array('jquery'), '2.2.0', true );
	//wp_register_script( 'isitowl-js', ISIT_P2P_CONNECTIONS_URI . '/assets/isitowl.js', array('jquery'), '2.2.0', true );
	wp_enqueue_style( 'owl-styles' );
			
}
add_action( 'wp_enqueue_scripts', 'isit_p2p_register_scripts' );

function isit_p2p_enqueue_scripts() {
	wp_enqueue_script( 'owl-js' );	
	//wp_enqueue_script( 'isitowl-js' );				
}

//######ATTACHMENT
add_filter( 'gform_notification', 'rw_notification_attachments', 10, 3 );
function rw_notification_attachments( $notification, $form, $entry ) {
    $log = 'rw_notification_attachments() - ';
    GFCommon::log_debug( $log . 'starting.' );
    
    if ( $notification['name'] == 'Admin Notification' ) {
        
        $fileupload_fields = GFCommon::get_fields_by_type( $form, array( 'fileupload' ) );
 
        if ( ! is_array( $fileupload_fields ) ) {
            return $notification;
        }
 
        $attachments = array();
        $upload_root = RGFormsModel::get_upload_root();
        
        foreach( $fileupload_fields as $field ) {
            
            $url = $entry[ $field['id'] ];
            
            if ( empty( $url ) ) {
                continue;
            } elseif ( $field['multipleFiles'] ) {
                $uploaded_files = json_decode( stripslashes( $url ), true );
                foreach ( $uploaded_files as $uploaded_file ) {
                    $attachment = preg_replace( '|^(.*?)/gravity_forms/|', $upload_root, $uploaded_file );
                    GFCommon::log_debug( $log . 'attaching the file: ' . print_r( $attachment, true  ) );
                    $attachments[] = $attachment;
                }
            } else {
                $attachment = preg_replace( '|^(.*?)/gravity_forms/|', $upload_root, $url );
                GFCommon::log_debug( $log . 'attaching the file: ' . print_r( $attachment, true  ) );
                $attachments[] = $attachment;
            }
            
        }
 
        $notification['attachments'] = $attachments;
 
    }
    
    GFCommon::log_debug( $log . 'stopping.' );
 
    return $notification;
}

//#############Allow excerpt to accept links######
function new_wp_trim_excerpt($text) {
	$raw_excerpt = $text;
	if ( '' == $text ) {
		$text = get_the_content('');
 
	$text = strip_shortcodes( $text );
 
	$text = apply_filters('the_content', $text);
	$text = str_replace(']]>', ']]>', $text);
	$text = strip_tags($text, '<a>,<br>');
	$excerpt_length = apply_filters('excerpt_length', 55);
 
	$excerpt_more = apply_filters('excerpt_more', ' ' . '[...]');
	$words = preg_split('/(<a.*?a>)|\n|\r|\t|\s/', $text, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE );
	if ( count($words) > $excerpt_length ) {
		array_pop($words);
		$text = implode(' ', $words);
		$text = $text . $excerpt_more;
	} 
	else {
		$text = implode(' ', $words);
	}
}
return apply_filters('new_wp_trim_excerpt', $text, $raw_excerpt);
 
}
remove_filter('get_the_excerpt', 'wp_trim_excerpt');
add_filter('get_the_excerpt', 'new_wp_trim_excerpt');

//###MOD to add admin column of modified date

add_action ( 'manage_posts_custom_column',	'isit_post_columns_data',	10,	2	);
add_filter ( 'manage_edit-post_columns',	'isit_post_columns_display'			);
add_filter( "manage_edit-post_sortable_columns", "last_modified_column_register_sortable" );

function last_modified_column_register_sortable( $columns ) {
	$columns["modified"] = "last_modified";
        return $columns;
}

function isit_post_columns_data( $column, $post_id ) {

	switch ( $column ) {

	case 'modified':
		$m_orig		= get_post_field( 'post_modified', $post_id, 'raw' );
		$m_stamp	= strtotime( $m_orig );
		$modified	= date('n/j/y @ g:i a', $m_stamp );

	       	$modr_id	= get_post_meta( $post_id, '_edit_last', true );
	       	$auth_id	= get_post_field( 'post_author', $post_id, 'raw' );
	       	$user_id	= !empty( $modr_id ) ? $modr_id : $auth_id;
	       	$user_info	= get_userdata( $user_id );
	
	       	echo '<p class="mod-date">';
	       	echo '<em>'.$modified.'</em><br />';
	       	echo 'by <strong>'.$user_info->display_name.'<strong>';
	       	echo '</p>';

		break;

	// end all case breaks
	}

}

function isit_post_columns_display( $columns ) {

	$columns['modified']	= 'Last Modified';

	return $columns;

}

//#####END MOD

function isit_p2p_connections() {

    p2p_register_connection_type( array(
            'name' => 'Portals_to_Services',
            'from' => 'page',
            'to' => 'service',
            'cardinality' => 'one-to-many',
            'sortable' => 'any',
            'title' => array('from' => 'Portal', 'to' => 'Services'),
            'admin_box' => array(
    		  'show' => 'any',
    		  'context' => 'advanced'
  	         ),
            'admin_column' => 'any',
            'to_labels' => array(
    		  'column_title' => 'Connected from Portal',
  	     ),
            'from_labels' => array(
    		  'column_title' => 'Connected Services',
  	     ),
            'admin_dropdown' => 'from'
    ) );

    p2p_register_connection_type( array(
            'name' => 'Services_to_Posts',
            'from' => 'service',
            'to' => 'post',
//#####
	'fields' => array(
		'Connection Type' => array(
			'title' => 'Connection Type',
			'type' => 'select',
			'values' => array( 'Standard', 'Featured','Featured In Service List'),
			'default' => 'Standard'
		),
		'Connection Icon' => array(
			'title' => 'Connection Icon',
			'type' => 'select',
			'values' => array( 'Default','Featured','Link', 'Testimonial', 'Strategy','Profile' ,'Form','Thumbnail'),
			'default' => 'Default'
		),
	  ),
//#####
	           //'cardinality' => 'one-to-many',
            'sortable' => 'any',
            'title' => array('from' => 'Service', 'to' => 'from Service(s)'),
            'admin_box' => array(
    		  'show' => 'any',
    		  'context' => 'advanced'
            ),
            'admin_column' => 'any',
            'from_labels' => array(
                'column_title' => 'Connected to Posts',
            ),
            'to_labels' => array(
                'column_title' => 'Connected from Services',
            ),
            'admin_dropdown' => 'to'
    ) );

    p2p_register_connection_type( array(
        'name' => 'Posts_to_Posts',
        'from' => 'post',
        'to' => 'post',
	'fields' => array(
		'Connection Type' => array(
			'title' => 'Connection Type',
			'type' => 'select',
			'values' => array( 'Standard', 'Featured'),
			'default' => 'Standard'
		),
		'Connection Icon' => array(
			'title' => 'Connection Icon',
			'type' => 'select',
			'values' => array( 'Default','Featured','Link', 'Testimonial', 'Strategy','Profile' ,'Form','Thumbnail'),
			'default' => 'Default'
		),
	  ),
	  // to get value later $canlitAuthor is a post object
	//$author_type = ' ('.p2p_get_meta($canlitAuthor->p2p_id, 'Author Type', true ).')';
        'sortable' => 'any',
        'title' => array('from' => 'child Post(s)','to' => 'parent Post(s)'),
        'admin_box' => array(
    		'show' => 'any',
    		'context' => 'advanced'
        ),
        'admin_column' => 'any',
        'from_labels' => array(
    		'column_title' => 'Connected from Posts'
        ),
        'to_labels' => array(
    		'column_title' => 'Connected to Posts'
        ),
        'admin_dropdown' => 'to'
    ) );
}
add_action( 'p2p_init', 'isit_p2p_connections' );

function get_default( $connection, $direction ) {
    return 'Standard';
}

/* ==================================================================
 *	Shortcode to display homepage
 * ================================================================== */
function isit_related_portals2 ( $atts ) {
	extract(shortcode_atts(array('tax_query' => array( array('taxonomy' => 'page-category','field'    => 'slug','terms'    => 'show-in-homepage',),)),$atts));
	$taxargs = array(
		'post_type' => array( 'page' ),
		'tax_query' => array( 
				array(
					'taxonomy' => 'page-category',
					'field'    => 'slug',
					'terms'    => 'show-in-homepage'
				)
		),
	);
    	$shortcode_output = '';
	$relatedPortals = new WP_Query( $taxargs );
	$portals = $relatedPortals->posts;
	$shortcode_output .= '<div class="homepage">';

	$count = 0;
	foreach ($portals as $portal){
		$count ++;
		if ($count < 4)
			$feat_image = '<img class="hpimage" src="'.wp_get_attachment_url( get_post_thumbnail_id($portal->ID) ).'"/>';
        else
			$feat_image = '<img class="hpimage" src="/files/2013/05/Feature2.png"/>';
		$portal_link = get_permalink( $portal->ID );
		$shortcode_output .= '<div class="box">';

		$shortcode_output .= '<div class="box-wrap boxhover centre"><div class="homeimg"><a class="portal-link" href="'.$portal_link.'">'.$feat_image.'</div><div class="middle"><h3 class="hometitle centre"><a class="portal" href="'.$portal_link.'">'.get_the_title($portal->ID).'</h3>';
		$shortcode_output .= $portal->post_excerpt.'</a></div>';
        if ($count < 4)
			$shortcode_output .= '<div><p class="searchtext">OR JUST SEARCH ABOVE!</p><a class="portal button" href="'.$portal_link.'">SHOW ME</a></div></div></div>';
		else
			$shortcode_output .= '<div><p class="searchtext">&nbsp;</p><a class="portal button" href="'.$portal_link.'">SHOW ME</a></div></div></div>';
	}


	if (count($portals)==3){ //Need to add an extra one here!!!
		$taxargs = array(
			'post_type' => array( 'post' ),
			'posts_per_page' => 1,
			'orderby' => 'rand',
			'tax_query' => array( 
				array(
					'taxonomy' => 'category',
					'field'    => 'slug',
					'terms'    => 'homead'
				)
			),
		);
		$relatedFeatures = new WP_Query( $taxargs );
		$feature = $relatedFeatures->posts;
		$shortcode_output .= '<div class="box">';
		$shortcode_output .= '<div class="">';

		$shortcode_output .= do_shortcode('[isit_features2 relatedcat="homead"]');

		//$shortcode_output .= '<div><p class="searchtext">&nbsp;</p><a class="portal button" href="'.$portal_link.'">SHOW ME</a></div></div></div>';
	}


	$shortcode_output .= '</div>';
	wp_reset_postdata();
	return $shortcode_output;
}
add_shortcode( 'isit_related_portals2', 'isit_related_portals2' );




/* ==================================================================
 *	Shortcode to display Content using ScrollIt [related_portals]
 * ================================================================== */
function isit_related_portals ( $atts ) {
	extract(shortcode_atts(array('tax_query' => array( array('taxonomy' => 'page-category','field'    => 'slug','terms'    => 'show-in-homepage',),)),$atts));
$taxargs = array(
	'post_type' => array( 'page' ),
	'tax_query' => array( 
				array(
					'taxonomy' => 'page-category',
					'field'    => 'slug',
					'terms'    => 'show-in-homepage'
				)
	),
);
        $shortcode_output = '';
	$relatedPortals = new WP_Query( $taxargs );
	$portals = $relatedPortals->posts;

//At this point, should have 3 pages or if count > 3 then - allows for override
//Search for one more at random from posts with category home
//merge with relatedPortals array allow for separate feature image formatting later.

	if (count($portals)==3){
//echo 'portal count'.count($portals); //-works!
		$taxargs = array(
			'post_type' => array( 'post' ),
			'posts_per_page' => 1,
			'orderby' => 'rand',
			'tax_query' => array( 
				array(
					'taxonomy' => 'category',
					'field'    => 'slug',
					'terms'    => 'home'
				)
			),
		);
		$relatedFeatures = new WP_Query( $taxargs );
		$feature = $relatedFeatures->posts;
//echo 'feature count'.count($feature);
		$portals = array_merge($portals,$feature);
//echo 'portal count'.count($portals);
	}


	$shortcode_output .= '<div class="boxwrap homepage">';
	$count = 0;
	foreach ($portals as $portal){
		$count ++;
		if ($count < 4)
			$feat_image = '<img class="hpimage" src="'.wp_get_attachment_url( get_post_thumbnail_id($portal->ID) ).'"/>';
                else
			$feat_image = '<img class="hpimage" src="/files/2013/05/Feature2.png"/>';
		$portal_link = get_permalink( $portal->ID );
		$shortcode_output .= '<div class="box">';

		$shortcode_output .= '<div class="box-wrap boxhover centre"><div class="homeimg"><a class="portal-link" href="'.$portal_link.'">'.$feat_image.'</div><div class="middle"><h3 class="hometitle centre"><a class="portal" href="'.$portal_link.'">'.get_the_title($portal->ID).'</a></h3>';
		$shortcode_output .= $portal->post_excerpt.'</div>';
                if ($count < 4)
			$shortcode_output .= '<div><a class="portal button" href="'.$portal_link.'">SHOW ME</a><p class="searchtext">OR JUST SEARCH ABOVE!</p></div></div></div>';
		else
			$shortcode_output .= '<div><a class="portal button" href="'.$portal_link.'">SHOW ME</a><p class="searchtext">&nbsp;</p></div></div></div>';
	}
	$shortcode_output .= '</div>';
	wp_reset_postdata();
	return $shortcode_output;
}
add_shortcode( 'isit_related_portals', 'isit_related_portals' );

/* ==================================================================
 *	Shortcode to display  [portal pages and related services]
 * ================================================================== */
function isit_related_services ( $atts ) {

	extract(shortcode_atts(array('columns'=> '3'),$atts));
        $shortcode_output = '';
    	$relatedServices = new WP_Query( array(
  		'connected_type' => 'Portals_to_Services',
  		'connected_items' => get_queried_object_id()
	) );

    	$services = $relatedServices->posts;
	$shortcode_output .= '<div class="boxwrap">';
        foreach ($services as $service){	

//####check if has excerpt
                if ($service->post_excerpt)
			$blurb = wp_trim_words($service->post_excerpt,18,' ...');
		else
			$blurb = wp_trim_words($service->post_content,18,' ...');

		$services = $relatedServices->posts;
		$shortcode_output .= '<div class="box service-page"><div class="box-wrap"><h3 class="centre service-post-title"><a class="services-link" href="'.get_permalink( $service->ID ).'">'.get_the_title($service->ID).'</h3><p class="service-blurb">'.$blurb.'</p></a><ul>';

//#########Here is where you could filter for NO features using connection_meta != featured
    		$nullrelatedPosts = new WP_Query( array(
  				'connected_type' => 'Services_to_Posts',
				'connected_direction' => 'from',
  				'fields' => 'ids',
				'_groupby' => 'ID',
				'connected_meta' => array(
   							array(
     								'key' => 'Connection Type',
								'compare' => 'NOT EXISTS'
    							)
						),
  				'connected_items' => $service
			) );

    		$relatedPosts = new WP_Query( array(
  				'connected_type' => 'Services_to_Posts',
				'connected_direction' => 'from',
  				'fields' => 'ids',
				'_groupby' => 'ID',
				'connected_meta' => array(
   							array(
     								'key' => 'Connection Type',
								'value' => array('Featured','Featured In Service List'),
								'compare' => 'NOT IN'
    							)
						),
  				'connected_items' => $service
			) );
        	$posts = array_merge($relatedPosts->posts,$nullrelatedPosts->posts);
		//$posts = $relatedPosts->posts;
        	foreach ($posts as $post){
			$shortcode_output .= '<li class="mini-box"><a class="service-component-link" href="'.get_permalink( $post ).'">'.get_the_title($post).'</a></li>';
		}
		$shortcode_output .= '</ul></div></div>';
	}//end services
	$shortcode_output .= '</div>';
	wp_reset_postdata();
	return $shortcode_output;
}
add_shortcode( 'isit_related_services', 'isit_related_services' );

/* ==================================================================
 *	Shortcode to display   [service pages and related posts]
 * ================================================================== */
function isit_related_service_to_posts ( $atts ) {

	extract(shortcode_atts(array('columns'=> '3'),$atts));
        $shortcode_output = '';
    	$relatednullPostsPosts = new WP_Query( array(
  				'connected_type' => 'Services_to_Posts',
				'connected_direction' => 'from',
				'connected_meta' => array(
							array(
							'key' => 'Connection Type',
							'compare' => 'NOT EXISTS'
							)
						),
  				'connected_items' => get_queried_object_id()
	) );
        $nullpostsposts = $relatednullPostsPosts->posts;

    	$relatedPosts = new WP_Query( array(
  				'connected_type' => 'Services_to_Posts',
				'connected_direction' => 'from',
				'connected_meta' => array(
							array(
							'key' => 'Connection Type',
							'value' => array('Featured','Featured In Service List'),
							'compare' => 'NOT IN'
							)
						),
  				'connected_items' => get_queried_object_id()
	) );
        $posts = array_merge($relatedPosts->posts,$relatednullPostsPosts->posts);
	$shortcode_output .= '<div class="boxwrap service-post">';
        foreach ($posts as $post){

		if (empty($post->post_excerpt)){
			$blurb = wp_trim_words($post->post_content,18,'...');
		}
		else{
			$blurb = wp_trim_words($post->post_excerpt,18,'...');
		}

		$relatedPostsPosts = new WP_Query( array(
  			'connected_type' => 'Posts_to_Posts',
			'connected_direction' => 'from',
  			'connected_items' => $post
		) );

		$postsposts = $relatedPostsPosts->posts;

		$shortcode_output .= '<div class="box service-page"><div class="box-wrap"><h3 class="centre service-post-title"><a class="service-component-link" href="'.get_permalink( $post->ID ).'">'.get_the_title($post->ID).'</h3><p class="servicecomponent-blurb">'.$blurb.'</p></a><ul>';
						
		foreach ($postsposts as $postspost){
			$shortcode_output .= '<li class="mini-box"><a class="supporting-technology-link" href="'.get_permalink( $postspost->ID ).'">'.get_the_title($postspost).'</a></li>';	

		}//end supporting-tech
		$shortcode_output .= '</ul></div></div>';
	}//end service-components
	$shortcode_output .= '</div>';
	wp_reset_postdata();
	return $shortcode_output;
}
add_shortcode( 'isit_related_service_to_posts', 'isit_related_service_to_posts' );

/* ==================================================================
 *	Shortcode to display Content using ScrollIt [related_supporting_technologies]
 * ================================================================== */
function isit_related_posts_to_posts ( $atts ) {

	extract(shortcode_atts(array('columns'=> '3'),$atts));
        $shortcode_output = '';
	$shortcode_output .= '<div class="boxwrap post-post">';
//***QUERY TO HANDLE NULL CASE

    	$relatednullPostsPosts = new WP_Query( array(
  				'connected_type' => 'Posts_to_Posts',
				'connected_direction' => 'from',
				'connected_meta' => array(
							array(
							'key' => 'Connection Type',
							'compare' => 'NOT EXISTS'
							)
						),
  				'connected_items' => get_queried_object_id()
	) );
        $nullpostsposts = $relatednullPostsPosts->posts;

        foreach ($nullpostsposts as $nullpostspost){

		if (empty($nullpostspost->post_excerpt)){
			$blurb = wp_trim_words($nullpostspost->post_content,18,'...');
		}
		else{
			$blurb = wp_trim_words($nullpostspost->post_excerpt,18,'...');
		}

		$shortcode_output .= '<div class="box"><a class="supporting-technology-link" href="'.get_permalink( $nullpostspost->ID ).'"><div class="box-wrap postbox  boxborder boxhover"><div class="supporting-technology-wrap"><h3 class="centre post-post-title">'.get_the_title($nullpostspost->ID).'</h3><p class="supporting-technology-blurb centre">'.$blurb.'</p></div></div></a></div>';
	}//end supporting-technologies


    	$relatedPostsPosts = new WP_Query( array(
  				'connected_type' => 'Posts_to_Posts',
				'connected_direction' => 'from',
				'connected_meta' => array(
							array(
							'key' => 'Connection Type',
							'value' => 'Featured',
							'compare' => '!='
							)
						),
  				'connected_items' => get_queried_object_id()
	) );
        $postsposts = $relatedPostsPosts->posts;

        foreach ($postsposts as $postspost){

		if (empty($postspost->post_excerpt)){
			$blurb = wp_trim_words($postspost->post_content,18,'...');
		}
		else{
			$blurb = wp_trim_words($postspost->post_excerpt,18,'...');
		}

		$shortcode_output .= '<div class="box postspost"><a class="supporting-technology-link" href="'.get_permalink( $postspost->ID ).'"><div class="box-wrap postbox  boxborder boxhover"><div class="supporting-technology-wrap"><h3 class="centre post-post-title">'.get_the_title($postspost->ID).'</h3><p class="supporting-technology-blurb centre">'.$blurb.'</p></div></div></a></div>';
	}//end supporting-technologies
	$shortcode_output .= '</div>';
	wp_reset_postdata();
	return $shortcode_output;
}
add_shortcode( 'isit_related_posts_to_posts', 'isit_related_posts_to_posts' );

function get_isit_thumb($fid,$relatedObj){

	$content_post = get_post($fid);
	$p2pID = $content_post->p2p_id;
	if ($relatedObj == 1) {
		$connection_type = p2p_get_meta( get_post($fid)->p2p_id, 'Connection Icon', true );
		if (!$connection_type) $connection_type = 'Thumbnail';
	} else {
		$connection_type = 'Thumbnail';
	}

	if (($connection_type == 'Thumbnail')&&( '' != get_the_post_thumbnail($fid) )) {
            	$thumb = wp_get_attachment_image_src( get_post_thumbnail_id($fid), 'thumbnail' );
            	$thumbnail_src = $thumb['0'];
        } else {
		if( $connection_type == 'Testimonial' ) :
    			$thumbnail_src = ISIT_P2P_CONNECTIONS_URI . '/usecaseimgs/UseCase_Testimonial.png';
		elseif( $connection_type ==  'Strategy') :
    			$thumbnail_src = ISIT_P2P_CONNECTIONS_URI . '/usecaseimgs/UseCase_Strategy.png';
		elseif( $connection_type ==  'Profile' ) :
    			$thumbnail_src = ISIT_P2P_CONNECTIONS_URI . '/usecaseimgs/UseCase_Profiles.png';
		elseif( $connection_type == 'Link') :
    			$thumbnail_src = ISIT_P2P_CONNECTIONS_URI . '/usecaseimgs/UseCase_Http.png';
		elseif( $connection_type == 'Form') :
    			$thumbnail_src = ISIT_P2P_CONNECTIONS_URI . '/usecaseimgs/UseCase_Forms.png';
		elseif( $connection_type == 'Featured') :
    			$thumbnail_src = ISIT_P2P_CONNECTIONS_URI . '/usecaseimgs/UseCase_Star.png';
		else:
            		$thumbnail_src = ISIT_P2P_CONNECTIONS_URI . '/usecaseimgs/UseCase_ISITFeatured.png';
			$connection_type = 'Default';

		endif;
        }

        return '<img class="'.$connection_type.$relatedObj.$test.'" src="'.$thumbnail_src.'">';
}

/* ==================================================================
 *	Shortcode to display Content using ScrollIt [related_supporting_technologies]
 * ================================================================== */
function isit_inline_posts_to_posts ( $atts ) {

//Chk whether this post allows showing multiple features 
//Multiple features will show multiple posts whereas the DEFAULT is to show ONE feature at random
	$current_post = get_queried_object();
	extract( shortcode_atts( array('showinline' => false,'servicepages' => false), $atts));
	$autoshow = true;
	if (has_category( 'no-autoshow-features', $current_post )) $autoshow = false;
	if (!$autoshow) $thisclass = ' no-autoshow';
	if ($showinline) $thisclass .= ' show-inline';
        $shortcode_output = '';
	if ('service' == get_post_type()){  //on services page then 
		$connected_type = 'Services_to_Posts';
		return; //No action on service pages
	}
	else
		$connected_type = 'Posts_to_Posts';
    	$relatedPostsPosts = new WP_Query( array(
  				'connected_type' => $connected_type,
				'connected_direction' => 'from',
				'connected_meta' => array(
							array(
								'key' => 'Connection Type',
								'value' => array('Featured','Featured In Service List'),
								'compare' => 'IN'
							)
						),
  				'connected_items' => get_queried_object_id()
	) );

if ($relatedPostsPosts){
	$show_multiple = false;
	if (has_category( 'show-multiple-features', $current_post )) $show_multiple = true;
        $postsposts = $relatedPostsPosts->posts;
	$evenodd = 'odd';
	if (count($postsposts)%2 ==0) $evenodd = 'even';
	if (!$show_multiple && ($autoshow||$showinline)){
		$rand_key = array_rand($postsposts);
		$postspost = $postsposts[$rand_key];
		if ($postspost){
			if (empty($postspost->post_excerpt)){
				$blurb = wp_trim_words($postspost->post_content,18,'...');
			}
			else{
				$blurb = wp_trim_words($postspost->post_excerpt,18,'...');
			}
			$shortcode_output .= '<div class="usecasebox '.$thisclass.'">';
//if this linktype is thumbnail and we are showing single
			if (p2p_get_meta( $postspost->p2p_id, 'Connection Icon', true ) == 'Thumbnail'){
					$shortcode_output .= '<div class="box-in single thumb"><a class="supporting-technology-link" href="'.get_permalink( $postspost->ID ).'"><div class="box-wrap postbox"><div class="supporting-technology-wrap expand">'.get_isit_thumb($postspost->ID,true).'<div class="box-content"><h3 class="centre post-post-title">'.get_the_title($postspost->ID).'</h3><p class="supporting-technology-blurb centre">'.$blurb.'</p></div></div></div></a></div>';
			}
			else{
					$shortcode_output .= '<div class="box-in"><a class="supporting-technology-link" href="'.get_permalink( $postspost->ID ).'"><div class="box-wrap postbox"><div class="supporting-technology-wrap expand">'.get_isit_thumb($postspost->ID,true).'<div class="box-content"><h3 class="centre post-post-title">'.get_the_title($postspost->ID).'</h3><p class="supporting-technology-blurb centre">'.$blurb.'</p></div></div></div></a></div>';
			}
		}
	}
	else{
	   if (($autoshow||$showinline)){
	     if (!$showinline){
		$shortcode_output .= '<div class="usecasebox multiple '.$evenodd.$thisclass.'">';
        	foreach ($postsposts as $postspost){

			if (empty($postspost->post_excerpt)){
				$blurb = wp_trim_words($postspost->post_content,18,'...');
			}
			else{
				$blurb = wp_trim_words($postspost->post_excerpt,18,'...');
			}

			$shortcode_output .= '<div class="box-in"><a class="supporting-technology-link" href="'.get_permalink( $postspost->ID ).'"><div class="box-wrap postbox"><div class="supporting-technology-wrap expand">'.get_isit_thumb($postspost->ID,true).'<div class="box-content"><h3 class="centre post-post-title">'.get_the_title($postspost->ID).'</h3><p class="supporting-technology-blurb centre">'.$blurb.'</p></div></div></div></a></div>';
		}//end supporting-technologies
	      }
	      else{ //show inline formatted as a list
		$numfeatures = count($postsposts);
		$shortcode_output .= '<div class="owl-carousel '.$connected_type.' '.$numfeatures.'">';
        	foreach ($postsposts as $postspost){

			if (empty($postspost->post_excerpt)){
				$blurb = wp_trim_words($postspost->post_content,18,'...');
			}		
			else{
				$blurb = wp_trim_words($postspost->post_excerpt,18,'...');
			}
			if ($numfeatures == 1){
				$shortcode_output .= 
			'<div class="row-fluid">
<div class="span2">
<a style="display:block;" href="'.get_permalink( $postspost->ID ).'">
<div class="pix shadow">'.get_isit_thumb($postspost->ID,true).'</a></div>
</div>
<div class="span10">
<a href="'.get_permalink( $postspost->ID ).'">
<h3 class="owl-post-title">'.get_the_title($postspost->ID).'</h3></a>
<p class="blurb">'.$blurb.'</p>
</div>
</div>';
			
			}
			else{
				$shortcode_output .= 
			'<div class="shortcode-content"><a class="owl-item-link" href="'.get_permalink( $postspost->ID ).'"><div class="pix shadow '.p2p_get_meta( $postspost->p2p_id, 'Connection Icon', true ).'">'.get_isit_thumb($postspost->ID,true).'</div><h3 class="owl-post-title">'.get_the_title($postspost->ID).'</h3></a><p class="blurb">'.$blurb.'</p></div>';
			}	
		}
		$shortcode_output .= '</div>';
//#######ADD JS HERE WITH Dynamic count of features remember quoted string!
	if ($numfeatures > 1){
		isit_p2p_enqueue_scripts();

		if ($numfeatures < 2) {
    			$maxitems = $numfeatures + 1;
			$dots = "false";
			$nav = "false";
		} elseif ($numfeatures <= 3) {
    			$maxitems = $numfeatures;
			$dots = "false";
			$nav = "false";
		} elseif ($numfeatures > 3) {
			$dots = "true";
			$nav = "true";
    			$maxitems = 3;
		}

		$shortcode_output .= "<script>
			jQuery(document).ready(function($){
				$('.owl-carousel').owlCarousel({
    					loop:true,
					dotsEach:true,
					nav:true,
					navText:['<i class=\'icon-chevron-left\'></i>','<i class=\'icon-chevron-right icon-white\'></i>'],
    					margin:10,
    					responsiveClass:true,
    					responsive:{
        					0:{
            						items:1,
            						//nav:true,
							//dots:".$dots.",
        					},
        					600:{
            						items:".$maxitems.",
            						nav:".$nav.",
	    						dots:".$dots.",
        					},
        					1000:{
            						items:".$maxitems.",
            						nav:".$nav.",
	    						dots:".$dots.",
            						loop:true
        					}
    					}
    				})
			});

		</script>";

	}
	else{
        echo '<style>.owl-carousel{
			display:block;
			margin:0;
			margin-top:20px;
                        width:100%;
		}
		.owl-carousel h3,.owl-carousel p{max-width:600px;padding-left:25px;}
		.owl-carousel img{clear:both;display:block;margin:0 auto;max-width:100%;vertical-align:middle;margin-right:10px;}
		.owl-carousel img{/*text-align:center;margin-right:20px;margin-top:20px;float:left;border:1px solid #00A9BF;*/width:120px !important;height:120px !important;border-radius:63px;background-color:#00A9BF;}
</style>';
	}
//#################################################
	    }
	   }
	}//end if
	$shortcode_output .= '</div>';
}
	wp_reset_postdata();
	return $shortcode_output;
}
add_shortcode( 'isit_inline_posts_to_posts', 'isit_inline_posts_to_posts' );

/* ==================================================================
 *	Shortcode to display parents in byline
 * ================================================================== */
function isit_parents ( $atts ) {
	extract(shortcode_atts(array('columns'=> '3','searchtemp' => false, 'connected_items' => get_queried_object_id() ),$atts));
        $shortcode_output = '';

     if (is_single() || $searchtemp){
    	$parentPortals = new WP_Query( array(
  		'connected_type' => 'Portals_to_Services',
		'connected_direction' => 'to',
  		'fields' => 'ids',
		'_groupby' => 'ID',
  		'connected_items' => $connected_items
	) );
        $portals = $parentPortals->posts;

    	$parentServices = new WP_Query( array(
  		'connected_type' => 'Services_to_Posts',
		'connected_direction' => 'to',
  		'fields' => 'ids',
		'_groupby' => 'ID',
  		'connected_items' => $connected_items
	) );
        $services = $parentServices->posts;

	$parentPosts = new WP_Query( array(
  		'connected_type' => 'Posts_to_Posts',
		'connected_direction' => 'to',
  		'fields' => 'ids',
		'_groupby' => 'ID',
  		'connected_items' => $connected_items
	) );

	$posts = $parentPosts->posts;
if ($posts || $services || $portals){
	$shortcode_output .= '<span class="parents-menu">';

	foreach ($portals as $portal){
		$shortcode_output .= '<a class="service-link" href="'.get_permalink($portal).'">'.get_the_title($portal).'</a>';
	}

	foreach ($services as $service){
		$shortcode_output .= '<a class="service-link" href="'.get_permalink($service).'">'.get_the_title($service).'</a>';
	}
        foreach ($posts as $post){
		$shortcode_output .= '<a class="post-link" href="'.get_permalink($post).'">'.get_the_title($post).'</a>';
	}				

	$shortcode_output .= '</span>';
	wp_reset_postdata();
	return 'Parent Page(s): '.$shortcode_output;
}
    }
}
add_shortcode( 'isit_parents', 'isit_parents' );

add_filter('disable_comments_allow_persistent_mode','remove_persistant_option',10,2);
function remove_persistant_option() {
	return false;
}

/* ==================================================================
 *	Shortcode for features on Portal pages (by Category)
 * ================================================================== */
function isit_features2 ( $atts ) {
	$relatedObj = true;
	$current_post = get_queried_object();

	extract( shortcode_atts( array('showinline' => false,'servicepages' => false,'relatedcat' => '', 'numfeatures_override' => '', 'addclass' => '', 'title' => ''), $atts));

	if ($relatedcat == ''){

		if ('service' == get_post_type()){  //on services page then 
			$connected_type = 'Services_to_Posts';
		}
		else
			$connected_type = 'Posts_to_Posts';

    	$relatedPostsPosts = new WP_Query( array(
  				'connected_type' => $connected_type,
				'connected_direction' => 'from',
				'connected_meta' => array(
							array(
								'key' => 'Connection Type',
								'value' => array('Featured','Featured In Service List'),
								'compare' => 'IN'
							)
						),
  				'connected_items' => get_queried_object_id()
		) );
    }
    else{
    	//get all posts within a category - given in parameter $relatedCategory

	$relatedObj = false;
    	$taxargs = array(
			'post_type' => array( 'service','post' ),
			'orderby' => 'rand',
			'tax_query' => array( 
						array(
							'taxonomy' => 'category',
							'field'    => 'slug',
							'terms'    => $relatedcat,
						)
			),
		);
		$relatedPostsPosts = new WP_Query( $taxargs );
		if (empty($addclass))
			$connected_type = 'Services_to_Posts '.$relatedcat;
    }

	if ($relatedPostsPosts){
		$postsposts = $relatedPostsPosts->posts;
		$numfeatures = count($postsposts);
		//if ($title) {
			//$shortcode_output .= '<h3>'.$title.'</h3>';
		//}
		$shortcode_output .= '<div class="owl-carousel'.$addclass.' '.$connected_type.' flag'.$numfeatures.' '.$addclass.'">';
        	foreach ($postsposts as $postspost){

			if (empty($postspost->post_excerpt)){
				$blurb = wp_trim_words($postspost->post_content,18,'...');
			}		
			else{
				$blurb = wp_trim_words($postspost->post_excerpt,18,'...');
			}
			$autoplay = '';
			if ($numfeatures > 0){
				if ($relatedcat == "homead"){
					$shortcode_output .= 
						'<div class="shortcode-content">
							<a class="owl-item-link" href="'.get_permalink( $postspost->ID ).'">
								<div class="homead">'.get_the_post_thumbnail($postspost->ID).'</div>
							</a>
						</div>';
					$autoplay = 'autoplay:true,autoplayTimeout:7000,autoplayHoverPause:true,';
				} else {
					$shortcode_output .= 
						'<div class="shortcode-content">
							<a class="owl-item-link" href="'.get_permalink( $postspost->ID ).'">
								<div class="pix shadow '.$relatedObj.'">'.get_isit_thumb($postspost->ID,$relatedObj).'</div>
								<h3 class="owl-post-title">'.get_the_title($postspost->ID).'</h3>
				
							<p class="blurb">'.$blurb.'</p></a>
						</div>';
				}
			
			}

		}
		$shortcode_output .= '</div></div></div>';

		if ($numfeatures > 0){
			$randomscript = '';
			if (!$relatedObj)
				$randomscript = 'beforeInit : function(elem){random(elem);},';
			$automateHTML = ''; //change span from 9 to 6
			if (!$relatedObj && (relatedcat != "home"))
				$automateHTML = '$(".entry-content .span9").addClass("span6").removeClass("span9");';
			isit_p2p_enqueue_scripts();

			if ($numfeatures < 2) {
				$dots = "false";
				$nav = "false";
				$maxitems = 2;
			} elseif ($numfeatures <= 3) {
    				$maxitems = $numfeatures;
				$dots = "true";
				$nav = "false";
				if (empty($addclass)) 
					$maxitems = 1;
			} elseif ($numfeatures > 3) {
				$dots = "true";
				$nav = "false";
				if (empty($addclass)) 
    					$maxitems = 1;
				else
					$maxitems = 3;
			}
			$shortcode_output .= "<script>
				jQuery(document).ready(function($){
					".$automateHTML."
					$('.owl-carousel".$addclass."').owlCarousel({
					".$autoplay."
    					loop:true,
					dotsEach:true,
					nav:false,
					navText:['<i class=\'icon-chevron-left\'></i>','<i class=\'icon-chevron-right icon-white\'></i>'],
    					margin:10,
					items:1,
    					responsiveClass:true,
					".$randomscript."
    					responsive:{
        					0:{
            					items:1,
						dots:".$dots.",
        					},
        					600:{
            					items:1,
            					nav:".$nav.",
	    					dots:".$dots.",
        					},
        					1000:{
            					items:".$maxitems.",
            					nav:".$nav.",
	    					dots:".$dots.",
            					loop:true
        					}
    					}
    				})
				});
			</script>";
		}
	}
	wp_reset_postdata();
	if (!$relatedObj  && ($relatedcat != "homead") && ($relatedcat != "home"))
		$shortcode_output = '<div class="span3 feature '.$relatedcat.'">'.$shortcode_output.'</span>';
	return $shortcode_output;
}
add_shortcode( 'isit_features2', 'isit_features2' );

/* ==================================================================
 *	Shortcode for features on Portal pages (by Category)
 * ================================================================== */
function isit_features ( $atts ) {
	$relatedObj = true;
	$current_post = get_queried_object();

	extract( shortcode_atts( array('showinline' => false,'servicepages' => false,'relatedcat' => ''), $atts));

	if ($relatedcat == ''){

		if ('service' == get_post_type()){  //on services page then 
			$connected_type = 'Services_to_Posts';
		}
		else
			$connected_type = 'Posts_to_Posts';

    	$relatedPostsPosts = new WP_Query( array(
  				'connected_type' => $connected_type,
				'connected_direction' => 'from',
				'connected_meta' => array(
							array(
								'key' => 'Connection Type',
								'value' => array('Featured','Featured In Service List'),
								'compare' => 'IN'
							)
						),
  				'connected_items' => get_queried_object_id()
		) );
    }
    else{
    	//get all posts within a category - given in parameter $relatedCategory
	$relatedObj = 0;
    	$taxargs = array(
			'post_type' => array( 'service','post' ),
			'orderby' => 'rand',
			'tax_query' => array( 
						array(
							'taxonomy' => 'category',
							'field'    => 'slug',
							'terms'    => $relatedcat,
						)
			),
		);
		$relatedPostsPosts = new WP_Query( $taxargs );
		$connected_type = 'Services_to_Posts';
    }

	if ($relatedPostsPosts){
		$postsposts = $relatedPostsPosts->posts;
		$numfeatures = count($postsposts);
		$shortcode_output .= '<div class="owl-carousel '.$connected_type.' flag'.$numfeatures.'">';
        	foreach ($postsposts as $postspost){

			if (empty($postspost->post_excerpt)){
				$blurb = wp_trim_words($postspost->post_content,18,'...');
			}		
			else{
				$blurb = wp_trim_words($postspost->post_excerpt,18,'...');
			}
			if ($numfeatures > 0){
				$shortcode_output .= 
			'<div class="shortcode-content">
				<a class="owl-item-link" href="'.get_permalink( $postspost->ID ).'">
					<div class="pix shadow '.$relatedObj.'">'.get_isit_thumb($postspost->ID,$relatedObj).'</div>
						<h3 class="owl-post-title">'.get_the_title($postspost->ID).'</h3>
				</a>
				<p class="blurb">'.$blurb.'</p>
			</div>';
			
			}

		}
		$shortcode_output .= '</div>';

		if ($numfeatures > 0){
			$randomscript = '';
			if (!$relatedObj)
				$randomscript = 'beforeInit : function(elem){random(elem);},';
			$automateHTML = ''; //change span from 9 to 6
			if (!$relatedObj)
				$automateHTML = '$(".span9").addClass("span6").removeClass("span9");';
			isit_p2p_enqueue_scripts();

			if ($numfeatures < 2) {
				$dots = "false";
				$nav = "false";
				$maxitems = 2;
			} elseif ($numfeatures <= 3) {
    				$maxitems = $numfeatures;
				$dots = "true";
				$nav = "false";
				$maxitems = 1;
			} elseif ($numfeatures > 3) {
				$dots = "true";
				$nav = "false";
    				$maxitems = 1;
			}
			$shortcode_output .= "<script>
				jQuery(document).ready(function($){
					".$automateHTML."
					$('.owl-carousel').owlCarousel({
    					loop:true,
					dotsEach:true,
					nav:false,
					navText:['<i class=\'icon-chevron-left\'></i>','<i class=\'icon-chevron-right icon-white\'></i>'],
    					margin:10,
					items:1,
    					responsiveClass:true,
					".$randomscript."
    					responsive:{
        					0:{
            					items:".$maxitems.",
						dots:".$dots.",
						loop:true
        					},
        					600:{
            					items:".$maxitems.",
            					nav:".$nav.",
	    					dots:".$dots.",
						loop:true
        					},
        					1000:{
            					items:".$maxitems.",
            					nav:".$nav.",
	    					dots:".$dots.",
            					loop:true
        					}
    					}
    				})
				});

			</script>";

		}
//#################################################
		//$shortcode_output .= '</div>';
	}
	wp_reset_postdata();
	if (!$relatedObj)
		$shortcode_output = '<div class="span3 feature">'.$shortcode_output.'</span>';
	return $shortcode_output;
}
add_shortcode( 'isit_features', 'isit_features' );
?>