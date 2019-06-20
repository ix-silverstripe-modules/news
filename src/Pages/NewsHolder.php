<?php
/**
 *
 * Creates a NewsHolder page which contains each of the news articles
 *
 **/

namespace Internetrix\News\Pages;

use Internetrix\News\Controllers\NewsHolderController;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\Subsites\Model\Subsite;
use Page;

class NewsHolder extends Page
{
	private static $icon            = 'internetrix/silverstripe-news:client/images/icons/newsholder-file.gif';

    private static $table_name      = 'IRX_NewsHolder';

    private static $description     = 'Page that lists all News Pages either from children or sitewide.';

    private static $singular_name   = 'News Holder';

    private static $plural_name     = 'News Holders';
	
	private static $db = [
		'PaginationLimit' 	=> 'Int',
		'NewsSource' 		=> 'Enum("Children,All","Children")',
		'NoNewsText' 		=> 'HTMLText'	
	];
	
	private static $defaults = [
		'PaginationLimit' => 20,
		'NoNewsText' => '<p>There aren\'t any news articles to display.</p>'
	];
	
	private static $allowed_children = [
		'NewsPage' => NewsPage::class
	];
	
	public function getCMSFields()
    {
		$fields = parent::getCMSFields();
		
		// Makes sure the Listing Summary Toggle is present before
		$configBefore = Config::inst()->get('News', 'news_fields_before');
		$configBefore = ($configBefore ? $configBefore : "Content");
		
		$putBefore = ($fields->fieldByName('Root.Main.ListingSummaryToggle') ? "ListingSummaryToggle" : $configBefore);

		$fields->addFieldToTab('Root.Main', NumericField::create('PaginationLimit', 'Pagination Limit'), $putBefore);

        $pages = NewsHolder::get();
        if ($pages && $pages->count() > 1) {
            $newsSource = $this->dbObject('NewsSource')->enumValues();
            $fields->addFieldToTab('Root.Main', DropdownField::create('NewsSource', 'News Source', $newsSource), 'Content');
        }

		$fields->addFieldToTab('Root.Main', HtmlEditorField::create('NoNewsText', 'No News Message')->setRows(2)->addExtraClass('withmargin'), $configBefore);
		
		$this->extend('updateNewsHolderCMSFields', $fields);
		
		return $fields;
	}
	
	public function Children()
    {
		$children = parent::Children();

		/* 
		* cms introduced requirement to check canView() on each child page, but we've injected a non-page (archive).
		*
		// Check to see if the archive feature is enabled
		if(Config::inst()->get('News', 'enable_archive')) {
			$request = Controller::curr()->getRequest();
			$action = $request->param('Action');
			$year = $request->param('ID');
			
			$children->unshift(ArrayData::create(array(
				'Title' 		=> 'Archive News',
				'MenuTitle' 	=> 'Archive News',
				'Link'			=> $this->Link('archive'),
				'LinkingMode' 	=> ($year) ? 'section' : 'current',
				'LinkOrSection'	=> $action == 'archive' ? 'section' : 'link',
				'Children'		=> $this->MenuYears()
			)));
		}
		*/
		
		foreach ($children as $c) {
			if ($c->ClassName == NewsPage::class) {
				$children->remove($c);
			}
		}
		
		$this->extend('updateNewsHolderChildren', $children);

		return $children;
	}
	
	public function onBeforeWrite()
    {
		parent::onBeforeWrite();
	
		if (!$this->PaginationLimit) {
			$this->PaginationLimit = 20;
		}
	}
	
	public function MenuYears( $useMonths = true, $showingYear = null )
    {
		$set   = new ArrayList();
		$year  = DB::getConn()->formattedDatetimeClause('"Date"', '%Y');
		if( $useMonths ) $year  = DB::getConn()->formattedDatetimeClause('"Date"', '%Y-%m');
		
		$query = new SQLSelect();
		
		// Modfiy select to add subsite in if it's installed
		if (class_exists('SilverStripe\Subsites\Model\Subsite')) {
			$query->setSelect("$year tDate, \"SiteTree\".\"SubsiteID\"")->addFrom('"NewsPage"');
		} else {
			$query->setSelect("$year tDate")->addFrom('"NewsPage"');
		}
		$query->addLeftJoin("SiteTree", '"SiteTree"."ID" = "NewsPage"."ID"');
		$query->setGroupBy('"tDate"');
		$query->setOrderBy('"Date" DESC');
		if (class_exists('SilverStripe\Subsites\Model\Subsite')) {
			$query->setWhere('"SiteTree"."SubsiteID" = ' . Subsite::currentSubsiteID());
		}
	
		$years = $query->execute()->column();
	
// 		if (!in_array(date('Y-m'), $years)) {
// 			array_unshift($years, date('Y-m'));
// 		}
		
		$selectedYear = Controller::curr()->getRequest()->param('ID');
	
		foreach ($years as $year) {
			$theYear = substr($year, 0, 4);
			$theMonth = substr($year, 5);

            $yearToCompare = $showingYear ? substr($showingYear, -4) : date("Y");

			if ($theYear == $yearToCompare && $useMonths) {
				$set->push(new ArrayData(array(
					'Title'    		=> date(" F ", mktime(0, 0, 0, $theMonth, 1, 2000)) . $theYear,
					'MenuTitle'    	=> date(" F ", mktime(0, 0, 0, $theMonth, 1, 2000)) . $theYear,
					'Link'    		=> $this->Link("archive/" . $theYear . "/" . $theMonth ),
					'LinkingMode'	=> ($selectedYear && ($selectedYear == $year)) ? 'current' : 'section',
				)));
			} else {
                $itemToPush = new ArrayData(array(
                    'Title'    		=> $theYear,
                    'MenuTitle'    	=> $theYear,
                    'Link'    		=> $this->Link("archive/" . $theYear ),
                    'LinkingMode'	=> ($selectedYear && ($selectedYear == $year)) ? 'current' : 'section',
                ));

                if (!$set->find('Title', $theYear))
                    $set->push($itemToPush);
			}
		}
		
		$this->extend('updateNewsHolderMenuYears', $set);
	
		return $set;
	}

    /**
     * @return string
     */
    public function getControllerName()
    {
        return NewsHolderController::class;
    }
}
