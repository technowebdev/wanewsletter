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
define('DATABASE', 'postgres');

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
	 * Derni�re table o� a �t� effectu� une insertion de donn�es
	 * 
	 * @var string
	 */
	var $last_insert_table = '';
	
	/**
	 * Port de connexion par d�faut
	 * 
	 * @var integer
	 */
	var $dbport       = 5432;
	
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
		$sql_connect = ( $persistent ) ? 'pg_pconnect' : 'pg_connect';
		
		$login_str = '';
		
		if( strpos($dbhost, ':') )
		{
			list($dbhost, $dbport) = explode(':', $dbhost);
			$login_str .= "host='$dbhost' port='$dbport' ";
		}
		else if( !empty($dbhost) )
		{
			$login_str .= "host='$dbhost' port='" . $this->dbport . "' ";
		}
		
		if( $dbname != '' )
		{
			$login_str .= "dbname='$dbname' ";
		}
		
		if( $dbuser != '' )
		{
			$login_str .= "user='$dbuser' ";
		}
		
		if( $dbpwd != '' )
		{
			$login_str .= "password='$dbpwd' ";
		}
		
		$this->connect_id = @$sql_connect($login_str);
		if( !$this->connect_id )
		{
			$this->sql_error['message'] = @$php_errormsg;
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
	 * Construit une requ�te de type INSERT, UPDATE ou DELETE � partir
	 * des diverses donn�es fournies
	 * 
	 * @param string $query_type  Type de requ�te (peut valoir INSERT, UPDATE ou DELETE)
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
			$query .= ' LIMIT ' . $limit . ' OFFSET ' . $start;
		}
		else if( preg_match("/^INSERT[\t\r\n ]+INTO[\t\r\n ]+([a-z0-9_-]+)/si", $query, $match) )
		{
			$this->last_insert_table = $match[1];
		}
		
		$curtime = explode(' ', microtime());
		$curtime = $curtime[0] + $curtime[1] - $starttime;
		
		$this->query_result = @pg_exec($this->connect_id, $query);
		
		$endtime = explode(' ', microtime());
		$endtime = $endtime[0] + $endtime[1] - $starttime;
		
		$this->sql_time += ($endtime - $curtime);
		$this->queries++;
		
		if( !$this->query_result )
		{
			$this->sql_error['message'] = @pg_errormessage($this->connect_id);
			$this->sql_error['query']   = $query;
			
			$this->transaction('ROLLBACK');
		}
		else
		{
			$this->sql_error = array('errno' => 0, 'message' => '', 'query' => '');
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
					$result = @pg_exec($this->connect_id, 'BEGIN');
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
					
					if( !($result = @pg_exec($this->connect_id, 'COMMIT')) )
					{
						@pg_exec($this->connect_id, 'ROLLBACK');
					}
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
					$result = @pg_exec($this->connect_id, 'ROLLBACK');
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
		if( !is_array($tables) )
		{
			$tables = array($tables);
		}
		
		foreach( $tables AS $tablename )
		{
			@pg_exec($this->connect_id, 'VACUUM ' . $tablename);
		}
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
		
		return ( $result != false ) ? @pg_numrows($result) : false;
	}
	
	/**
	 * sql::affected_rows()
	 * 
	 * Nombre de lignes affect�es par la derni�re requ�te DML
	 * 
	 * @access public
	 * @return mixed
	 */
	function affected_rows()
	{
		return ( $this->query_result != false ) ? @pg_cmdtuples($this->query_result) : false;
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
		
		return ( $result != false ) ? @pg_fetch_row($result) : false;
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
		
		return ( $result != false ) ? @pg_fetch_array($result, null, PGSQL_ASSOC) : false;
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
		while( $row = @pg_fetch_array($result, null, PGSQL_ASSOC) )
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
		
		return ( $result != false ) ? @pg_numfields($result) : false;
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
		
		return ( $result != false ) ? @pg_fieldname($result, $offset) : false;
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
			return @pg_result($result, $row, $field);
		}
		else
		{
			return @pg_result($result, $row);
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
		
		return ( $result != false ) ? pg_result_seek($result, $row) : false;
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
		if( $this->query_result != false )
		{
			$query = "SELECT currval('" . $this->last_insert_table . "_id_seq') AS last_value";
			$result_next_id =  @pg_exec($this->connect_id, $query);
			if( $result_next_id )
			{
				$row_next_id = @pg_fetch_array($result_next_id, 0, PGSQL_ASSOC);
				return ( $row_next_id ) ? $row_next_id['last_value'] : false;
			}
		}
		
		return false;
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
			@pg_freeresult($result);
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
		return str_replace("'", "''", str_replace('\\', '\\\\', $str));
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
			
			$result = @pg_close($this->connect_id);
			$this->connect_id   = null;
			$this->query_result = null;
			
			return $result;
		}
		else
		{
			return false;
		}
	}
}

