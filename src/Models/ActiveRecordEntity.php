<?php

namespace src\Models;

use src\Services\Db;

abstract class ActiveRecordEntity
{
    protected $id;

    public function getId(): int
    {
        return $this->id;
    }

    public static function getById(int $id): ?self
    {
        $db = Db::getInstance();

        $entities = $db->query(
            'SELECT * FROM `' . static::getTableName() . '` WHERE id=:id;',
            [':id' => $id],
            static::class
        );

        return $entities ? $entities[0] : null;
    }

    public function __set(string $name, $value)

    {
        $camelCaseName = $this->underscoreToCamelCase($name);

        $this->$camelCaseName = $value;
    }

    private function mapPropertiesToDbFormat(): array
    {
        $reflector = new \ReflectionObject($this);
        $properties = $reflector->getProperties();

        $mappedProperties = [];

        foreach ($properties as $property) {
            $propertyName = $property->getName();

            $propertyNameAsUnderscore = $this->camelCaseToUnderscore($propertyName);

            $mappedProperties[$propertyNameAsUnderscore] = $this->$propertyName;
        }

        return $mappedProperties;
    }

    private function underscoreToCamelCase(string $source): string
    {
        return lcfirst(str_replace('_', '', ucwords($source, '_')));
    }

    private function camelCaseToUnderscore(string $source): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $source));
    }

    private function update(array $mappedProperties): void
    {
        $columns2params = [];
        $params2values = [];

        $index = 1;

        foreach ($mappedProperties as $column => $value) {
            $param = ':param' . $index; // :param1
            $columns2params[] = $column . ' = ' . $param; // column1 = :param1

            $params2values[$param] = $value; // [:param1 => value1]
            $index++;

        }

        $sql = 'UPDATE ' . static::getTableName() . ' SET ' . implode(', ', $columns2params) . ' WHERE id = ' . $this->id;

        $a = implode(', ', $columns2params);

        print_r($a);

        echo '<pre>';
        var_dump($sql);
        echo '</pre>';
        echo '<pre>';
        var_dump($params2values);
        echo '</pre>';

        $db = Db::getInstance();

        $db->query($sql, $params2values, static::class);

//        var_dump($sql);
//
//        var_dump($params2values);

//        echo '<pre>';
//        var_dump($columns2params);
//        echo '</pre>';
//        echo '<pre>';
//        var_dump($params2values);
//        echo '</pre>';
//        var_dump($columns2params);
//        var_dump($params2values);
    }

    private function insert(array $mappedProperties): void
    {

        $filteredProperties = array_filter($mappedProperties);

        $columns = [];
        $paramsNames = [];
        $params2values = [];

        foreach ($filteredProperties as $columnName => $value) {
            $columns[] = '`' . $columnName. '`';

            $paramName = ':' . $columnName;
            $paramsNames[] = $paramName;
            $params2values[$paramName] = $value;
        }

        $columnsViaSemicolon = implode(', ', $columns);
        $paramsNamesViaSemicolon = implode(', ', $paramsNames);

        $sql = 'INSERT INTO ' . static::getTableName() . ' (' . $columnsViaSemicolon . ') VALUES (' . $paramsNamesViaSemicolon . ');';

        $db = Db::getInstance();
        $db->query($sql, $params2values, static::class);

//        echo '<pre>';
//        var_dump($sql);
//        echo '</pre>';
//
//        echo '<pre>';
//        var_dump($columns);
//        echo '</pre>';
//
//        echo '<pre>';
//        var_dump($paramsNames);
//        echo '</pre>';
//
//        echo '<pre>';
//        var_dump($params2values);
//        echo '</pre>';
    }

    public function delete(): void
    {
        $db = Db::getInstance();

        $db->query(
            'DELETE FROM `' . static::getTableName() . '` WHERE id = :id',
            [':id' => $this->id]
        );

        $this->id = null;
    }

    public static function findOneByColumn(string $columnName, $value): ?self
    {
        $db = Db::getInstance();

        $result = $db->query(
            'SELECT * FROM `' . static::getTableName() . '` WHERE `' . $columnName . '` = :value LIMIT 1;',
            [':value' => $value],
            static::class
        );

        if ($result === []) {
            return null;
        }

        return $result[0];
    }

    public function save(): void
    {
        $mappedProperties = $this->mapPropertiesToDbFormat();

        if ($this->id !== null) {
            $this->update($mappedProperties);
        } else {
            $this->insert($mappedProperties);
        }
    }

    public static function findAll(): array
    {
        $db = Db::getInstance();

        return $db->query('SELECT * FROM `' . static::getTableName() . '`;', [], static::class);
    }

    abstract protected static function getTableName(): string;
}