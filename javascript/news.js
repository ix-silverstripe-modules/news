(function($) {
	$(function() {

		var fetching = false;
		
		$(document).on("click", '.show-more',function(e) {
			e.preventDefault();
			var me = $(this);
			
			if(!fetching){
				fetching = true;
				
				me.addClass('loading');
				$.ajax({
					url: me.attr('href'),
					success: function(data) {
						me.remove();
						$('#news-container').append(data);
						fetching = false;
						history.pushState(null, null, me.attr('href'));
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