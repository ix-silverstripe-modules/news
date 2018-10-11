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

namespace Internetrix\News;

use Internetrix\VersionedModelAdmin\VersionedModelAdmin;

class NewsAdmin extends VersionedModelAdmin {
	
	private static $menu_icon = 'vendor/internetrix/silverstripe-news/images/icons/news_icon.png';
	
	private static $title       = 'News';
	private static $menu_title  = 'News';
	private static $url_segment = 'news';

	private static $managed_models  = [
	    'NewsPage' => NewsPage::class
    ];

	private static $model_importers = [];
	
}

