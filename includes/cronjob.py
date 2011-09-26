#!/usr/bin/env python
# -*- Encoding: UTF-8 -*-

import json, datetime, smtplib
from email.mime.text import MIMEText

def monthsInTheFuture ( date ):
	now = datetime.date.today()
	date = date.split('-')
	date = datetime.date(int(date[0]), int(date[1]), 1)
	diff = date - now
	return diff.days/30

def sortMeetings ( a, b ):
	return int(a[0].replace('-', '')) - int(b[0].replace('-', ''))

def formatDate ( date ):
	date = date.split('-')
	return '%s/%s/%s' % (date[2], date[1], date[0])

def main ( ):
	f = open ( '/var/www/dikurevy/meets/data/meetings.json' )
	j = json.load(f, 'utf-8')
	f.close()
	l = []
	for date in j:
		if monthsInTheFuture(date) > 3 or monthsInTheFuture(date) < 0:
			continue
		l.append([date, j[date]])
	l.sort(sortMeetings)
	if len(l) < 1:
		return False
	s = u'Denne måneds revymøder:'
	w = False
	for date in l:
		if int(date[0].split('-')[1]) <= (datetime.date.today().month + 1):
			s = u'''%s

%s -- %s:
    %s
    http://møder.dikurevy.dk/?meeting=%s''' % (s, formatDate(date[0]), date[1]['title'], date[1]['comment'], date[0])
		else:
			if not w:
				s = u'''%s

De efterfølgende to måneder:
''' % s
				w = True
			s = u'''%s
%s -- %s''' % (s, formatDate(date[0]), date[1]['title'])
	s = u'%s\n\nTilmeld dig møder på http://møder.dikurevy.dk/' % s
	s = s.strip()
	
	msg = MIMEText(s.encode('utf-8'))
	msg['Subject'] = u'De tre næste måneders revymøder'
	msg['From'] = 'revyboss@diku.dk'
	msg['Reply-To'] = 'revy@diku.dk'
	msg['To'] = 'revy@diku.dk'
	t = smtplib.SMTP('localhost')
	t.sendmail('revyboss@diku.dk', 'revy@diku.dk', msg.as_string())
	t.quit()

main()
