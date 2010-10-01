<?php
/**
 * Copyright (c) 2002-2006 Aur�lien Maille
 * 
 * This file is part of Wanewsletter.
 * 
 * Wanewsletter is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License 
 * as published by the Free Software Foundation; either version 2 
 * of the License, or (at your option) any later version.
 * 
 * Wanewsletter is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Wanewsletter; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * 
 * @package Wanewsletter
 * @author  Bobe <wascripts@phpcodeur.net>
 * @link    http://phpcodeur.net/wascripts/wanewsletter/
 * @license http://www.gnu.org/copyleft/gpl.html  GNU General Public License
 * @version $Id$
 * 
 * Vous pouvez tr�s facilement traduire Wanewsletter dans une autre langue.
 * Il vous suffit pour cela de traduire ce qui se trouve entre
 * guillemets. Attention, ne touchez pas � la partie $lang['....']
 * 
 * des %1\$s, %s, %d ou autre signe de ce genre signifient qu'ils
 * vont �tre remplac�s par un contenu variable. Placez les de fa�on
 * ad�quat dans la phrase mais ne les enlevez pas.
 * Enfin, les \n repr�sentent un retour � la ligne.
 */


$lang['General_title']              = "Administration des listes de diffusion";

$lang['Title']['accueil']           = "Informations g�n�rales sur la newsletter";
$lang['Title']['install']           = "Installation de Wanewsletter";
$lang['Title']['upgrade']           = "Mise � jour de Wanewsletter";
$lang['Title']['reinstall']         = "R�installation de Wanewsletter";
$lang['Title']['database']          = "Acc�s base de donn�es";
$lang['Title']['admin']             = "Administration";
$lang['Title']['error']             = "Erreur !";
$lang['Title']['info']              = "Information !";
$lang['Title']['select']            = "S�lection";
$lang['Title']['confirm']           = "Confirmation";
$lang['Title']['config_lang']       = "Choix de la langue";
$lang['Title']['config_perso']      = "Personnalisation";
$lang['Title']['config_cookies']    = "Cookies";
$lang['Title']['config_email']      = "Envois des emails";
$lang['Title']['config_files']      = "Fichiers joints";
$lang['Title']['config_stats']      = "Module de statistiques";
$lang['Title']['config_divers']     = "Divers";
$lang['Title']['profile']           = "Profil de %s";
$lang['Title']['mod_profile']       = "�dition du profil de %s";
$lang['Title']['manage']            = "Actions possibles de l'utilisateur";
$lang['Title']['other_options']     = "Options diverses";
$lang['Title']['info_liste']        = "Informations sur la liste de diffusion";
$lang['Title']['add_liste']         = "Cr�er une liste de diffusion";
$lang['Title']['edit_liste']        = "�diter une liste de diffusion";
$lang['Title']['purge_sys']         = "Syst�me de purge";
$lang['Title']['cron']              = "Option cron";
$lang['Title']['logs']              = "Liste des newsletters envoy�es � cette liste";
$lang['Title']['abo']               = "Liste des abonn�s de cette liste de diffusion";
$lang['Title']['stats']             = "Statistiques des listes de diffusion";
$lang['Title']['tools']             = "Outils Wanewsletter";
$lang['Title']['export']            = "Exporter des adresses emails";
$lang['Title']['import']            = "Importer des adresses emails";
$lang['Title']['ban']               = "Gestion des emails bannis";
$lang['Title']['attach']            = "Gestion des extensions de fichiers";
$lang['Title']['backup']            = "Syst�me de sauvegarde";
$lang['Title']['restore']           = "Syst�me de restauration";
$lang['Title']['generator']         = "G�n�rateur de formulaires d'inscriptions";
$lang['Title']['send']              = "Formulaire d'envoi";
$lang['Title']['join']              = "Joindre un fichier � la newsletter";
$lang['Title']['joined_files']      = "Fichiers joints � cette newsletter";
$lang['Title']['Show_popup']        = "Aper�u de %s";
$lang['Title']['profil_cp']         = "Panneau de gestion de compte";
$lang['Title']['sendkey']           = "Recevoir vos identifiants";
$lang['Title']['archives']          = "Archives des listes de diffusion";
$lang['Title']['sendpass']          = "G�n�rer un nouveau mot de passe";
$lang['Title']['form']              = "Inscription � la liste de diffusion";


//
// Modules de l'administration
//
$lang['Module']['accueil']          = "Accueil";
$lang['Module']['config']           = "Configuration";
$lang['Module']['login']            = "Connexion";
$lang['Module']['logout']           = "D�connexion";
$lang['Module']['logout_2']         = "D�connexion [%s]";
$lang['Module']['send']             = "Envoi";
$lang['Module']['users']            = "Utilisateurs";
$lang['Module']['subscribers']      = "Inscrits";
$lang['Module']['list']             = "Listes";
$lang['Module']['log']              = "Archives";
$lang['Module']['tools']            = "Outils";
$lang['Module']['stats']            = "Statistiques";
$lang['Module']['editprofile']      = "�diter votre profil";


//
// Texte des divers boutons
//
$lang['Button']['valid']            = "Valider";
$lang['Button']['reset']            = "R�initialiser";
$lang['Button']['go']               = "Aller";
$lang['Button']['edit']             = "Modifier";
$lang['Button']['delete']           = "Supprimer";
$lang['Button']['cancel']           = "Annuler";
$lang['Button']['purge']            = "Purger";
$lang['Button']['classer']          = "Classer";
$lang['Button']['search']           = "Chercher";
$lang['Button']['save']             = "Sauvegarder";
$lang['Button']['send']             = "Envoyer";
$lang['Button']['preview']          = "Pr�visualiser";
$lang['Button']['add_file']         = "Joindre un fichier";
$lang['Button']['del_file']         = "Supprimer les fichiers s�lectionn�s";

$lang['Button']['del_abo']          = "Supprimer les abonn�s s�lectionn�s";
$lang['Button']['del_logs']         = "Supprimer les newsletters s�lectionn�s";
$lang['Button']['del_account']      = "Supprimer ce compte";
$lang['Button']['links']            = "Placer le lien de d�sinscription";
$lang['Button']['dl']               = "T�l�charger";
$lang['Button']['conf']             = "Confirmer";


//
// Diff�rents messages d'information et d'erreur
//
$lang['Message']['Subscribe_1']             = "Inscription r�ussie !\nVous allez recevoir un email de confirmation.\nAttention, le lien de confirmation contenu dans l'email sera valide pendant %d jours !\nPass� ce d�lai, il vous faudra vous r�inscrire.";
$lang['Message']['Subscribe_2']             = "Inscription r�ussie !";
$lang['Message']['Confirm_ok']              = "Votre inscription a �t� confirm�e !";
$lang['Message']['Confirm_double']          = "Vous avez d�j� confirm� votre inscription";
$lang['Message']['Unsubscribe_1']           = "Ok, vous allez recevoir un email qui vous permettra de confirmer votre choix";
$lang['Message']['Unsubscribe_2']           = "Vous n'�tes d�sormais plus inscrit � cette liste de diffusion";
$lang['Message']['Unsubscribe_3']           = "Votre email a bien �t� retir� de notre base de donn�es";
$lang['Message']['Success_setformat']       = "Le changement de format a �t� effectu� avec succ�s";
$lang['Message']['Invalid_email']           = "L'adresse email que vous avez indiqu�e n'est pas valide";
$lang['Message']['Unrecognized_email']      = "Domaine inconnu ou compte non reconnu par le serveur (%s)";
$lang['Message']['Unknown_email']           = "Email inconnu";
$lang['Message']['Email_banned']            = "Cet email ou ce type d'email a �t� banni";
$lang['Message']['Allready_reg']            = "Vous �tes d�j� inscrit !";
$lang['Message']['Reg_not_confirmed']       = "Vous �tes d�j� inscrit mais n'avez pas encore confirm� votre inscription.\nVous allez recevoir un nouvel email de confirmation.\nAttention, le lien de confirmation contenu dans l'email sera valide pendant %d jours !\nPass� ce d�lai, il vous faudra vous r�inscrire.";
$lang['Message']['Reg_not_confirmed2']      = "Vous �tes d�j� inscrit mais n'avez pas encore confirm� votre inscription";
$lang['Message']['Allready_confirm']        = "Vous avez d�j� confirm� votre inscription !";
$lang['Message']['Unknown_list']            = "Liste inconnue";
$lang['Message']['Failed_sending']          = "L'email n'a pu �tre envoy� !";
$lang['Message']['Inactive_format']         = "Impossible de changer de format";
$lang['Message']['Invalid_date']            = "D�sol�, la date de confirmation est d�pass�e";
$lang['Message']['Invalid_code']            = "Code invalide !";
$lang['Message']['Invalid_email2']          = "Adresse email invalide !";
$lang['Message']['Failed_sending2']         = "L'email n'a pu �tre envoy� ! %s";

