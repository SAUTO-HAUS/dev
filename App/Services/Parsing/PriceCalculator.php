<?php

namespace App\Services\Parsing;

use App\Core\Container;
use PDO;

class PriceCalculator
{
    private $db;
    private $prefix;

    public function __construct()
    {
        $this->db = Container::get('db');
        $this->prefix = Container::get('prefix');
    }

    /**
     * Compute the final price breakdown for one car.
     *
     * @param float|null $priceSourceEur Source price already converted to EUR
     * @param string     $countryCode    ISO-3166 alpha-2 of the source country
     * @param array      $raw            Full adapter payload (for excise inputs: fuel, engine volume, year)
     *
     * @return array|null Breakdown with line items + computed total, or null
     *                    when essential inputs are missing.
     */
    public function compute(?float $priceSourceEur, string $countryCode, array $raw): ?array
    {
        if ($priceSourceEur === null || $priceSourceEur <= 0) {
            return null;
        }

        $config = $this->loadCountryConfig($countryCode);
        if (!$config) {
            return null;
        }

        $excise = $this->computeMoldovanExcise(
            $raw['fuel_type'] ?? null,
            $raw['engine_volume'] ?? null,
            $raw['year'] ?? null
        );

        $markupAmount = $priceSourceEur * ((float)$config['markup_percent'] / 100);

        $lines = [
            'price_source' => round($priceSourceEur, 2),
            'delivery_to_moldova' => (float)$config['delivery_to_moldova'],
            'inspection' => (float)$config['inspection'],
            'evacuator_source_country' => (float)$config['evacuator_source_country'],
            'dealer_commission' => (float)$config['dealer_commission'],
            'broker_service' => (float)$config['broker_service'],
            'evacuator_chisinau' => (float)$config['evacuator_chisinau'],
            'interpol_check' => (float)$config['interpol_check'],
            'documents_processing' => (float)$config['documents_processing'],
            'recycling_tax' => (float)$config['recycling_tax'],
            'oil_change' => (float)$config['oil_change'],
            'cleaning' => (float)$config['cleaning'],
            'moldovan_excise' => $excise,
            'markup' => round($markupAmount, 2),
        ];

        $extras = json_decode($config['extra_costs'] ?? '[]', true) ?: [];
        foreach ($extras as $key => $value) {
            $lines[$key] = (float)$value;
        }

        $lines['total'] = round(array_sum($lines), 2);
        $lines['_country_code'] = $countryCode;
        $lines['_country_name'] = $config['country_name'];

        return $lines;
    }

    private function loadCountryConfig(string $countryCode): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM '.$this->prefix.'_parsing_price_config WHERE country_code = ? AND active = 1 LIMIT 1');
        $stmt->execute([$countryCode]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function computeMoldovanExcise(?string $fuelType, ?int $engineVolume, ?int $year): float
    {
        if (!$fuelType || !$engineVolume || !$year) {
            return 0.0;
        }
        return 0.0;
    }
}