//
// PostgreSQL
// - Bas� sur phpPgAdmin 2.4.2
//
class sql_backup {
	/**
	 * Fin de ligne
	 * 
	 * @var boolean
	 * @access public
	 */
	var $eol = "\n";
	
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
		global $db;
		
		$result = $db->query("SELECT VERSION()");
		
		$contents  = '/* ------------------------------------------------------------ ' . $this->eol;
		$contents .= "  $toolname PostgreSQL Dump" . $this->eol;
		$contents .= $this->eol;
		$contents .= "  Host     : $dbhost" . $this->eol;
		
		if( $db->num_rows($result) > 0 )
		{
			$server_info = $db->result($result, 0, 0);
			$contents .= "  Server   : " . $server_info . $this->eol;
		}
		
		$contents .= "  Database : $dbname" . $this->eol;
		$contents .= '  Date     : ' . date('d/m/Y H:i:s') . $this->eol;
		$contents .= ' ------------------------------------------------------------ */' . $this->eol;
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
		
		$sql = "SELECT tablename 
			FROM pg_tables 
			WHERE tablename NOT LIKE 'pg%' 
			ORDER BY tablename";
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir la liste des tables', ERROR);
		}
		
		$tables = array();
		while( $row = $db->fetch_row($result) )
		{
			$tables[$row[0]] = '';
		}
		$db->free_result($result);
		
		return $tables;
	}
	
	/**
	 * sql_backup::get_sequences()
	 * 
	 * Retourne une cha�ne de requ�te pour la reg�n�ration des s�quences
	 * 
	 * @param boolean $drop_option  Ajouter une requ�te de suppression conditionnelle de s�quence
	 * 
	 * @access public
	 * @return string
	 */
	function get_sequences($drop_option)
	{
		global $db, $backup_type;
		
		$sql = "SELECT relname 
			FROM pg_class 
			WHERE NOT relname ~ 'pg_.*' AND relkind ='S' 
			ORDER BY relname";
		if( !($result_seq = $db->query($sql)) )
		{
			trigger_error('Impossible de r�cup�rer les s�quences', ERROR);
		}
		
		$num_seq = $db->num_rows($result_seq);
		
		$contents = '';
		
		for( $i = 0; $i < $num_seq; $i++ )
		{
			$sequence = $db->result($result_seq, $i, 'relname');
			$result   = $db->query('SELECT * FROM ' . $sequence);
			
			if( $row = $db->fetch_array($result) )
			{
				if( $drop_option )
				{
					$contents .= "DROP SEQUENCE $sequence;" . $this->eol;
				}
				
				$contents .= 'CREATE SEQUENCE ' . $sequence . ' start ' . $row['last_value'] . ' increment ' . $row['increment_by'] . ' maxvalue ' . $row['max_value'] . ' minvalue ' . $row['min_value'] . ' cache ' . $row['cache_value'] . '; ' . $this->eol;
				
				if( $row['last_value'] > 1 && $backup_type != 1 )
				{
					$contents .= 'SELECT NEXTVALE(\'' . $sequence . '\'); ' . $this->eol;
				}
			}
		}
		
		return $contents;
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
	
		$contents  = '/* ------------------------------------------------------------ ' . $this->eol;
		$contents .= '  Sequences ' . $this->eol;
		$contents .= ' ------------------------------------------------------------ */' . $this->eol;
		$contents .= $this->get_sequences($drop_option);
		
		$contents .= $this->eol;
		$contents .= '/* ------------------------------------------------------------ ' . $this->eol;
		$contents .= '  Struture de la table ' . $tabledata['name'] . ' ' . $this->eol;
		$contents .= ' ------------------------------------------------------------ */' . $this->eol;
		
		if( $drop_option )
		{
			$contents .= 'DROP TABLE IF EXISTS ' . $tabledata['name'] . ';' . $this->eol;
		}
		
		$sql = "SELECT a.attnum, a.attname AS field, t.typname as type, a.attlen AS length, 
				a.atttypmod as lengthvar, a.attnotnull as notnull 
			FROM pg_class c, pg_attribute a, pg_type t 
			WHERE c.relname = '" . $tabledata['name'] . "' 
				AND a.attnum > 0 
				AND a.attrelid = c.oid 
				AND a.atttypid = t.oid 
			ORDER BY a.attnum";
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir le contenu de la table ' . $tabledata['name'], ERROR);
		}
		
		$contents .= 'CREATE TABLE ' . $tabledata['name'] . ' (' . $this->eol;
		
		while( $row = $db->fetch_array($result) )
		{
			$sql = "SELECT d.adsrc AS rowdefault 
				FROM pg_attrdef d, pg_class c 
				WHERE (c.relname = '" . $tabledata['name'] . "') 
					AND (c.oid = d.adrelid) 
					AND d.adnum = " . $row['attnum'];
			if( $res = $db->query($sql) )
			{
				$row['rowdefault'] = $db->result($res, 0, 'rowdefault');
			}
			else
			{
				unset($row['rowdefault']);
			}
			
			if( $row['type'] == 'bpchar' )
			{
				// Internally stored as bpchar, but isn't accepted in a CREATE TABLE statement.
				$row['type'] = 'char';
			}
			
			$contents .= ' ' . $row['field'] . ' ' . $row['type'];
			
			if( eregi('char', $row['type']) && $row['lengthvar'] > 0 )
			{
				$contents .= '(' . ($row['lengthvar'] - 4) . ')';
			}
			else if( eregi('numeric', $row['type']) )
			{
				$contents .= sprintf('(%s,%s)', (($row['lengthvar'] >> 16) & 0xffff), (($row['lengthvar'] - 4) & 0xffff));
			}
			
			if (!empty($row['rowdefault']))
			{
				$contents .= ' DEFAULT \'' . $row['rowdefault'] . '\'';
			}
			
			if ($row['notnull'] == 't')
			{
				$contents .= ' NOT NULL';
			}
			
			$contents .= ',' . $this->eol;
		}
		
		//
		// Generate constraint clauses for UNIQUE and PRIMARY KEY constraints
		//
		$sql = "SELECT ic.relname AS index_name, bc.relname AS tab_name, ta.attname AS column_name, 
				i.indisunique AS unique_key, i.indisprimary AS primary_key 
			FROM pg_class bc, pg_class ic, pg_index i, pg_attribute ta, pg_attribute ia 
			WHERE (bc.oid = i.indrelid) 
				AND (ic.oid = i.indexrelid) 
				AND (ia.attrelid = i.indexrelid) 
				AND (ta.attrelid = bc.oid)
				AND (bc.relname = '" . $tabledata['name'] . "') 
				AND (ta.attrelid = i.indrelid) 
				AND (ta.attnum = i.indkey[ia.attnum-1]) 
			ORDER BY index_name, tab_name, column_name";
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible de r�cup�rer les cl�s primaires et unique de la table ' . $tabledata['name'], ERROR);
		}
		
		$primary_key = '';
		$index_rows	 = array();
		
		while( $row = $db->fetch_array($result) )
		{
			if( $row['primary_key'] == 't' )
			{
				$primary_key .= ( ( $primary_key != '' ) ? ', ' : '' ) . $row['column_name'];
				$primary_key_name = $row['index_name'];
			}
			else
			{
				//
				// We have to store this all this info because it is possible to have a multi-column key...
				// we can loop through it again and build the statement
				//
				$index_rows[$row['index_name']]['table']  = $tabledata['name'];
				$index_rows[$row['index_name']]['unique'] = ($row['unique_key'] == 't') ? ' UNIQUE ' : '';
				
				if( empty($index_rows[$row['index_name']]['column_names']) )
				{
					$index_rows[$row['index_name']]['column_names'] = $row['column_name'] . ', ';
				}
				else
				{
					$index_rows[$row['index_name']]['column_names'] .= $row['column_name'] . ', ';
				}
			}
		}
		
		$index_create = '';
		if( count($index_rows) )
		{
			foreach( $index_rows AS $idx_name => $props )
			{
				$props['column_names'] = ereg_replace(', $', '', $props['column_names']);
				$index_create .= 'CREATE ' . $props['unique'] . " INDEX $idx_name ON " . $tabledata['name'] . " (" . $props['column_names'] . ');' . $this->eol;
			}
		}
		
		if( !empty($primary_key) )
		{
			$contents .= "CONSTRAINT $primary_key_name PRIMARY KEY ($primary_key)," . $this->eol;
		}
		
		//
		// Generate constraint clauses for CHECK constraints
		//
		$sql = "SELECT rcname as index_name, rcsrc 
			FROM pg_relcheck, pg_class bc 
			WHERE rcrelid = bc.oid 
				AND bc.relname = '" . $tabledata['name'] . "' 
				AND NOT EXISTS (
					SELECT * 
					FROM pg_relcheck as c, pg_inherits as i 
					WHERE i.inhrelid = pg_relcheck.rcrelid 
						AND c.rcname = pg_relcheck.rcname 
						AND c.rcsrc = pg_relcheck.rcsrc 
						AND c.rcrelid = i.inhparent
				)";
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible de r�cup�rer les clauses de contraintes de la table ' . $tabledata['name'], ERROR);
		}
		
		//
		// Add the constraints to the sql file.
		//
		while( $row = $db->fetch_array($result) )
		{
			$contents .= 'CONSTRAINT ' . $row['index_name'] . ' CHECK ' . $row['rcsrc'] . ',' . $this->eol;
		}
		
		$contents = ereg_replace(',' . $this->eol . '$', '', $contents);
		$index_create = ereg_replace(',' . $this->eol . '$', '', $index_create);
		
		$contents .= $this->eol . ');' . $this->eol;
		
		if( !empty($index_create) )
		{
			$contents .= $this->eol . $index_create;
		}
		
		return $contents;
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
		
		$contents = '';
		
		$sql = 'SELECT * FROM ' . $tablename;
		if( !($result = $db->query($sql)) )
		{
			trigger_error('Impossible d\'obtenir le contenu de la table ' . $tablename, ERROR);
		}
		
		if( $row = $db->fetch_row($result) )
		{
			$contents  = $this->eol;
			$contents .= '/* ------------------------------------------------------------ ' . $this->eol;
			$contents .= '  Contenu de la table ' . $tablename . ' ' . $this->eol;
			$contents .= ' ------------------------------------------------------------ */' . $this->eol;
			
			$fields = array();
			for( $j = 0, $n = $db->num_fields($result); $j < $n; $j++ )
			{
				$fields[] = $db->field_name($j, $result);
			}
			
			$fields = implode(', ', $fields);
			
			do
			{
				$contents .= "INSERT INTO $tablename ($fields) VALUES";
				
				foreach( $row AS $key => $value )
				{
					if( is_null($value) )
					{
						$row[$key] = 'NULL';
					}
					else
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