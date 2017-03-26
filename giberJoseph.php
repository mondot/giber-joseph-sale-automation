#!/usr/local/bin/php
<?php

require_once (__DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

use Behat\Mink\Driver\Selenium2Driver;
use Behat\Mink\Session;
use Behat\Mink\Element\DocumentElement;

define('LIVRES_REPRIS_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'caisses' . DIRECTORY_SEPARATOR . 'livres_repris.txt');

echo 'Récupération des codes barres des différentes caisses.' . "\n\n";

$caisse1 = file(__DIR__ . DIRECTORY_SEPARATOR . 'caisses' . DIRECTORY_SEPARATOR . 'caisse1.txt');
$caisse2 = file(__DIR__ . DIRECTORY_SEPARATOR . 'caisses' . DIRECTORY_SEPARATOR . 'caisse2.txt');
$caisse3 = file(__DIR__ . DIRECTORY_SEPARATOR . 'caisses' . DIRECTORY_SEPARATOR . 'caisse3.txt');
$caisse4 = file(__DIR__ . DIRECTORY_SEPARATOR . 'caisses' . DIRECTORY_SEPARATOR . 'caisse4.txt');
$caisse5 = file(__DIR__ . DIRECTORY_SEPARATOR . 'caisses' . DIRECTORY_SEPARATOR . 'caisse5.txt');

$caisses = [
	'caisse1' => $caisse1,
	'caisse2' => $caisse2,
	'caisse3' => $caisse3,
	'caisse4' => $caisse4,
	'caisse5' => $caisse5
];

echo 'Démarrage de la session firefox' . "\n\n";

$driver  = new Selenium2Driver('firefox');
$session = new Session($driver);

$session->start();

echo 'Visite de la page pour estimer ces livres sur giberjoseph.com' . "\n\n";
$session->visit('http://www.gibertjoseph.com/sao/sao/quoteRequest/');
$page = $session->getPage();

$previousAmount = 0;
$numeroCaisse   = 0;

$today = date("d.m.y");
file_put_contents(LIVRES_REPRIS_PATH, "\n\n" . 'Estimation faite le ' . $today, FILE_APPEND);

foreach ($caisses as $caisse) {

	$livresRepris = [];
	$numeroCaisse++;

	echo "\n\n" . 'Traitement de la caisse numéro ' . $numeroCaisse . "\n\n";
	file_put_contents(LIVRES_REPRIS_PATH, "\n\n" . 'caisse numéro ' . $numeroCaisse . "\n\n", FILE_APPEND);

	foreach ($caisse as $code) {

		$page->fillField('code', $code);
		$page->pressButton('add_sao');

		sleep(5);

		$totalAmount = getTotalAmount($page);

		if ($totalAmount > $previousAmount) {
			$livresRepris[] = $code;
			$previousAmount = $totalAmount;
		}

		//Dans le cas où le site de Giber Joseph bug (tous les livres sont retirés d'un coup)
		if ($totalAmount < $previousAmount) {
			echo 'Un bug est survenu dans l\'affichage du site GJ.' . "\n";
			echo 'Vérifiez la liste des livres repris' . "\n\n";
			if ($totalAmount > 0) {
				$livresRepris[] = $code;
			}
			$previousAmount = $totalAmount;
		}
	}

	echo 'Voici la liste des livre repris pour la caisse ' . $numeroCaisse . ' :' . "\n\n";
	var_dump($livresRepris);
	file_put_contents(LIVRES_REPRIS_PATH, $livresRepris, FILE_APPEND);

}

$session->stop();

function getTotalAmount(DocumentElement $page) {
	$totalAmountElement = $page->findById('total-amount');
	$totalAmountText    = str_replace(',', '.', $totalAmountElement->getText());
	$totalAmount        = floatval(substr($totalAmountText,0,4));

	return $totalAmount;
}