$lang['Message']['Success_export']          = "L'exportation des emails a �t� effectu�e avec succ�s. \nVous trouverez le fichier de sauvegarde dans le r�pertoire des fichiers temporaires du script (Pensez � le supprimer apr�s l'avoir r�cup�r� !)";
$lang['Message']['Success_import']          = "Les emails ont �t� import�s avec succ�s";
$lang['Message']['Success_import2']         = "L'importation s'est effectu�e avec succ�s mais certains emails ont �t� refus�s";
$lang['Message']['Success_import3']         = "L'importation s'est effectu�e avec succ�s mais certains emails ont �t� refus�s. \nCliquez %sici%s pour t�l�charger le rapport (N'oubliez pas de supprimer le fichier du serveur par la suite)";
$lang['Message']['Success_import4_0']       = "Aucun email n'a �t� import�";
$lang['Message']['Success_import4_1']       = "%d email a �t� import� avec succ�s";
$lang['Message']['Success_import4_n']       = "%d emails ont �t� import�s avec succ�s";
$lang['Message']['Success_modif']           = "Les modifications ont �t� effectu�es avec succ�s";
$lang['Message']['Success_backup']          = "La sauvegarde des tables a �t� effectu�e avec succ�s. \nVous trouverez le fichier de sauvegarde dans le r�pertoire des fichiers temporaires du script (Pensez � le supprimer apr�s l'avoir r�cup�r� !)";
$lang['Message']['Success_restore']         = "La restauration des donn�es a �t� effectu�e avec succ�s";
$lang['Message']['Success_logout']          = "Vous avez �t� d�connect� de l'administration";
$lang['Message']['Success_purge']           = "La purge a �t� effectu�e avec succ�s (%d abonn�(s) supprim�(s))";
$lang['Message']['Success_send']            = "L'envoi partiel a �t� effectu� avec succ�s � <b>%d</b> abonn�s.\nLa lettre de diffusion a �t� envoy�e jusqu'� pr�sent � <b>%d</b> abonn�s sur un total de <b>%d</b>";
$lang['Message']['Success_send_finish']     = "Envoi termin� avec succ�s.\nCette lettre de diffusion a �t� envoy�e � un total de <b>%d</b> abonn�s";
$lang['Message']['Success_operation']       = "L'op�ration a �t� effectu�e avec succ�s";

$lang['Message']['Profile_updated']         = "Le profil a �t� mis � jour avec succ�s";
$lang['Message']['Admin_added']             = "L'utilisateur a �t� ajout� avec succ�s, il va recevoir par email ses identifiants de connexion";
$lang['Message']['Admin_deleted']           = "L'utilisateur a �t� supprim� avec succ�s";
$lang['Message']['liste_created']           = "La nouvelle liste de diffusion a �t� cr��e avec succ�s";
$lang['Message']['liste_edited']            = "La liste de diffusion a �t� modifi�e avec succ�s";
$lang['Message']['Liste_del_all']           = "La liste a �t� supprim�e avec succ�s, ainsi que les abonn�s et newsletters qui y �taient rattach�s";
$lang['Message']['Liste_del_move']          = "La liste a �t� supprim�e avec succ�s.\nLes abonn�s et newsletters qui y �taient rattach�s ont �t� d�plac�s vers la liste s�lectionn�e";
$lang['Message']['logs_deleted']            = "Les newsletters ont �t� supprim�es avec succ�s";
$lang['Message']['log_deleted']             = "La newsletter a �t� supprim�e avec succ�s";
$lang['Message']['log_saved']               = "La newsletter a �t� sauvegard�e avec succ�s";
$lang['Message']['log_ready']               = "La newsletter a �t� sauvegard�e avec succ�s et est pr�te � �tre envoy�e";
$lang['Message']['abo_deleted']             = "Les abonn�s ont �t� supprim�s avec succ�s";
$lang['Message']['Send_canceled']           = "Op�ration effectu�e. Tous les envois restants pour cette newsletter ont �t� annul�s";
$lang['Message']['List_is_busy']            = "Une op�ration est en cours sur cette liste. Veuillez patienter quelques instants et retenter la manipulation";

$lang['Message']['Not_authorized']          = "Vous n'avez pas les permissions suffisantes pour acc�der � cette page ou ex�cuter cette action";
$lang['Message']['Not_auth_view']           = "Vous n'�tes pas autoris� � visualiser cette liste de diffusion";
$lang['Message']['Not_auth_edit']           = "Vous n'�tes pas autoris� � effectuer des modifications sur cette liste de diffusion";
$lang['Message']['Not_auth_del']            = "Vous n'�tes pas autoris� � effectuer des suppressions sur cette liste de diffusion";
$lang['Message']['Not_auth_send']           = "Vous n'�tes pas autoris� � effectuer des envois � cette liste de diffusion";
$lang['Message']['Not_auth_import']         = "Vous n'�tes pas autoris� � importer des adresses emails dans cette liste de diffusion";
$lang['Message']['Not_auth_export']         = "Vous n'�tes pas autoris� � exporter des adresses emails de cette liste de diffusion";
$lang['Message']['Not_auth_ban']            = "Vous n'�tes pas autoris� � effectuer des modifications sur la liste de bannissement de cette liste de diffusion";
$lang['Message']['Not_auth_attach']         = "Vous n'�tes pas autoris� � joindre des fichiers ou � voir les fichiers joints de cette liste de diffusion";

