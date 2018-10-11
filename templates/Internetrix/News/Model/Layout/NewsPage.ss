<% if $LoadOneImage %><img src="$LoadOneImage(700,420).URL" class="news-image"><% end_if %>

<h1>$Title</h1>
Posted: $Date.Long

$Content

<% if $ShareLinksEnabled %>
<!-- share/ prev next -->                        
<div class="social-share container">
    <div class="medium-8 columns">
        <h3>Share This Post</h3>
    </div>    
    <div class="medium-4 columns">
        <% include Internetrix\\News\\Sharing %>
    </div>
</div>
<% end_if %>

<div class="news-nav container">
    <div class="medium-4 small-6 column">
        <% if $PrevNextPage(prev) %><a href="$PrevNextPage(prev)"><i class="fa fa-chevron-left"></i>  Previous</a><% else %>&nbsp;<% end_if %>
    </div>
    <div class="medium-4 small-6 column medium-push-4 text-right">
        <% if $PrevNextPage %><a href="$PrevNextPage">Next  <i class="fa fa-chevron-right"></i></a><% else %>&nbsp;<% end_if %>
    </div>         
    <div class="medium-4 small-12 column medium-pull-4 text-center">
        <a href="$BackLink">Back to Listing</a>
    </div>                               
</div>   