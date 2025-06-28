<?php

namespace App\Db;

use App\Core\Container;

class CarPhoto
{
    private $db;
    private $prefix;

    public function __construct()
    {
        $this->db = Container::get('db');
        $this->prefix = Container::get('prefix');
    }

    /**
     * Get all photos
     * @return mixed
     */
    public function getPhotos()
    {
        $sql = 'SELECT * FROM ' . $this->prefix . '_car_pht ORDER BY `id`';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get photo by id
     * @param $id
     * @return mixed
     */
    public function getPhotoById($id)
    {
        $photo = $this->db->prepare("
            SELECT * FROM " . $this->prefix . "_car_pht 
            WHERE id = :id
        ");
        $photo->execute([':id' => $id]);
        return $photo->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Get photos by car id
     * @param $carId
     * @return mixed
     */
    public function getPhotosByCarId($carId)
    {
        $sql = 'SELECT * FROM ' . $this->prefix . '_car_pht WHERE `it_id`='. $carId .' ORDER BY `main` DESC, `pos`, `id`';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get photos by car id and photo id
     * @param $carId
     * @param $photoId
     * @return mixed
     */
    public function getPhotosByCarIdAndPhotoId($carId, $photoId)
    {
        $sql = 'SELECT * FROM ' . $this->prefix . '_car_pht WHERE `it_id`='. $carId .' AND `id`='. $photoId;
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}