<% loop News %>
	<div class="news-wrap">
		<% if LoadOneImage %>
			<img title="$Tite" alt="$Title" src="$LoadOneImage(160,96).URL">
		<% end_if %>
		
   		<div class="news-story">
        	<h2>$Title</h2>
        	<time datetime="$Date">$Date.format(j F Y)</time>
            <% if $ListingSummary %>$ListingSummary<% else %><p>$Content.Summary</p><% end_if %>
     
        	<div class="button-more">
            	<a href="$Link">Read More &raquo;</a>
            </div>
		</div>
     </div>   
<% end_loop %>

<% if MoreNews %>
	<div>
		<a href="$MoreLink" class="show-more">Show More...</a>
    </div>
<% end_if %>

<br />
<% with $News %>
    <% include Pagination %>
<% end_with %>