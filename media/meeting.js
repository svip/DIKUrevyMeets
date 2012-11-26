function toggleNames ( ) {
	var spans = document.getElementsByTagName('span');
	for ( i in spans ) {
		if (spans[i].getAttribute('class') != 'username')
			continue;
		nameA = spans[i].firstChild.data;
		nameB = spans[i].getAttribute('title');
		spans[i].parentNode.innerHTML = '<span title="' + nameA + '" class="username">' + nameB + '</span>';
	}
}
