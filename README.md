News Module
=======================================

An extension for adding news and news article pages to a site. Adds a NewsHolder and NewsArticle page type.

Provides two extensions for HomePage and Page to allow news articles to be displayed on the HomePage or in the sidebar of a page.

Maintainers
------------------
*  Stewart Wilson (<stewart.wilson@internetrix.com.au>)

## Requirements

* SilverStripe 3.1.10 or above
* jQuery (if using Ajax Pagination)

## Dependencies

[silverstripe-modules/VersionedModelAdmin](https://gitlab.internetrix.net/silverstripe-modules/versionedmodeladmin)
[micschk/silverstripe-excludechildren](https://github.com/micschk/silverstripe-excludechildren)

## Configuration

You can disable certain features in the config.yml of your site.

	News:
	  enable_sharing: true
	  enable_archive: true
	  pagination_type: ajax

### Ajax Pagination Setup

For Ajax Pagination, you must set the config as below:

	pagination_type: ajax
	
Additionally, your news articles must be contained within a div and your more articles link/button must have a certain class

	<div id="news-container">
	<% include NewsList %>
	</div>
	
	<% if MoreNews %>
	<div class="show-more">
		<a href="$MoreLink">Show More...</a>
    </div>
	<% end_if %>

