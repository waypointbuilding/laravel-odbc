<?php

namespace Waypoint\Odbc;

use PDOStatement;

class ODBCPdoStatement extends PDOStatement
{
    protected $query;
    protected $params = [];
    protected $statement;

    public function __construct($conn, $query)
    {
        $this->query = preg_replace('/(?<=\s|^):[^\s:]++/um', '?', $query);

        $this->params = $this->getParamsFromQuery($query);
        $this->statement = odbc_prepare($conn, $this->query);
    }

    protected function getParamsFromQuery($qry)
    {
        $params = [];
        $qryArray = explode(" ", $qry);
        $i = 0;

        while (isset($qryArray[$i])) {
            if (preg_match("/^:/", $qryArray[$i]))
                $params[$qryArray[$i]] = null;
            $i++;
        }

        return $params;
    }

    public function rowCount()
    {
        return odbc_num_rows($this->statement);
    }

    public function bindValue($param, $val, $ignore = null)
    {
        $this->params[$param] = $val;
    }

    public function execute($ignore = null)
    {
        odbc_execute($this->statement, $this->params);
        $this->params = [];
    }

    public function fetchAll($how = NULL, $class_name = NULL, $ctor_args = NULL)
    {
        $records = [];
        while ($record = $this->fetch()) {
            for ($i=1; $i <= odbc_num_fields($this->statement); $i++) { 
                $fieldName = odbc_field_name($this->statement , $i);
                if(odbc_field_type($this->statement , $i)=='DECIMAL') {
                    $record[$fieldName] = floatval($record[$fieldName]);
                }
                $record[strtolower($fieldName)] = $record[$fieldName];
                unset($record[$fieldName]);
            }
            $records[] = $record;
        }
        return $records;
    }

    public function fetch($option = null, $ignore = null, $ignore2 = null)
    {
        return odbc_fetch_array($this->statement);
    }
}