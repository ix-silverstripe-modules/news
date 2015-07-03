<?php
/**
 *
 * The News object which displays the news article
 *
 *
 * @author stewart.wilson@internetrix.com.au
 * @package news
 *
 **/
class News extends Page implements HiddenClass{
	
	private static $icon 			= 'news/images/icons/newspage';
	private static $default_sort 	= '"Date" DESC, "Created" DESC';
	
	private static $db = array(
		'Date' 				=> 'Date',
	    'Author' 			=> 'Text'
	);
	
	private static $defaults = array(
		'ShowListingImageOnPage' => true
	);
	
	private static $has_one = array(
		'Image'		=> 'Image'
	);
	
	private static $searchable_fields = array(
			'Title' => array('filter' => 'PartialMatchFilter', 'title' => 'Title' ),
			'Author' => array('filter' => 'PartialMatchFilter', 'author' => 'Author' )
	);
	
	private static $summary_fields = array(
		"Title",
		"Status",
		"Date",
		"Author",
		"Parent.Title"
//		"ListingSummary.CMSThumbnail"
	);
	
	private static $field_labels = array(
//		"ListingSummary.CMSThumbnail" 	=> 'Image'
		"Parent.Title"		=> "News Holder"
	);
	
	public function populateDefaults(){
		parent::populateDefaults();
		
		$this->setField('Date', date('Y-m-d', strtotime('now')));
		
		$member = Member::currentUser();
		$member = $member ? $member->getName() : "";
		
		$this->setField('Author', $member);
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
		
		$fields->addFieldToTab("Root.Main", $date = new DateField("Date"), $putBefore);
		$date->setConfig('showcalendar', true);
		$date->setConfig('dateformat', 'dd/MM/YYYY');
		
		$fields->addFieldToTab('Root.Main', UploadField::create('ListingImage', 'Listing Image')
			->addExtraClass('withmargin')
			->setFolderName(Config::inst()->get('Upload', 'uploads_folder') . '/News')
		, 'ShowListingImageOnPage');
		
		$fields->addFieldToTab('Root.Main', TextField::create('Author','Author Name'), $putBefore);
		
		$this->extend('updateNewsCMSFields', $fields);

		return $fields;
	}
	
	public function Status(){
		if($this->isNew()){
			return 'New Page';
		}elseif($this->isPublished()){
			return 'Published';
		}else{
			return 'Unpublished';
		}
	}
	
	public function getDateMonth() {
		return date('F', strtotime($this->Date));
	}
	
	public function NewsHolderTitle(){
		return $this->Parent()->MenuTitle;
	}
	
	public function NewsHolderLink(){
		return $this->Parent()->Link();
	}
	
	public function LoadOneImage($width = 900, $height = 320){
	
		$imageDO = $this->owner->ListingImage();
	
		if($imageDO && $imageDO->ID){
			$widthImage = $imageDO->SetWidth($width);
			return $widthImage ? $widthImage->forTemplate() : false;
		}else if($this->owner->Content){
			$dom = new DOMDocument();
			@$dom->loadHTML($this->owner->Content);
	
			$imgs = $dom->getElementsByTagName("img");
			$imageTag = false;
	
			foreach($imgs as $img){
				//get image src
				$src = $img->getAttribute('src');
	
				if($src){
					if(stripos($src, 'assets/') === 0 && ! file_exists(BASE_PATH . '/' . $src)){
						continue;
					}
	
					$imgTitle 	= $img->getAttribute('title');
					$imgALT 	= $img->getAttribute('alt');
					$imgWidth 	= $img->getAttribute('alt');
	
					$imageTag = "<img src=\"{$src}\" alt=\"{$imgALT}\" title=\"{$imgTitle}\"/>";
					break;
				}
			}
	
			return $imageTag;
		}
	}
	
}

class News_Controller extends Page_Controller {
	
	public function init() {
		parent::init();
	}
	
	public function ShareLinksEnabled() {
		return Config::inst()->get('News', 'enable_sharing');
	}
	
	public function BackLink(){
 		$url 	 = false;
 		$value = Session::get('NewsOffset'.$this->ParentID);
 		
 		if($value) {
 			// Get parent
 			$parent = $this->Parent;
 			$url = $parent->Link("?start=$value".'#'.$this->URLSegment);
 		}
	
		if(!$url){
			$page = $this->Parent();
			$url = $page ? $page->Link() : false;
		}
		return $url;
	}
	
	public function PrevNextPage($Mode = 'next') {
	
		if($Mode == 'next'){
			$Direction 			= "Date:GreaterThanOrEqual";
			$Sort 				= "Date ASC, Created ASC";
		}
		elseif($Mode == 'prev'){
			$Direction			= "Date:LessThanOrEqual";
			$Sort 				= "Date DESC, Created DESC";
		}
		else{
			return false;
		}
	
		$PrevNext = News::get()
			->filter(array(
					'ParentID'		  => $this->ParentID,
					$Direction 		  => $this->Date
			))
			->exclude('ID', $this->ID)
			->sort($Sort)
			->first() ;
	
		if ($PrevNext){
			return $PrevNext->Link();
		}
	}
	
}
