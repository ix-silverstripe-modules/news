News Module
=======================================

A module for adding news and news article pages to a site. Adds a NewsHolder and NewsArticle page type. Provides a page extension to allow News to show on other page types such as a HomePage.

## Glorious Maintainers

*  Stewart Wilson (<stewart.wilson@internetrix.com.au>)
*  Guy Watson (<guy.watson@internetrix.com.au>)

## Requirements

* SilverStripe 3.1.13 or above

## Dependencies

* [silverstripe-modules/VersionedModelAdmin](https://gitlab.internetrix.net/silverstripe-modules/versionedmodeladmin)
* [silverstripe-modules/listingsummary](https://gitlab.internetrix.net/silverstripe-modules/listingsummary)
* [micschk/silverstripe-excludechildren](https://github.com/micschk/silverstripe-excludechildren)
* jQuery (1.1.1 or newer)

## Notable Features

* Integrates with Listing Summary Module
* Enable and disable sharing capabilities
* Enable and disable archive page
* Enable and disable RSS
* Two types of pagination available
* AJAX Pagination implements HTML5 push state
* This great README file!

## Configuration

You can disable certain features in the config.yml of your site.

	News:
	  enable_sharing: true # adds share links for facebook, google+, twitter, pinterest, linkedin, email.
	  enable_archive: false # Archive at [newsholder]/archive/[year]/[optional month]
	  enable_rss: true # RSS page at [newsholder]/rss
	  pagination_type: static # or ajax
	  news_fields_before: 'Content' # To position the news fields in the CMS
	Page:
	  hide_sidebar: true # or an array of disallowed Page types (ClassNames)
	  extensions:
	    - NewsPageExtension

### Pagination types

For Ajax Pagination, you must set the config as below:

	pagination_type: ajax

For Static Pagination (ie, the next / prev buttons), you must set the config as below:

	pagination_type: static

Static pagination is contained in the Include file Pagination.ss and can be reused (or overridden) by any type of paginated list.

### Sidebar

When the NewsPageExtension is applied to Page, it allows any page to list latest news items. The module adds a "sidebar" tab when editing pages in the CMS to control the list. The tab can be disabled sitewide or on specified page types. To display the news, template code would look like this:

	<% if ShowLatestNews %>
		<% loop LatestNews %>
		...
		<% end_if %>
	<% end_if %> 

## Extensions

These extension points can be used to manipulate the module. 

* updateNewsHolderCMSFields - extends getCMSFields() on the NewsHolders.
* updateNewsHolderChildren - extends Children() for the list of available child pages. News pages are omitted.
* updateNewsCMSFields - extends getCMSFields() on the NewsPages.
* updateNewsHolderMenuYears - extends MenuYears() on the NewsHolder, to alter the Archive listing.

You can apply NewsPageExtension on pages you'd like to enable showing News Articles on other pages types.

