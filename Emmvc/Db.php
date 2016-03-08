<?php

namespace Emmvc;
 


class Db
{
    protected $tableName    = '';
    protected $queries      = array();
    public    $join         = array();
    protected $text     = 0;
    protected $pdo      = array();
    public    $host     = '';
    protected $sql      = array(
            'select'    => ''
            , 'where'   => ''
            , 'order'   => ''
            , 'limit'   => ''
            , 'group'   => ''
            , 'having'  => ''
            );
 
    private function join( $join, $condition, $params = array() )
    {
        $str                    = $join . ' ON ' . $condition;
        $this->join['str'][] = $str;
        $this->join['par'][] = $params;
        return $this;
    }
 
    public function leftjoin( $join, $condition, $params = array() )
    {
        return $this->join( ' LEFT JOIN ' . $join, $condition, $params );
    }
 
    public function rightjoin( $join, $condition, $params = array() )
    {
        return $this->join( ' RIGHT JOIN ' . $join, $condition, $params );
    }
 
    public function innerjoin( $join, $condition , $params = array() )
    {
        return $this->join( ' INNER JOIN ' . $join , $condition, $params  );
    }
 
    protected function setNull()
    {
            $this->sql       = array(
            'select'    => ''
            , 'where'   => ''
            , 'order'   => ''
            , 'limit'   => ''
            , 'group'   => ''
            , 'having'  => ''
            );
            $this->tableName = NULL;
            $this->join          = array();
            $this->text          = 0;
            $this->host          = '';
    }
    public function __get( $name )
    {
        if ( $name == 'text' )
            $this->text  = 1;
            return $this;
    }
    public function __call( $methodName, $args )
    {
        $methodName = strtolower( $methodName );
 
        if ( array_key_exists( $methodName , $this->sql ) )
        {
             if ( empty( $args[0] ) || ( is_string( $args[0] ) AND trim( $args[0] === '') ))
                $this->sql[$methodName]      = '';
            else
                $this->sql[$methodName]      = $args;
 
            if ( $methodName == 'limit' )
                $this->sql[$methodName]      = $args;
        } else if ( $methodName == 'from' )
        {
            $this->tableName = $args[0];
        } else if ( $methodName == 'host' )
        {
            $this->host          = $args;
        } else
        {
            echo 'class ' . get_class( $this )
                    . " function $methodName not exists";
        }
        return $this;
    }
 
    function queryAll()
    {
        $select     = $this->sql['select']   != ''
                    ? $this->sql['select'][0]
                    : ' * ';
        $where      = '';
        $data       = array();
 
        $join       = '';
        if ( isset( $this->join['str'] ) )
        {
            foreach ( $this->join['str'] as $t_join )
            $join   .= $t_join;
 
            foreach ( $this->join['par'] as $params )
                foreach ( $params as $param)
                    $data[]     = $param;
        }
 
        if ( $this->sql['where'] !== '' )
        {
            $where  = $this->comWhere( $this->sql['where'] );
            foreach ( $where['data'] as $v )
                $data[]     = $v;
            $where  = ' WHERE ' . $where['where'];
        }
 
        $group      = '';
        if ( $this->sql['group'] != '' )
        {
                $group  = " GROUP BY " . implode( ',', array_fill( 0, count( $this->sql['group'] ) , '?' ));
                foreach ( $this->sql['group'] as $val )
                    $data[] = $val;
        }
        $having = '';
        if ( $this->sql['having'] != '')
        {
            $having = $this->comWhere( $this->sql['having'] );
            foreach ( $having['data'] as $val )
                $data[] = $val;
            $having = ' HAVING ' . $having['where'];
        }
        $order  = '';
        if ( $this->sql['order'] != '')
        {
            $order  = ' ORDER BY ' . $this->sql['order'][0];
        }
        $limit  = '';
        if ( $this->sql['limit'] != '' )
        {
            $limit  = $this->comLimit( $this->sql['limit'] );
        }
        $sql        = "SELECT $select FROM " . $this->tableName
                    . " $join $where $group $having $order $limit";
        return $this->pdo_query( $sql, $data );
    }
 