$lang['Message']['Error_login']             = "Login ou mot de passe incorrect !";
$lang['Message']['Error_sendpass']          = "Login ou email incorrect !";
$lang['Message']['Bad_confirm_pass']        = "Nouveau mot de passe et confirmation de mot de passe sont diff�rents";
$lang['Message']['bad_ftp_param']           = "La connexion au serveur ftp n'a pu �tre �tablie, v�rifiez vos param�tres \n(%s)";
$lang['Message']['bad_smtp_param']          = "La connexion au serveur smtp n'a pu �tre �tablie, v�rifiez vos param�tres \n(%s)";
$lang['Message']['bad_pop_param']           = "La connexion au serveur pop n'a pu �tre �tablie, v�rifiez vos param�tres \n(%s)";
$lang['Message']['Alphanum_pass']           = "Le mot de passe doit �tre compos� de 4 � 30 caract�res qui soient alphanum�riques, du tiret (-) et/ou de _";
$lang['Message']['Invalid_session']         = "Session non valide !";
$lang['Message']['fields_empty']            = "Certains champs obligatoires ne sont pas remplis";
$lang['Message']['Owner_account']           = "Vous ne pouvez pas supprimer votre propre compte !";
$lang['Message']['Invalid_login']           = "Ce pseudo n'est pas valide, le pseudo doit faire entre 2 et 30 caract�res";
$lang['Message']['Double_login']            = "Un utilisateur utilise d�j� ce pseudo";
$lang['Message']['No_liste_exists']         = "Aucune liste n'est disponible";
$lang['Message']['No_liste_id']             = "Aucune liste de diffusion n'a �t� s�lectionn�e";
$lang['Message']['No_log_id']               = "Aucune newsletter n'a �t� s�lectionn�e";
$lang['Message']['log_not_exists']          = "Cette newsletter n'existe pas !";
$lang['Message']['No_log_to_load']          = "Il n'y a actuellement aucune newsletter � charger";
$lang['Message']['No_log_to_send']          = "Il n'y a actuellement aucun envoi � reprendre";
$lang['Message']['No_abo_id']               = "Aucun abonn� n'a �t� s�lectionn�";
$lang['Message']['No_abo_email']            = "Aucune de ces adresses email n'est pr�sente dans cette liste de diffusion";
$lang['Message']['abo_not_exists']          = "Cet abonn� n'existe pas !";
$lang['Message']['Failed_open_file']        = "Impossible d'ouvrir le fichier re�u";
$lang['Message']['File_not_exists']         = "Le fichier %s n'existe pas ou n'est pas accessible en lecture";
$lang['Message']['Bad_file_type']           = "Le type de fichier re�u a �t� interdit ou n'est pas valide";
$lang['Message']['Error_local']             = "Aucun fichier trouv� au chemin %s";
$lang['Message']['No_data_received']        = "Aucune donn�e n'a �t� r�ceptionn�e";
$lang['Message']['Stats_disabled']          = "Le module de statistiques a �t� d�sactiv�";
$lang['Message']['No_gd_lib']               = "Ce module requiert la librairie GD, or celle-ci ne semble pas pr�sente sur le serveur";
$lang['Message']['No_subscribers']          = "Vous ne pouvez pas envoyer de newsletter � cette liste car elle ne compte pas encore d'abonn�";
$lang['Message']['Unknown_engine']          = "Aucun moteur d'envoi sp�cifi� !";
$lang['Message']['No_log_found']            = "Aucune newsletter pr�te � �tre envoy�e n'a �t� trouv�e";
$lang['Message']['Invalid_url']             = "L'url donn�e n'est pas valide";
$lang['Message']['Unaccess_host']           = "L'h�te %s semble inaccessible actuellement";
$lang['Message']['Not_found_at_url']        = "Le fichier ne semble pas pr�sent � l'url indiqu�e";
$lang['Message']['No_data_at_url']          = "Aucune donn�e disponible sur le fichier";
$lang['Message']['Error_load_url']          = "Erreur dans le chargement de l'url \"%1\$s\" (%2\$s)";
$lang['Message']['No_form_url']             = "Vous n'avez pas sp�cifi� l'adresse du formulaire dans la %sconfiguration de votre liste%s.\nVous devez corriger cela avant de pouvoir commencer l'envoi.";

$lang['Message']['Cannot_create_dir']       = "Impossible de cr�er le r�pertoire %s";
$lang['Message']['Dir_not_writable']        = "Le r�pertoire <samp>%s</samp> n'existe pas ou n'est pas accessible en �criture";
$lang['Message']['sql_file_not_readable']   = "Les fichiers sql ne sont pas accessibles en lecture ! (setup/schemas/)";

$lang['Message']['Ftp_unable_connect']      = "Impossible de se connecter au serveur ftp";
$lang['Message']['Ftp_error_login']         = "L'authentification aupr�s du serveur ftp a �chou�";
$lang['Message']['Ftp_error_mode']          = "Impossible de changer le mode du serveur";
$lang['Message']['Ftp_error_path']          = "Impossible d'acc�der au r�pertoire sp�cifi�";
$lang['Message']['Ftp_error_put']           = "Impossible d'uploader le fichier sur le serveur ftp";
$lang['Message']['Ftp_error_get']           = "Impossible de r�cup�rer le fichier du serveur ftp";
$lang['Message']['Ftp_error_del']           = "Impossible de supprimer le fichier du serveur ftp";

$lang['Message']['Upload_error_1']          = "Le fichier exc�de le poids autoris� par la directive upload_max_filesize de php.ini";
$lang['Message']['Upload_error_2']          = "Le fichier exc�de le poids autoris� par le champ MAX_FILE_SIZE";
$lang['Message']['Upload_error_3']          = "Le fichier n'a �t� upload� que partiellement";
$lang['Message']['Upload_error_4']          = "Aucun fichier n'a �t� upload�";
$lang['Message']['Upload_error_5']          = "Une erreur inconnue est survenue, le fichier n'a pu �tre upload�";
$lang['Message']['Upload_error_6']          = "Le r�pertoire des fichiers temporaires est inaccessible ou n'existe pas";
$lang['Message']['Upload_error_7']          = "�chec de l'�criture du fichier sur le disque";
$lang['Message']['Invalid_filename']        = "Nom de fichier non valide";
$lang['Message']['Invalid_action']          = "Action non valide";
$lang['Message']['Invalid_ext']             = "Cette extension de fichier a �t� interdite";
$lang['Message']['weight_too_big']          = "Le poids total des fichiers joints exc�de le maximum autoris�, il ne vous reste que %s de libre";

$lang['Message']['Compress_unsupported']    = "Format de compression non support�";
$lang['Message']['Database_unsupported']    = "Cette base de donn�es n'est pas support�e par le syst�me de sauvegarde/restauration";

$lang['Message']['Profil_cp_disabled']      = "Le panneau de gestion de compte est actuellement d�sactiv�";
$lang['Message']['Inactive_account']        = "Votre compte est actuellement inactif, vous avez d� recevoir un email pour l'activer.";
$lang['Message']['IDs_sended']              = "Vos identifiants vous ont �t� envoy�s par email";
$lang['Message']['Logs_sent']               = "Les newsletters s�lectionn�es ont �t� envoy�es � votre adresse: %s";
$lang['Message']['Archive_class_needed']    = "Le module d'export n�cessite la pr�sence du paquet <abbr title=\"PHP Extension and Application Repository\" xml:lang=\"en\" lang=\"en\">PEAR</abbr> <q>%s</q>. Consultez la documentation pour plus de d�tails.";
$lang['Message']['Chdir_error']             = "Impossible de configurer le r�pertoire courant sur %s (erreur avec chdir())";
$lang['Message']['Twice_sending']           = "Une newsletter est d�j� en cours d'envoi pour cette liste. Terminez ou annulez cet envoi avant d'en commencer un autre.";


