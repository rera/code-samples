(function( $ ){
    var methods = {
        init : function(options) {
        	var search = $('<input type="text" name="search"' + ( (options.hasOwnProperty('searchclass') && typeof options.searchclass === 'string') ? ' class="'+options.searchclass+'"' : '' ) + ' placeholder="Search" value="" />');
			$(search).insertBefore(this);
			$(search).change(function() {
				$(this).next().find('li').css("display", "none").each(function() {
					if($(this).text().toLowerCase().indexOf($(search).val().toLowerCase()) >= 0) {
						$(this).css("display", "list-item");
					}
				});
			}).keyup(function() {
				$(this).change();
			});	
        }
    };

    $.fn.listSearch = function(methodOrOptions) {
        if ( methods[methodOrOptions] ) {
            return methods[ methodOrOptions ].apply( this, Array.prototype.slice.call( arguments, 1 ));
        } else if ( typeof methodOrOptions === 'object' || ! methodOrOptions ) {
            // Default to "init"
            return methods.init.apply( this, arguments );
        } else {
            $.error( 'Method ' +  methodOrOptions + ' does not exist on jQuery.listSearch' );
        }    
    };
})( jQuery );