    function queryRow()
    {
        $data   = $this->queryAll();
        $data    = empty( $data[0] ) ? null : $data[0];
        return $data;
    }
 
    function insert( $array = null )
    {
        $sql = "INSERT INTO {$this->tableName}("
             . implode(',', array_keys($array)).") VALUES ("
             . implode(',', array_fill(0, count($array), '?')) . ")";
        return $this->pdo_query( $sql, array_values( $array ) );
    }
 
    function update( $array = null )
    {
        $data   = array();
        if ( is_array( $array ))
        {
            $s      = '';
            foreach ( $array as $k => $v )
            {
                $s      .= " $k = ? ,";
                $data[]  = $v;
            }
            $s      = rtrim( $s, ',' );
        } else
        {
            return;
        }
 
        $limit  = '';
 
        if ( $this->sql['where'] != '' )
        {
            $where      = $this->comWhere( $this->sql['where'] );
            $sql        = "UPDATE " . $this->tableName
                        . " SET $s WHERE " . $where['where'];
            if ( ! empty( $where['data'] ))
            {
                foreach ( $where['data'] as $v )
                    $data[]     = $v;
            }
            $order  = '';
            if ( $this->sql['order'] != '')
            {
                $order  = ' ORDER BY ' . $this->sql['order'][0];
            }
            if ( $this->sql['limit'] != '' )
            {
                $limit  = $this->comLimit( $this->sql['limit'] );
            }
            $sql    .= $order . $limit;
        } else
        {
            $sql    = "UPDATE " . $this->tableName . " SET $s LIMIT 1";
        }
        return $this->pdo_query( $sql, $data );
    }
 
    function delete()
    {
        $where  = '';
        $data   = array();
 
        if ( $this->sql['where'] != '')
        {
            $where  = $this->comWhere( $this->sql['where'] );
            $data   = $where['data'];
            $where  = ' WHERE ' . $where['where'];
        }
        $select     = $this->sql['select']   != ''
                    ? $this->sql['select'][0]
                    : '';
 
        $order  = '';
        if ( $this->sql['order'] != '')
        {
            $order  = ' ORDER BY ' . $this->sql['order'][0];
        }
 
        $limit  = '';
        if ( $this->sql['limit'] != '' )
        {
            $limit  = $this->comLimit( $this->sql['limit'] );
        }
        if ( $where == '' )
            $limit  = ' LIMIT 1 ';
 
        $sql    = 'DELETE ' . $select. ' FROM ' . $this->tableName . " $where $order $limit ";
 
        return $this->pdo_query( $sql, $data );
    }
 
    private function comLimit( $args )
    {
        if ( isset( $args[1] ))
            $limit = ' LIMIT ' . (int)$args[0] . ' , ' . (int)$args[1];
        else
            $limit = ' LIMIT ' . (int)$args[0];
        return $limit;
    }
 
    private function comWhere( $args )
    {
        $where  = ' ';
        $data   = array();
 
        if ( empty( $args ))
            return array( 'where' => '' , 'data' => $data );
        foreach ( $args as $option )
        {
            if ( empty( $option ))
            {
                $where  .= '';
                continue;
            } else if ( is_array( $option ))
            {
                foreach ( $option as $k => $v )
                {
                    if ( is_array( $v ))
                    {
                        if ( strpos( $k, '?' ))
                            $where  .= $k;
                        else
                            $where  .= $k . " IN (" . implode( ',', array_fill( 0, count($v), '?' )). ")";
                        foreach ( $v as $val )
                            $data[] = $val;
                    } else if ( strpos( $k, ' ' ))
                    {
                        $where      .= $k . '?';
                        $data[]     = $v;
                    } else
                    {
                        $where      .= "$k = ?";
                        $data[]     = $v;
                    }
                    $where  .= ' AND ';
                }
                $where = rtrim( $where, 'AND ' );
                $where .= ' OR ';
                continue;
            }
        }
        $where  = rtrim( $where, 'OR ' );
        return array( 'where' => $where, 'data' => $data );
    }
 
