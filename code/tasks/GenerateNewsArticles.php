<?php

namespace SilverStripe\Internetrix\News;

use SilverStripe\Dev\BuildTask;

class GenerateNewsArticles extends BuildTask {

	protected $title = 'Generate Dummy News Articles';

	protected $description = 'Generate dummy news articles for testing';

	public function run($request) {
		$amount = 10; // amount of articles to generate
		
		$text = "<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed nec mi urna. Proin nunc purus, porta et malesuada non, ullamcorper eget nisl. Nam pulvinar accumsan lobortis. Donec in lacinia felis. Praesent dictum nisi non libero porta, pharetra ullamcorper tellus tincidunt. In pellentesque tellus tincidunt, egestas libero sit amet, ullamcorper dolor. Maecenas lacinia id dolor sit amet cursus. Aliquam erat volutpat. Ut ut luctus dolor. Cras sed diam cursus, dictum tellus ut, vehicula tellus. Donec gravida tortor a aliquet lobortis. Curabitur pellentesque iaculis faucibus.</p>
<p>Mauris ut turpis interdum, porta enim et, tristique sem. Quisque facilisis consectetur justo, sit amet ornare sem tempor eget. Aliquam dapibus libero mauris, vel consectetur sapien volutpat ornare. Morbi ac nisi nec nisi consequat sollicitudin eget vel purus. Phasellus ut lacus posuere, consequat turpis in, pretium felis. Phasellus tellus quam, consequat in metus vel, venenatis pulvinar arcu. Praesent suscipit tortor vel justo elementum pretium. Quisque commodo porta cursus. Fusce eget vulputate erat. Mauris iaculis auctor augue, ac semper eros bibendum ac.</p>";
		
		// holder
		$newsHolder = NewsHolder::get()->last();
		
		if(!$newsHolder) {
			echo "Cannot run without news holder";
			die();
		}
		
		for ($x = 1; $x <= $amount; $x++) {
		    echo "Generating news article $x<br />";
		    $dateTimestamp = rand(1357004961,time()); // Between 1/1/2013 and now
		    
		    $newsArticle = new News();
		    $newsArticle->Title = "article x$x";
		    $newsArticle->ParentID = $newsHolder->ID;
		    $newsArticle->Author = "Generated";
		    $newsArticle->Date = date('d/m/Y', $dateTimestamp);
		    $newsArticle->Content = $text;
		    $newsArticle->write();
		    $newsArticle->publish('Stage', 'Live');
		} 

		echo "<br />Generated $amount articles";
	}

}