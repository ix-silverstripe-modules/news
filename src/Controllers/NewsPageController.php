<?php

namespace Internetrix\News\Controllers;

use Internetrix\News\Pages\NewsPage;
use PageController;
use SilverStripe\Core\Config\Config;
use SilverStripe\View\Requirements;

class NewsPageController extends PageController
{
    public function init()
    {
        parent::init();
        Requirements::javascript("vendor/internetrix/silverstripe-news/client/javascript/news.js");
    }

    public function ShareLinksEnabled()
    {
        return Config::inst()->get('Internetrix\News', 'enable_sharing');
    }

    public function BackLink()
    {
        $url 	 = false;
        //$value = Session::get('NewsOffset'.$this->ParentID);
        $session = $this->getRequest()->getSession();
        $value = $session->get('NewsOffset'.$this->ParentID);

        if ($value) {
            // Get parent
            $parent = $this->Parent;
            $url = $parent->Link("?start=$value".'#'.$this->URLSegment);
        }

        if (!$url) {
            $page = $this->Parent();
            $url = $page ? $page->Link('#'.$this->URLSegment) : false;
        }

        return $url;
    }

    public function PrevNextPage($Mode = 'next')
    {
        $myID = false;

        $PrevNext = NewsPage::get()
            ->filter(array(
                'ParentID'		  => $this->ParentID,
            ))
            ->sort("Date DESC, Created DESC, ID DESC")
            ->map('ID','Date')->toArray();

        if (isset($PrevNext[$this->ID])) {
            $keys = array_keys($PrevNext);
            $position = array_search($this->ID, $keys);
            if ($Mode == 'prev' && isset($keys[$position - 1])) {
                $myID = $keys[$position - 1];
            } elseif ($Mode == 'next' && isset($keys[$position + 1])) {
                $myID = $keys[$position + 1];
            }
        }

        if ($myID && $page = NewsPage::get()->ByID($myID)) {
            return $page->Link();
        }
        return false;
    }
}
