<?php
/**
 *
 */
class News extends Page implements HiddenClass{
	
	private static $icon 			= 'irxnews/images/icons/newspage';
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
	//		'Date' => array('filter' => 'DateRangeFilter', 'date' => 'Date' ),
			'Author' => array('filter' => 'PartialMatchFilter', 'author' => 'Author' )
	);
	
	private static $summary_fields = array(
		"Title",
		"Status",
		"Date",
		"Author",
		"ListingSummary.CMSThumbnail"
	);
	
	private static $field_labels = array(
		"ListingSummary.CMSThumbnail" 	=> 'Image'
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
			}
		}
		
	}
	
	public function getCMSFields(){
		
		$fields = parent::getCMSFields();
		
		// If an image has not been set, open the toggle field to remind user
		//if($this->ListingImageID == 0){
		//	$toggle = $fields->fieldByName('Root.Main.ListingSummaryToggle');
		//	$toggle->setStartClosed(false);
		//}
		
		$fields->addFieldToTab('Root.Main', DropdownField::create('ParentID','News Holder?', NewsHolder::get()->map()->toArray()), 'ListingSummaryToggle');
		
		$fields->addFieldToTab("Root.Main", $date = new DateField("Date"),"ListingSummaryToggle");
		$date->setConfig('showcalendar', true);
		$date->setConfig('dateformat', 'dd/MM/YYYY');
		
		$fields->addFieldToTab('Root.Main', UploadField::create('ListingImage', 'Listing Image')
			->addExtraClass('withmargin')
			->setFolderName(Config::inst()->get('Upload', 'uploads_folder') . '/News')
		, 'ShowListingImageOnPage');
		
		$fields->addFieldToTab('Root.Main', TextField::create('Author','Author Name'), 'ListingSummaryToggle');
		
		$this->extend('IRXupdateNewsCMSFields', $fields);

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
	
	public function TagsFilter(){
		$tags = $this->Tags();
		if($tags && $tags->Count()){
			$Classes = array();
			foreach ($tags as $tag){
				$Classes[] = $tag->TagFilterName();
			}
				
			return implode(' ', $Classes);
		}
	
		return '';
	}
	
}

class News_Controller extends Page_Controller {
	
	public function init() {
		parent::init();
	}
	
	public function BackLink(){
		$request = $this->getRequest();
		$url 	 = false;
	
		if($request->requestVar('_REDIRECT_BACK_URL')) {
			$url = $request->requestVar('_REDIRECT_BACK_URL');
		} else if($request->getHeader('Referer')) {
			$url = $request->getHeader('Referer');
			//need to check the referer isnt the same page
			if($url == Director::absoluteURL($this->Link())){
				$url = false;
			}
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