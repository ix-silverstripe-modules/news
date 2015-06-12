<?php
class NewsAdmin extends VersionedModelAdmin {
	
	private static $menu_icon = 'news/images/icons/news_icon.png';
	
	private static $title       = 'News';
	private static $menu_title  = 'News';
	private static $url_segment = 'news';

	private static $managed_models  = 'News';
	private static $model_importers = array();
	
}
