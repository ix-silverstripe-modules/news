<?php
/**
 *
 * Creates a NewsHolder page which contains each of the news articles
 *
 *
 * @author stewart.wilson@internetrix.com.au
 * @package news
 *
 **/
class NewsHolder extends Page {
	
	private static $icon = 'news/images/icons/newsholder';
	
	private static $extensions = array(
		"ExcludeChildren"
	);
	
	private static $excluded_children = array(
		'News'
	);
	
	private static $db = array(
		'PaginationLimit' 	=> 'Int',
		'NewsSource' 		=> 'enum("Children,All","Children")',
		'NoNewsText' 		=> 'Varchar(255)'	
	);
	
	private static $defaults = array(
		'PaginationLimit' => 20
	);
	
	private static $allowed_children = array(
		'News'
	);
	
	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldToTab('Root.Main', NumericField::create('PaginationLimit', 'Pagination Limit'), 'ListingSummaryToggle');
		$fields->addFieldToTab('Root.Main', DropdownField::create('NewsSource', 'News Source', $this->dbObject('NewsSource')->enumValues()), 'ListingSummaryToggle');
		
		$fields->addFieldsToTab('Root.Main', TextField::create('NoNewsText', 'No News Message'), 'Content');
		
		$this->extend('updateNewsHolderCMSFields', $fields);
		
		return $fields;
	}
	
	public function Children(){
		$children = parent::Children();

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
			
			
			
			foreach($children as $c){
				if($c->ClassName == 'News'){
					$children->remove($c);
				}
			}
		}
		
		$this->extend('updateNewsHolderChildren', $children);
		
		return $children;
	}
	
	public function onBeforeWrite(){
		parent::onBeforeWrite();
	
		if(!$this->PaginationLimit){
			$this->PaginationLimit = 20;
		}
	}
	
	public function MenuYears() {
		$set   = new ArrayList();
		$year  = DB::getConn()->formattedDatetimeClause('"Date"', '%Y');
	
		$query = new SQLQuery();
		
		// Modfiy select to add subsite in if it's installed
		if(class_exists('Subsite')) {
			$query->setSelect("$year tDate, \"SiteTree\".\"SubsiteID\"")->addFrom('"News"');
		} else {
			$query->setSelect("$year tDate")->addFrom('"News"');
		}
		$query->addLeftJoin("SiteTree", '"SiteTree"."ID" = "News"."ID"');
		$query->setGroupBy('"tDate"');
		$query->setOrderBy('"Date" DESC');
		if(class_exists('Subsite')) {
			$query->setWhere('"SiteTree"."SubsiteID" = ' . Subsite::currentSubsiteID());
		}
	
		$years = $query->execute()->column();
	
		if (!in_array(date('Y'), $years)) {
			array_unshift($years, date('Y'));
		}
		
		$selectedYear = Controller::curr()->getRequest()->param('ID');
	
		foreach ($years as $year) {
			$set->push(new ArrayData(array(
				'Title'    		=> $year,
				'MenuTitle'    	=> $year,
				'Link'    		=> $this->Link("archive/" . $year . "/"),
				'LinkingMode'	=> ($selectedYear && ($selectedYear == $year)) ? 'current' : 'section',
			)));
		}
	
		return $set;
	}
	
}

class NewsHolder_Controller extends Page_Controller {
	
	protected $year;
	
	public static $allowed_actions = array(
		'archive',
		'rss',
		'index'
	);
	
	public static $url_handlers = array(
		'archive/$Year'		=> 'archive',
		'archive'			=> 'archive',
		'rss'				=> 'rss',
		'' 					=> 'index'
	);
	
	public function init() {
		parent::init();
		
		if(Config::inst()->get('News', 'pagination_type') == "ajax") {
			Requirements::block("framework/thirdparty/jquery/jquery.js"); // block out thirdparty framework jquery
			Requirements::javascript("news/javascript/jquery.js"); // Make sure jQuery is included first
			Requirements::javascript("news/javascript/news.js");
		}
		
		RSSFeed::linkToFeed($this->Link("rss"), "Latest News feed");
		
	}
	
public function getOffset() {
		if(!isset($_REQUEST['start'])) {
			$_REQUEST['start'] = 0;
		}
		
		return $_REQUEST['start'];
	}
	
	public function index() {
		if(Director::is_ajax()) {
			$this->Ajax = true;
			return $this->renderWith('NewsList');
		}
		return array();
	}
	
	public function archive($request){
		if(!Config::inst()->get('News', 'enable_archive')) return $this->httpError(404);
		
		$year = (int) $request->param('Year');

		if($year){
			$this->year = $year;
		}else{
			$this->year = date('Y');
		}
		
		$page = new Page();
		$page->Title 	 	 = 'archive';
		$page->MenuTitle 	 = 'archive';
		$this->extracrumbs[] = $page;

		$data = array(
			'Title' 	=> $this->year . ' News Archive',
			'Content' 	=> '',
			'InArchive'	=> true,
			'NoNewsText' => $this->NoNewsText ? $this->NoNewsText : "Sorry, there is no news articles to show."
		);
	
		return $this->customise($data)->renderWith(array('NewsHolder_archive', 'NewsHolder', 'Page'));
	}
	
	public function rss() {
		if(!Config::inst()->get('News', 'enable_rss')) return $this->httpError(404);
		
		// Creates a new RSS Feed list
		$rss = new RSSFeed(
				$this->News(40), 
				$this->Link("rss"), 
				"Latest News feed"
		);
		// Outputs the RSS feed to the user.
		return $rss->outputToBrowser();
	}
	
	public function ArchiveNews(){
		$news = News::get()->sort('"Date" DESC')->where(DB::getConn()->formattedDatetimeClause('"Date"', '%Y') . " = $this->year" );
		return GroupedList::create($news);
	}
	
	public function News($overridePagination = null){
		
		if($overridePagination){
			$this->PaginationLimit = $overridePagination;
		}
		
		$news 				= News::get();
		
		if($this->NewsSource == 'Children'){
			$news = $news->filter('ParentID', $this->ID);
		}
		
		if(Config::inst()->get('News', 'pagination_type') == "ajax") {
			$all_news_count 	= $news->count();
			$list 				= $news->limit($this->PaginationLimit, $this->getOffset());	
			$next 				= $this->getOffset() + $this->PaginationLimit;
			$this->MoreNews 	= ($next < $all_news_count);
			$this->MoreLink 	= HTTP::setGetVar("start", $next);
			
			return $list;
		}
		
		if(Config::inst()->get('News', 'pagination_type') == "static") {
			return PaginatedList::create($news, $this->request)->setPageLength($this->PaginationLimit);
		}
	}
	
	public function Years() {
		$set   = new ArrayList();
		$year  = DB::getConn()->formattedDatetimeClause('"Date"', '%Y');
	
		$query = new SQLQuery();
		$query->setSelect("DISTINCT $year")->addFrom('"News"');
		$query->setOrderBy('"Date" DESC');
	
		$years = $query->execute()->column();
	
		if (!in_array(date('Y'), $years)) {
			array_unshift($years, date('Y'));
		}
	
		foreach ($years as $year) {
			$set->push(new ArrayData(array(
					'Year'    => $year,
					'Link'    => $this->Link("archive/" . $year . "/"),
					'Current' => $year == $this->year
			)));
		}
	
		return $set;
	}
}