//
// Divers
//
$lang['Subscribe']                  = "Inscription";
$lang['Unsubscribe']                = "D�sinscription";
$lang['Setformat']                  = "Changer de format";
$lang['Email_address']              = "Adresse email";
$lang['Format']                     = "Format";
$lang['Button_valid']               = "Valider";
$lang['Diff_list']                  = "Listes de diffusion";
$lang['Start']                      = "D�but";
$lang['End']                        = "Fin";
$lang['Prev']                       = "Pr�c�dent";
$lang['Next']                       = "Suivant";
$lang['First_page']                 = "Premi�re page";
$lang['Prev_page']                  = "Page pr�c�dente";
$lang['Next_page']                  = "Page suivante";
$lang['Last_page']                  = "Derni�re page";
$lang['Yes']                        = "oui";
$lang['No']                         = "non";
$lang['Login']                      = "Login d'acc�s";
$lang['Password']                   = "Mot de passe d'acc�s";
$lang['Not_available']              = "Non disponible";
$lang['Seconds']                    = "secondes";
$lang['Days']                       = "jours";
$lang['Other']                      = "Autres";
$lang['Unknown']                    = "Inconnu";
$lang['Choice_liste']               = "S�lectionnez une liste";
$lang['View_liste']                 = "G�rer une liste";
$lang['Admin']                      = "Administrateur";
$lang['User']                       = "Utilisateur";
$lang['Page_of']                    = "Page <b>%d</b> sur <b>%d</b>";
$lang['Classement']                 = "Classer par";
$lang['By_subject']                 = "par sujet";
$lang['By_date']                    = "par date";
$lang['By_email']                   = "par email";
$lang['By_format']                  = "par format";
$lang['By_asc']                     = "croissant";
$lang['By_desc']                    = "d�croissant";
$lang['Filename']                   = "Nom du fichier";
$lang['Filesize']                   = "Taille du fichier";
$lang['No_data']                    = "Non fourni";
$lang['MO']                         = "Mo";
$lang['KO']                         = "Ko";
$lang['Octets']                     = "Octets";
$lang['Wait_loading']               = "Veuillez patienter pendant le chargement de la page";
$lang['Show']                       = "Visualiser";
$lang['View']                       = "Voir";
$lang['Edit']                       = "�diter";
$lang['Import']                     = "Importer";
$lang['Export']                     = "Exporter";
$lang['Ban']                        = "Bannir";
$lang['Attach']                     = "Attacher";
$lang['Autologin']                  = "Se connecter automatiquement";
$lang['Faq']                        = "FAQ du script";
$lang['Author_note']                = "Notes de l'auteur";
$lang['Page_loading']               = "Veuillez patienter pendant le chargement de la page";
$lang['Label_link']                 = "Se d�sinscrire";
$lang['Account_login']              = "Entrez l'adresse email de votre compte";
$lang['Account_pass']               = "Mot de passe ou code de votre compte";
$lang['Maximum_size']               = "Taille maximum: %s";
$lang['Lost_password']              = "Mot de passe perdu ?";
$lang['Name']                       = "Nom";
$lang['Value']                      = "Valeur";

$lang['Click_return_index']         = "Cliquez %sici%s pour retourner sur l'accueil";
$lang['Click_return_back']          = "Cliquez %sici%s pour retourner sur la page pr�c�dente";
$lang['Click_return_form']          = "Cliquez %sici%s pour retourner au formulaire";
$lang['Click_start_send']           = "Cliquez %sici%s si vous souhaitez d�marrer l'envoi maintenant";
$lang['Click_resend_auto']          = "Cliquez %sici%s pour continuer l'envoi de fa�on automatique";
$lang['Click_resend_manuel']        = "Cliquez %sici%s pour envoyer un autre flot d'emails";

//
// Sujets de divers emails envoy�s
//
$lang['Subject_email']['Subscribe'] = "Inscription � la newsletter de %s";
$lang['Subject_email']['Unsubscribe_1'] = "Confirmation de d�sinscription";
$lang['Subject_email']['New_subscribe'] = "Nouvel inscrit � la newsletter";
$lang['Subject_email']['Unsubscribe_2'] = "D�sinscription de la newsletter";
$lang['Subject_email']['New_admin'] = "Administration de la newsletter de %s";
$lang['Subject_email']['New_pass']  = "Votre nouveau mot de passe";
$lang['Subject_email']['Sendkey']   = "Les identifiants de votre compte";


//
// Panneau de gestion de compte (profil_cp.php)
//
$lang['Welcome_profil_cp']          = "Bienvenue sur le panneau de gestion de votre compte.\nVous pouvez ici modifier votre profil abonn� et consulter les archives.";
$lang['Explain']['editprofile']     = "Ici, vous avez la possibilit� de modifier les donn�es de votre compte.\nVous pouvez renseigner votre pr�nom ou pseudo pour personnaliser les newsletters que vous recevrez (selon les r�glages de l'administrateur). Vous pouvez �galement mettre un mot de passe � votre compte, ce qui sera plus simple � taper que le code de votre compte.";
$lang['Explain']['sendkey']         = "Si vous avez perdu les identifiants de votre compte, vous pouvez demander � ce qu'ils vous soient renvoy�s par email";
$lang['Explain']['archives']        = "Vous pouvez, � partir de cette page, demander � recevoir les pr�c�dentes newsletters envoy�es aux listes de diffusion auxquelles vous �tes inscrit.\nAttention, pour chaque newsletter s�lectionn�e, vous recevrez un email.";


//
// Page d'accueil
//
$lang['Explain']['accueil']         = "Bienvenue sur l'administration de Wanewsletter, nous vous remercions d'avoir choisi Wanewsletter comme solution de newsletter/mailing liste.\n L'administration vous permet de contr�ler vos listes de diffusion de fa�on tr�s simple. \nVous pouvez � tout moment retourner sur cette page en cliquant sur le logo Wanewsletter en haut � gauche de l'�cran.";
$lang['Registered_subscribers']     = "Il y a au total <b>%1\$d</b> inscrits, soit <b>%2\$s</b> nouveaux inscrits par jour";
$lang['Registered_subscriber']      = "Il y a au total <b>1</b> inscrit, soit <b>%s</b> nouveaux inscrits par jour";
$lang['No_registered_subscriber']   = "Il n'y a aucun inscrit pour l'instant";
$lang['Tmp_subscribers']            = "Il y a <b>%d</b> personnes n'ayant pas confirm� leur inscription";
$lang['Tmp_subscriber']             = "Il y a <b>1</b> personne n'ayant pas confirm� son inscription";
$lang['No_tmp_subscriber']          = "Il n'y a actuellement aucune inscription non confirm�e";
$lang['Last_newsletter']            = "Derni�re newsletter envoy�e le <b>%s</b>";
$lang['Total_newsletters']          = "Un total de <b>%1\$d</b> newsletters ont �t� envoy�es, soit <b>%2\$s</b> newsletters par mois";
$lang['Total_newsletter']           = "Un total de <b>1</b> newsletter a �t� envoy�e, soit <b>%s</b> newsletters par mois";
$lang['No_newsletter_sended']       = "Aucune newsletter n'a encore �t� envoy�e";
$lang['Dbsize']                     = "Taille de la base de donn�es (tables du script)";
$lang['Total_Filesize']             = "Espace disque occup� par les fichiers (pi�ces jointes et statistiques)";


//
// Page : Configuration
//
$lang['Explain']['config']          = "Le formulaire ci-dessous vous permet de configurer tous les aspects du script";
$lang['Explain']['config_cookies']  = "Ces param�tres vous permettent de r�gler les cookies utilis�s par le script. \nSi vous n'�tes pas s�r de vous, laissez les param�tres par d�faut";
$lang['Explain']['config_files']    = "Vous avez la possibilit� de joindre des fichiers � vos envois de newsletters. \nPour ce faire, le script offre deux options. Le plus simple est de stocker les fichiers sur le serveur, dans le r�pertoire d�fini comme r�pertoire de stockage (le r�pertoire en question doit �tre accessible en �criture). \nSi, pour une raison ou une autre, cela n'est pas rendu possible sur votre serveur, le script a la possibilit� de stocker les fichiers sur un serveur <acronym title=\"File Transfert Protocol\" xml:lang=\"en\">ftp</acronym>.\n Vous devez alors entrer les param�tres d'acc�s au serveur ftp en question.";
$lang['Explain']['config_email']    = "Ces param�tres vous permettent de configurer les m�thodes d'envois d'emails � utiliser. \nLe premier moteur prend comme destinataire l'adresse email de la newsletter elle-m�me, avec les destinataires en copie cach�e. Le deuxi�me moteur est un peu plus lourd mais envoie un email pour chaque abonn� (ce dernier sera automatiquement utilis� si l'h�bergeur est <strong>Online</strong>).\n Si, pour une raison quelconque, votre serveur ne dispose pas de fonction mail() ou d�riv�, vous avez la possibilit� d'utiliser un serveur <acronym title=\"Simple Mail Transfert Protocol\" xml:lang=\"en\">smtp</acronym> pr�cis en indiquant les param�tres d'acc�s au script. \nAttention cependant, certaines restrictions peuvent survenir dans ce cas pr�cis. R�f�rez vous, pour plus de pr�cisions, � la %sfaq du script%s.";
$lang['Explain']['config_stats']    = "Le script dispose d'un petit module de statistique. Celui ci demande que la librairie GD soit install�e sur votre serveur pour fonctionner. \nSi Si vous ne souhaitez pas utiliser cette fonctionnalit�, il est recommand� de d�sactiver le module de statistiques pour �viter des traitement de donn�es superflus par le script.";

