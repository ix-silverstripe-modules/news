<?php
/**
 *
 * A ModelAdmin to the CMS to enable easy News Article edits
 *
 *
 * @author stewart.wilson@internetrix.com.au
 * @package news
 *
 **/

namespace Internetrix\News\Admin;

use Internetrix\VersionedModelAdmin\VersionedModelAdmin;
use Internetrix\News\Pages\NewsPage;

class NewsAdmin extends VersionedModelAdmin
{
	private static $menu_icon = 'internetrix/silverstripe-news:client/images/icons/news_icon.png';
	
	private static $title       = 'News';
	private static $menu_title  = 'News';
	private static $url_segment = 'news';

	private static $managed_models  = [
	    NewsPage::class
    ];

	private static $model_importers = [];
}
