<% if $Image %><div style="background-image: url($Image.URL);" class="blog-image"></div><% end_if %>

<h1>$Title</h1>
Posted: $Date.Long

$Content

<!-- share/ prev next -->                        
<div class="social-share container">
    <div class="medium-8 columns">
        <h3>Share This Post</h3>
    </div>    
    <div class="medium-4 columns">
        <ul class="share-buttons">           
            <li>
                <a href="https://www.facebook.com/sharer/sharer.php?u=$AbsoluteLink.URLATT" target="_blank">
                    Facebook
                </a>
            </li>
            <li>
                <a href="https://twitter.com/intent/tweet?source=$BaseHref.URLATT&text=$Title.URLATT:$AbsoluteLink" target="_blank" title="Tweet">
                	Twitter
               </a>
            </li>
            <li>
                <a href="https://plus.google.com/share?url=$AbsoluteLink.URLATT" target="_blank" title="Share on Google+">
                    Google Plus
                </a>
            </li>
            <li>
                <a href="http://www.linkedin.com/shareArticle?mini=true&url=$AbsoluteLink.URLATT&summary=$Title.URLATT&source=$BaseHref.URLATT" target="_blank" title="Share on LinkedIn">
                    LinkedIn
                </a>
            </li>
        </ul>
    </div>
</div>

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