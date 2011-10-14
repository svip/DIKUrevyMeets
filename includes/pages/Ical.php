<?php

/*
 * This is the ical page class, not the ical return functionality class,
 * see /includes/ical.php for that.
 */
class Ical extends Page {

	protected function render() {
		global $debug;
		
		$url = 'http://moeder.dikurevy.dk/?do=ical';
		if ( $debug )
			$url = './?do=ical';
		$content = '<p><a href="'.$url.'">Link til den fulde ical-kalender</a><br />(højreklik og gem linket, indsæt det derefter i dit kalenderprogram)</p>';
		$content .= '<h2>Filtér din ical-kalender</h2>';
		$tags = $this->database->getTags();
		if ( count($tags) > 0 ) {
			$content .= '<p class="left">Vælg en etikette at filtere din kalender på:<br />(højreklik og gem linket, indsæt det derefter i dit kalenderprogram)</p>';
			$content .= '<ul>';
			foreach ( $tags as $tag ) {
				$content .= '<li><a href="'.$url.'&amp;tags='.$tag.'">'.$tag.'</a></li>';
			}
			$content .= '</ul>';
		} else {
			$content .= '<p>Der er ingen etiketter defineret.  Det er derfor ikke muligt at filtere på dem.</p>';
		}
		$this->content = $content;
	}
}

$page = new Ical($database, $auth);
