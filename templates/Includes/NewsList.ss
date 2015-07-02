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

<% if MoreNews %>
	<div>
		<a href="$MoreLink" class="show-more">Show More...</a>
    </div>
<% end_if %>

<br />
<% if $News.MoreThanOnePage %>
    <% if $News.NotFirstPage %>
        <a class="prev" href="$News.PrevLink">Prev</a>
    <% end_if %>
    <% loop $News.Pages %>
        <% if $CurrentBool %>
            $PageNum
        <% else %>
            <% if $Link %>
                <a href="$Link">$PageNum</a>
            <% else %>
                ...
            <% end_if %>
        <% end_if %>
        <% end_loop %>
    <% if $News.NotLastPage %>
        <a class="next" href="$News.NextLink">Next</a>
    <% end_if %>
<% end_if %>