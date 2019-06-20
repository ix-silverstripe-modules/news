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

namespace Internetrix\News\Pages;

use Internetrix\News\Controllers\NewsPageController;
use SilverStripe\Assets\Upload;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Forms\TextField;
use DOMDocument;
use SilverStripe\Assets\File;
use Page;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Security\Security;

class NewsPage extends Page
{
    private static $icon 			= 'internetrix/silverstripe-news:client/images/icons/newspage-file.gif';

    private static $description = 'Page that displays a single news article.';

    private static $table_name  = 'IRX_NewsPage';

    private static $singular_name = 'News Page';

    private static $plural_name = 'News Pages';

    private static $default_sort 	= '"Date" DESC, "Created" DESC';

    private static $show_in_sitetree = false;

    private static $allowed_children = [];

    private static $db = [
        'Date' 				=> 'Datetime',
        'NewsAuthor' 		=> 'Varchar(255)'
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
        "Date",
        "NewsAuthor" => "Author",
        "Parent.Title",
        "ListingImage.CMSThumbnail"
    ];

    private static $field_labels = [
        "ListingImage.CMSThumbnail" 	=> 'Image',
        "Parent.Title"		=> "News Holder"
    ];

    public function populateDefaults()
    {
        parent::populateDefaults();
        $this->setField('Date', date('Y-m-d H:i:s', strtotime('now')));
        if (!Controller::curr()->hasAction("build")){
            $member = Security::getCurrentUser();
            $member = $member ? $member->getName() : "";
            $this->setField('Author', $member);
        }
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (!$this->ParentID) {
            $parent = NewsHolder::get()->First();
            if ($parent) {
                $this->setField('ParentID', $parent->ID);
            } else {
                $newParent = new NewsHolder();
                $newParent->Title = 'News';
                $newParent->URLSegment = 'news';
                $newParent->write();
                $newParent->copyVersionToStage('Stage', 'Live');

                $this->setField('ParentID', $newParent->ID);
            }
        }
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        // Makes sure the Listing Summary Toggle is present before
        $configBefore = Config::inst()->get('News', 'news_fields_before');
        $configBefore = ($configBefore ? $configBefore : "Content");

        $putBefore = ($fields->fieldByName('Root.Main.ListingSummaryToggle') ? "ListingSummaryToggle" : $configBefore);

        // If an image has not been set, open the toggle field to remind user
        if (class_exists("Internetrix\ListingSummary\Model\ListingPage") && $putBefore == "ListingSummaryToggle") {
            if ($this->ListingImageID == 0) {
                $toggle = $fields->fieldByName('Root.Main.ListingSummaryToggle');
                $toggle->setStartClosed(false);
            }
        }

        $fields->addFieldToTab('Root.Main', DropdownField::create('ParentID','News Holder', NewsHolder::get()->map()->toArray()), $putBefore);

        $fields->addFieldToTab("Root.Main", $date = DatetimeField::create("Date", "Date"), $putBefore);

        $fields->addFieldToTab('Root.Main', UploadField::create('ListingImage', 'Listing Image')
            ->addExtraClass('withmargin')
            ->setFolderName(Config::inst()->get(Upload::class, 'uploads_folder') . '/News')
            , 'ShowListingImageOnPage');

        $fields->addFieldToTab('Root.Main', TextField::create('NewsAuthor','Author Name'), $putBefore);

        $this->extend('updateNewsCMSFields', $fields);

        return $fields;
    }

    public function getDateMonth()
    {
        return date('F Y', strtotime($this->Date));
    }

    public function NewsHolderTitle()
    {
        return $this->Parent()->MenuTitle;
    }

    public function NewsHolderLink()
    {
        return $this->Parent()->Link();
    }

    public function LoadOneImage($width = 700, $height = 420)
    {
        $imageDO = $this->ListingImage();
        $useListImage = $this->ShowListingImageOnPage;
        $imageTag = false;

        if ($imageDO && $imageDO->ID && $useListImage) {
            $imageTag = $imageDO->Fit($width, $height);
        }

        if (!$imageTag && $this->Content) {
            $dom = new DOMDocument();
            @$dom->loadHTML($this->Content);

            $imgs = $dom->getElementsByTagName("img");

            foreach ($imgs as $img) {
                //get image src
                $src = $img->getAttribute('src');

                if ($src) {
                    if (stripos($src, 'assets/') === 0 && ! file_exists(BASE_PATH . '/' . $src)) {
                        continue;
                    }

                    $imgTag = File::find($src);
                    if ($imgTag)
                        break;
                }
            }

        }
        return $imageTag;
    }

    /**
     * @return string
     */
    public function getControllerName()
    {
        return NewsPageController::class;
    }
}
