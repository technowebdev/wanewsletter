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

if( !defined('_INC_CLASS_WADB_MYSQL') ) {

define('_INC_CLASS_WADB_MYSQL', true);

define('SQL_INSERT', 1);
define('SQL_UPDATE', 2);
define('SQL_DELETE', 3);

define('SQL_FETCH_NUM',   MYSQL_NUM);
define('SQL_FETCH_ASSOC', MYSQL_ASSOC);
define('SQL_FETCH_BOTH',  MYSQL_BOTH);

class Wadb_mysql {
	
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
	var $dbname = '';
	
	/**
	 * Options de connexion
	 * 
	 * @var array
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
	 * @access public
	 */
	var $queries = 0;
	
	/**
	 * Dur�e totale d'ex�cution des requ�tes SQL
	 * 
	 * @var integer
	 * @access public
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
	function Wadb_mysql($dbname, $options = null)
	{
		$this->dbname = $dbname;
		
		if( is_array($options) ) {
			$this->options = array_merge($this->options, $options);
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
		
		$connect = 'mysql_connect';
		
		if( is_array($options) ) {
			$this->options = array_merge($this->options, $options);
		}
		
		if( !empty($this->options['persistent']) ) {
			$connect = 'mysql_pconnect';
		}
		
		if( !is_null($port) ) {
			$host .= ':' . $port;
		}
		
		if( !($this->link = $connect($host, $username, $passwd)) ) {
			$this->errno = mysql_errno();
			$this->error = mysql_error();
			$this->link  = null;
		}
		else if( !mysql_select_db($this->dbname) ) {
			$this->errno = mysql_errno($this->link);
			$this->error = mysql_error($this->link);
			
			mysql_close($this->link);
			$this->link  = null;
		}
		else {
			$this->serverVersion = mysql_get_server_info($this->link);
			$this->clientVersion = mysql_get_client_info();
			
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
	 * le nouveau jeu de caract�res de la connexion en cours.
	 * Utilisable uniquement avec MySQL >= 4.1.1
	 * 
	 * @param string $encoding
	 * 
	 * @access public
	 * @return string
	 */
	function encoding($encoding = null)
	{
		$charsetSupport = version_compare($this->serverVersion, '4.1.2', '>=');
		
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
		//
		// Pour MySQL < 4.0.6 uniquement
		// @see http://dev.mysql.com/doc/refman/4.1/en/news-4-0-6.html
		//
		if( version_compare($this->serverVersion, '4.0.6', '<')
			&& preg_match('/\s+LIMIT\s+(\d+)\s+OFFSET\s+(\d+)\s*$/i', $query, $match) )
		{
			$query  = substr($query, 0, -strlen($match[0]));
			$query .= " LIMIT $match[2], $match[1]";
		}
		
		$curtime = array_sum(explode(' ', microtime()));
		$result  = mysql_query($query, $this->link);
		$endtime = array_sum(explode(' ', microtime()));
		
		$this->sqltime += ($endtime - $curtime);
		$this->queries++;
		
		if( !$result ) {
			$this->errno = mysql_errno($this->link);
			$this->error = mysql_error($this->link);
			$this->lastQuery = $query;
			
			$this->rollBack();
		}
		else {
			$this->errno = 0;
			$this->error = '';
			$this->lastQuery = '';
			
			if( !is_bool($result) ) {// on a r�ceptionn� une ressource ou un objet
				$result = new WadbResult_mysql($this->link, $result);
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
			if( is_null($value) ) {
				$value = 'NULL';
			}
			else if( is_bool($value) ) {
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
					if( is_null($value) ) {
						$value = 'NULL';
					}
					else if( is_bool($value) ) {
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
		
		mysql_query('OPTIMIZE TABLE ' . $tables, $this->link);
	}
	
	/**
	 * D�marre le mode transactionnel
	 * 
	 * @access public
	 * @return boolean
	 */
	function beginTransaction()
	{
		mysql_query('SET AUTOCOMMIT=0', $this->link);
		return mysql_query('BEGIN', $this->link);
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
		if( !($result = mysql_query('COMMIT', $this->link)) ) {
			mysql_query('ROLLBACK', $this->link);
		}
		
		mysql_query('SET AUTOCOMMIT=1', $this->link);
		
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
		$result = mysql_query('ROLLBACK', $this->link);
		mysql_query('SET AUTOCOMMIT=1', $this->link);
		
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
		return mysql_affected_rows($this->link);
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
		return mysql_insert_id($this->link);
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
		return mysql_real_escape_string($string, $this->link);
	}
	
	/**
	 * V�rifie l'�tat de la connexion courante et effectue si besoin une reconnexion
	 * 
	 * @access public
	 * @return boolean
	 */
	function ping()
	{
		return mysql_ping($this->link);
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
			@$this->rollBack();
			$result = mysql_close($this->link);
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

class WadbResult_mysql {
	
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
	function WadbResult_mysql($link, $result)
	{
		$this->link   = $link;
		$this->result = $result;
		$this->fetchMode = MYSQL_BOTH;
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
		
		return mysql_fetch_array($this->result, $mode);
	}
	
	/**
	 * Renvoie sous forme d'objet la ligne suivante dans le jeu de r�sultat
	 * 
	 * @access public
	 * @return object
	 */
	function fetchObject()
	{
		return mysql_fetch_object($this->result);
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
		while( $row = $this->fetch($mode) ) {
			array_push($rowset, $row);
		}
		
		return $rowset;
	}
	
	/**
	 * Retourne le contenu de la colonne pour l'index ou le nom donn�
	 * � l'index suivant dans le jeu de r�sultat.
	 * 
	 * @param mixed $column  Index ou nom de la colonne
	 * 
	 * @access public
	 * @return string
	 */
	function column($column)
	{
		$row = mysql_fetch_array($this->result);
		
		return (is_array($row) && isset($row[$column])) ? $row[$column] : false;
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
		if( in_array($mode, array(MYSQL_NUM, MYSQL_ASSOC, MYSQL_BOTH)) ) {
			$this->fetchMode = $mode;
			return true;
		}
		else {
			trigger_error("Invalid fetch mode", E_USER_WARNING);
			return false;
		}
	}
	
	/**
	 * Lib�re la m�moire allou�e
	 * 
	 * @access public
	 * @return void
	 */
	function free()
	{
		if( !is_null($this->result) && is_resource($this->link) ) {
			mysql_free_result($this->result);
			$this->result = null;
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
		$this->free();
	}
}

class WadbBackup_mysql {
	
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
	function WadbBackup_mysql($infos)
	{
		$this->infos = $infos;
		
		if( !isset($this->infos['host']) ) {
			$this->infos['host'] = 'localhost';
		}
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
		
		if( version_compare($db->serverVersion, '4.1.2', '>=') ) {
			$contents .= sprintf("SET NAMES '%s';%s", $db->encoding(), $this->eol);
			$contents .= $this->eol;
		}
		
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
		while( $row = $result->fetch() ) {
			$tables[$row['Name']] = ( isset($row['Engine']) ) ? $row['Engine'] : $row['Type'];
		}
		
		return $tables;
	}
	
	/**
	 * Utilisable pour l'ajout de requ�te suppl�mentaires (s�quences, configurations diverses, etc)
	 * 
	 * @param boolean $drop_option
	 * 
	 * @access public
	 * @return string
	 */
	function get_other_queries($drop_option)
	{
		return '';
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
		
		$result->setFetchMode(SQL_FETCH_ASSOC);
		
		if( $row = $result->fetch() ) {
			$contents  = $this->eol;
			$contents .= '-- ' . $this->eol;
			$contents .= '-- Contenu de la table ' . $tablename . ' ' . $this->eol;
			$contents .= '-- ' . $this->eol;
			
			$fields = array();
			for( $j = 0, $n = mysql_num_fields($result->result); $j < $n; $j++ ) {
				$data = mysql_fetch_field($result->result, $j);
				$fields[] = $db->quote($data->name);
			}
			
			$fields = implode(', ', $fields);
			
			do {
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
			while( $row = $result->fetch() );
		}
		$result->free();
		
		return $contents;
	}
}

}
?>
