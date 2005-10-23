<?php
/*******************************************************************
 *          
 *          Fichier         :   delete_mail.php 
 *          Cr�� le         :   05 d�cembre 2003 
 *          Derni�re modif  :   29 f�vrier 2004 
 *          Email           :   wascripts@phpcodeur.net 
 * 
 *              Copyright � 2002-2004 phpCodeur
 * 
 *******************************************************************/

/*******************************************************************
 *  This program is free software; you can redistribute it and/or 
 *  modify it under the terms of the GNU General Public License as 
 *  published by the Free Software Foundation; either version 2 of 
 *  the License, or (at your option) any later version. 
 *******************************************************************/


//
// Ceci est un fichier de test ou d'aide lors du d�veloppement. 
// Commentez la ligne suivante uniquement si vous �tes s�r de ce que vous faites !
//
exit('<b>Fichier de d�veloppement d�sactiv�</b>');

/*
 * @link http://www.cru.fr/listes/atelier/bounce.html
 * 
 * Ce script se charge de scanner le compte mail indiqu� pour r�cup�rer les mail-daemon renvoy�s 
 * en cas de compte inexistant ou de boite pleine et supprime les emails indiqu�s de la base des 
 * inscrits (si boite inexistante).
 * 
 * Si vous utilisez ce script pour scanner le compte sur lequel vous avez demand� que soient renvoy�s 
 * les mails de retours d'erreur, faites attention de d�commenter ensuite la ligne plus haut pour �viter 
 * des actes malveillants.
 * 
 * Je rappelle que ce script est juste un script de d�veloppement et ne devrait pas �tre utilis�
 */

$pop_host = '';
$pop_port = 110; // port du serveur. La valeur par d�faut (110) est la plus r�pandue.
$pop_user = '';
$pop_pass = '';

define('IN_NEWSLETTER', true);

$waroot = '../';
require($waroot . 'start.php');
include($waroot . 'includes/class.pop.php');

$pop = new Pop();
$pop->connect($pop_host, $pop_port, $pop_user, $pop_pass);

$total    = $pop->stat_box();
$mail_box = $pop->list_mail();

$deleted_mails = array();

foreach( $mail_box AS $mail_id => $mail_size )
{
    $headers = $pop->parse_headers($mail_id);
    
/*  $output  = implode("\n", $headers) . "\n--------------------\n";
    $output .= $pop->contents[$mail_id]['message'];
    $output .= "\n------------------------";
    plain_error($output, false);
    continue;
*/  
    //
    // Les emails de retour d'erreur ne sp�cifient pas de return-path ou en sp�cifient un vide
    //
/*  if( !empty($headers['return-path']) && strlen($headers['return-path']) > 2 )
    {
        continue;
    }
*/  
    $bounce         = !empty($headers['received']) && stristr($headers['received'], 'bounce');
    $deliveryStatus = !empty($headers['content-type']) && preg_match('/report-type="?delivery-status"?/i', $headers['content-type']);
    
    if( !$bounce && !$deliveryStatus )
    {
        continue;
    }
    
    $message = $pop->contents[$mail_id]['message'];
    if( !preg_match('/<([^@>]+@[^>]+)>/', $message, $match) )
    {
        continue;
    }
    
    $sql = "SELECT abo_id 
        FROM " . ABONNES_TABLE . " 
        WHERE abo_email = '" . $db->escape($match[1]) . "'";
    $result = $db->query($sql);
    
    $abo_id = $db->result($result, 0, 'abo_id');
    
    $sql = "DELETE FROM " . ABONNES_TABLE . " WHERE abo_id = " . $abo_id;
    $db->query($sql);
    
    $sql = "DELETE FROM " . ABO_LISTE_TABLE . " WHERE abo_id = " . $abo_id;
    $db->query($sql);
    
    $deleted_mails[] = $match[1];
    
    //
    // On supprime l'email maintenant devenu inutile
    //
    $pop->delete_mail($mail_id);
}//end for

$pop->quit();

$output  = "Op�ration effectu�e avec succ�s\n";
$output .= count($deleted_mails) . " compte(s) supprim�(s) pour cause d'adresse non valide.\n\n";

foreach( $deleted_mails AS $mail )
{
    $output .= ' - ' . $mail . "\n";
}

plain_error($output);

?>