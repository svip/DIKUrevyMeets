<?php

class Admin extends Page {

	protected function render ( ) {
		if ( !$this->auth->isAdmin() ) {
			$this->content = '<p>Du skal lige som være administrator her, eh?</p>';
			return;
		}
		switch ( $_GET['admin'] ) {
			case 'meeting':
				$this->meetingPage();
				break;		
			case 'front':
			default:
				$this->frontPage();
				break;
		}
	}
	
	private function frontPage ( ) {
		if ( isset ( $_POST['newmeeting-submit'] ) ) {
			$title = $_POST['newmeeting-title'];
			$comment = $_POST['newmeeting-comment'];
			$date = $_POST['newmeeting-date'];
			$meettime = explode('-', $_POST['newmeeting-meettime']);
			$haseating = isset ($_POST['newmeeting-haseating'])?true:false;
			$eattime = explode('-', $_POST['newmeeting-eattime']);
			$spend = $_POST['newmeeting-spend'];
			if ( $haseating ) {
				$schedule = array(
					array(
						'title'=>'Aftensmad',
						'type'=>'eat',
						'start'=>trim($eattime[0]),
						'end'=>trim($eattime[1]),
						'open'=>true
					),
					array(
						'title'=>'Møde',
						'type'=>'meet',
						'start'=>trim($meettime[0]),
						'end'=>trim($meettime[0])
					)
				);
			} else {
				$schedule = array(
					array(
						'title'=>'Møde',
						'type'=>'meet',
						'start'=>trim($meettime[0]),
						'end'=>trim($meettime[0])
					)
				);			
			}
			$this->database->insertMeeting($date, $title, $schedule, 
				$comment, $spend);
			header ( 'Location: ./?admin=front' );
		}
		$list = "<ul>\n";
		foreach ( $this->database->getSortedMeetings() as $date => $meeting ) {
			$list .= '<li><a href="./?admin=meeting&amp;date='.$date.'">'.$date.': '.$meeting->{'title'}."</a></li>\n";
		}
		$list .= "</ul>\n";
		$form = '<form method="post">
<fieldset>
<legend>Nyt møde</legend>
<label for="newmeeting-title">Overskrift:</label>
<input type="text" id="newmeeting-title" name="newmeeting-title" />
<label for="newmeeting-comment">Eventuel kommentar:</label>
<input type="text" id="newmeeting-comment" name="newmeeting-comment" />
<label for="newmeeting-date">Dato (format: <tt>ÅÅÅÅ-MM-DD</tt>):</label>
<input type="text" id="newmeeting-date" name="newmeeting-date" />
<label for="newmeeting-meettime">Mødetid:</label>
<input type="text" id="newmeeting-meettime" name="newmeeting-meettime" value="19:00 - 23:00" />
<label for="newmeeting-haseating">Er der mad?</label>
<input type="checkbox" id="newmeeting-haseating" name="newmeeting-haseating" checked="true" /><br />
<label for="newmeeting-eattime">Spisetid:</label>
<input type="text" id="newmeeting-eattime" name="newmeeting-eattime" value="18:00 - 19:00" />
<label for="newmeeting-spend">Indkøbspris:</label>
<input type="text" id="newmeeting-spend" name="newmeeting-spend" />
<input type="submit" name="newmeeting-submit" value="Nyt møde!" />
</fieldset>
</form>';
		$this->content = $list.$form;
	}
	
	private function meetingPage ( ) {
		$date = $_GET['date'];
		$meeting = $this->database->getMeeting($date);
		if ( empty ($meeting) ) {
			header( 'Location: ./?admin=front' );
		}
		$users = '';
		foreach ( $meeting->users as $user ) {
			
		}
	}
}

$page = new Admin($database, $auth);
