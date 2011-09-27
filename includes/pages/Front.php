<?php

class Front extends Page { 
	
	protected function render ( ) {
		$list = '<table>';
		foreach ( $this->database->getSortedMeetings() as $date => $meeting ) {
			$renderSelf = null;
			$uniques = 0;
			foreach ( $meeting->schedule as $item ) {
				if ( $item->unique ) {
					$uniques++;
					if ( is_null($renderSelf) )
						$renderSelf = false;
				} else {
					$renderSelf = true;
				}
			}
			if ( $renderSelf )
				$list .= '<tr><td>'.$this->weekDay($date, true).'</td>
				<td>'.$this->readableDate($date).'</td>
				<td><a href="?meeting='.$date.'">'.$meeting->{'title'}."</a></td></tr>\n";
			else
				$list .= '<tr><td rowspan="'.($uniques+1).'">'.$this->weekDay($date, true).'</td>
				<td rowspan="'.($uniques+1).'">'.$this->readableDate($date).'</td>
				<td>'.$meeting->{'title'}."</td></tr>\n";
			foreach ( $meeting->schedule as $id => $item ) {
				if ( $item->unique ) {
					$list .= '<tr><td><a href="?meeting='.$date.'&amp;subid='.(isset($item->id)?$item->id:$id).'">'.$item->{'title'}."</a></td></tr>\n";
				}
			}
		}
		$list .= '</table>';
		$this->content = '<h1>DIKUrevy-møder</h1>';
		$this->content .= $list;
	}
	
}

$page = new Front($database, $auth);
