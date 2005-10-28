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

define('DATABASE', 'mysql4');

class sql {
	
	var $connect_id   = '';
	var $query_result = '';
	var $trc_started  = 0;
	var $sql_error    = array('errno' => '', 'message' => '', 'query' => '');
	
	var $queries      = 0;
	var $sql_time     = 0;
	
	function sql($dbhost, $dbuser, $dbpwd, $dbname, $persistent = false)
	{
		$sql_connect = ( $persistent ) ? 'mysqli_pconnect' : 'mysqli_connect';
		
		$this->connect_id = @$sql_connect($dbhost, $dbuser, $dbpwd);
		
		if( $this->connect_id )
		{
			$select_db = @mysqli_select_db($dbname, $this->connect_id);
			
			if( !$select_db )
			{
				@mysqli_close($this->connect_id);
			}
		}
	}
	
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
	
	function query_build($query_type, $table, $query_data, $sql_where = '')
	{
		$fields = $values = array();
		
		foreach( $query_data AS $field => $value )
		{
			$fields[] = $field;
			$values[] = $this->prepare_value($value);
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
		
		$this->query_result = @mysqli_query($query, $this->connect_id);
		
		$endtime = explode(' ', microtime());
		$endtime = $endtime[0] + $endtime[1] - $starttime;
		
		$this->sql_time += ($endtime - $curtime);
		$this->queries++;
		
		if( !$this->query_result )
		{
			$this->sql_error['errno']   = @mysqli_errno($this->connect_id);
			$this->sql_error['message'] = @mysqli_error($this->connect_id);
			$this->sql_error['query']   = $query;
		}
		else
		{
			$this->sql_error = array('errno' => '', 'message' => '', 'query' => '');
		}
		
		return $this->query_result;
	}
	
	function transaction($transaction)
	{
		switch($transaction)
		{
			case START_TRC:
				if( !$this->trc_started )
				{
					$this->trc_started = true;
					$result = @mysqli_autocommit($this->connect_id, false);
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
					
					if( !($result = @mysqli_commit($this->db_connect_id)) )
					{
						@mysqli_rollback($this->connect_id);
						$result = false;
					}
					
					@mysqli_autocommit($this->connect_id, true);
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
					$result = @mysqli_rollback($this->connect_id);
					@mysqli_autocommit($this->connect_id, true);
				}
				else
				{
					$result = true;
				}
				break;
		}
		
		return $result;
	}
	
	function check($tables)
	{
		if( !is_array($tables) )
		{
			$tables = array($tables);
		}
		
		$tables_list = implode(', ', $tables);
		
		@mysqli_query('OPTIMIZE TABLE ' . $tables_list, $this->connect_id);
		
		return true;
	}
	
	function num_rows($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( $result ) ? @mysqli_num_rows($result) : false;
	}
	
	function affected_rows()
	{
		return ( $this->connect_id ) ? @mysqli_affected_rows($this->connect_id) : false;
	}
	
	function fetch_row($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( $result ) ? @mysqli_fetch_row($result) : false;
	}
	
	function fetch_array($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( $result ) ? @mysqli_fetch_array($result, MYSQL_ASSOC) : false;
	}
	
	function fetch_rowset($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		$rowset = array();
		while( $row = @mysqli_fetch_array($result, MYSQL_ASSOC) )
		{
			$rowset[] = $row;
		}
		
		return $rowset;
	}
	
	function num_fields($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		return ( $result ) ? @mysqli_num_fields($result) : false;
	}
	
	function field_name($offset, $result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		if( is_resource($result) )
		{
			$fields = mysqli_fetch_fields($result);
			return $fields[$offset]->name;
		}
		else
		{
			return false;
		}
	}
	
	function result($result, $row, $field = '')
	{
		mysqli_data_seek($result, $row);
		$data = mysqli_fetch_array($result);
		
		if( $field != '' )
		{
			return isset($data[$field]) ? $data[$field] : false;
		}
		else
		{
			return $data[0];
		}
	}
	
	function next_id()
	{
		return ( $this->connect_id ) ? @mysqli_insert_id($this->connect_id) : false;
	}
	
	function free_result($result = false)
	{
		if( !$result )
		{
			$result = $this->query_result;
		}
		
		if( is_resource($result) )
		{
			@mysqli_free_result($result);
		}
	}
	
	function escape($str)
	{
		return mysqli_real_escape_string($str, $this->connect_id);
	}
	
	function close_connexion()
	{
		if( $this->connect_id )
		{
			$this->free_result($this->query_result);
			$this->transaction(END_TRC);
			
			return @mysqli_close($this->connect_id);
		}
		else
		{
			return false;
		}
	}
}// fin de la classe

?>