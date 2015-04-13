<?php
namespace ParagonIE\AsgardClient\Storage\Adapters;

use \ParagonIE\AsgardClient as Asgard;

class SqliteAdapter implements DBAdapterInterface
{
    private $pdo;
    
    public function __construct($path = ':memory:')
    {
        if ($path instanceof \PDO) {
            $this->pdo = $path;
        } else {
            $this->pdo = new \PDO('sqlite:'.$path);
        }
    }
    
    /**
     * Just perform a direct lookup
     * 
     * @param string $queryString
     * @param array $params
     * @return array
     */
    public function run($queryString, $params = [])
    {
        $statement = $this->pdo->prepare($queryString);
        if ($statement->execute(params)) {
            return $statement->fetchAll(\PDO::FETCH_ASSOC);
        }
        throw new \Exception("SQL error\n" . print_r([$queryString, $params], true));
    }
    /**
     * Just perform a direct lookup
     * 
     * @param string $queryString
     * @param array $params
     * @return array
     */
    public function single($queryString, $params = [])
    {
        $statement = $this->pdo->prepare($queryString);
        if ($statement->execute($params)) {
            return $statement->fetchColumn(0);
        }
        throw new \Exception("SQL error\n" . print_r([$queryString, $params], true));
    }
    
    /**
     * Delete rows from a particular table
     * 
     * @param string $table
     * @param array $where
     * @return boolean
     */
    public function delete($table, $where = [])
    {
        $queryString = "DELETE FROM ".Asgard\Utilities::escapeSqlIdentifier($table);
        $params = [];
        if (!empty($where) && is_array($where)) {
            $queryString .= ' WHERE ';
            $preface = false;
            foreach ($where as $i => $v) {
                if ($preface) {
                    $queryString .= ' AND ';
                }
                $queryString .= Asgard\Utilities::escapeSqlIdentifier($i).' = ?';
                $params []= $v;
            }
        } else {
            return false;
        }
        
        $statement = $this->pdo->prepare($queryString);
        if ($statement instanceof \PDOStatement) {
            return $statement->execute($params);
        }
        throw new \Exception(\print_r([$statement, $queryString, $params], 1));
    }
    
    /**
     * Get the PDO objcet for this particular database adapter
     * 
     * @return PDO
     */
    public function getPdo()
    {
        return $this->pdo;
    }
    
    /**
     * Insert a row into the database
     * 
     * @param type $table
     * @return type
     */
    public function insert($table, $rows)
    {
        $keys = \array_keys($rows);
        $vals = \array_values($rows);
        array_walk($keys, ['\ParagonIE\AsgardClient\Utilities', 'escapeSqlIdentifier']);
        
        $queryString = "INSERT INTO ".Asgard\Utilities::escapeSqlIdentifier($table).' ('.
                implode(', ', $keys).
            ') VALUES ('.
                implode(', ', \array_fill(0, count($vals), '?')).
            ')';
        
        $statement = $this->pdo->prepare($queryString);
        return $statement->execute($vals);
    }
    