$lang['Default_lang']               = "S�lectionnez la langue par d�faut";
$lang['Sitename']                   = "Nom de votre site";
$lang['Urlsite']                    = "URL du site";
$lang['Urlsite_note']               = "(ex: http://www.monsite.com)";
$lang['Urlscript']                  = "URL du script";
$lang['Urlscript_note']             = "(ex: /repertoire/)";
$lang['Sig_email']                  = "Signature � ajouter � la fin des emails";
$lang['Sig_email_note']             = "(emails d'inscription et de confirmation)";
$lang['Dateformat']                 = "Format des dates";
$lang['Fct_date']                   = "Voir la fonction %sdate()%s";
$lang['Enable_profil_cp']           = "Activer le panneau de gestion de compte pour les abonn�s";
$lang['Cookie_name']                = "Nom du cookie";
$lang['Cookie_path']                = "Chemin du cookie";
$lang['Session_length']             = "Dur�e d'une session sur l'administration";
$lang['Upload_path']                = "R�pertoire de stockage des fichiers joints";
$lang['Max_filesize']               = "Poids total des fichiers joints � une newsletter";
$lang['Max_filesize_note']          = "(somme de la taille en octet des fichiers joints)";
$lang['Use_ftp']                    = "Utilisation d'un serveur ftp pour stocker les fichiers joints";
$lang['Ftp_server']                 = "Nom du serveur ftp";
$lang['Ftp_server_note']            = "(nom sans le ftp:// initial, ou adresse ip)";
$lang['Ftp_port']                   = "Port de connexion";
$lang['Ftp_port_note']              = "La valeur par d�faut conviendra la plupart du temps";
$lang['Ftp_pasv']                   = "Serveur ftp en mode passif";
$lang['Ftp_pasv_note']              = "(Mode actif ou passif)";
$lang['Ftp_path']                   = "Chemin vers le r�pertoire de stockage des fichiers";
$lang['Ftp_user']                   = "Nom d'utilisateur";
$lang['Ftp_pass']                   = "Mot de passe";
$lang['Check_email']                = "V�rification approfondie des emails � l'inscription";
$lang['Check_email_note']           = "(V�rifie l'existence du domaine et du compte associ�. Voir %sla faq%s)";
$lang['Choice_engine_send']         = "M�thode d'envoi � utiliser";
$lang['With_engine_bcc']            = "Un envoi avec les destinataires en copie cach�e";
$lang['With_engine_uniq']           = "Un envoi pour chaque abonn�";
$lang['Emails_paquet']              = "Nombre d'emails par flot d'envoi";
$lang['Emails_paquet_note']         = "Laissez � 0 pour tout envoyer en un flot";
$lang['Use_smtp']                   = "Utilisation d'un serveur <acronym title=\"Simple Mail Transfert Protocol\" xml:lang=\"en\">smtp</acronym> pour les envois";
$lang['Use_smtp_note']              = "Seulement si votre serveur ne dispose d'aucune fonction d'envoi d'emails ou que vous d�sirez utiliser un serveur SMTP sp�cifique !";
$lang['Smtp_server']                = "Adresse du serveur smtp";
$lang['Smtp_port']                  = "Port de connexion";
$lang['Smtp_port_note']             = "La valeur par d�faut conviendra dans la grande majorit� des cas.";
$lang['Smtp_user']                  = "Votre login smtp";
$lang['Smtp_pass']                  = "Votre mot de passe smtp";
$lang['Auth_smtp_note']             = "Seulement si votre serveur smtp requiert une authentification !";
$lang['Disable_stats']              = "D�sactiver le module de statistiques";


//
// Page : Gestion et permissions des admins
//
$lang['Explain']['admin']           = "Vous pouvez, � partir de ce panneau, g�rer votre profil.\nVous pouvez �galement, si vous en avez les droits, g�rer les autres administrateurs, leur profil, leurs droits, ajouter des administrateurs, en retirer...";
$lang['Click_return_profile']       = "Cliquez %sici%s pour retourner au panneau de gestion des profils";
$lang['Add_user']                   = "Ajouter un utilisateur";
$lang['Del_user']                   = "Supprimer cet utilisateur";
$lang['Del_note']                   = "Attention, cette op�ration est irr�versible";
$lang['Email_new_subscribe']        = "�tre pr�venu par email des nouvelles inscriptions";
$lang['Email_unsubscribe']          = "�tre pr�venu par email des d�sinscriptions";
$lang['New_pass']                   = "Nouveau mot de passe";
$lang['Conf_pass']                  = "Confirmez le mot de passe";
$lang['Note_pass']                  = "seulement si vous changez votre mot de passe";
$lang['Choice_user']                = "S�lectionnez un utilisateur";
$lang['View_profile']               = "Voir le profil de";
$lang['Confirm_del_user']           = "Vous confirmez la suppression de l'utilisateur s�lectionn� ?";
$lang['Login_new_user']             = "Son login";
$lang['Email_new_user']             = "Son email";
$lang['Email_note']                 = "(O� il recevra son mot de passe)";
$lang['User_level']                 = "Niveau de cet utilisateur";
$lang['Liste_name2']                = "Nom de la liste";


