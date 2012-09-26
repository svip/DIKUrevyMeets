<?php

/*
 * This is the ical page class, not the ical return functionality class,
 * see /includes/ical.php for that.
 */
class Ical extends Page {

	protected function render() {
		global $Debug;
		
		// Make this more standardised to allow global set value.
		$url = 'http://moeder.dikurevy.dk/?do=ical';
		if ( $Debug )
			$url = './?do=ical';
		$content = gfRawMsg('<p><a href="$1">$2</a><br />($3)</p>',
			$url,
			gfMsg('ical-linktofull'),
			gfMsg('ical-instructions')
		);
		$content .= gfRawMsg(
			'<h2>$1</h2>',
			gfMsg('ical-filtercalendar-header')
		);
		$tags = $this->database->getTags();
		if ( count($tags) > 0 ) {
			$content .= gfRawMsg('<p class="left">$1<br />($2)</p>',
				gfMsg('ical-filtercalendar-intro'),
				gfMsg('ical-instructions')
			);
			$content .= '<ul>';
			foreach ( $tags as $tag ) {
				$content .= gfRawMsg('<li><a href="$1&amp;tags=$2">$2</a></li>',
					$url, $tag
				);
			}
			$content .= '</ul>';
		} else {
			$content .= gfRawMsg('<p>$1</p>',
				gfMsg('ical-filtercalendar-notags')
			);
		}
		$this->content = $content;
	}
}