    /**
     * Select values from a table
     * 
     * @param string $table
     * @param array $columns ['']
     * @param array $where {'': ''}
     * @param array $orderBy [ ['column', 'ASC'], 'column2', 'DESC'] ]
     * @param array $groupBy ['']
     * @param int|null $offset
     * @param int|null $limit
     * @return type
     * @throws \Exception
     * @return array
     */
    public function select(
        $table,
        $columns = [],
        $where = [],
        $orderBy = [],
        $groupBy = [],
        $offset = 0,
        $limit = null
    ) {
        $queryString = "SELECT ";
        $params = [];
        
        // SELECT columns
        if (empty($columns) || $columns == '*') {
            $queryString .= ' * ';
        } elseif (is_string($columns)) {
            $queryString .= ' '.$columns.' ';
        } else {
            $_cols = $columns;
            array_walk($_cols, ['Asgard\Utilities', 'escapeSqlIdentifier']);
            $queryString .= implode(', ', $_cols).' ';
            unset($_cols);
        }
        
        // FROM table
        $queryString .= ' FROM '.Asgard\Utilities::escapeSqlIdentifier($table).' ';
        
        // WHERE
        if (!empty($where)) {
            $queryString .= ' WHERE ';
        }
        if (\is_array($where)) {
            $preface = false;
            foreach ($where as $i => $v) {
                if ($preface) {
                    $queryString .= ' AND ';
                }
                $queryString .= ' '.Asgard\Utilities::escapeSqlIdentifier($i).' = ? ';
                $params []= $v;
                $preface = true;
            }
        }
        
        // GROUP BY
        if (!empty($groupBy)) {
            $queryString .= ' GROUP BY ';
            
            $preface = false;
            foreach ($groupBy as $sort) {
                if ($preface) {
                    $queryString .= ',';
                }
                $queryString .= ' '.Asgard\Utilities::escapeSqlIdentifier($sort);
                $preface = true;
            }
        }
        
        // ORDER BY
        if (!empty($orderBy)) {
            $queryString .= ' ORDER BY ';
            
            $preface = false;
            foreach ($orderBy as $sort) {
                if ($preface) {
                    $queryString .= ',';
                }
                if (count($sort) !== 2) {
                    $column = \array_shift($sort);
                    $direction = 'ASC';
                } else {
                    list($column, $direction) = $sort;
                    if (!preg_match('#^(A|DE)SC$#', $direction)) {
                        $direction = 'ASC';
                    }
                }
                $queryString .= ' '.Asgard\Utilities::escapeSqlIdentifier($column).' '.$direction.'';
                $preface = true;
            }
        }
        
        // LIMIT AND OFFSET
        if ($limit !== null) {
            $queryString .= ' LIMIT ' . (int) $limit;
            if ($offset > 0) {
                $queryString .= ' OFFSET ' . (int) $offset . ' ';
            }
        } elseif (!empty($offset)) {
            $queryString .= ' OFFSET '. (int) $offset. ' ';
        }
        
        // Now let's run the query and fetch some results
        $statement = $this->pdo->prepare($queryString);
        if ($statement instanceof \PDOStatement) {
            if ($statement->execute($params)) {
                return $statement->fetchAll(\PDO::FETCH_ASSOC);
            }
        }
        throw new \Exception("SQL error\n" . print_r([$queryString, $params], true));
    }
    /**
     * Select a single cell from a table
     * 
     * @param string $table
     * @param array $columns ['']
     * @param array $where {'': ''}
     * @param array $orderBy [ ['column', 'ASC'], 'column2', 'DESC'] ]
     * @param array $groupBy ['']
     * @param int|null $offset
     * @return type
     * @throws \Exception
     * @return array
     */
    public function selectOne(
        $table,
        $columns = [],
        $where = [],
        $orderBy = [],
        $groupBy = [],
        $offset = 0
    ) {
        $results = $this->select($table, $columns, $where, $orderBy, $groupBy, $offset, 1);
        while (\is_array($results)) {
            $results = \array_shift($results);
        }
        return $results;
    }
    
    /**
     * Select a single row from a table
     * 
     * @param string $table
     * @param array $columns ['']
     * @param array $where {'': ''}
     * @param array $orderBy [ ['column', 'ASC'], 'column2', 'DESC'] ]
     * @param array $groupBy ['']
     * @param int|null $offset
     * @return type
     * @throws \Exception
     * @return array
     */
    public function selectRow(
        $table,
        $columns = [],
        $where = [],
        $orderBy = [],
        $groupBy = [],
        $offset = 0
    ) {
        $results = $this->select($table, $columns, $where, $orderBy, $groupBy, $offset, 1);
        return \array_shift($results);
    }
    
    /**
     * Update rows in a SQLite database
     * 
     * @param string $table
     * @param string $rows
     * @param string $where
     * @return type
     * @throws \Exception
     */
    public function update($table, $rows, $where = [])
    {
        // UPDATE [table]
        
        $queryString = ' UPDATE '.Asgard\Utilities::escapeSqlIdentifier($table).' ';
        $params = [];
        
        // SET
        
        $preface = false;
        foreach ($rows as $i => $v) {
            if ($preface) {
                $queryString .= ', ';
            }
            $queryString .= Asgard\Utilities::escapeSqlIdentifier($i).' = ?';
            $params[] = $v;
            $preface = true;
        }
        
        // WHERE
        
        $queryString .= ' WHERE ';
        if (empty($where)) {
            $queryString .= ' 1';
            // Sanity check. Use a direct query if you need to update all rows.
            throw new \Exception(
                "Please don't use update() without a where clause.\n".
                print_r([$queryString, $params], true)
            );
        }
        
        $preface = false;
        foreach ($where as $i => $v) {
            if ($preface) {
                $queryString .= ' AND ';
            }
            $queryString .= ' '.Asgard\Utilities::escapeSqlIdentifier($i).' = ? ';
            $params []= $v;
            $preface = true;
        }
        $statement = $this->pdo->prepare($queryString);
        return $statement->execute($params);
    }
    
    /**
     * Create the tables necessary for ASGard to run
     * 
     * @param string $label
     * @return boolean
     */
    public function createTables($label = '') {
        return $this->pdo->exec(
            \file_get_contents(ASGARD_ROOT.'/data/ddl/sqlite_'.$label.'.sql')
        );
    }
}