//
// Page : Gestion des listes
//
$lang['Explain']['liste']           = "Ici, vous pouvez ajouter, modifier, supprimer des listes de diffusion, et r�gler le syst�me de purge.";
$lang['Explain']['purge']           = "Le syst�me de purge vous permet de nettoyer automatiquement la table des abonn�s en supprimant les comptes non activ�s et dont la date de validit� est d�pass�e.\nCette option est inutile si votre liste ne demande pas de confirmation d'inscription";
$lang['Explain']['cron']            = "Si vous voulez utilisez l'option de gestion des inscription avec cron, remplissez les champs ci dessous (voir %sla faq%s)";
$lang['Click_create_liste']         = "Cliquez %sici%s pour cr�er une liste de diffusion";
$lang['Click_return_liste']         = "Cliquez %sici%s pour retourner aux informations sur cette liste";
$lang['ID_list']                    = "ID de la liste";
$lang['Liste_name']                 = "Nom de la liste de diffusion";
$lang['Liste_public']               = "Liste publique";
$lang['Liste_startdate']            = "Date de cr�ation de cette liste";
$lang['Auth_format']                = "Format autoris�";
$lang['Sender_email']               = "Adresse email d'envoi";
$lang['Return_email']               = "Adresse email pour les retours d'erreurs";
$lang['Confirm_subscribe']          = "Demande de confirmation";
$lang['Confirm_always']             = "Toujours";
$lang['Confirm_once']               = "� la premi�re inscription";
$lang['Limite_validate']            = "Limite de validit� pour la confirmation d'inscription";
$lang['Note_validate']              = "(inutile si on ne demande pas de confirmation)";
$lang['Enable_purge']               = "Activer la purge automatique";
$lang['Purge_freq']                 = "Fr�quence des purges";
$lang['Total_newsletter_list']      = "Nombre total de newsletters envoy�es";
$lang['Reg_subscribers_list']       = "Nombre d'inscrits � cette liste";
$lang['Tmp_subscribers_list']       = "Nombre d'inscriptions non confirm�es";
$lang['Last_newsletter2']           = "Derni�re newsletter envoy�e le";
$lang['Form_url']                   = "URL absolu de la page o� se trouve le formulaire";
$lang['Create_liste']               = "Cr�er une liste";
$lang['Edit_liste']                 = "Modifier cette liste";
$lang['Delete_liste']               = "Supprimer cette liste";
$lang['Invalid_liste_name']         = "Le nom de votre liste de diffusion doit faire entre 3 et 30 caract�res";
$lang['Unknown_format']             = "Format demand� inconnu";
$lang['Move_abo_logs']              = "Que souhaitez-vous faire des abonn�s et newsletters rattach�s � cette liste ?";
$lang['Delete_all']                 = "�tes-vous s�r de vouloir supprimer cette liste, ainsi que les abonn�s et newsletters qui y sont rattach�s ?";
$lang['Move_to_liste']              = "D�placer les abonn�s et newsletters vers";
$lang['Delete_abo_logs']            = "Ou les retirer de la base de donn�es";
$lang['Use_cron']                   = "Utiliser l'option cron";
$lang['Pop_server']                 = "Nom ou IP du serveur POP";
$lang['Pop_port']                   = "Port de connexion";
$lang['Pop_port_note']              = "La valeur par d�faut conviendra dans la grande majorit� des cas.";
$lang['Pop_user']                   = "Login de connexion";
$lang['Pop_pass']                   = "Mot de passe de connexion";
$lang['Liste_alias']                = "Alias de la liste (si n�cessaire)";


//
// Page : Gestion des logs/archives
//
$lang['Explain']['logs']            = "Ici, vous pouvez visualiser et supprimer les newsletter pr�c�demment envoy�es";
$lang['Click_return_logs']          = "Cliquez %sici%s pour retourner � la liste des newsletters";
$lang['Log_subject']                = "Sujet de la newsletter";
$lang['Log_date']                   = "Date d'envoi";
$lang['Log_numdest']                = "Nombre de destinataires";
$lang['Delete_logs']                = "�tes-vous s�r de vouloir supprimer les newsletters s�lectionn�s ?";
$lang['Delete_log']                 = "�tes-vous s�r de vouloir supprimer cette newsletter ?";
$lang['No_log_sended']              = "Aucune newsletter n'a �t� envoy�e � cette liste";
$lang['Joined_files']               = "Cette archive a %d fichiers joints";
$lang['Joined_file']                = "Cette archive a un fichier joint";
$lang['Export_nl']                  = "Exporter cette newsletter";


//
// Page : Gestion des abonn�s
//
$lang['Explain']['abo']             = "Ici, vous pouvez voir, modifier et supprimer les comptes des personnes qui se sont inscrites � vos listes de diffusion";
$lang['Click_return_abo']           = "Cliquez %sici%s pour retourner � la liste des abonn�s";
$lang['Click_return_abo_profile']   = "Cliquez %sici%s pour retourner au profil de l'abonn�";
$lang['Delete_abo']                 = "�tes-vous s�r de vouloir supprimer les abonn�s s�lectionn�s ?";
$lang['No_abo_in_list']             = "Il n'y a pas encore d'abonn� � cette liste de diffusion";
$lang['Susbcribed_date']            = "Date d'inscription";
$lang['Search_abo']                 = "Faire une recherche par mots cl�s";
$lang['Search_abo_note']            = "(vous pouvez utiliser * comme joker)";
$lang['Days_interval']              = "Inscrit les %d derniers jours";
$lang['All_abo']                    = "Tous les abonn�s";
$lang['Inactive_account']           = "Les comptes non activ�s";
$lang['No_search_result']           = "La recherche n'a retourn� aucun r�sultat";
$lang['Abo_pseudo']                 = "Pseudo de l'abonn�";
$lang['Liste_to_register']          = "Cet abonn� est inscrit aux listes suivantes";
$lang['Fast_deletion']              = "Suppression rapide";
$lang['Fast_deletion_note']         = "Entrez une ou plusieurs adresses emails, s�par�es par une virgule, et elles seront supprim�es de la liste de diffusion";
$lang['Choice_Format']              = "format choisi";
$lang['Warning_email_diff']         = "Attention, vous allez modifier l'adresse email de cet abonn�\nSouhaitez-vous continuer ?";
$lang['Goto_list']                  = "Retour � la liste des abonn�s";
$lang['View_account']               = "Voir ce compte";
$lang['Edit_account']               = "Modifier ce compte";
$lang['TagsList']                   = "Liste des tags";


//
// Page : Outils du script
//
$lang['Explain']['tools']           = "Vous avez � votre disposition plusieurs outils pour g�rer au mieux vos listes de diffusion";
$lang['Explain']['export']          = "Vous pouvez ici exporter les adresses email d'une liste donn�e, et pour le format donn� (non pris en compte si la liste n'est pas multi-format).\nSi vous n'indiquez aucun caract�re de s�paration, le fichier contiendra un email par ligne";
$lang['Explain']['import']          = "Si vous voulez ajouter plusieurs adresses email, mettez un email par ligne ou s�parez les par un caract�re tel que ; et indiquez le dans le champ en question.\nSi votre serveur l'autorise, vous pouvez uploader un fichier contenant la liste des emails, indiquez �galement le caract�re de s�paration (sauf si un email par ligne). Dans le cas contraire, vous avez toutefois la possibilit� de sp�cifier le chemin vers un fichier pr�alablement upload� via ftp (chemin relatif � partir de la racine du script) .\nSi le fichier est compress� dans un format support� par le serveur et le script, il sera automatiquement d�compress�.\n(une limite de %s emails a �t� fix�e; Voyez la %sfaq du script%s pour plus de d�tails)";
$lang['Explain']['ban']             = "Vous pouvez bannir un email entier, de type user@domain.com, ou un fragment d'email en utilisant * comme joker\n\n <u>Exemples</u> :\n <ul><li> toto@titi.com, l'utilisateur ayant l'email toto@titi.com ne pourra s'inscrire</li><li> *.fr.st; Tous les emails ayant pour extension .fr.st ne pourront s'inscrire</li><li> *@domaine.net, tous les emails ayant pour extension @domaine.net ne pourront s'inscrire</li><li> saddam@*, tous les emails ayant pour prefixe saddam@ ne pourront s'inscrire</li><li> *warez*, tous les emails contenant le mot warez ne pourront s'inscrire</li></ul>";
$lang['Explain']['unban']           = "Pour d�bannir un email ou fragment d'email, utilisez la combinaison clavier/souris appropri�e � votre ordinateur et votre navigateur";
$lang['Explain']['forbid_ext']      = "Pour interdire plusieurs extensions de fichiers en m�me temps, s�parez les par une virgule";
$lang['Explain']['reallow_ext']     = "Pour r�autoriser une ou plusieurs extensions, utilisez la combinaison clavier/souris appropri�e � votre ordinateur et votre navigateur";
$lang['Explain']['backup']          = "Ce module vous permet de sauvegarder les tables du script, ainsi que d'�ventuelles autres tables sp�cifi�es, s'il y en a.\nVous pouvez d�cider de sauvegarder tout, uniquement la structure ou les donn�es, et vous pouvez demander � ce que le fichier soit compress� (selon les options disponibles et librairies install�es sur le serveur).\nEnfin, vous pouvez soit t�l�charger directement le fichier, ou demander au script de le stocker sur le serveur, auquel cas, le fichier sera cr�� dans le r�pertoire des fichiers temporaires du script";
$lang['Explain']['restore']         = "Ce module vous permet de restaurer les tables du script � l'aide d'une sauvegarde g�n�r�e par wanewsletter ou un quelconque gestionnaire de bases de donn�es.\nSi l'upload de fichier n'est pas autoris� sur le serveur, vous avez toutefois la possibilit� de sp�cifier un fichier pr�c�demment upload� via ftp en indiquant son chemin (relatif � la racine du script)";
$lang['Explain']['generator']       = "Vous devez entrer ici l'adresse absolue ou les donn�es du formulaire seront re�ues (en g�n�ral, l'adresse o� se trouvera le formulaire lui m�me)";
$lang['Explain']['code_html']       = "Placez ce code � l'adresse que vous avez/allez indiquer dans la configuration de la liste de diffusion";
$lang['Explain']['code_php']        = "Vous devez placer ce code � l'adresse de destination du formulaire (adresse entr�e pr�c�demment), le fichier doit avoir l'extension php !\nLe script s'occupe de trouver le chemin canonique � placer dans la variable \$waroot, si toutefois il n'est pas bon, vous devrez le modifier vous m�me et indiquer le bon chemin (le chemin doit �tre relatif, pas absolu)";

