<?php
/**
 * Activer l'abonnement d'un auteur
 *
 * @plugin     Abonnements
 * @copyright  2014
 * @author     cedric
 * @licence    GNU/GPL
 * @package    SPIP\Abos\API
 */
if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('base/abstract_sql');
/**
 * TODO : revoir lien abonnement-transaction qui doit passer par la commande
 *
 * @param $id_transaction
 * @param $abo_uid
 * @param $mode_paiement
 * @param string $validite
 * @param int $id_auteur
 * @return bool|int
 */
function abos_activer_abonnement_dist($id_transaction,$abo_uid,$mode_paiement,$validite="",$id_auteur=0){
	spip_log("abos/activer_abonnement id_transaction=$id_transaction abo_uid=$abo_uid mode=$mode_paiement validite=$validite","bank");

	if (!$abo_uid OR !$mode_paiement){
		spip_log("Appel activer abonnement sans abo_uid ou mode_paiement".var_export(debug_backtrace(),true),"bank"._LOG_ERREUR);
		return false;
	}

	$id_abonnement = 0;
	$old_statut = $new_statut = "";
	$regle = false;

	// recuperer la transaction et son abonnement associe
	// et verifier que c'est bien le bon
	if ($id_transaction){
		$res = sql_select("*","spip_abonnements_liens","objet='transaction' AND id_objet=".intval($id_transaction));
		if (!$row = sql_fetch($res)
		 OR !$id_abonnement = $row['id_abonnement']
		 OR !($abo = sql_fetsel("*","spip_abonnements","id_abonnement=".intval($id_abonnement)))
		){
		 	spip_log("Impossible de retrouver l'abo lie a la transaction $id_transaction",'abo_erreurs'._LOG_ERREUR);
			return false;
		}

		if (!$trans = sql_fetsel("*","spip_transactions","id_transaction=".intval($id_transaction))){
			spip_log("statut inconnu sur transaction $id_transaction",'abo_erreurs'._LOG_ERREUR);
			return false;
		}
		if ($trans['statut']=='commande'){
		 	spip_log("La transaction $id_transaction n'a pas ete reglee (abo $abo_uid)",'abo_erreurs'._LOG_ERREUR);
			return false;
		}
		$regle = true;
		if (strncmp($trans['statut'],'echec',5)==0
		  AND $id_transaction==$abo['id_transaction_echeance']){
			// la transaction a echoue, liberer le pointeur dessus
			sql_updateq("spip_abonnements",array("id_transaction_echeance"=>0),"id_abonnement=".intval($id_abonnement));
		 	spip_log("La transaction $id_transaction a echoue (abo $abo_uid)",'abo_erreurs');
			$regle = false;
		}
		// si la transaction est reglee et liee a une souscription, on appelle le pipeline activer_abonnement
		// pour que la souscription se mette a jour, il faut lui passr un data=0 pour cela
		if ($trans['parrain']=='souscription' AND $trans['statut']==='ok'){
			pipeline(
				'bank_abos_activer_abonnement',
				array(
					'args'=>array(
						'id_transaction'=>$id_transaction,
						'abo_uid'=>$abo_uid,
						'mode_paiement'=>$mode_paiement,
						'validite'=>$validite,
						'id_auteur'=>$id_auteur,
					),
					'data' => 0,
				)
			);
		}


		// si la transaction est en attente on fait comme si reglee et on active
	}
	elseif ( !($abo = sql_fetsel("*","spip_abonnements","abonne_uid=".sql_quote($abo_uid))) ){
	  spip_log("Impossible de retrouver l'abo_uid $abo_uid",'abo_erreurs'._LOG_ERREUR);
		return false;
	}

	// verifier l'auteur
	if ($id_auteur
	  AND $abo['id_auteur']
	  AND $id_auteur!=$abo['id_auteur']) {
		spip_log("Impossible d'activer l'abonnement $abo_uid  id_auteur incoherent $id_auteur/".$abo['id_auteur'],'abo_erreurs'._LOG_ERREUR);
		return false;
	}

	$set = array(
		"abonne_uid"=>$abo_uid,
		"mode_paiement"=>$mode_paiement,
	);

	if ($id_transaction
	  AND ($id_transaction==$abo['id_transaction_echeance']) ){
		if ($regle){
			//stocker le statut pour le comparer par la suite et envoyer un mail si nouvel abonnement
			$old_statut = $abo['statut'];
			$new_statut = "ok";
			$set["credits"] = $abo['credits_echeance'];
			$echeance = date('Y-m-d H:i:s',strtotime("+".$abo['duree_echeance'],strtotime($abo['date_echeance'])));
			$set["date_echeance"] = $echeance;
			$set["statut"] = 'ok';
			$set["id_transaction_echeance"] = 0;
			if ($validite=='echeance'){
				// fixons la date de fin a l'echeance+1jour
				$fin = date('Y-m-d H:i:s',strtotime("+1 day",strtotime($echeance)));
				$set["date_fin"] = $fin;
			}
		}
		else {
			$set['statut'] = 'resilie';
			$set["id_transaction_echeance"] = 0;
		}
	}

	if (!$abo['id_auteur'] OR $abo['id_auteur']==-1) {
		if (!$id_auteur AND $trans['parrain']=='souscription' AND $id_souscription = $trans['tracking_id']){
			$souscription = sql_fetsel("*", "spip_souscriptions", 'id_souscription=' . intval($id_souscription));
			if ($cadeau = $souscription['cadeau']
			  AND $cadeau = unserialize($cadeau)){
				if (isset($cadeau['id_auteur'])){
					$abo['id_auteur'] = $id_auteur = $cadeau['id_auteur'];
				}
			}
			elseif($souscription['id_auteur']){
				$abo['id_auteur'] = $id_auteur = $souscription['id_auteur'];
			}
		}
		if (!$id_auteur){
			if (isset($trans['id_auteur']) AND $trans['id_auteur']){
				$abo['id_auteur'] = $id_auteur = $trans['id_auteur'];
			}
		}
		if (!$id_auteur){
			$abo['id_auteur'] = $id_auteur = isset($GLOBALS['visiteur_session']['id_auteur'])?$GLOBALS['visiteur_session']['id_auteur']:0;
		}
		if (!$id_auteur) {
			spip_log("Impossible d'activer l'abonnement $abo_uid : pas de id_auteur connu",'abo_erreurs'._LOG_ERREUR);
			return false;
		}
		$set["id_auteur"] = $id_auteur;
	}

	if ($validite AND $validite!='echeance') {
		$set["date_fin"] = $validite;
	}

	if (sql_updateq("spip_abonnements",$set,"id_abonnement=".intval($id_abonnement))){
		if ($row = sql_fetsel("*","spip_transactions","id_transaction=".intval($id_transaction))){
			if (($row['id_auteur']==0) AND $id_transaction)
				sql_updateq("spip_transactions",array("id_auteur"=>$row['id_auteur']=$abo['id_auteur']),"id_transaction=".intval($id_transaction));
		}

		// ne pas relancer les autres abonnements ok, du coup
		sql_updateq("spip_abonnements",array('relance'=>'off'),'id_auteur='.intval($abo['id_auteur'])." AND statut='ok' AND id_abonnement!=".intval($id_abonnement));

		// tous les autres abonnements en commande avec la meme transaction
		sql_updateq("spip_abonnements",array('id_transaction_echeance'=>0),"id_abonnement!=".intval($id_abonnement)." AND id_transaction_echeance=".intval($id_transaction)." AND statut='prepa'");
	}

	if ($old_statut
	  AND $new_statut
		AND $old_statut!=='resilie'
	  AND $old_statut != $new_statut) {

		$notifications = charger_fonction("notifications","inc");
		$notifications('activerabonnement',$id_abonnement,array('statut'=>$new_statut,'statut_ancien'=>$old_statut));
	}

	// retourner false si le reglement a echoue
	return $regle?$id_abonnement:false;
}
