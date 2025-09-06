<?php
return [
	'com' => [
		'host' => 'whois.verisign-grs.com',
		'query' => '%s',
		'not_found' => '/No match for\s+\"[^\"]+\"/i',
		'parser' => 'verisign',
	],
	'net' => [
		'host' => 'whois.verisign-grs.com',
		'query' => '%s',
		'not_found' => '/No match for\s+\"[^\"]+\"/i',
		'parser' => 'verisign',
	],
	'org' => [
		'host' => 'whois.pir.org',
		'query' => '%s',
		'not_found' => '/NOT FOUND/i',
		'parser' => 'pir',
	],
	'com.tr' => [
		'host' => 'whois.trabis.gov.tr',
		'alt_hosts' => ['whois.nic.tr'],
		'query' => '%s',
		'not_found' => '/(No match found|No entries found|DOMAIN NOT FOUND|Bulunamad[ıi])/i',
		'parser' => 'trabis',
	],
];
