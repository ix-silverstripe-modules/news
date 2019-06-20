<?php

namespace Internetrix\News\Controllers;

use Internetrix\News\Pages\NewsPage;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DB;
use SilverStripe\View\Requirements;
use SilverStripe\Control\RSS\RSSFeed;
use SilverStripe\Core\Convert;
use SilverStripe\Control\Director;
use SilverStripe\ORM\GroupedList;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Control\HTTP;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\PaginatedList;
use Page;
use PageController;

class NewsHolderController extends PageController
{
    protected $start;
    protected $searchQuery;
    protected $year;
    protected $month;

    private static $allowed_actions = [
        'archive',
        'rss',
        'index'
    ];

    private static $url_handlers = [
        'archive/$Year/$Month' => 'archive',
        'archive/$Year' => 'archive',
        'archive' => 'archive',
        'rss' => 'rss',
        '' => 'index'
    ];

    public function init()
    {
        parent::init();

        //if(Config::inst()->get('News', 'pagination_type') == "ajax") {
        Requirements::javascript("vendor/internetrix/silverstripe-news/client/javascript/news.js");
        //}

        $request 			= $this->getRequest();
        $getParams 			= $request->getVars();
        $this->date 		= isset($getParams['date']) 		? $getParams['date'] 							: null;
        $this->searchQuery 	= isset($getParams['searchQuery']) 	? Convert::raw2sql($getParams['searchQuery']) 	: null;

        RSSFeed::linkToFeed($this->Link("rss"), "Latest News feed");
    }

    public function getOffset()
    {
        if (!isset($_REQUEST['start']) || $_REQUEST['start'] < 0 || $_REQUEST['start'] > 999 ) {
            $_REQUEST['start'] = 0;
        }

        return $_REQUEST['start'];
    }

    public function index()
    {
        if (Director::is_ajax()) {
            $this->Ajax = true;
            $this->response->addHeader("Vary", "Accept"); // This will enable pushState to work correctly
            return $this->renderWith('NewsList');
        }
        return array();
    }

    public function archive($request)
    {
        if (!Config::inst()->get('News', 'enable_archive')) return $this->httpError(404);

        $year = (int) $request->param('Year');
        $month = 0;

        if ($year && is_numeric($year) && $year < 2050 && $year > 2010) {
            $this->year = $year;
            $month = (int) $request->param('Month');
            if ($month && is_numeric($month) && $month <= 12 && $month > 0) {
                if ($month < 10)
                    $this->month = "0".$month;
                else
                    $this->month = "".$month;
            }
        } else {
            $this->year = date('Y');
            $year = $this->year;
        }

        $page = new Page();
        $page->Title 	 	 = $this->Title . ': ' . ( $month ? date(" F ", mktime(0, 0, 0, $month, 1, 2000) ) : '' ) . $year;
        $page->MenuTitle 	 = ( $month ? date(" F ", mktime(0, 0, 0, $month, 1, 2000) ) : '' ) . $year;
        $this->extracrumbs[] = $page;

        $data = array(
            'Title' 	=> $page->Title,
            'Content' 	=> '',
            'InArchive'	=> true,
            'NoNewsText' => $this->NoNewsText ? $this->NoNewsText : "<p>There aren't any news articles to display.</p>"
        );

        if(Director::is_ajax()) {
            $this->Ajax = true;
            $this->response->addHeader("Vary", "Accept"); // This will enable pushState to work correctly
            return $this->renderWith('NewsList');
        }

        return $this->customise($data)->renderWith(array('NewsHolder_archive', 'NewsHolder', 'Page'));
    }

    public function rss()
    {
        if (!Config::inst()->get('News', 'enable_rss')) return $this->httpError(404);

        // Creates a new RSS Feed list
        $rss = new RSSFeed(
            $this->News(40),
            $this->Link("rss"),
            "Latest News feed"
        );
        // Outputs the RSS feed to the user.
        return $rss->outputToBrowser();
    }

    /*
     * Obsolete. Doesn't respect parent newsholder or pagination.
     * */
    public function ArchiveNews($overridePagination = null)
    {
        $news = NewsPage::get()->sort('"Date" DESC')->where(DB::getConn()->formattedDatetimeClause('"Date"', '%Y') . " = $this->year" );
        return GroupedList::create($news);
    }

    public function News($overridePagination = null)
    {
        if ($overridePagination) {
            $this->PaginationLimit = $overridePagination;
        }

        $paginationType = Config::inst()->get('News', 'pagination_type');

        $news = NewsPage::get();

        $toreturn = "";

        if ($this->NewsSource == 'Children') {
            $news = $news->filter('ParentID', $this->ID);
        }

        if ($this->date) {
            $startAu = str_replace('/', '-', $this->date);
            $startAu = date('Y-m-d', strtotime($startAu));
            $news = $news->filter(array('Date:LessThanOrEqual' => $startAu));
        }

        if ($this->searchQuery) {
            $newsTable = 'SiteTree';
            if (Versioned::current_stage() == 'Live') {
                $newsTable .= '_Live';
            }
            $news = $news->where("\"$newsTable\".\"Title\" LIKE '%" . $this->searchQuery . "%' OR \"$newsTable\".\"Content\" LIKE '%" . $this->searchQuery . "%'");
        }

        $this->extend('updateNews', $news);


        if ($this->month) $news = $news->where(DB::getConn()->formattedDatetimeClause('"Date"', '%Y-%m') . " = '{$this->year}-{$this->month}'");
        elseif ($this->year) $news = $news->where(DB::getConn()->formattedDatetimeClause('"Date"', '%Y') . " = {$this->year}");


        if ($paginationType == "ajax") {
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

        $session = $this->getRequest()->getSession();
        $session->set('NewsOffset'.$this->ID, $this->getOffset());

        return $toreturn;
    }

    public function DateField()
    {
        $dateField = DateField::create('date', 'Date');
        $dateField->setConfig('showcalendar', true);
        $dateField->setConfig('dateformat', 'dd/MM/YYYY');
        $dateField->setConfig('jQueryUI.changeMonth', true);

        if ($this->date) {
            $dateField->setValue($this->date);
        } else {
            $dateField->setValue(date('d/m/Y'));
        }

        $this->extend('updateDateField', $dateField);

        return $dateField;
    }

    public function searchQueryField()
    {
        $searchQuery = TextField::create('searchQuery', 'Search Query')
            ->addExtraClass('search-news');

        if($this->searchQuery){
            $searchQuery->setValue($this->searchQuery);
        }

        $this->extend('updateSearchQueryField', $searchQuery);

        return $searchQuery;
    }
}
