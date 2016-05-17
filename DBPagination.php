<?php
/**
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to gregory_jarrige@yahoo.fr so we can send you a copy immediately.
 *
 * @category   MacaronDB
 * @package    DB
 * @copyright  Copyright (c) 2012-2015 Gregory Jarrige 
 * @author     Gregory Jarrige <gregory_jarrige@yahoo.fr>
 * @license    New BSD License
 * @version    DBFactory.php 2012-03-28 09:15:47
 *
 * Description : 
 * Classe permettant de générer une barre de pagination
 * Créée à partir de fonctions extraites de cet excellent livre : 
 *   PHP Cookbook (2nd Edition), par Adam Trachtenberg et David Sklar, O'Reilly (2006) 
 * De légères modifications ont été apportée au code initial, telles que : 
 * - le regroupement de ces 2 fonctions dans une classe abstraite, sous forme de
 *   méthodes statiques, afin de renforcer la robustesse et de faciliter la
 *   réutilisation au sein de projets orientés objet 
 * - la possibilité de passer la page d'appel aux 2 méthodes, ceci afin de faciliter 
 *   la réutilisation de ces 2 méthodes sur différentes pages 
 * - il a été nécessaire d'ajouter un tableau $params permettant de transmettre d'une 
 *   page à l'autre des paramètres autres que l'offset, tels que les critères de 
 *   sélection saisis sur le formulaire de recherche.
 * - le nombre de pages directement "appelables" a été limité à 5, des points de suspension
 *   sont ajoutés ensuite, et le lien vers la dernière page est ajouté en fin de barre de 
 *   pagination (la version initiale proposait un lien vers chaque page, ce qui
 *   donnait des résultats particulièrement laids sur des jeux de données de grande taille. 
 */

interface DBPaginationInterface {
	static function pcPrintLink($inactive, $text, $offset, $current_page, $params_page) ;
	static function pcIndexedLinks($total, $offset, $per_page, $curpage, $parmpage) ;
}

abstract class DBPagination implements DBPaginationInterface {
	
	/**
	 * Constructeur non public pour éviter tout risque d'instanciation "par erreur"
	 * @throws Exception
	 */
	private function __construct() {
		throw new Exception ( "Instanciation non autorisée sur cette classe." );
	}
	
	/**
	 * Méthode utilisée par la méthode pcIndexeedLinks pour générer les liens de la barre de pagination
	 * @param boolean $inactive
	 * @param text $text
	 * @param integer $offset
	 * @param text $current_page
	 * @param text $params_page
	 */
	public static function pcPrintLink($inactive, $text, $offset, $current_page, $params_page) {
		// on prépare l'URL avec tous les paramètres sauf "offset"
		if (is_null ( $offset ) || $offset == '' || intval($offset) <= 0) {
			$offset = '1';
		}
		$url = '';
		$params_page ['offset'] = $offset;
		$url = '?' . http_build_query ( $params_page, null, '&amp;' );
		if ($inactive) {
			print "<span class='inactive'>$text</span>\n";
		} else {
			print "<span class='active'>" . "<a href='" . htmlentities ( $current_page ) . "$url'>$text</a></span>\n";
		}
	}
	
	/**
	 * Méthode utilisée pour générer une barre de pagination sur les listes SQL
	 * Exemple d'utilisation : 
	 *    DBPagination::pcIndexedLinks ( $nb_lignes_total, $offset, MAX_LINES_BY_PAGE, $_SERVER ['PHP_SELF'], $params );
	 * @param integer $total
	 * @param integer $offset
	 * @param integer $per_page
	 * @param text $curpage
	 * @param text $parmpage
	 */
	public static function pcIndexedLinks($total, $offset, $per_page, $curpage, $parmpage) {
		$separator = ' | ';
		
		self::pcPrintLink ( $offset == 1, '<< Pr&eacute;c.', $offset - $per_page, $curpage, $parmpage );
		
		$compteur = 0;
		$top_suspension = false;
		
		// affichage de tous les groupes à l'exception du dernier
		for($start = 1, $end = $per_page; $end < $total; $start += $per_page, $end += $per_page) {
			$compteur += 1;
			if ($compteur < 5) {
				print $separator;
				self::pcPrintLink ( $offset == $start, "$start-$end", $start, $curpage, $parmpage );
			} else {
				/*
				 * if ($compteur == 15) { $compteur = 0 ; print '<br />'.PHP_EOL ; } else { print $separator; } ;
				 */
				if (! $top_suspension) {
					$top_suspension = true;
					print ' | ... ';
				}
			}
		}
		
        $end = ($total > $start) ? '-'.$total : '';
		
		print $separator;
		self::pcPrintLink ( $offset == $start, "$start$end", $start, $curpage, $parmpage );
		
		print $separator;
		self::pcPrintLink ( $offset == $start, 'Suiv. >>', $offset + $per_page, $curpage, $parmpage );
	}
}
