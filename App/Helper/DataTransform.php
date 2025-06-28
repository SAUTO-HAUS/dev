<?php

namespace App\Helper;

class DataTransform
{
    /**
     * @param $data
     * @return array
     */
    public function getKeyValue($array): array
    {
        $features = [];
        foreach ($array['features_groups'] as $group) {
            foreach ($group['features'] as $feature) {
                $features[$feature['id']] = $feature['value'];
            }
        }

        $features['price'] = [];
        foreach ($array['price']['currencies'] as $currency) {
            $features['price'][$currency['unit']] = $currency['value'];
        }
        $features['price']['current_value'] = $array['price']['value'];
        $features['price']['current_unit'] = $array['price']['unit'];

        return $features;
    }
}
