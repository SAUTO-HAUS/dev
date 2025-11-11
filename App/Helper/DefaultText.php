<?php

namespace App\Helper;

use App\Services\Api999Service;

class DefaultText
{
    const CATEGORY_AUTO = 658;
    /**
     * Get all categories
     *
     * @param string $id
     * @return string
     * @throws \Exception
     */
    public function getKeywords($id): string
    {
        switch ($id) {
            case 1404:
                return 'vanzari auto in credit, vanzari auto fara prima rata, automobilincredit, automobil in credit, masinaincredit, credit auto, cumparaincredit, masina in credit, masina in credit fara prima rata, cumpara in credit, credit fara prima rata, faraprimarata, fara prima rata, 0primarata, autoinleasing, auto in leasing, leasing auto, masina in leasing, masina in lizing, schimb auto, schimb, schimb cu adaos, tradein, trade in, trade in auto';
        }

        return '';
    }

    public function getContacts($account_id): array
    {
        // Special case for Sauto-stock-extern - API doesn't return phones
        if ($account_id == 3) {
            __log("Returning hardcoded phone for Sauto-stock-extern: 37379600326", 'phone_debug.log');
            return ['37379600326'];
        }
        
        __log("Getting phones from API for account_id: " . $account_id, 'phone_debug.log');
        $phones = (new Api999Service($account_id))->getPhones();
        $contacts = [];
        if (!empty($phones['phone_numbers'])) {
            foreach ($phones['phone_numbers'] as $phone) {
                $contacts[] = $phone['phone_number'];
            }
        }
        
        // for Sauto-auto-comerciale
        if ($account_id == 2 && !in_array('37379600616', $contacts)) {
            $contacts[] = '37379600616';
        }
        
        __log("API returned contacts: " . json_encode($contacts), 'phone_debug.log');
        return $contacts;
    }
}
