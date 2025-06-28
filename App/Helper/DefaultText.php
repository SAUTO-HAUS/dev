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
        $phones = (new Api999Service($account_id))->getPhones();
        $contacts = [];
        if (!empty($phones['phone_numbers'])) {
            foreach ($phones['phone_numbers'] as $phone) {
                $contacts[] = $phone['phone_number'];
            }
        }
        return $contacts;
    }
}
