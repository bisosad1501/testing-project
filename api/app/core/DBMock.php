<?php
/**
 * DB Mock Class cho Unit Testing
 * File: /app/core/DBMock.php
 */

// Chỉ tạo các class này nếu chưa tồn tại
if (!class_exists('DB')) {
    class DB 
    {
        private static $pdo;
        
        public static function setPDO($pdo)
        {
            self::$pdo = $pdo;
        }
        
        public static function table($table)
        {
            return new DBQueryBuilder(self::$pdo, $table);
        }
        
        public static function raw($value)
        {
            return new DBRawExpression($value);
        }
    }
}

if (!class_exists('DBRawExpression')) {
    class DBRawExpression
    {
        public $value;
        
        public function __construct($value)
        {
            $this->value = $value;
        }
        
        public function __toString()
        {
            return $this->value;
        }
    }
}

if (!class_exists('DBQueryBuilder')) {
    class DBQueryBuilder
    {
        private $pdo;
        private $table;
        private $wheres = [];
        private $selects = ['*'];
        private $joins = [];
        private $limit = null;
        private $offset = null;
        private $orderBy = [];
        
        public function __construct($pdo, $table)
        {
            $this->pdo = $pdo;
            $this->table = $table;
        }
        
        public function where($column, $operator, $value)
        {
            $this->wheres[] = [
                'column' => $column,
                'operator' => $operator,
                'value' => $value,
                'boolean' => 'AND'
            ];
            return $this;
        }
        
        public function orWhere($column, $operator, $value)
        {
            $this->wheres[] = [
                'column' => $column,
                'operator' => $operator,
                'value' => $value,
                'boolean' => 'OR'
            ];
            return $this;
        }
        
        public function select($columns)
        {
            if (is_array($columns)) {
                $this->selects = array_merge($this->selects, $columns);
            } else {
                $this->selects[] = $columns;
            }
            return $this;
        }
        
        public function leftJoin($table, $first, $operator, $second)
        {
            $this->joins[] = [
                'type' => 'LEFT',
                'table' => $table,
                'first' => $first,
                'operator' => $operator,
                'second' => $second
            ];
            return $this;
        }
        
        public function limit($limit)
        {
            $this->limit = $limit;
            return $this;
        }
        
        public function offset($offset)
        {
            $this->offset = $offset;
            return $this;
        }
        
        public function orderBy($column, $direction = 'ASC')
        {
            $this->orderBy[] = [
                'column' => $column,
                'direction' => $direction
            ];
            return $this;
        }
        
        public function count($column = '*')
        {
            $sql = "SELECT COUNT($column) as count FROM {$this->table}";
            $sql .= $this->compileWhereClauses();
            
            $stmt = $this->pdo->prepare($sql);
            $this->bindWhereValues($stmt);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        }
        
        public function get()
        {
            $sql = "SELECT " . implode(', ', $this->selects) . " FROM {$this->table}";
            
            // Add joins
            foreach ($this->joins as $join) {
                $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['first']} {$join['operator']} {$join['second']}";
            }
            
            // Add where clauses
            $sql .= $this->compileWhereClauses();
            
            // Add order by
            if (!empty($this->orderBy)) {
                $orders = [];
                foreach ($this->orderBy as $order) {
                    $orders[] = "{$order['column']} {$order['direction']}";
                }
                $sql .= " ORDER BY " . implode(', ', $orders);
            }
            
            // Add limit
            if ($this->limit !== null) {
                $sql .= " LIMIT {$this->limit}";
                
                // Add offset
                if ($this->offset !== null) {
                    $sql .= " OFFSET {$this->offset}";
                }
            }
            
            $stmt = $this->pdo->prepare($sql);
            $this->bindWhereValues($stmt);
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convert to object
            $objects = [];
            foreach ($results as $result) {
                $objects[] = (object) $result;
            }
            
            return $objects;
        }
        
        public function insert($data)
        {
            $columns = implode(', ', array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));
            
            $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(array_values($data));
            
            return $this->pdo->lastInsertId();
        }
        
        public function update($data)
        {
            $setClauses = [];
            foreach ($data as $column => $value) {
                $setClauses[] = "$column = ?";
            }
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses);
            $sql .= $this->compileWhereClauses();
            
            $stmt = $this->pdo->prepare($sql);
            
            // Bind set values
            $paramIndex = 1;
            foreach ($data as $value) {
                $stmt->bindValue($paramIndex++, $value);
            }
            
            // Bind where values
            foreach ($this->wheres as $where) {
                $stmt->bindValue($paramIndex++, $where['value']);
            }
            
            return $stmt->execute();
        }
        
        public function delete()
        {
            $sql = "DELETE FROM {$this->table}";
            $sql .= $this->compileWhereClauses();
            
            $stmt = $this->pdo->prepare($sql);
            $this->bindWhereValues($stmt);
            
            return $stmt->execute();
        }
        
        private function compileWhereClauses()
        {
            if (empty($this->wheres)) {
                return '';
            }
            
            $sql = " WHERE ";
            $firstClause = true;
            
            foreach ($this->wheres as $where) {
                if (!$firstClause) {
                    $sql .= " {$where['boolean']} ";
                }
                
                $sql .= "{$where['column']} {$where['operator']} ?";
                $firstClause = false;
            }
            
            return $sql;
        }
        
        private function bindWhereValues($stmt)
        {
            $paramIndex = 1;
            foreach ($this->wheres as $where) {
                $stmt->bindValue($paramIndex++, $where['value']);
            }
        }
    }
}