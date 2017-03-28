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

$today = date("d.m.y");
file_put_contents(LIVRES_REPRIS_PATH, "\n" . 'Estimation faite le ' . $today, FILE_APPEND);

$numeroCaisse = 0;
$prixTotal    = 0;

foreach ($caisses as $caissePleine) {

	$numeroCaisse++;

	echo 'Traitement de la caisse numéro ' . $numeroCaisse . "\n\n";
	file_put_contents(LIVRES_REPRIS_PATH, "\n\n" . 'caisse numéro ' . $numeroCaisse . "\n\n", FILE_APPEND);

	$livresRepris = [];
	$prixCaisse   = 0;

	$nbrDePack = intval((count($caissePleine)/15));

	for ($i = 0; $i <= $nbrDePack; $i++) {

		$caisseQuinzeCodes = array_slice($caissePleine, 15 * $i, 15);//Quinze est le nombre de livres à partir duquel le site GJ bug

		$giberJosephQuotePage = retrievePageFromUrl($session, 'http://www.gibertjoseph.com/sao/sao/quoteRequest/');
    $previousAmount = 0;

		foreach ($caisseQuinzeCodes as $code) {

			$code = substr($code, 0, 13);

			$giberJosephQuotePage->fillField('code', $code);
			$giberJosephQuotePage->pressButton('add_sao');

			sleep(2);

			$totalAmount = getTotalAmount($giberJosephQuotePage);

			if ($totalAmount > $previousAmount) {
				$livresRepris[] = $code . ';' . "\n";
				$previousAmount = $totalAmount;
			}

			//Dans le cas où le site de Giber Joseph bug
			if ($totalAmount < $previousAmount) {

				echo 'Un bug est survenu dans l\'affichage du site GJ.' . "\n";
				echo 'Vérifiez la liste des livres repris' . "\n\n";

				if ($totalAmount > 0) {
					$livresRepris[] = $code;
				}

				$previousAmount = $totalAmount;
			}

		}

		$prixCaisse += $totalAmount;
		$session->stop();
	}

	echo 'Ci-dessous la liste des livre repris pour la caisse ' . $numeroCaisse . ' pour un montant de ' . $prixCaisse . '€' . "\n\n";
	var_dump($livresRepris);

	file_put_contents(LIVRES_REPRIS_PATH, $livresRepris, FILE_APPEND);
	file_put_contents(LIVRES_REPRIS_PATH, "\n" . 'Pour un montant de : ' . $prixCaisse . '€' . "\n", FILE_APPEND);

	$prixTotal += $prixCaisse;
}

file_put_contents(LIVRES_REPRIS_PATH, "\n\n" . 'La revente s\'élève à ' . $prixTotal . '€' . "\n\n", FILE_APPEND);
file_put_contents(LIVRES_REPRIS_PATH, "\n" . '-----------------------------------------------------------' . "\n", FILE_APPEND);


function getTotalAmount(DocumentElement $page) {
	$totalAmountElement = $page->findById('total-amount');
	$totalAmountText    = str_replace(',', '.', $totalAmountElement->getText());
	$totalAmount        = floatval(substr($totalAmountText,0,4));

	return $totalAmount;
}

function retrievePageFromUrl(Session $session, $url) {

	$session->start();

	echo 'Visite de la page ' . $url . "\n\n";
	$session->visit($url);

	return $session->getPage();
}
