<?php
/**
 *
 * Provides an extension to other pages to show news articles
 *
 *
 * @author stewart.wilson@internetrix.com.au
 * @package news
 *
 **/
class NewsPageExtension extends DataExtension {
	
	private static $db = array(
		'ShowLatestNews' 	=> 'Boolean',
		'LatestNewsCount' 	=> 'Int'
	);
	
	private static $defaults = array(
		'ShowLatestNews' 	=> true,
		'LatestNewsCount' 	=> 4
	);
	
	public function IRXupdateCMSFields(FieldList &$fields) {
		$hide_sidebar = Config::inst()->get('Page', 'hide_sidebar');
		if(!$hide_sidebar || ($hide_sidebar && !in_array(get_class($this->owner), $hide_sidebar))){
			
				$fields->addFieldToTab('Root.SideBar', HeaderField::create('NewsOptions', 'News Options'), 'PageBannersHeading');
				$fields->addFieldToTab('Root.SideBar', CheckboxField::create('ShowLatestNews', 'Show the latest news?'), 'PageBannersHeading');
				$fields->addFieldToTab('Root.SideBar', NumericField::create('LatestNewsCount', 'How many news articles to show in the sidebar?')
					->displayIf('ShowLatestNews')->isChecked()->end(), 'PageBannersHeading');
		}
		return $fields;
	}
	
	public function LatestNews(){
		$limit 	 = $this->owner->LatestNewsCount ? $this->owner->LatestNewsCount : 3;
		return NewsPage::get()->sort('Date', 'DESC')->limit($limit);
	}
	
	public function ViewAllNewsLink(){
		$newsPage = NewsHolder::get()->first();
		return $newsPage ? $newsPage->Link() : false;
	}
	
	public function onBeforeWrite(){
		parent::onBeforeWrite();
		
		if(!$this->owner->LatestNewsCount){
			$this->owner->LatestNewsCount = 4;
		}
	}
}


