<?php
namespace App\Db;

use App\Core\Container;

class Adverts
{
    private $db;
    private $prefix;

    public function __construct()
    {
        $this->db = Container::get('db');
        $this->prefix = Container::get('prefix');
    }

    public function getAdverts()
    {
        $sql = 'SELECT * FROM ' . $this->prefix . '_adverts ORDER BY `id` DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAdvertById($id)
    {
        $advert = $this->db->prepare("
            SELECT * FROM " . $this->prefix . "_adverts 
            WHERE id = :id
        ");
        $advert->execute([':id' => $id]);
        return $advert->fetch(\PDO::FETCH_ASSOC);
    }

    public function getAdvertsByCarId($carId)
    {
        $sql = 'SELECT * FROM ' . $this->prefix . '_adverts WHERE `car_id`='. $carId .' ORDER BY `id` DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getActiveAdvertsByCarId($carId)
    {
        $sql = 'SELECT * FROM ' . $this->prefix . '_adverts WHERE `car_id`='. $carId .' and `active` = 1 ORDER BY `type` ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $carId
     * @param $status
     * @return void
     */
    public function changeActiveStatus($carId, $status)
    {
        $stmt = $this->db->prepare("
            UPDATE " . $this->prefix . "_adverts
            SET `active` = :status
            WHERE car_id = :id
        ");
        $stmt->execute([':id' => $carId, ':status' => $status]);
    }
}