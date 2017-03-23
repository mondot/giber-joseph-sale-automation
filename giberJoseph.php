#!/usr/local/bin/php
<?php

require_once (__DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Session;
use Behat\Mink\Element\DocumentElement;

$caisse1 = file(__DIR__ . DIRECTORY_SEPARATOR . 'caisses' . DIRECTORY_SEPARATOR . 'caisse1.txt');

$driver  = new Selenium2Driver('firefox');
$session = new Session($driver);

$session->start();

$session->visit('http://www.gibertjoseph.com/sao/sao/quoteRequest/');
$page = $session->getPage();

$amount = 0;

foreach ($caisse1 as $code) {
	$page->fillField('code', $code);
	$page->pressButton('add_sao');
	sleep(2);

	$totalAmount = getTotalAmount($page);

	if($totalAmount > $amount) {
		echo 'on a gagné de largent';
		$amount = $amount + $totalAmount;
	}
}

$session->stop();

function getTotalAmount(DocumentElement $page) {
	$totalAmountElement = $page->findById('total-amount');
	$totalAmountText    = str_replace(',', '.', $totalAmountElement->getText());
	$totalAmount        = floatval(substr($totalAmountText,0,4));

	return $totalAmount;
}
