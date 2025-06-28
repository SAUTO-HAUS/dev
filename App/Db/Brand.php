<?php

namespace App\Db;

use App\Core\Container;

class Brand
{
    private $db;
    private $prefix;

    public function __construct()
    {
        $this->db = Container::get('db');
        $this->prefix = Container::get('prefix');
    }

    public function getBrands()
    {
        $stmt = $this->db->prepare('SELECT `br`,`br_nm` FROM ' . $this->prefix . '_car_list ORDER BY `br` ASC');
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}