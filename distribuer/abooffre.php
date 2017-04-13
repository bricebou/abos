<?php
/**
 * Abonner un auteur
 *
 * @plugin     Abonnements
 * @copyright  2014
 * @author     cedric
 * @licence    GNU/GPL
 * @package    SPIP\Abos\API
 */
if (!defined('_ECRIRE_INC_VERSION')) return;

/**
 * Distribuer un ou des(?) abonnements
 * @param $id_abo_offre
 * @param $detail
 * @param $commande
 */
function distribuer_abooffre_dist($id_abo_offre,$detail,$commande){


	$abonner = charger_fonction("abonner","abos");
	$options = array(
		'id_commande' => $commande['id_commande'],
		'id_auteur' => $commande['id_auteur'],
		'statut' => 'ok',
		'mode_paiement' => $commande['mode'],
		'prix_ht_initial' => $detail['prix_unitaire_ht'], // reprendre le prix qui a ete enregistre dans la commande
	);

	if (isset($commande['echeances_date_debut']) and intval($commande['echeances_date_debut'])) {
		$options['date_debut'] = $commande['echeances_date_debut'];
	}

	$nb = $detail['quantite'];
	while($nb-->0){
		$abonner($id_abo_offre,$options);
	}

}