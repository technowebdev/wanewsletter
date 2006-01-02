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
 */

if( !defined('_INC_CLASS_WADB') ) {

define('_INC_CLASS_WADB', true);

define('SQL_INSERT', 1);
define('SQL_UPDATE', 2);
define('SQL_DELETE', 3);

define('SQL_FETCH_NUM',   MYSQLI_NUM);
define('SQL_FETCH_ASSOC', MYSQLI_ASSOC);
define('SQL_FETCH_BOTH',  MYSQLI_BOTH);

class Wadb {
	
	/**
	 * Connexion � la base de donn�es
	 * 
	 * @var resource
	 * @access private
	 */
	var $link;
	
	/**
	 * Nom de la base de donn�es
	 * 
	 * @var string
	 * @access private
	 */
	var $dbname;
	
	/**
	 * Options de connexion
	 * 
	 * @var resource
	 * @access private
	 */
	var $options = array();
	
	/**
	 * Code d'erreur
	 * 
	 * @var integer
	 * @access public
	 */
	var $errno = 0;
	
	/**
	 * Message d'erreur
	 * 
	 * @var string
	 * @access public
	 */
	var $error = '';
	
	/**
	 * Derni�re requ�te SQL ex�cut�e (en cas d'erreur seulement)
	 * 
	 * @var string
	 * @access public
	 */
	var $lastQuery = '';
	
	/**
	 * Nombre de requ�tes SQL ex�cut�es depuis le d�but de la connexion
	 * 
	 * @var integer
	 * @access private
	 */
	var $queries = 0;
	
	/**
	 * Dur�e totale d'ex�cution des requ�tes SQL
	 * 
	 * @var string
	 * @access private
	 */
	var $sqltime = 0;
	
	/**
	 * Version du serveur
	 * 
	 * @var string
	 * @access public
	 */
	var $serverVersion = '';
	
	/**
	 * Version du client
	 * 
	 * @var string
	 * @access public
	 */
	var $clientVersion = '';
	
	/**
	 * Constructeur de classe
	 * 
	 * @param string $dbname   Nom de la base de donn�es
	 * @param array  $options  Options de connexion/utilisation
	 * 
	 * @access public
	 */
	function Wadb($dbname, $options = null)
	{
		$this->dbname = $dbname;
		
		if( is_array($options) ) {
			$this->options = $options;
		}
	}
	
	/**
	 * Connexion � la base de donn�es
	 * 
	 * @param array $infos    Informations de connexion
	 * @param array $options  Options de connexion/utilisation
	 * 
	 * @access public
	 * @return boolean
	 */
	function connect($infos = null, $options = null)
	{
		if( is_array($infos) ) {
			foreach( array('host', 'username', 'passwd', 'port') as $info ) {
				$$info = ( isset($infos[$info]) ) ? $infos[$info] : null;
			}
		}
		
		$connect = 'mysqli_connect';
		
		if( is_array($options) ) {
			$this->options = array_merge($this->options, $options);
			
			if( !empty($this->options['persistent']) ) {
				$connect = 'mysqli_pconnect';
			}
		}
		
		if( !($this->link = $connect($host, $username, $passwd, $this->dbname, $port)) ) {
			$this->errno = mysqli_connect_errno();
			$this->error = mysqli_connect_error();
			$this->link  = null;
		}
		else {
			$this->serverVersion = mysqli_get_server_info($this->link);
			$this->clientVersion = mysqli_get_client_info();
			
			if( !empty($this->options['charset']) ) {
				$this->encoding($this->options['charset']);
			}
		}
	}
	
	/**
	 * @access public
	 * @return boolean
	 */
	function isConnected()
	{
		return !is_null($this->link);
	}
	
	/**
	 * Renvoie le jeu de caract�res courant utilis�.
	 * Si l'argument $encoding est fourni, il est utilis� pour d�finir
	 * le nouveau jeu de caract�res de la connexion en cours
	 * 
	 * @param string $encoding
	 * 
	 * @access public
	 * @return string
	 */
	function encoding($encoding = null)
	{
		$charsetSupport = version_compare($this->serverVersion, '4.1.1', '>=');
		
		if( $charsetSupport ) {
			$res = $this->query("SHOW VARIABLES LIKE 'character_set_client'");
			$curEncoding = $res->column('Value');
		}
		else {
			$curEncoding = 'latin1'; // TODO
		}
		
		if( $charsetSupport && !is_null($encoding) ) {
			$this->query("SET NAMES $encoding");
		}
		
		return $curEncoding;
	}
	
	/**
	 * Ex�cute une requ�te sur la base de donn�es
	 * 
	 * @param string $query
	 * 
	 * @access public
	 * @return mixed
	 */
	function query($query)
	{
		$curtime = array_sum(explode(' ', microtime()));
		$result  = mysqli_query($this->link, $query);
		$endtime = array_sum(explode(' ', microtime()));
		
		$this->sqltime += ($endtime - $curtime);
		$this->queries++;
		
		if( !$result ) {
			$this->errno = mysqli_errno($this->link);
			$this->error = mysqli_error($this->link);
			$this->lastQuery = $query;
			
			$this->rollBack();
		}
		else {
			$this->errno = 0;
			$this->error = '';
			$this->lastQuery = '';
			
			if( !is_bool($result) ) {// on a r�ceptionn� une ressource ou un objet
				$result = new WadbResult($this->link, $result);
			}
		}
		
		return $result;
	}
	
	/**
	 * Construit une requ�te de type INSERT ou UPDATE � partir des diverses donn�es fournies
	 * 
	 * @param string $type      Type de requ�te (peut valoir INSERT ou UPDATE)
	 * @param string $table     Table sur laquelle effectuer la requ�te
	 * @param array  $data      Tableau des donn�es � ins�rer. Le tableau a la structure suivante:
	 *                          array(column_name => column_value[, column_name => column_value])
	 * @param array $sql_where  Cha�ne de condition
	 * 
	 * @access public
	 * @return mixed
	 */
	function build($type, $table, $data, $sql_where = null)
	{
		$fields = $values = array();
		
		foreach( $data as $field => $value ) {
			if( is_bool($value) ) {
				$value = intval($value);
			}
			else if( !is_int($value) && !is_float($value) ) {
				$value = '\'' . $this->escape($value) . '\'';
			}
			
			array_push($fields, $this->quote($field));
			array_push($values, $value);
		}
		
		if( $type == SQL_INSERT ) {
			$query = sprintf('INSERT INTO %s (%s) VALUES(%s)', $table, implode(', ', $fields), implode(', ', $values));
		}
		else if( $type == SQL_UPDATE ) {
			
			$query = 'UPDATE ' . $table . ' SET ';
			for( $i = 0, $m = count($fields); $i < $m; $i++ ) {
				$query .= $fields[$i] . ' = ' . $values[$i] . ', ';
			}
			
			$query = substr($query, 0, -2);
			
			if( is_array($sql_where) && count($sql_where) > 0 ) {
				$query .= ' WHERE ';
				foreach( $sql_where as $field => $value ) {
					if( is_bool($value) ) {
						$value = intval($value);
					}
					else if( !is_int($value) && !is_float($value) ) {
						$value = '\'' . $this->escape($value) . '\'';
					}
					
					$query .= sprintf('%s = %s AND ', $this->quote($field), $value);
				}
				
				$query = substr($query, 0, -5);
			}
		}
		
		return $this->query($query);
	}
	
	/**
	 * Prot�ge un nom de base, de table ou de colonne en pr�vision de son utilisation
	 * dans une requ�te
	 * 
	 * @param string $name
	 * 
	 * @access public
	 * @return string
	 */
	function quote($name)
	{
		return '`' . $name . '`';
	}
	
	/**
	 * @param mixed $tables  Nom de table ou tableau de noms de table
	 * 
	 * @access public
	 * @return void
	 */
	function vacuum($tables)
	{
		if( is_array($tables) ) {
			$tables = implode(', ', $tables);
		}
		
		mysqli_query($this->link, 'OPTIMIZE TABLE ' . $tables);
	}
	
	/**
	 * D�marre le mode transactionnel
	 * 
	 * @access public
	 * @return boolean
	 */
	function beginTransaction()
	{
		return mysqli_autocommit($this->link, false);
	}
	
	/**
	 * Envoie une commande COMMIT � la base de donn�es pour validation de la
	 * transaction courante
	 * 
	 * @access public
	 * @return boolean
	 */
	function commit()
	{
		if( !($result = mysqli_commit($this->link)) ) {
			mysqli_rollback($this->link);
		}
		
		mysqli_autocommit($this->link, true);
		
		return $result;
	}
	
	/**
	 * Envoie une commande ROLLBACK � la base de donn�es pour annulation de la
	 * transaction courante
	 * 
	 * @access public
	 * @return boolean
	 */
	function rollBack()
	{
		$result = mysqli_rollback($this->link);
		mysqli_autocommit($this->link, true);
		
		return $result;
	}
	
	/**
	 * Renvoie le nombre de lignes affect�es par la derni�re requ�te DML
	 * 
	 * @access public
	 * @return boolean
	 */
	function affectedRows()
	{
		return mysqli_affected_rows($this->link);
	}
	
	/**
	 * Retourne l'identifiant g�n�r� automatiquement par la derni�re requ�te
	 * INSERT sur la base de donn�es
	 * 
	 * @access public
	 * @return integer
	 */
	function lastInsertId()
	{
		return mysqli_insert_id($this->link);
	}
	
	/**
	 * �chappe une cha�ne en pr�vision de son insertion dans une requ�te sur
	 * la base de donn�es
	 * 
	 * @param string $string
	 * 
	 * @access public
	 * @return string
	 */
	function escape($string)
	{
		return mysqli_real_escape_string($this->link, $string);
	}
	
	/**
	 * V�rifie l'�tat de la connexion courante et effectue si besoin une reconnexion
	 * 
	 * @access public
	 * @return boolean
	 */
	function ping()
	{
		return mysqli_ping($this->link);
	}
	
	/**
	 * Ferme la connexion � la base de donn�es
	 * 
	 * @access public
	 * @return boolean
	 */
	function close()
	{
		if( !is_null($this->link) ) {
			$this->commit();
			$result = mysqli_close($this->link);
			$this->link = null;
			
			return $result;
		}
		else {
			return true;
		}
	}
	
	/**
	 * Destructeur de classe
	 * 
	 * @access public
	 * @return void
	 */
	function __destruct()
	{
		$this->close();
	}
}

class WadbResult {
	
	/**
	 * Connexion � la base de donn�es
	 * 
	 * @var resource
	 * @access private
	 */
	var $link;
	
	/**
	 * Ressource de r�sultat de requ�te
	 * 
	 * @var resource
	 * @access private
	 */
	var $result;
	
	/**
	 * Index courant dans le jeu de r�sultats
	 * 
	 * @var integer
	 * @access public
	 */
	var $offset = 0;
	
	/**
	 * Nombre de lignes renvoy�es par la requ�te
	 * 
	 * @var resource
	 * @access private
	 */
	var $_count = 0;
	
	/**
	 * Mode de r�cup�ration des donn�es
	 * 
	 * @var integer
	 * @access private
	 */
	var $fetchMode;
	
	/**
	 * Constructeur de classe
	 * 
	 * @param resource $link    Ressource de connexion � la base de donn�es
	 * @param resource $result  Ressource de r�sultat de requ�te
	 * 
	 * @access public
	 */
	function WadbResult($link, $result)
	{
		$this->link   = $link;
		$this->result = $result;
		$this->_count = mysqli_num_rows($result);
		$this->fetchMode = MYSQLI_BOTH;
	}
	
	/**
	 * Renvoie le nombre de lignes du r�sultat
	 * 
	 * @access public
	 * @return integer
	 */
	function count()
	{
		return $this->_count;
	}
	
	/**
	 * Renvoie la ligne suivante dans le jeu de r�sultat
	 * 
	 * @param integer $mode  Mode de r�cup�ration des donn�es
	 * 
	 * @access public
	 * @return array
	 */
	function fetch($mode = null)
	{
		if( is_null($mode) ) {
			$mode = $this->fetchMode;
		}
		
		$this->offset++;
		return mysqli_fetch_array($this->result, $mode);
	}
	
	/**
	 * Renvoie sous forme d'objet la ligne suivante dans le jeu de r�sultat
	 * 
	 * @access public
	 * @return object
	 */
	function fetchObject()
	{
		$this->offset++;
		return mysqli_fetch_object($this->result);
	}
	
	/**
	 * Renvoie un tableau de toutes les lignes du jeu de r�sultat
	 * 
	 * @param integer $mode  Mode de r�cup�ration des donn�es
	 * 
	 * @access public
	 * @return array
	 */
	function fetchAll($mode = null)
	{
		if( is_null($mode) ) {
			$mode = $this->fetchMode;
		}
		
		$rowset = array();
		for( $i = 0; $i < $this->_count; $i++ ) {
			array_push($rowset, $this->fetch($mode));
		}
		
		return $rowset;
	}
	
	/**
	 * Retourne le contenu de la colonne pour l'index ou le nom donn�
	 * � l'index courant dans le jeu de r�sultat.
	 * Ne d�place PAS le pointeur de r�sultat
	 * 
	 * @param mixed $column  Index ou nom de la colonne
	 * 
	 * @access public
	 * @return string
	 */
	function column($column)
	{
		$row = mysqli_fetch_array($this->result);
		mysqli_data_seek($this->result, $this->offset);
		
		return isset($row[$column]) ? $row[$column] : false;
	}
	
	/**
	 * Renvoie la ligne courante dans le jeu de r�sultat.
	 * Ne d�place PAS le pointeur de r�sultat
	 * 
	 * @param integer $mode  Mode de r�cup�ration des donn�es
	 * 
	 * @access public
	 * @return array
	 */
	function current($mode = null)
	{
		if( is_null($mode) ) {
			$mode = $this->fetchMode;
		}
		
		$row = mysqli_fetch_array($this->result, $mode);
		mysqli_data_seek($this->result, $this->offset);
		
		return $row;
	}
	
	/**
	 * Configure le mode de r�cup�ration par d�faut
	 * 
	 * @param integer $mode  Mode de r�cup�ration des donn�es
	 * 
	 * @access public
	 * @return boolean
	 */
	function setFetchMode($mode)
	{
		if( in_array($mode, array(MYSQLI_NUM, MYSQLI_ASSOC, MYSQLI_BOTH)) ) {
			$this->fetchMode = $mode;
			return true;
		}
		else {
			trigger_error("Invalid fetch mode", E_USER_WARNING);
			return false;
		}
	}
	
	/**
	 * Place le pointeur de r�sultat � la premi�re position
	 * 
	 * @access public
	 * @return boolean
	 */
	function rewind()
	{
		if( $this->_count > 0 ) {
			$this->offset = 0;
			return true;
		}
		else {
			return false;
		}
	}
	
	/**
	 * Place le pointeur de r�sultat � la position pr�c�dente
	 * 
	 * @access public
	 * @return boolean
	 */
	function prev()
	{
		if( $this->offset > 0 ) {
			$this->offset--;
			return true;
		}
		else {
			return false;
		}
	}
	
	/**
	 * Place le pointeur de r�sultat � la position suivante
	 * 
	 * @access public
	 * @return boolean
	 */
	function next()
	{
		if( $this->offset < $this->_count ) {
			$this->offset++;
			return true;
		}
		else {
			return false;
		}
	}
	
	/**
	 * Place le pointeur de r�sultat � la position $offset
	 * 
	 * @param integer $offset
	 * 
	 * @access public
	 * @return boolean
	 */
	function seek($offset)
	{
		$this->offset = $offset;
		return mysqli_data_seek($this->result, $offset);
	}
	
	/**
	 * Indique si une ligne pr�c�dente est disponible dans le jeu de r�sultat
	 * 
	 * @access public
	 * @return boolean
	 */
	function hasPrev()
	{
		return ( $this->offset > 0 ) ? true : false;
	}
	
	/**
	 * Indique si une ligne suivante est disponible dans le jeu de r�sultat
	 * 
	 * @access public
	 * @return boolean
	 */
	function hasMore()
	{
		return ( $this->offset < $this->_count ) ? true : false;
	}
	
	/**
	 * Lib�re la m�moire allou�e
	 * 
	 * @access public
	 * @return boolean
	 */
	function free()
	{
		return @mysqli_free_result($this->result);
	}
	
	/**
	 * Destructeur de classe
	 * 
	 * @access public
	 * @return boolean
	 */
	function __destruct()
	{
		$this->free();
	}
}

class WadbBackup {
	
	/**
	 * Informations concernant la base de donn�es
	 * 
	 * @var array
	 * @access private
	 */
	var $infos = array();
	
	/**
	 * Fin de ligne
	 * 
	 * @var boolean
	 * @access public
	 */
	var $eol = "\n";
	
	/**
	 * Constructeur de classe
	 * 
	 * @param array $infos  Informations concernant la base de donn�es
	 * 
	 * @access public
	 */
	function WadbBackup($infos)
	{
		$this->infos = $infos;
	}
	
	/**
	 * G�n�ration de l'en-t�te du fichier de sauvegarde
	 * 
	 * @param string $toolname  Nom de l'outil utilis� pour g�n�rer la sauvegarde
	 * 
	 * @access public
	 * @return string
	 */
	function header($toolname = '')
	{
		global $db;
		
		$contents  = '-- ' . $this->eol;
		$contents .= "-- $toolname MySQL Dump" . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= "-- Host     : " . $this->infos['host'] . $this->eol;
		$contents .= "-- Server   : " . $db->serverVersion . $this->eol;
		$contents .= "-- Database : " . $this->infos['dbname'] . $this->eol;
		$contents .= '-- Date     : ' . date('d/m/Y H:i:s O') . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= $this->eol;
		
		return $contents;
	}
	
	/**
	 * Retourne la liste des tables pr�sentes dans la base de donn�es consid�r�e
	 * 
	 * @access public
	 * @return array
	 */
	function get_tables()
	{
		global $db;
		
		if( !($result = $db->query('SHOW TABLE STATUS FROM ' . $db->quote($this->infos['dbname']))) ) {
			trigger_error('Impossible d\'obtenir la liste des tables', ERROR);
		}
		
		$tables = array();
		while( $result->hasMore() ) {
			$row = $result->fetch();
			$tables[$row['Name']] = ( isset($row['Engine']) ) ? $row['Engine'] : $row['Type'];
		}
		
		return $tables;
	}
	
	/**
	 * Retourne la structure d'une table de la base de donn�es sous forme de requ�te SQL de type DDL
	 * 
	 * @param array   $tabledata    Informations sur la table (provenant de self::get_tables())
	 * @param boolean $drop_option  Ajouter une requ�te de suppression conditionnelle de table
	 * 
	 * @access public
	 * @return string
	 */
	function get_table_structure($tabledata, $drop_option)
	{
		global $db;
		
		$contents  = '-- ' . $this->eol;
		$contents .= '-- Struture de la table ' . $tabledata['name'] . ' ' . $this->eol;
		$contents .= '-- ' . $this->eol;
		
		if( $drop_option ) {
			$contents .= 'DROP TABLE IF EXISTS ' . $db->quote($tabledata['name']) . ';' . $this->eol;
		}
		
		if( !($result = $db->query('SHOW CREATE TABLE ' . $db->quote($tabledata['name']))) ) {
			trigger_error('Impossible d\'obtenir la structure de la table', ERROR);
		}
		
		$create_table = $result->column('Create Table');
		$result->free();
		
		$contents .= preg_replace("/(\r\n?)|\n/", $this->eol, $create_table) . ';' . $this->eol;
		
		return $contents;
	}
	
	/**
	 * Retourne les donn�es d'une table de la base de donn�es sous forme de requ�tes SQL de type DML
	 * 
	 * @param string $tablename  Nom de la table � consid�rer
	 * 
	 * @access public
	 * @return string
	 */
	function get_table_data($tablename)
	{
		global $db;
		
		$contents = '';
		
		$sql = 'SELECT * FROM ' . $db->quote($tablename);
		if( !($result = $db->query($sql)) ) {
			trigger_error('Impossible d\'obtenir le contenu de la table ' . $tablename, ERROR);
		}
		
		if( $result->count() > 0 ) {
			$contents  = $this->eol;
			$contents .= '-- ' . $this->eol;
			$contents .= '-- Contenu de la table ' . $tablename . ' ' . $this->eol;
			$contents .= '-- ' . $this->eol;
			
			$fields = array();
			for( $j = 0, $n = mysqli_num_fields($result->result); $j < $n; $j++ ) {
				$data = mysqli_fetch_field_direct($result->result, $j);
				$fields[] = $db->quote($data->name);
			}
			
			$fields = implode(', ', $fields);
			
			do {
				$row = $result->fetch(SQL_FETCH_ASSOC);
				
				$contents .= 'INSERT INTO ' . $db->quote($tablename) . " ($fields) VALUES";
				
				foreach( $row as $key => $value ) {
					if( is_null($value) ) {
						$row[$key] = 'NULL';
					}
					else {
						$row[$key] = '\'' . $db->escape($value) . '\'';
					}
				}
				
				$contents .= '(' . implode(', ', $row) . ');' . $this->eol;
			}
			while( $result->hasMore() );
		}
		$result->free();
		
		return $contents;
	}
}

}
?>