$lang['Select_tool']                = "S�lectionnez l'outil que vous voulez utiliser";
$lang['Export_format']              = "Export au format";
$lang['Plain_text']                 = "texte plat";
$lang['Char_glue']                  = "Caract�re de s�paration";
$lang['Compress']                   = "Compression";
$lang['Format_to_export']           = "Exporter les abonn�s qui ont le format";
$lang['Format_to_import']           = "Format � donner aux abonn�s";
$lang['File_upload_restore']        = "Indiquez l'acc�s au fichier de sauvegarde";
$lang['File_upload']                = "<i>ou</i> bien, vous pouvez sp�cifier un fichier texte";
$lang['File_local']                 = "<i>ou</i> bien, vous pouvez sp�cifier un fichier local";
$lang['No_email_banned']            = "Aucun email banni";
$lang['Ban_email']                  = "Email ou fragment d'email � bannir";
$lang['Unban_email']                = "Email ou fragment d'email � d�bannir";
$lang['No_forbidden_ext']           = "Aucune extension interdite";
$lang['Forbid_ext']                 = "Interdire une extension";
$lang['Reallow_ext']                = "Extension(s) � r�-autoriser";
$lang['Backup_type']                = "Type de sauvegarde";
$lang['Backup_full']                = "Compl�te";
$lang['Backup_structure']           = "Structure uniquement";
$lang['Backup_data']                = "Donn�es uniquement";
$lang['Drop_option']                = "Ajouter des �nonc�s DROP TABLE";
$lang['File_action']                = "Que voulez-vous faire du fichier";
$lang['Download_action']            = "Le t�l�charger";
$lang['Store_action']               = "Le stocker sur le serveur";
$lang['Additionnal_tables']         = "Tables suppl�mentaires � sauvegarder";
$lang['Target_form']                = "URL de r�ception du formulaire";


//
// Page : Envoi des newsletters
//
$lang['Explain']['send']            = "Le formulaire d'envoi vous permet de r�diger vos newsletters, de les envoyer, les sauvegarder ou les supprimer, de joindre des fichiers joints..\nSi vous utiliser le deuxi�me moteur d'envoi, vous pouvez, � l'instar de <code>{LINKS}</code>, placer <code>{NAME}</code> dans le texte, pour afficher le nom de l'abonn� si celui ci l'a indiqu�.\nVous pouvez �galement utiliser des tags d'inclusion pour ajouter du contenu externe. %sConsultez la FAQ%s pour plus de d�tails.\n\nSi vous cr�ez un mod�le r�utilisable et que vous lancez l'envoi sans avoir sauvegard�, le mod�le sera sauvegard� et une copie sera cr��e pour les archives. Si vous avez cr�� un mod�le, vous pouvez le recharger, le modifier puis sauvegarder les changements. Toutefois, si vous faites cela en modifiant le statut de la newsletter, une copie sera cr��e et les modifications seront sauvegard�es dessus et non sur le mod�le";
$lang['Explain']['join']            = "Vous pouvez ici joindre des fichiers � votre newsletter (attention � ne pas trop alourdir votre newsletter)\nSi l'upload de fichier n'est pas autoris� sur le serveur, vous pourrez indiquer un fichier distant (ex&thinsp;: <samp>http://www.domaine.com/rep/image.gif</samp>) ou un fichier manuellement upload� dans le r�pertoire des fichiers joints\nVous pouvez �galement utiliser un des fichiers joints dans une autre newsletter de cette liste";
$lang['Explain']['text']            = "R�digez ici votre newsletter au format texte. N'oubliez pas de placer le lien de d�sinscription, soit en cliquant sur le bouton d�di� s'il est disponible, soit en ajoutant manuellement le tag <code>{LINKS}</code> dans votre newsletter";
$lang['Explain']['html']            = "R�digez ici votre newsletter au format html. N'oubliez pas de placer le lien de d�sinscription , soit en cliquant sur le bouton d�di� s'il est disponible, soit en ajoutant manuellement le tag <code>{LINKS}</code> dans votre newsletter (le lien sera au format html)\nSi vous voulez utiliser un des fichiers joints (une image, un son...) dans la newsletter html, placer au lieu de l'adresse du fichier cid:nom_du_fichier\n\n<em>Exemple&thinsp;:</em>\n\nVous avez upload� l'image image1.gif et d�sirez l'utiliser dans une balise image de la newsletter html, vous placerez alors la balise img avec pour l'attribut src : cid:image1.gif ( <code>&lt;img src=\"cid:image1.gif\" alt=\"texte alternatif\" /&gt;</code> )";

$lang['Select_log_to_load']         = "Choisissez la newsletter � charger";
$lang['Select_log_to_send']         = "Choisissez la newsletter dont vous voulez reprendre l'envoi";
$lang['Load_by_URL']                = "Chargez une newsletter depuis une URL";
$lang['From_an_URL']                = "depuis une URL";
$lang['Create_log']                 = "Cr�er une newsletter";
$lang['Load_log']                   = "Charger une newsletter";
$lang['List_send']                  = "Liste des envois en cours";
$lang['Restart_send']               = "Reprendre cet envoi";
$lang['Cancel_send']                = "Annuler cet envoi";
$lang['Model']                      = "Mod�le";
$lang['Dest']                       = "Destinataire";
$lang['Log_in_text']                = "Newsletter au format texte";
$lang['Log_in_html']                = "Newsletter au format HTML";
$lang['Last_modified']              = "Derni�re modification le %s";
$lang['Total_log_size']             = "Poids approximatif de la newsletter";
$lang['Join_file_to_log']           = "Fichier � joindre � cette newsletter";
$lang['Subject_empty']              = "Vous devez donner un sujet � votre newsletter";
$lang['Body_empty']                 = "Vous devez remplir le(s) champs texte";
$lang['No_links_in_body']           = "Vous devez placer le lien de d�sinscription";
$lang['Cid_error_in_body']          = "Certains fichiers cibl�s dans votre newsletter <abbr>HTML</abbr> avec le scheme <samp>cid:</samp> sont manquants (%s)";
$lang['Status']                     = "Statut";
$lang['Done']                       = "Effectu�";
$lang['Status_writing']             = "Newsletter normale";
$lang['Status_model']               = "Mod�le r�utilisable";
$lang['File_on_server']             = "fichier existant";
$lang['Cancel_send_log']            = "�tes-vous s�r de vouloir annuler cet envoi ? (Cela ne sera effectif que pour les envois restants)";
$lang['Receive_copy']               = "Recevoir une copie";
$lang['Receive_copy_title']         = "Si actif, vous recevrez une copie de la newsletter envoy�e";


