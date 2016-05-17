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
 * Classe permettant de g�n�rer une barre de pagination
 * Cr��e � partir de fonctions extraites de cet excellent livre : 
 *   PHP Cookbook (2nd Edition), par Adam Trachtenberg et David Sklar, O'Reilly (2006) 
 * De l�g�res modifications ont �t� apport�e au code initial, telles que : 
 * - le regroupement de ces 2 fonctions dans une classe abstraite, sous forme de
 *   m�thodes statiques, afin de renforcer la robustesse et de faciliter la
 *   r�utilisation au sein de projets orient�s objet 
 * - la possibilit� de passer la page d'appel aux 2 m�thodes, ceci afin de faciliter 
 *   la r�utilisation de ces 2 m�thodes sur diff�rentes pages 
 * - il a �t� n�cessaire d'ajouter un tableau $params permettant de transmettre d'une 
 *   page � l'autre des param�tres autres que l'offset, tels que les crit�res de 
 *   s�lection saisis sur le formulaire de recherche.
 * - le nombre de pages directement "appelables" a �t� limit� � 5, des points de suspension
 *   sont ajout�s ensuite, et le lien vers la derni�re page est ajout� en fin de barre de 
 *   pagination (la version initiale proposait un lien vers chaque page, ce qui
 *   donnait des r�sultats particuli�rement laids sur des jeux de donn�es de grande taille. 
 */

interface DBPaginationInterface {
	static function pcPrintLink($inactive, $text, $offset, $current_page, $params_page) ;
	static function pcIndexedLinks($total, $offset, $per_page, $curpage, $parmpage) ;
}

abstract class DBPagination implements DBPaginationInterface {
	
	/**
	 * Constructeur non public pour �viter tout risque d'instanciation "par erreur"
	 * @throws Exception
	 */
	private function __construct() {
		throw new Exception ( "Instanciation non autoris�e sur cette classe." );
	}
	
	/**
	 * M�thode utilis�e par la m�thode pcIndexeedLinks pour g�n�rer les liens de la barre de pagination
	 * @param boolean $inactive
	 * @param text $text
	 * @param integer $offset
	 * @param text $current_page
	 * @param text $params_page
	 */
	public static function pcPrintLink($inactive, $text, $offset, $current_page, $params_page) {
		// on pr�pare l'URL avec tous les param�tres sauf "offset"
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
	 * M�thode utilis�e pour g�n�rer une barre de pagination sur les listes SQL
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
		
		// affichage de tous les groupes � l'exception du dernier
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