    public function sql( $sql, $params )
    {
        if ( false === strpos( $sql, '?' ))
            return $sql;
 
        $sql    = str_replace( '?', '"%s"', $sql );
        array_unshift( $params, $sql );
        return call_user_func_array( 'sprintf', $params );
    }
    
    
    function pdo_connect()
    {
    	if ( $this->host == '')
    		$host   = 'default';
    	else
    		$host   = $this->host[0];
    
    			if ( empty( $this->pdo[$host] ) )
    			{
    				$config[$host]['dsn']       = 'mysql:host=mysql.win;dbname=test;charset=utf8';
    				$config[$host]['user']      = 'root';
    				$config[$host]['pass']      = '123456';
    				$config[$host]['prefix']    = '';
    				$option[$host]      = array(
    						PDO::ATTR_DEFAULT_FETCH_MODE    =>PDO::FETCH_ASSOC
    						, PDO::ATTR_ERRMODE             => PDO::ERRMODE_EXCEPTION
    				);
    				$this->pdo[$host]    = new PDO( $config[$host]['dsn'], $config[$host]['user'], $config[$host]['pass'], $option[$host] );
    				$this->pdo[$host]->table_prefix       = $config[$host]['prefix'];
    			}
    			return $this->pdo[$host];
    }
    
    function pdo_query( $sql, $data = array() )
    {
    	try
    	{
    		$start_time     = microtime();
    		$return     = '';
    		$pdo        = $this->pdo_connect();
    		$sql    = str_replace( '<<_', $pdo->table_prefix, $sql );
    
    		if ( $this->text == 1 )
    		{
    			$text[]     = $sql;
    			$text[]     = $this->sql( $sql, $data );
    			$this->setNull();
    			return $text;
    		}
    
    		if ( strpos( $sql, '?' ) === FALSE )
    		{
    			$return = $pdo->query( $sql )->fetchAll();
    			if ( isset( $this->host[1] ))
    				unset( $this->pdo[$this->host[0]] );
    
    				$this->setNull();
    				return $return;
    		}
    
    		$prepare    = $pdo->prepare( $sql );
    		$result     = $prepare->execute( $data );
    
    		switch ( strtolower( $sql{0} ) )
    		{
    			case 's' :
    				$return = $prepare->fetchAll();
    				break;
    			case 'i' :
    				$return = $pdo->lastInsertid();
    				break;
    			case 'd' :
    			case 'u' :
    				$return = $prepare->rowCount();
    				break;
    			default :
    				$return = $prepare->fetchAll();
    		}
    
    		if ( isset( $this->host[1] ))
    			unset( $this->pdo[$this->host[0]] );
    
    			$end_time   = microtime();
    			$end_time   = explode( ' ', $end_time );
    			$start_time = explode( ' ', $start_time );
    			$end_time   = number_format( ( $end_time[0] + $end_time[1] ) - ( $start_time[0] + $start_time[1] ), 8);
    			$this->setNull();
    			$this->queries[] = array( $end_time, $this->sql( $sql, $data ) . "\n" . $sql );
    			return $return;
    
    	}catch( PDOException $e )
    	{
    		echo $e->getMessage();
    	}
    
    }
    
    public function begintransaction()
    {
    	$pdo    = $this->pdo_connect();
    	$pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);
    	$pdo->beginTransaction();
    }
    
    public function commit()
    {
    	$pdo    = $this->pdo_connect();
    	$pdo->commit();
    	$pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
    }
    
    public function rollback()
    {
    	$pdo    = $this->pdo_connect();
    	$pdo->rollBack();
    	$pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
    }
    public function dbVersion()
    {
    	return $this->pdo_connect()
    	->getAttribute( PDO::ATTR_SERVER_VERSION );
    }
    
    
    
    
    
    
    
    
    
 
}



