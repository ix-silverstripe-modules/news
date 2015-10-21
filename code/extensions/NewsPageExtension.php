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
				$tab = 'Root.SideBar';
		        $insertBefore = '';
				$fields->addFieldToTab($tab, HeaderField::create('NewsOptions', 'News Options'), $insertBefore);
				$fields->addFieldToTab($tab, CheckboxField::create('ShowLatestNews', 'Show the latest news items?'), $insertBefore);
				$fields->addFieldToTab($tab, NumericField::create('LatestNewsCount', 'How many news items?')
					->displayIf('ShowLatestNews')->isChecked()->end(), $insertBefore);
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