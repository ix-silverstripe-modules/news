<?php

namespace Internetrix\News\Model;

use Internetrix\News\Pages\NewsPage;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\Parsers\URLSegmentFilter;
use TractorCow\Colorpicker\Forms\ColorField;

class NewsCategory extends DataObject
{
	private static $default_sort = "Sort";

    private static $table_name = 'IRX_NewsCategory';

	private static $db = [
		'Title' 		=> 'Varchar(100)',
		'URLSegment' 	=> 'Varchar(255)',
		'Colour' 	    => 'Varchar(255)',
		'Sort'			=> 'Int'
	];
	
	private static $many_many = [
		'News' => NewsPage::class
	];
	
	private static $searchable_fields = [
		'Title'
	];

	private static $summary_fields = [
		'ColourBlock' 	=> 'Colour',
		'Title'	 		=> 'Title',
		'NumberOfNews'	=> 'Number of News'
	];

    private static $casting = [
        'ColourBlock'   => 'HTMLText'
    ];
	
	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->removeByName('Sort');
		$fields->removeByName('URLSegment');
		$fields->removeByName('News');

		$fields->addFieldToTab('Root.Main', ColorField::create('Colour', 'Colour'));
		
		return $fields;
	}
	
	public function onBeforeWrite()
    {
		parent::onBeforeWrite();
		
		if ($this->isChanged('Title')) {
			$filter	 			= URLSegmentFilter::create();
			$this->URLSegment 	= $filter->filter($this->Title);
			
			// Ensure that this object has a non-conflicting URLSegment value.
			$count = 2;
			while (!$this->validURLSegment()) {
				$this->URLSegment = preg_replace('/-[0-9]+$/', null, $this->URLSegment) . '-' . $count;
				$count++;
			}
		}
	}
	
	public function validURLSegment()
    {
		$segment 		  = Convert::raw2sql($this->URLSegment);
		$existingCategory = NewsCategory::get()->filter('URLSegment', $segment)->first();
	
		return !($existingCategory);
	}
	
	public function NumberOfNews()
    {
		return $this->News()->Count();
	}
	
	public function ColourBlock()
    {
		$html = new DBHTMLText();
		$html->setValue("<div style='width: 20px; height: 20px; background-color: #" . $this->Colour . ";'></div>");
		return $html;
	}
	
	public function IsChecked()
    {
		$request 	= Controller::curr()->getRequest();
		$types 		= $request->getVar('types');
		
		if (stripos($types, $this->URLSegment) !== false) {
			return true;
		}
	}
}