//
// Page : Statistiques
//
$lang['Explain']['stats']           = "Cette page vous permet de visualiser un graphique � barre, repr�sentant le nombre d'inscriptions par jour, pour le mois et l'ann�e donn�s, ainsi qu'un deuxi�me graphique repr�sentant la r�partition des abonn�s, par liste de diffusion.\nSi votre serveur n'a pas de librairie GD install�e, vous devriez alors d�sactiver ce module dans la configuration du script";
$lang['Num_abo_per_liste']          = "R�partition des abonn�s par liste de diffusion";
$lang['Subscribe_per_day']          = "Inscriptions/Jours";
$lang['Graph_bar_title']            = "Le nombre d'inscriptions par jour pour le mois donn�";
$lang['Camembert_title']            = "Les parts des diff�rentes listes par rapport au nombre total d'abonn�s";
$lang['Stats_dir_not_writable']     = "Le r�pertoire <samp>stats/</samp> ne semble pas accessible en �criture !";


//
// Installation du script
//
$lang['Welcome_in_install']         = "Bienvenue dans le script d'installation de Wanewsletter. \nCe script requiert PHP >= 4.3.0 ou PHP >= 5.1.0 pour fonctionner.\nAvant de continuer l'installation, prenez le temps de lire le fichier %slisez-moi%s, il contient des directives importantes pour la r�ussite de l'installation.\nAssurez-vous �galement d'avoir pris connaissance de la %slicence d'utilisation de Wanewsletter%s avant de continuer. Une traduction fran�aise <strong>non officielle</strong> est disponible � l'adresse %sphpcodeur.net/wascripts/gpl%s";
$lang['Welcome_in_upgrade']         = "Bienvenue dans le script de mise � jour de Wanewsletter. \nVous disposez actuellement de la version %s de Wanewsletter.\n Par mesure de s�curit�, il est <strong>fortement conseill�</strong> de faire une sauvegarde des tables du script avant de proc�der � la mise � jour.";
$lang['Warning_reinstall']          = "<b>Attention !</b> Wanewsletter semble d�j� install�. \nSi vous souhaitez r�installer le script, entrez votre login et mot de passe d'administrateur. \nAttention, toutes les donn�es de l'installation pr�c�dente seront d�finitivement perdues.\n Si vous souhaitez plut�t effectuer une mise � jour d'une installation existante, utilisez le script upgrade.php";
$lang['Start_install']              = "D�marrer l'installation";
$lang['Start_upgrade']              = "D�marrer la mise � jour";
$lang['Result_install']             = "R�sultat de l'installation";
$lang['Result_upgrade']             = "R�sultat de la mise � jour";
$lang['PHP_version_error']          = "D�sol� mais Wanewsletter %s requiert PHP >= 4.3.0 ou PHP >= 5.1.0";
$lang['Not_installed']              = "Aucune version de Wanewsletter ne semble pr�sente, le fichier de configuration est vide ou absent du serveur";
$lang['mssql_support_end']          = "D�sol� mais le support de SQL Server a �t� retir� dans Wanewsletter 2.3";
$lang['No_db_support']              = "D�sol� mais Wanewsletter %s requiert une base de donn�es %s";
$lang['Connect_db_error']           = "Impossible de se connecter � la base de donn�es (%s)";
$lang['sqldir_perms_problem']       = "Pour utiliser Wanewsletter avec une base de donn�es SQLite, vous devez rendre accessible en lecture et �criture le r�pertoire includes/sql/";
$lang['DB_type_undefined']          = "Le type de base de donn�es n'est pas d�fini !";

$lang['Success_install']            = "L'installation s'est bien d�roul�e.\nVous pouvez maintenant acc�der � %sl'administration%s";
$lang['Success_upgrade']            = "La mise � jour s'est bien d�roul�e.\nVous pouvez maintenant acc�der � %sl'administration%s";
$lang['Success_without_config']     = "L'op�ration s'est bien effectu�e mais le fichier de configuration n'a pu �tre cr��.\nVous pouvez le t�l�charger et l'uploader par vos propres moyens sur le serveur dans le r�pertoire includes/ du script.";
$lang['Error_in_install']           = "Une erreur s'est produite durant l'installation.\n\nL'erreur est : %s\nLa requ�te est : %s";
$lang['Error_in_upgrade']           = "Une erreur s'est produite durant la mise � jour.\n\nL'erreur est : %s\nLa requ�te est : %s";
$lang['Upgrade_not_required']       = "Aucune mise � jour n'est n�cessaire pour votre version actuelle de Wanewsletter";
$lang['Unknown_version']            = "Version inconnue, la mise � jour ne peut continuer.";

$lang['dbtype']                     = "Type de base de donn�es";
$lang['dbhost']                     = "Nom du serveur de base de donn�es";
$lang['dbname']                     = "Nom de votre base de donn�es";
$lang['dbuser']                     = "Nom d'utilisateur";
$lang['dbpwd']                      = "Mot de passe";
$lang['prefixe']                    = "Pr�fixe des tables";


//
// Conversions des formats de date
//
$datetime['Monday']     = "Lundi";
$datetime['Tuesday']    = "Mardi";
$datetime['Wednesday']  = "Mercredi";
$datetime['Thursday']   = "Jeudi";
$datetime['Friday']     = "Vendredi";
$datetime['Saturday']   = "Samedi";
$datetime['Sunday']     = "Dimanche";
$datetime['Mon']        = "Lun";
$datetime['Tue']        = "Mar";
$datetime['Wed']        = "Mer";
$datetime['Thu']        = "Jeu";
$datetime['Fri']        = "Ven";
$datetime['Sat']        = "Sam";
$datetime['Sun']        = "Dim";

$datetime['January']    = "Janvier";
$datetime['February']   = "F�vrier";
$datetime['March']      = "Mars";
$datetime['April']      = "Avril";
$datetime['May']        = "Mai";
$datetime['June']       = "Juin";
$datetime['July']       = "Juillet";
$datetime['August']     = "Ao�t";
$datetime['September']  = "Septembre";
$datetime['October']    = "Octobre";
$datetime['November']   = "Novembre";
$datetime['December']   = "D�cembre";
$datetime['Jan']        = "Jan";
$datetime['Feb']        = "F�v";
$datetime['Mar']        = "Mar";
$datetime['Apr']        = "Avr";
$datetime['May']        = "Mai";
$datetime['Jun']        = "Juin";
$datetime['Jul']        = "Juil";
$datetime['Aug']        = "Ao�";
$datetime['Sep']        = "Sep";
$datetime['Oct']        = "Oct";
$datetime['Nov']        = "Nov";
$datetime['Dec']        = "D�c";


//
// Donn�es diverses sur la langue
//
$lang['CHARSET']        = 'ISO-8859-1';
$lang['CONTENT_LANG']   = 'fr';
$lang['CONTENT_DIR']    = 'ltr'; // sens du texte Left To Right ou Right To Left
$lang['TRANSLATE']      = '';


// Formatage de nombres
$lang['DEC_POINT']      = ",";
$lang['THOUSANDS_SEP']  = "\xA0"; // Espace ins�cable

?>