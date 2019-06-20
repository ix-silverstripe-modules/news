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

namespace Internetrix\News\Extensions;

use Internetrix\News\Pages\NewsHolder;
use Internetrix\News\Pages\NewsPage;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Core\Config\Config;

class NewsPageExtension extends DataExtension
{
	private static $db = [
		'ShowLatestNews' 	=> 'Boolean',
		'LatestNewsCount' 	=> 'Int'
	];

	private static $defaults = [
		'ShowLatestNews' 	=> true,
		'LatestNewsCount' 	=> 4
	];

	public function IRXupdateCMSFields(FieldList &$fields)
    {
		$hide_sidebar = Config::inst()->get('Page', 'hide_sidebar');
		if (!$hide_sidebar || (!is_bool($hide_sidebar) && !in_array(get_class($this->owner), $hide_sidebar))) {
				$tab = 'Root.SideBar';
				$insertBefore = '';
				$fields->addFieldToTab($tab, HeaderField::create('NewsOptions', 'News Options'), $insertBefore);
				$fields->addFieldToTab($tab, CheckboxField::create('ShowLatestNews', 'Show the latest news items?'), $insertBefore);
				$fields->addFieldToTab($tab, NumericField::create('LatestNewsCount', 'How many news items?')
					->displayIf('ShowLatestNews')->isChecked()->end(), $insertBefore);
		}
		return $fields;
	}

	public function LatestNews()
    {
		$limit = $this->owner->LatestNewsCount < 0 || $this->owner->LatestNewsCount > 99 ? $this->owner->LatestNewsCount : 4;
		return NewsPage::get()->sort('Date', 'DESC')->limit($limit);
	}

	public function ViewAllNewsLink()
    {
		$result = false;
		$newsPage = NewsHolder::get();
		if ($newsPage->Count() == 1)
			$result = $newsPage->first()->Link();
		elseif ($newsPage->Count() > 1 ) {
			$list = $newsPage->filter('NewsSource','All');
			if ($list->Count() >= 1)
				$result = $list->first()->Link();
		}
		return $result;
	}

	public function onBeforeWrite()
    {
		parent::onBeforeWrite();

		if ($this->owner->LatestNewsCount < 0 || $this->owner->LatestNewsCount > 99) {
			$this->owner->LatestNewsCount = 4;
		}
	}
}
