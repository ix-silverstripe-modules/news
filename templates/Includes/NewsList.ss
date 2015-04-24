<% loop News %>
	<div class="news-wrap">
		<% if Image %>
			<img title="$Tite" alt="$Title" src="$Image.CroppedImage(680,241).URL">
		<% end_if %>
		
       	<div class="news-date">
        	<p>$Date.format(M)</p>
            <div id="news-day">
            <p>$Date.format(d)</p></div>
         </div>
        	
   		<div class="news-story">
        	<h1>$Title</h1>
            <h5>$Author</h5>
            $Content.Summary
     
        	<div class="button-more">
            	<a href="$Link">Read More &raquo;</a>
            </div>
		</div>
     </div>   
<% end_loop %>

<% if MoreEvents %>
	<div class="show-more">
		<a href="$MoreLink">Show More...</a>
    </div>
<% end_if %>