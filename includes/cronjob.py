#!/usr/bin/env python
# -*- Encoding: UTF-8 -*-

import json, datetime, smtplib, os
from email.mime.text import MIMEText
from optparse import OptionParser

class MailTask(object):
	
	today = 0
	debug = False
	months = ['januar', 'februar', 'marts', 'april', 'maj', 'juni', 'juli',
			'august', 'september', 'oktober', 'november', 'december']

	def monthsInTheFuture ( self, date ):
		now = self.today
		date = date.split('-')
		date = datetime.date(int(date[0]), int(date[1]), 1)
		diff = date - now
		return diff.days/30

	def sortMeetings ( self, a, b ):
		return int(a[0].replace('-', '')) - int(b[0].replace('-', ''))

	def formatDate ( self, date ):
		date = date.split('-')
		return '%s/%s/%s' % (date[2], date[1], date[0])
	
	def monthName ( self, month ):
		return self.months[month-1]

	def __init__ ( self, today=None, debug=False ):
		self.debug = debug
		if today != None:
			s = today.split('-')
			self.today = datetime.date(int(s[0]), int(s[1]), int(s[2]))
		else:
			self.today = datetime.date.today()
		f = open ( '/var/www/dikurevy/meets/data/meetings.json' )
		j = json.load(f, 'utf-8')
		f.close()
		l = []
		for date in j:
			if self.monthsInTheFuture(date) > 2 or self.monthsInTheFuture(date) < 0:
				continue
			l.append([date, j[date]])
		l.sort(self.sortMeetings)
		if len(l) < 1:
			return False
		s = u'Denne måneds revymøder:'
		w = False
		for date in l:
			if int(date[0].split('-')[1]) == self.today.month:
				s = u'''%s

	%s -- %s:
		%s
		http://møder.dikurevy.dk/?meeting=%s''' % (s, self.formatDate(date[0]), date[1]['title'], date[1]['comment'], date[0])
			else:
				if not w:
					s = u'''%s

	De efterfølgende to måneder:
	''' % s
					w = True
				s = u'''%s
	%s -- %s''' % (s, self.formatDate(date[0]), date[1]['title'])
		s = u'%s\n\nTilmeld dig møder på http://møder.dikurevy.dk/' % s
		s = s.strip()
		
		subject = u'Revy-begivenheder for %s' % self.monthName(self.today.month)
		
		if self.debug:
			print subject
			print s
		else:
			msg = MIMEText(s.encode('utf-8'), 'plain', 'utf-8')
			msg['Subject'] = subject
			msg['From'] = 'boss@dikurevy.dk'
			msg['Reply-To'] = 'revy@dikurevy.dk'
			msg['To'] = 'revy@dikurevy.dk'
			t = smtplib.SMTP('localhost')
			t.sendmail('boss@dikurevy.dk', 'revy@dikurevy.dk', msg.as_string())
			t.quit()

class GitCommit(object):
	
	def __init__( self, debug ):
		os.system ( "cd /var/www/dikurevy/meets && git add data/*.json && git commit -m 'Data-opdatering'" )
		#&& git push git@github.com:svip/DIKUrevyMeets.git

def main ( ):
	parser = OptionParser()
	parser.add_option("--daily", dest="daily", action="store_true")
	parser.add_option("--monthly", dest="monthly", action="store_true")
	parser.add_option("--date", dest="date", help="Set date to test by.")
	parser.add_option("-d", "--debug", dest="debug", help="Set debug mode",
			action="store_true")
	(options, args) = parser.parse_args()
	
	if options.monthly:
		MailTask(options.date, options.debug)
	elif options.daily:
		GitCommit(options.debug)
	else:
		print "You forgot to ask for anything to do, see --help"

main()
