<?php
/**
 * Déclarations relatives à la base de données
 *
 * @plugin     Abonnements
 * @copyright  2014
 * @author     cedric
 * @licence    GNU/GPL
 * @package    SPIP\Abos\Pipelines
 */

if (!defined('_ECRIRE_INC_VERSION')) return;


/**
 * Déclaration des alias de tables et filtres automatiques de champs
 *
 * @pipeline declarer_tables_interfaces
 * @param array $interfaces
 *     Déclarations d'interface pour le compilateur
 * @return array
 *     Déclarations d'interface pour le compilateur
 */
function abos_declarer_tables_interfaces($interfaces) {

	$interfaces['table_des_tables']['abo_offres'] = 'abo_offres';
	$interfaces['table_des_tables']['abonnements'] = 'abonnements';

	$interfaces['table_des_traitements']['INTITULE_SYNTHESE']['abo_offres']= _TRAITEMENT_TYPO;
	$interfaces['table_des_traitements']['PHRASE_ASTERISQUE']['abo_offres']= _TRAITEMENT_RACCOURCIS;

	return $interfaces;
}


/**
 * Déclaration des objets éditoriaux
 *
 * @pipeline declarer_tables_objets_sql
 * @param array $tables
 *     Description des tables
 * @return array
 *     Description complétée des tables
 */
function abos_declarer_tables_objets_sql($tables) {

	$tables['spip_abo_offres'] = array(
		'type' => 'abooffre',
		'page' => false,
		'principale' => "oui", 
		'table_objet_surnoms' => array('abooffre'), // table_objet('abooffre') => 'abo_offres' 
		'field'=> array(
			"id_abo_offre"       => "bigint(21) NOT NULL",
			"titre"              => "text NOT NULL",
			"descriptif"         => "text NOT NULL",
			"texte"              => "text NOT NULL",
			"duree"              => "varchar(10) NOT NULL DEFAULT ''",
			"prix"               => "varchar(25) NOT NULL DEFAULT ''",
			"prix_renouvellement" => "varchar(25) NOT NULL DEFAULT ''",
			"taux_tva"           => "varchar(10) NOT NULL DEFAULT ''",
			"wha_oid"            => "varchar(10) NOT NULL DEFAULT ''",
			"statut"             => "varchar(20)  DEFAULT '0' NOT NULL", 
			"maj"                => "TIMESTAMP"
		),
		'key' => array(
			"PRIMARY KEY"        => "id_abo_offre",
			"KEY statut"         => "statut", 
		),
		'titre' => "titre AS titre, '' AS lang",
		 #'date' => "",
		'champs_editables'  => array('titre', 'descriptif', 'texte', 'duree', 'prix', 'prix_renouvellement','taux_tva'),
		'champs_versionnes' => array('titre', 'descriptif', 'texte', 'duree', 'prix', 'prix_renouvellement','taux_tva'),
		'rechercher_champs' => array('titre'=>4,'descriptif'=>2,'texte'=>2,'wha_oid'=>1),
		'tables_jointures'  => array(),
		'statut_textes_instituer' => array(
			'prepa'    => 'abooffre:texte_statut_en_cours_redaction',
			'publie'   => 'abooffre:texte_statut_publie',
			'poubelle' => 'abooffre:texte_statut_poubelle',
		),
		'statut'=> array(
			array(
				'champ'     => 'statut',
				'publie'    => 'publie',
				'previsu'   => 'publie,prepa',
				'exception' => array('statut','tout')
			)
		),
		'texte_changer_statut' => 'abooffre:texte_changer_statut_abooffre', 
		

	);

	$tables['spip_abonnements'] = array(
		'type' => 'abonnement',
		'page' => false,
		'principale' => "oui",
		'field'=> array(
			"id_abonnement"      => "bigint(21) NOT NULL",
			"id_abo_offre"       => "bigint(21) NOT NULL",
			"id_auteur"          => "bigint(21) NOT NULL",
			"date_debut"         => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
			"date_fin"           => "datetime DEFAULT NULL",
			"date_echeance"      => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
			"duree_echeance"     => "varchar(10) NOT NULL DEFAULT ''",
			"prix_echeance"      => "varchar(25) NOT NULL DEFAULT ''",
			"credits_echeance"   => "text NOT NULL DEFAULT ''",
			"mode_echeance"      => "varchar(10) NOT NULL DEFAULT 'tacite'",
			"id_transaction_echeance" => "bigint(21) NOT NULL DEFAULT '0'",
			"id_transaction_essai" => "bigint(21) NOT NULL DEFAULT '0'",
			"credits"            => "text NOT NULL DEFAULT ''",
			"mode_paiement"      => "varchar(10) NOT NULL DEFAULT ''",
			"abonne_uid"         => "varchar(50) NOT NULL DEFAULT ''",
			"confirm"            => "varchar(255) NOT NULL DEFAULT ''",
			"commentaire"        => "text NOT NULL DEFAULT ''",
			"message"            => "text NOT NULL DEFAULT ''",
			"relance"            => "varchar(3) NOT NULL DEFAULT ''",
			"statut"             => "varchar(20)  DEFAULT '0' NOT NULL",
			"maj"                => "TIMESTAMP"
		),
		'key' => array(
			"PRIMARY KEY"        => "id_abonnement",
			"KEY statut"         => "statut", 
		),
		'titre' => "abonne_uid AS titre, '' AS lang",
		 #'date' => "",
		'champs_editables'  => array(),
		'champs_versionnes' => array(),
		'rechercher_champs' => array('abonne_uid'=>1,'mode_paiement'=>1),
		'rechercher_jointures' => array(
			'auteur' => array('email' => 1),
		),
		'tables_jointures'  => array('id_transaction'=>'abonnements_liens'),
		'statut_textes_instituer' => array(
			'prepa'   => 'abonnement:texte_statut_prepa',
			'ok'   => 'abonnement:texte_statut_ok',
			'resilie'   => 'abonnement:texte_statut_resilie',
		),
		'statut_images' => array(
			'abonnement-16.png',
			'prepa'=>'puce-preparer-8.png',
			'ok'=>'puce-publier-8.png',
			'resilie'=>'puce-supprimer-8.png',
		),
		'statut'=> array(
			array(
				'champ'     => 'statut',
				'publie'    => 'ok',
				'previsu'   => 'ok',
				'exception' => array('statut','tout')
			)
		),
		'texte_changer_statut' => 'abonnement:texte_changer_statut_abonnement', 
		

	);
	$tables[]['tables_jointures'][]= 'abonnements_liens';
	$tables[]['tables_jointures'][]= 'abonnements';

	return $tables;
}


/**
 * Déclaration des tables secondaires (liaisons)
 *
 * @pipeline declarer_tables_auxiliaires
 * @param array $tables
 *     Description des tables
 * @return array
 *     Description complétée des tables
 */
function abos_declarer_tables_auxiliaires($tables) {

	$tables['spip_abonnements_liens'] = array(
		'field' => array(
			"id_abonnement"      => "bigint(21) DEFAULT '0' NOT NULL",
			"id_objet"           => "bigint(21) DEFAULT '0' NOT NULL",
			"objet"              => "VARCHAR(25) DEFAULT '' NOT NULL",
			"date"                 => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'"
		),
		'key' => array(
			"PRIMARY KEY"        => "id_abonnement,id_objet,objet",
			"KEY id_abonnement"  => "id_abonnement"
		)
	);

	return $tables;
}


?>