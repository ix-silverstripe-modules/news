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
		'NewsPage'
	);
	
	private static $db = array(
		'PaginationLimit' 	=> 'Int',
		'NewsSource' 		=> 'enum("Children,All","Children")',
		'NoNewsText' 		=> 'HTMLText'	
	);
	
	private static $defaults = array(
		'PaginationLimit' => 20,
		'NoNewsText' => '<p>Sorry! There are no news articles to display.</p>'
	);
	
	private static $allowed_children = array(
		'NewsPage'
	);
	
	public function getCMSFields() {
		$fields = parent::getCMSFields();
		
		// Makes sure the Listing Summary Toggle is present before
		$configBefore = Config::inst()->get('News', 'news_fields_before');
		$configBefore = ($configBefore ? $configBefore : "Content");
		
		$putBefore = ($fields->fieldByName('Root.Main.ListingSummaryToggle') ? "ListingSummaryToggle" : $configBefore);

		$fields->addFieldToTab('Root.Main', NumericField::create('PaginationLimit', 'Pagination Limit'), $putBefore);
		$fields->addFieldToTab('Root.Main', DropdownField::create('NewsSource', 'News Source', $this->dbObject('NewsSource')->enumValues()), $putBefore);
		
		$fields->addFieldsToTab('Root.Main', HtmlEditorField::create('NoNewsText', 'No News Message')->setRows(2)->addExtraClass('withmargin'), $configBefore);
		
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
		}
		
		foreach($children as $c){
			if($c->ClassName == 'NewsPage'){
				$children->remove($c);
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
			$query->setSelect("$year tDate, \"SiteTree\".\"SubsiteID\"")->addFrom('"NewsPage"');
		} else {
			$query->setSelect("$year tDate")->addFrom('"NewsPage"');
		}
		$query->addLeftJoin("SiteTree", '"SiteTree"."ID" = "NewsPage"."ID"');
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
		
		$this->extend('updateNewsHolderMenuYears', $set);
	
		return $set;
	}
	
}

class NewsHolder_Controller extends Page_Controller {
	
	protected $start;
	protected $searchQuery;
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
			Requirements::javascript("news/javascript/news.js");
		}
		
		$request 			= $this->getRequest();
		$getParams 			= $request->getVars();
		$this->date 		= isset($getParams['date']) 		? $getParams['date'] 							: null;
		$this->searchQuery 	= isset($getParams['searchQuery']) 	? Convert::raw2sql($getParams['searchQuery']) 	: null;
		
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
			$this->response->addHeader("Vary", "Accept"); // This will enable pushState to work correctly
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
			'NoNewsText' => $this->NoNewsText ? $this->NoNewsText : "<p>Sorry! There are no news articles to display.</p>"
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
		$news = NewsPage::get()->sort('"Date" DESC')->where(DB::getConn()->formattedDatetimeClause('"Date"', '%Y') . " = $this->year" );
		return GroupedList::create($news);
	}
	
	public function News($overridePagination = null){
		
		if($overridePagination){
			$this->PaginationLimit = $overridePagination;
		}
		
		$paginationType = Config::inst()->get('News', 'pagination_type');
		
		$news = NewsPage::get();
		
		$toreturn = "";
		
		if($this->NewsSource == 'Children'){
			$news = $news->filter('ParentID', $this->ID);
		}
		
		if($this->date){
			$startAu = str_replace('/', '-', $this->date);
			$startAu = date('Y-m-d', strtotime($startAu));
			$news = $news->filter(array('Date:LessThanOrEqual' => $startAu));
		}
		
		if($this->searchQuery){
			$newsTable = 'SiteTree';
			if(Versioned::current_stage() == 'Live'){
				$newsTable .= '_Live';
			}
			$news = $news->where("\"$newsTable\".\"Title\" LIKE '%" . $this->searchQuery . "%' OR \"$newsTable\".\"Content\" LIKE '%" . $this->searchQuery . "%'");
		}
		
		if($paginationType == "ajax") {
			$startVar = $this->request->getVar("start");
			
			if($startVar && !Director::is_ajax()) { // Only apply this when the user is returning from the article OR if they were linked here
				$toload = ($startVar / $this->PaginationLimit); // What page are we at?
				$limit = (($toload + 1) * $this->PaginationLimit); // Need to add 1 so we always load the first page as well (articles 0 to 5)
				
				$list = $news->limit($limit, 0);
				$next = $limit;
			} else {
				$offset = $this->getOffset();
				$limit = $this->PaginationLimit;
				
				$list = $news->limit($limit, $offset);
				$next = $offset + $this->PaginationLimit;
			}
			
			$this->AllNewsCount = $news->count();
			$this->MoreNews 	= ($next < $this->AllNewsCount);
			$this->MoreLink 	= HTTP::setGetVar("start", $next);
			
			$toreturn = $list;
		} else {
			$this->AllNewsCount = $news->count();
			$toreturn = PaginatedList::create($news, $this->request)->setPageLength($this->PaginationLimit);
		}

		Session::set('NewsOffset'.$this->ID, $this->getOffset());

		return $toreturn;
	}
	
	public function DateField(){
		$dateField = DateField::create('date', 'Date');
		$dateField->setConfig('showcalendar', true);
		$dateField->setConfig('dateformat', 'dd/MM/YYYY');
		$dateField->setConfig('jQueryUI.changeMonth', true);
	
		if($this->date){
			$dateField->setValue($this->date);
		}else{
			$dateField->setValue(date('d/m/Y'));
		}
		
		$this->extend('updateDateField', $dateField);
	
		return $dateField;
	}
	
	public function searchQueryField(){
		$searchQuery = TextField::create('searchQuery', 'Search Query')
			->addExtraClass('search-news');
	
		if($this->searchQuery){
			$searchQuery->setValue($this->searchQuery);
		}
			
		$this->extend('updateSearchQueryField', $searchQuery);
	
		return $searchQuery;
	}
}