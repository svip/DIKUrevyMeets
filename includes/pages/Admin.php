<?php

class Admin extends Page {

	protected function render ( ) {
		switch ( $_GET['admin'] ) {
		
			case 'front':
			default:
				$this->frontPage();
		}
	}
	
	private function frontPage ( ) {
		if ( isset ( $_POST['newmeeting-submit'] ) ) {
			$title = $_POST['newmeeting-title'];
			$comment = $_POST['newmeeting-comment'];
			$date = $_POST['newmeeting-date'];
			$meettime = $_POST['newmeeting-meettime'];
			$haseating = isset ($_POST['newmeeting-haseating'])?true:false;
			$eattime = $_POST['newmeeting-eattime'];
			$this->database->insertMeeting($date, $title, $haseating, $comment,
					$meettime, $eattime);
			header ( 'Location: ./?admin=front' );
		}
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
<input type="text" id="newmeeting-meettime" name="newmeeting-meettime" value="19:00" />
<label for="newmeeting-haseating">Er der mad?</label>
<input type="checkbox" id="newmeeting-haseating" name="newmeeting-haseating" checked="true" /><br />
<label for="newmeeting-eattime">Spisetid:</label>
<input type="text" id="newmeeting-eattime" name="newmeeting-eattime" value="18:00" />
<input type="submit" name="newmeeting-submit" value="Nyt møde!" />
</fieldset>
</form>';
		$this->content = $form;
	}
}

$page = new Admin($database, $auth);
