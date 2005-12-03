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

if( !defined('CLASS_SQL_INC') ) {

define('CLASS_SQL_INC', true);
define('DATABASE', 'mysql');

class sql {
	/**
	 * Ressource de connexion
	 * 
	 * @var resource
	 */
	var $connect_id   = '';
	
	/**
	 * Ressource de r�sultat
	 * 
	 * @var resource
	 */
	var $query_result = '';
	
	/**
	 * Transaction en cours ou non
	 * 
	 * @var integer
	 */
	var $trc_started  = 0;
	
	/**
	 * Retours d'erreur (code et message)
	 * 
	 * @var array
	 */
	var $sql_error    = array('errno' => '', 'message' => '', 'query' => '');
	
	/**
	 * Nombre de requ�tes effectu�es depuis le lancement du script
	 * 
	 * @var integer
	 */
	var $queries      = 0;
	
	/**
	 * Temps d'ex�cution du script affect� au traitement des requ�tes SQL
	 * 
	 * @var string
	 */
	var $sql_time     = 0;
	
	/**
	 * sql::sql()
	 * 
	 * Constructeur de classe
	 * Initialise la connexion � la base de donn�es
	 * 
	 * @param string  $dbhost      H�te de la base de donn�es
	 * @param string  $dbuser      Nom d'utilisateur
	 * @param string  $dbpwd       Mot de passe
	 * @param string  $dbname      Nom de la base de donn�es
	 * @param boolean $persistent  Connexion persistante ou non
	 * 
	 * @access public
	 * @return void
	 */
	function sql($dbhost, $dbuser, $dbpwd, $dbname, $persistent = false)
	{
		$sql_connect = ( $persistent ) ? 'mysql_pconnect' : 'mysql_connect';
		
		$this->connect_id = @$sql_connect($dbhost, $dbuser, $dbpwd);
		
		if( $this->connect_id != false )
		{
			$select_db = mysql_select_db($dbname, $this->connect_id);
			
			if( !$select_db )
			{
				$this->sql_error['errno']   = mysql_errno($this->connect_id);
				$this->sql_error['message'] = mysql_error($this->connect_id);
				$this->close($this->connect_id);
			}
		}
		else
		{
			$this->sql_error['errno']   = mysql_errno();
			$this->sql_error['message'] = mysql_error();
		}
	}
	
	/**
	 * sql::prepare_value()
	 * 
	 * Pr�pare une valeur pour son insertion dans la base de donn�es
	 * (Dans la pratique, �chappe les caract�res potentiellement dangeureux)
	 * 
	 * @param mixed $value
	 * 
	 * @access private
	 * @return mixed
	 */
	function prepare_value($value)
	{
		if( is_bool($value) || preg_match('/^[0-9]+$/', $value) )
		{
			$tmp = intval($value);
		}
		else
		{
			$tmp = '\'' . $this->escape($value) . '\'';
		}
		
		return $tmp;
	}
	
	/**
	 * sql::query_build()
	 * 
	 * Construit une requ�te de type INSERT ou UPDATE � partir
	 * des diverses donn�es fournies
	 * 
	 * @param string $query_type  Type de requ�te (peut valoir INSERT ou UPDATE)
	 * @param string $table       Table sur laquelle effectuer la requ�te
	 * @param array  $query_data  Tableau des donn�es � ins�rer. Le tableau a la structure suivante:
	 *                            array(column_name => column_value[, column_name => column_value])
	 * @param string $sql_where   Cha�ne de condition
	 * 
	 * @access public
	 * @return string
	 */
	function query_build($query_type, $table, $query_data, $sql_where = '')
	{
		$fields = $values = array();
		
		foreach( $query_data AS $field => $value )
		{
			array_push($fields, $field);
			array_push($values, $this->prepare_value($value));
		}
		
		if( $query_type == 'INSERT' )
		{
			$query_string  = 'INSERT INTO ' . $table . ' ';
			$query_string .= '(' . implode(', ', $fields) . ') VALUES(' . implode(', ', $values) . ')';
		}
		else if( $query_type == 'UPDATE' )
		{
			$query_string  = 'UPDATE ' . $table . ' SET ';
			for( $i = 0; $i < count($fields); $i++ )
			{
				$query_string .= ( $i > 0 ) ? ', ' : '';
				$query_string .= $fields[$i] . ' = ' . $values[$i];
			}
			
			if( is_array($sql_where) && count($sql_where) )
			{
				$ary = array();
				foreach( $sql_where AS $field => $value )
				{
					$ary[] = $field . ' = ' . $this->prepare_value($value);
				}
				
				$query_string .= ' WHERE ' . implode(' AND ', $ary);
			}
		}
		
		return $this->query($query_string);
	}
	
	/**
	 * sql::query()
	 * 
	 * Effectue une requ�te � destination de la base de donn�es et retourne le r�sultat
	 * En cas d'erreur, la m�thode stocke les informations d'erreur dans sql::sql_error
	 * et retourne false
	 * 
	 * @param string  $query  La requ�te SQL � ex�cuter
	 * @param integer $start  R�up�re les lignes de r�sultat � partir de la position $start
	 * @param integer $limit  Limite le nombre de r�sultat � retourner
	 * 
	 * @access public
	 * @return resource
	 */
	function query($query, $start = null, $limit = null)
	{
		global $starttime;
		
		unset($this->query_result);
		
		if( isset($start) && !empty($limit) )
		{
			$query .= ' LIMIT ' . $start . ', ' . $limit;
		}
		
		$curtime = explode(' ', microtime());
		$curtime = $curtime[0] + $curtime[1] - $starttime;
		
		$this->query_result = @mysql_query($query, $this->connect_id);
		
		$endtime = explode(' ', microtime());
		$endtime = $endtime[0] + $endtime[1] - $starttime;
		
		$this->sql_time += ($endtime - $curtime);
		$this->queries++;
		
		if( !$this->query_result )
		{
			$this->sql_error['errno']   = @mysql_errno($this->connect_id);
			$this->sql_error['message'] = @mysql_error($this->connect_id);
			$this->sql_error['query']   = $query;
			
			$this->transaction('ROLLBACK');
		}
		else
		{
			$this->sql_error = array('errno' => '', 'message' => '', 'query' => '');
		}
		
		return $this->query_result;
	}
	
	/**
	 * sql::transaction()
	 * 
	 * Gestion des transactions
	 * 
	 * @param integer $transaction
	 * 
	 * @access public
	 * @return boolean
	 */
	function transaction($transaction)
	{
		switch($transaction)
		{
			case START_TRC:
				if( !$this->trc_started )
				{
					$this->trc_started = true;
					@mysql_query('SET AUTOCOMMIT=0', $this->connect_id);
					$result = @mysql_query('BEGIN', $this->connect_id);
				}
				else
				{
					$result = true;
				}
				break;
				
			case END_TRC:
				if( $this->trc_started )
				{
					$this->trc_started = false;
					
					if( !($result = @mysql_query('COMMIT', $this->connect_id)) )
					{
						@mysql_query('ROLLBACK', $this->connect_id);
					}
					
					@mysql_query('SET AUTOCOMMIT=1', $this->connect_id);
				}
				else
				{
					$result = true;
				}
				break;
				
			case 'ROLLBACK':
				if( $this->trc_started )
				{
					$this->trc_started = false;
					$result = @mysql_query('ROLLBACK', $this->connect_id);
					@mysql_query('SET AUTOCOMMIT=1', $this->connect_id);
				}
				else
				{
					$result = true;
				}
				break;
		}
		
		return $result;
	}
	
	/**
	 * sql::check()
	 * 
	 * Optimisation des tables
	 * 
	 * @param mixed $tables  Nom de la table ou tableau de noms de table � optimiser
	 * 
	 * @access public
	 * @return void
	 */
	function check($tables)
	{
		if( is_array($tables) )
		{
			$tables_list = implode(', ', $tables);
		}
		
		$tables = implode(', ', $tables);
		
		@mysql_query('OPTIMIZE TABLE ' . $tables, $this->connect_id);
	}
	
	/**
	 * sql::num_rows()
	 * 
	 * Nombre de lignes retourn�es
	 * 
	 * @param resource $result  Ressource de r�sultat de requ�te
	 * 
	 * @access public
	 * @return mixed
	 */
	function num_rows($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( $result != false ) ? @mysql_num_rows($result) : false;
	}
	
	/**
	 * sql::affected_rows()
	 * 
	 * Nombre de lignes affect�es par la derni�re requ�te DML
	 * 
	 * @param resource $result  Ressource de r�sultat de requ�te
	 * 
	 * @access public
	 * @return mixed
	 */
	function affected_rows()
	{
		return ( $this->connect_id != false ) ? @mysql_affected_rows($this->connect_id) : false;
	}
	
	/**
	 * sql::fetch_row()
	 * 
	 * Retourne un tableau index� num�riquement correspondant � la ligne de r�sultat courante
	 * et d�place le pointeur de lecture des r�sultats
	 * 
	 * @param resource $result  Ressource de r�sultat de requ�te
	 * 
	 * @access public
	 * @return mixed
	 */
	function fetch_row($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( $result != false ) ? @mysql_fetch_row($result) : false;
	}
	
	/**
	 * sql::fetch_array()
	 * 
	 * Retourne un tableau associatif correspondant � la ligne de r�sultat courante
	 * et d�place le pointeur de lecture des r�sultats
	 * 
	 * @param resource $result  Ressource de r�sultat de requ�te
	 * 
	 * @access public
	 * @return mixed
	 */
	function fetch_array($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( $result != false ) ? @mysql_fetch_array($result, MYSQL_ASSOC) : false;
	}
	
	/**
	 * sql::fetch_rowset()
	 * 
	 * Retourne un tableau bi-dimensionnel correspondant � toutes les lignes de r�sultat
	 * 
	 * @param resource $result  Ressource de r�sultat de requ�te
	 * 
	 * @access public
	 * @return array
	 */
	function fetch_rowset($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		$rowset = array();
		while( $row = @mysql_fetch_array($result, MYSQL_ASSOC) )
		{
			array_push($rowset, $row);
		}
		
		return $rowset;
	}
	
	/**
	 * sql::num_fields()
	 * 
	 * Retourne le nombre de champs dans le r�sultat
	 * 
	 * @param resource $result  Ressource de r�sultat de requ�te
	 * 
	 * @access public
	 * @return mixed
	 */
	function num_fields($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( $result != false ) ? @mysql_num_fields($result) : false;
	}
	
	/**
	 * sql::field_name()
	 * 
	 * Retourne le nom de la colonne � l'index $offset dans le r�sultat
	 * 
	 * @param integer  $offset  Position de la colonne dans le r�sultat
	 * @param resource $result  Ressource de r�sultat de requ�te
	 * 
	 * @access public
	 * @return mixed
	 */
	function field_name($offset, $result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( $result != false ) ? @mysql_field_name($result, $offset) : false;
	}
	
	/**
	 * sql::result()
	 * 
	 * Retourne la valeur d'une colonne dans une ligne de r�sultat donn�e
	 * 
	 * @param resource $result  Ressource de r�sultat de requ�te
	 * @param integer  $row     Num�ro de la ligne de r�sultat
	 * @param string   $field   Nom de la colonne
	 * 
	 * @access public
	 * @return mixed
	 */
	function result($result, $row, $field = '')
	{
		if( $field != '' )
		{
			return @mysql_result($result, $row, $field);
		}
		else
		{
			return @mysql_result($result, $row);
		}
	}
	
	/**
	 * sql::result_seek()
	 * 
	 * D�place le pointeur interne de r�sultat
	 * 
	 * @param integer  $row     Num�ro de la ligne de r�sultat
	 * @param resource $result  Ressource de r�sultat de requ�te
	 * 
	 * @access public
	 * @return boolean
	 */
	function result_seek($row, $result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( $result != false ) ? mysql_data_seek($result, $row) : false;
	}
	
	/**
	 * sql::next_id()
	 * 
	 * Retourne l'identifiant g�n�r� par la derni�re requ�te INSERT
	 * 
	 * @access public
	 * @return mixed
	 */
	function next_id()
	{
		return ( $this->connect_id != false ) ? @mysql_insert_id($this->connect_id) : false;
	}
	
	/**
	 * sql::free_result()
	 * 
	 * Lib�re le r�sultat de la m�moire
	 * 
	 * @param resource $result  Ressource de r�sultat de requ�te
	 * 
	 * @access public
	 * @return void
	 */
	function free_result($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		if( $result != false )
		{
			@mysql_free_result($result);
		}
	}
	
	/**
	 * sql::escape()
	 * 
	 * �chappe une cha�ne de caract�re en pr�vision de son insertion dans la base de donn�es
	 * 
	 * @param string $str
	 * 
	 * @access public
	 * @return string
	 */
	function escape($str)
	{
		return mysql_escape_string($str);
	}
	
	/**
	 * sql::close()
	 * 
	 * Cl�t la connexion � la base de donn�es
	 * 
	 * @access public
	 * @return boolean
	 */
	function close()
	{
		if( $this->connect_id != false )
		{
			$this->free_result($this->query_result);
			$this->transaction(END_TRC);
			
			$result = @mysql_close($this->connect_id);
			$this->connect_id   = null;
			$this->query_result = null;
			
			return $result;
		}
		else
		{
			return false;
		}
	}
}// fin de la classe

class sql_backup {
	/**
	 * Fin de ligne
	 * 
	 * @var string
	 * @access public
	 */
	var $eol = "\n";
	
	/**
	 * Protection des noms de table et de colonnes avec un quote invers� ( ` )
	 * 
	 * @var boolean
	 * @access public
	 */
	var $protect_name = TRUE;
	
	/**
	 * sql_backup::header()
	 * 
	 * G�n�ration de l'en-t�te du fichier de sauvegarde
	 * 
	 * @param string $dbhost    H�te de la base de donn�es
	 * @param string $dbname    Nom de la base de donn�es
	 * @param string $toolname  Nom de l'outil utilis� pour g�n�rer la sauvegarde
	 * 
	 * @access public
	 * @return string
	 */
	function header($dbhost, $dbname, $toolname = '')
	{
		$contents  = '-- ' . $this->eol;
		$contents .= "-- $toolname MySQL Dump" . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= "-- Serveur  : $dbhost" . $this->eol;
		$contents .= "-- Database : $dbname" . $this->eol;
		$contents .= '-- Date     : ' . date('d/m/Y H:i:s') . $this->eol;
		$contents .= '-- ' . $this->eol;
		$contents .= $this->eol;
		
		return $contents;
	}
	
	/**
	 * sql_backup::get_tables()
	 * 
	 * Retourne la liste des tables pr�sentes dans la base de donn�es consid�r�e
	 * 
	 * @param string $dbname
	 * 
	 * @access public
	 * @return array
	 */
	function get_tables($dbname)
	{
		global $db;
		
		$quote = ( $this->protect_name ) ? '`' : '';
		
		if( !($result = $db->query('SHOW TABLE STATUS FROM ' . $quote . $dbname . $quote)) )
		{
			trigger_error('Impossible d\'obtenir la liste des tables', ERROR);
		}
		
		$tables = array();
		while( $row = $db->fetch_row($result) )
		{
			$tables[$row[0]] = $row[1];
		}
		
		return $tables;
	}
	
	/**
	 * sql_backup::get_table_structure()
	 * 
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
		
		$quote = ( $this->protect_name ) ? '`' : '';
		
		$contents  = '-- ' . $this->eol;
		$contents .= '-- Struture de la table ' . $tabledata['name'] . ' ' . $this->eol;
		$contents .= '-- ' . $this->eol;
		
		if( $drop_option )
		{
			$contents .= 'DROP TABLE IF EXISTS ' . $quote . $tabledata['name'] . $quote . ';' . $this->eol;
		}
		
		//
		// La requ�te 'SHOW CREATE TABLE' est disponible � partir de MySQL 3.23.20
		//
		if( version_compare(mysql_get_server_info(), '3.23.20', '>=') == true )
		{
			if( !($result = $db->query('SHOW CREATE TABLE `' . $tabledata['name'] . '`')) )
			{
				trigger_error('Impossible d\'obtenir la structure de la table', ERROR);
			}
			
			$create_table = $db->result($result, 0, 'Create Table');
			$create_table = preg_replace("/(\r\n?)|\n/", $this->eol, $create_table);
			
			if( !$this->protect_name )
			{
				$create_table = str_replace('`', '', $create_table);
			}
			
			$contents .= $create_table;
			
			$db->free_result($result);
		}
		else
		{
			$contents .= 'CREATE TABLE ' . $quote . $tabledata['name'] . $quote . ' (' . $this->eol;
			
			if( !($result = $db->query('SHOW FIELDS FROM ' . $quote . $tabledata['name'] . $quote)) )
			{
				trigger_error('Impossible d\'obtenir les noms des colonnes de la table', ERROR);
			}
			
			$end_line = false;
			while( $row = $db->fetch_array($result) )
			{
				if( $end_line )
				{
					$contents .= ',' . $this->eol;
				}
				
				$contents .= "\t" . $quote . $row['Field'] . $quote . ' ' . $row['Type'];
				$contents .= ( !empty($row['Default']) ) ? ' DEFAULT \'' . $row['Default'] . '\'' : '';
				$contents .= ( $row['Null'] != 'YES' ) ? ' NOT NULL' : '';
				$contents .= ( $row['Extra'] != '' ) ? ' ' . $row['Extra'] : '';
				
				$end_line = true;
			}
			$db->free_result($result);
			
			if( !($result = $db->query('SHOW KEYS FROM ' . $quote . $tabledata['name'] . $quote)) )
			{
				trigger_error('Impossible d\'obtenir les cl�s de la table', ERROR);
			}
			
			$index = array();
			while( $row = $db->fetch_array($result) )
			{
				$name = $row['Key_name'];
				
				if( $name != 'PRIMARY' && $row['Non_unique'] == 0 )
				{
					$name = 'unique=' . $name;
				}
				
				if( !isset($index[$name]) )
				{
					$index[$name] = array();
				}
				
				$index[$name][] = $quote . $row['Column_name'] . $quote;
			}
			$db->free_result($result);
			
			foreach( $index AS $var => $columns )
			{
				$contents .= ',' . $this->eol . "\t";
				
				if( $var == 'PRIMARY' )
				{
					$contents .= 'PRIMARY KEY';
				}
				else if( ereg('^unique=(.+)$', $var, $regs) )
				{
					$contents .= 'UNIQUE ' . $quote . $regs[1] . $quote;
				}
				else
				{
					$contents .= 'KEY ' . $quote . $var . $quote;
				}
				
				$contents .= ' (' . $quote . implode($quote . ', ' . $quote, $columns) . $quote . ')';
			}
			
			$contents .= $this->eol . ')' . ( ( !empty($tabledata['type']) ) ? ' TYPE=' . $tabledata['type'] : '' );
		}
		
		return $contents . ';' . $this->eol;
	}
	
	/**
	 * sql_backup::get_table_data()
	 * 
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
		
		$quote = ( $this->protect_name ) ? '`' : '';
		
		$contents = '';
		
		$sql = 'SELECT * FROM ' . $quote . $tablename . $quote;
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir le contenu de la table ' . $tablename, ERROR);
		}
		
		if( $row = $db->fetch_row($result) )
		{
			$contents  = $this->eol;
			$contents .= '-- ' . $this->eol;
			$contents .= '-- Contenu de la table ' . $tablename . ' ' . $this->eol;
			$contents .= '-- ' . $this->eol;
			
			$fields = array();
			$num_fields = $db->num_fields($result);
			for( $j = 0; $j < $num_fields; $j++ )
			{
				$fields[] = $db->field_name($j, $result);
			}
			
			$columns_list = implode($quote . ', ' . $quote, $fields);
			
			do
			{
				$contents .= 'INSERT INTO ' . $quote . $tablename . $quote . ' (' . $quote . $columns_list . $quote . ') VALUES';
				
				foreach( $row AS $key => $value )
				{
					if( !isset($value) )
					{
						$row[$key] = 'NULL';
					}
					else if( !is_numeric($value) )
					{
						$row[$key] = '\'' . $db->escape($value) . '\'';
					}
				}
				
				$contents .= '(' . implode(', ', $row) . ');' . $this->eol;
			}
			while( $row = $db->fetch_row($result) );
		}
		$db->free_result($result);
		
		return $contents;
	}
}

}
?>