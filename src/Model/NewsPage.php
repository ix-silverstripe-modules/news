<?php
/**
 *
 * The News object which displays the news article
 *
 *
 * @author guy.watson@internetrix.com.au
 * @package news
 *
 **/

namespace Internetrix\News\Model;

use SilverStripe\Control\Controller;
use SilverStripe\Security\Member;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Forms\TimeField;
use SilverStripe\Forms\TextField;
use DOMDocument;
use SilverStripe\Assets\File;
use SilverStripe\View\Requirements;
use Page;
use PageController;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\DateField;

class NewsPage extends Page{

	private static $icon 			= 'internetrix/silverstripe-news:client/images/icons/newspage-file.gif';
	private static $default_sort 	= '"Date" DESC, "Created" DESC';

	private static $db = [
		'Date' 				=> 'Datetime',
		'NewsAuthor' 			=> 'Varchar(255)'
	];

	private static $defaults = [
		'ShowListingImageOnPage' => true
	];

	private static $searchable_fields = [
		'Title' => [
		    'filter'    => 'PartialMatchFilter',
            'title'     => 'Title',
            'field'     => TextField::class
        ],
		'NewsAuthor' => [
		    'filter'    => 'PartialMatchFilter',
            'title'     => 'Author',
            'field'     => TextField::class
        ]
	];

	private static $summary_fields = [
		"Title",
		"Status",
		"Date",
		"NewsAuthor" => "Author",
		"Parent.Title",
		"ListingImage.CMSThumbnail"
	];

	private static $field_labels = [
		"ListingImage.CMSThumbnail" 	=> 'Image',
		"Parent.Title"		=> "News Holder"
	];

	public function populateDefaults(){
		parent::populateDefaults();
		$this->setField('Date', date('Y-m-d H:i:s', strtotime('now')));
		if( !Controller::curr()->hasAction("build") ){
			$member = Member::currentUser();
			$member = $member ? $member->getName() : "";
			$this->setField('Author', $member);
		}
	}

	public function onBeforeWrite(){
		parent::onBeforeWrite();

		if(!$this->ParentID){
			$parent = NewsHolder::get()->First();
			if($parent){
				$this->setField('ParentID', $parent->ID);
			} else {
				$newParent = new NewsHolder();
				$newParent->Title 		= 'News';
				$newParent->URLSegment = 'news';
				$newParent->write();
				$newParent->publish('Stage', 'Live');

				$this->setField('ParentID', $newParent->ID);
			}
		}

	}

	public function getCMSFields(){

		$fields = parent::getCMSFields();

		// Makes sure the Listing Summary Toggle is present before 
		$configBefore = Config::inst()->get('News', 'news_fields_before');
		$configBefore = ($configBefore ? $configBefore : "Content");

		$putBefore = ($fields->fieldByName('Root.Main.ListingSummaryToggle') ? "ListingSummaryToggle" : $configBefore);

		// If an image has not been set, open the toggle field to remind user
		if(class_exists("ListingPage") && $putBefore == "ListingSummaryToggle") {
			if($this->ListingImageID == 0){
				$toggle = $fields->fieldByName('Root.Main.ListingSummaryToggle');
				$toggle->setStartClosed(false);
			}
		}

		$fields->addFieldToTab('Root.Main', DropdownField::create('ParentID','News Holder?', NewsHolder::get()->map()->toArray()), $putBefore);

		$fields->addFieldToTab("Root.Main", $date = DatetimeField::create("Date", "Date"), $putBefore);

		$fields->addFieldToTab('Root.Main', UploadField::create('ListingImage', 'Listing Image')
			->addExtraClass('withmargin')
			->setFolderName(Config::inst()->get('Upload', 'uploads_folder') . '/News')
			, 'ShowListingImageOnPage');

		$fields->addFieldToTab('Root.Main', TextField::create('NewsAuthor','Author Name'), $putBefore);

		$this->extend('updateNewsCMSFields', $fields);

		return $fields;
	}

	public function Status(){
		if( $this->isNew() ) return 'New Page';
		// getIsModifiedOnStage has been deprecated, need replacement to compare differences
		//elseif( $this->getIsModifiedOnStage() && $this->isPublished() ) return 'Modified';
		elseif( $this->isPublished() ) return 'Published';
		return 'Draft';
	}

	public function getDateMonth() {
		return date('F Y', strtotime($this->Date));
	}

	public function NewsHolderTitle(){
		return $this->Parent()->MenuTitle;
	}

	public function NewsHolderLink(){
		return $this->Parent()->Link();
	}

	public function LoadOneImage($width = 700, $height = 420){

		$imageDO = $this->ListingImage();
		$useListImage = $this->ShowListingImageOnPage;
		$imageTag = false;

		if($imageDO && $imageDO->ID && $useListImage){
			$imageTag = $imageDO->Fit($width, $height);
		}

		if( !$imageTag && $this->Content ){
			$dom = new DOMDocument();
			@$dom->loadHTML($this->Content);

			$imgs = $dom->getElementsByTagName("img");

			foreach($imgs as $img){
				//get image src
				$src = $img->getAttribute('src');

				if($src){
					if(stripos($src, 'assets/') === 0 && ! file_exists(BASE_PATH . '/' . $src)){
						continue;
					}

					$imgTag = File::find($src);
					if( $imgTag )
						break;
				}
			}

		}
		return $imageTag;
	}

}

class NewsPageController extends PageController {

	public function init() {
		parent::init();
		Requirements::javascript("vendor/internetrix/silverstripe-news/client/javascript/news.js");
	}

	public function ShareLinksEnabled() {
		return Config::inst()->get('News', 'enable_sharing');
	}

	public function BackLink(){
		$url 	 = false;
		//$value = Session::get('NewsOffset'.$this->ParentID);
        $session = $this->getRequest()->getSession();
        $value = $session->get('NewsOffset'.$this->ParentID);

		if($value) {
			// Get parent
			$parent = $this->Parent;
			$url = $parent->Link("?start=$value".'#'.$this->URLSegment);
		}

		if(!$url){
			$page = $this->Parent();
			$url = $page ? $page->Link('#'.$this->URLSegment) : false;
		}

		return $url;
	}

	public function PrevNextPage($Mode = 'next') {

		$myID = false;
		
		$PrevNext = NewsPage::get()
			->filter(array(
				'ParentID'		  => $this->ParentID,
			))
			->sort("Date DESC, Created DESC, ID DESC")
			->map('ID','Date')->toArray();
		
		if( isset($PrevNext[$this->ID]) ){
			$keys = array_keys($PrevNext);
			$position = array_search($this->ID, $keys);
			if( $Mode == 'prev' && isset($keys[$position - 1]) ){
				$myID = $keys[$position - 1];
			} elseif( $Mode == 'next' && isset($keys[$position + 1]) ){
				$myID = $keys[$position + 1];
			}
		}
		
		if( $myID && $page = NewsPage::get()->ByID($myID) ){
			return $page->Link();
		}
		return false;
	}

}