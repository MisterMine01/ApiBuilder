<?php

namespace Api\Model\Type;

use Api\Model\Collection;
use Api\Services\MsQl;
use Api\Services\ORM;

class ManyToMany extends AbstractType implements SqlBasedTypeInterface
{
    private $me_object;
    private string $object_type;
    private bool $is_first;
    private string $named_me;
    private string $named_object;
    private string $middle_table_name;

    public function __construct($me_object, $object_type, bool $is_first)
    {
        $this->me_object = $me_object;
        $this->object_type = $object_type;
        $this->named_me = ORM::class_to_table_name($me_object);
        $this->named_object = ORM::class_to_table_name($object_type);
        $this->is_first = $is_first;
        if ($this->is_first) {
            $this->middle_table_name = $this->named_me . '_' . $this->named_object;
        } else {
            $this->middle_table_name = $this->named_object . '_' . $this->named_me;
        }
        parent::__construct('INT', false, false);
    }

    public function getSqlCreationType(): ?string
    {
        return null;
    }

    public function getMoreSql(): array
    {
        if (!$this->is_first) {
            return [];
        }
        $table_name = $this->middle_table_name;
        $named_me = $this->named_me;
        $named_object = $this->named_object;
        $first_id = $named_me . '_id';
        $second_id = $named_object . '_id';
        return [
            "now" => [
                'CREATE TABLE ' . $table_name . ' (' .
                    $first_id . ' INT NOT NULL,' .
                    $second_id . ' INT NOT NULL,' .
                    'PRIMARY KEY (' . $first_id . ', ' . $second_id . '))',
            ],
            "after" => [
                `ALTER TABLE ` . $table_name . ` ADD FOREIGN KEY (` . $first_id . `) REFERENCES ` . $named_me . `(id)`,
                `ALTER TABLE ` . $table_name . ` ADD FOREIGN KEY (` . $second_id . `) REFERENCES ` . $named_object . `(id)`,
            ],
        ];
    }

    public function getGoodTypedValue($value): mixed
    {
        return (string) $value;
    }

    public function getResultFromDb(MsQl $msql, string $id)
    {
        $query = "SELECT " . $this->named_object . ".* FROM " . $this->named_object .
            " INNER JOIN " . $this->middle_table_name . " ON " . $this->named_object . ".id = " . $this->middle_table_name . "." . $this->named_object . "_id" .
            " WHERE " . $this->middle_table_name . "." . $this->named_me . "_id = " . $id;
        $result = $msql->prepare($query);
        $data = $msql->execute($result);
        return new Collection($msql, $data, $this->object_type);
    }
}
