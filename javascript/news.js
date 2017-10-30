(function($) {
	$(function() {

		var fetching = false;
		
		$(document).on("click", 'a.show-more',function(e) {
			e.preventDefault();
			var me = $(this);
			
			if(!fetching){
				fetching = true;
				
				me.addClass('loading');
				$.ajax({
					url: me.attr('href'),
					success: function(data) {
						fetching = false;
						history.pushState(null, null, me.attr('href'));
						if( me.parent().hasClass('show-more') ) me.parent().remove();
						else me.remove();
						$('#news-container').append(data);
					}
				});
			}
			
		});
	});
	
	$('ul.share-buttons').on('click', 'a', function(e){
		window.open($(this).prop('href') + '&media=' + $('img.news-image').prop('src'),'sharer','width=640,height=480');
		return false;
	});

})(jQuery);