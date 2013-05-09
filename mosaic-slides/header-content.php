<!-- include Cycle plugin -->
<script type="text/javascript" src="http://cloud.github.com/downloads/malsup/cycle/jquery.cycle.all.latest.js"></script>
<script type="text/javascript">
$(document).ready(function() {
	$('.cycle').each( function(){
	    $(this).cycle({
			fx:     'fade', 
			speed:   1500, 
			timeout: Math.floor((Math.random()*10)+1) * 1000,  
			next:   $(this), 
			pause:   1 
		});
	});
});
</script>
<div id="slide-container">
	<div class="left cycle">
		<img src="<?php bloginfo('stylesheet_directory'); ?>/images/photos/Waterfront_Pano_01_v31410_24w_300dpi.jpg" />
		<img src="<?php bloginfo('stylesheet_directory'); ?>/images/photos/_F1X6096.jpg" />
		<img src="<?php bloginfo('stylesheet_directory'); ?>/images/photos/DSC_0044.jpg" />
		<img src="<?php bloginfo('stylesheet_directory'); ?>/images/photos/IMG_0076.jpg" />
		<img src="<?php bloginfo('stylesheet_directory'); ?>/images/photos/Mat-Kearney-with-Crowd-Back.jpg" />

	</div>
	<div class="right">
		<div class="top">
			<div class="left cycle">
				<img src="<?php bloginfo('stylesheet_directory'); ?>/images/photos/CbNsuperbowl_Mickey_8825.jpg" />
				<img src="<?php bloginfo('stylesheet_directory'); ?>/images/photos/_MG_6440fourthflagler2009.jpg" />
      	        <img src="<?php bloginfo('stylesheet_directory'); ?>/images/photos/CbNsuperbowl_Mickey_8801.jpg" />
				<img src="<?php bloginfo('stylesheet_directory'); ?>/images/photos/_77W2525fourthflagler2009.jpg" />
			</div>
			<div class="right cycle">
				<img src="<?php bloginfo('stylesheet_directory'); ?>/images/photos/_F1X6021.jpg" />
				<img src="<?php bloginfo('stylesheet_directory'); ?>/images/photos/PC215693.jpg" />
				<img src="<?php bloginfo('stylesheet_directory'); ?>/images/photos/DSC_0120.jpg" />
                <img src="<?php bloginfo('stylesheet_directory'); ?>/images/photos/_M1C6305.jpg" />
			</div>
		</div>
		<div class="bottom cycle">
		        <img src="<?php bloginfo('stylesheet_directory'); ?>/images/photos/CbNsuperbowl.jpg" />
				<img src="<?php bloginfo('stylesheet_directory'); ?>/images/photos/_MG_6522fourthflagler2009.jpg" />
                <img src="<?php bloginfo('stylesheet_directory'); ?>/images/photos/CbNsuperbowl_2570.jpg" />
				<img src="<?php bloginfo('stylesheet_directory'); ?>/images/photos/_F1X5978.jpg" />
				<img src="<?php bloginfo('stylesheet_directory'); ?>/images/photos/_F1X5993.jpg" />
		</div>
	</div>
</div>
<div id="logo"></div>