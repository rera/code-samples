<?php  
	/* Template Name: Portfolio */  
	get_header();  
	
	// create empty master array 
	$platforms = array();
	// loop through all taxonomy platforms
	foreach(get_terms('platform', 'orderby=name&order=desc&hide_empty=1') as $p) {
		// query platform posts
		$args = array(
			'platform' => $p->name,
			'post_type' => 'portfolio',
			'post_status' => 'publish',
			'posts_per_page' => 18
		);
		$query = new WP_Query($args);
		// create temp posts array
		$posts = array();
		// loop posts
		if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post();
			// add to temp posts array
			$posts[] = array(
				'title'		=>	str_ireplace('"', '', trim(get_the_title())),
				'content'	=>	str_ireplace('"', '', trim(get_the_content())),
				'types'		=>	wp_get_post_terms( $post->ID, 'project-type', array("fields" => "names") ),
				'thumbnail'	=>	get_the_post_thumbnail(),
				'thumburl'	=>	portfolio_thumbnail_url($post->ID),
				'site'		=>	get_post_custom_values('projLink')
			);
		endwhile; endif;
		// add posts array to post collection in platform array
		$platforms[$p->name] = $posts;
	}	
?> 

<?php

	// BUILD PAGE //
	
	$html = array();
	
	// create tabs ul
	$html[] = '<ul id="platforms" class="nav nav-tabs">';
	// create tab links for each platform
	$k = 0;
	foreach($platforms as $platform=>$posts) {
		$temp = array(
			'<li',
			( ($k++ == 0) ? ' class="active"' : '' ),
			'><a href="#',
			$platform,
			'" data-toggle="tab">',
			$platform,
			'</a></li>'
		);
		$html[] = implode('',$temp);
	}
	$html[] = '</ul>';
	
	
	// create content divs
	$html[] = '<div class="tab-content">';
	//create tab pane for each platform
	$j = 0;
	foreach($platforms as $platform=>$posts) {
		$temp = array(
			'<div class="tab-pane',
			( ($j++ == 0) ? ' active' : '' ),
			'" id="',
			$platform,
			'">'
		);
		$html[] = implode('',$temp);
		
		// loop through all posts in platform
		$i = 0;
		foreach($posts as $post) {
			// if new row, add ul container
			if($i++ % 3 == 0)
				$html[] = '<ul class="row-fluid thumbnails">';
				
			// build li for post	
			$temp = array(
				'<li class="span4">',
				'<div class="thumbnail"> ',
				'<a title="',
				$post['title'],
				'" class="thickbox" rel="portfolio" data-terms="',
				join(', ',$post['types']),
				'" href="',
				$post['thumburl'],
				'">',
				$post['thumbnail'],
				'</a>'
			);
			$html[] = implode('',$temp);
			
			// add link button to url in custom field
			if($post['site'][0] != "") {	
				$temp = array(
					'<a href="',
					$post['site'][0],
					'" target="_blank" class="btn btn-block btn-info">Visit the Site</a>'
				);
				$html[] = implode('',$temp);
			}
			else 
				$html[] = '<a href="#" class="btn btn-block btn-info disabled">No Link</a>';
				
			$html[] = '</div>';
			$html[] = '</li>';
			
			// if new row, terminate ul container
			if($i % 3 == 0)
				$html[] = '</ul>';
		}
		$html[] = '</div>';
	}
	$html[] = '</div>';
	
	echo implode("\n",$html);
?>

<?php get_footer(); ?>  