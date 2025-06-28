<?php

namespace App\Db;

use App\Core\Container;

class Car
{
    private $db;
    private $prefix;

    public function __construct()
    {
        $this->db = Container::get('db');
        $this->prefix = Container::get('prefix');
    }

    public function getCars()
    {
        $sql = 'SELECT * FROM ' . $this->prefix . '_car_list ORDER BY `br` ASC, `mo` ASC'; //`mo` + 0 ASC
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getLastTimeShift()
    {
        $sql = 'SELECT * FROM ' . $this->prefix . '_car_ctlg where time_shift is not null ORDER BY `id` DESC limit 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getCarListByBrand($br)
    {
        $sql = 'SELECT * FROM ' . $this->prefix . '_car_list WHERE `br`=:br ORDER BY `mo` ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['br' => $br]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getCarsCtlg($limit)
    {
        $sql = 'SELECT * FROM ' . $this->prefix . '_car_ctlg WHERE `act`="1" ORDER BY `id` DESC LIMIT ' . ($limit+1);
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getCarById($id)
    {
        $car = $this->db->prepare("
            SELECT * FROM " . $this->prefix . "_car_ctlg 
            WHERE id = :id
        ");
        $car->execute([':id' => $id]);
        return $car->fetch(\PDO::FETCH_ASSOC);
    }

    public function getCarsImg($carId, $limit = null)
    {
        $sql = 'SELECT * FROM ' . $this->prefix . '_car_pht WHERE `it_id`=:carId ORDER BY `main` DESC, `pos`, `id`';
        if ($limit) {
            $sql .= ' LIMIT ' . $limit;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['carId' => $carId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}