<?php

namespace App\Services;

/**
 * Class OrderHasher
 * Menghitung ulang hash data pesanan dengan format yang sama seperti dApp:
 *   keccak256(abi.encodePacked(order_code, customer_name, whatsapp_number,
 *                              car_model, package_name, custom_notes, uint256 total_amount))
 *
 * Membutuhkan paket composer `kornrunner/keccak` (PHP tidak punya keccak256 bawaan;
 * hash('sha3-256') BERBEDA dengan keccak256 Ethereum).
 */
class OrderHasher
{
    public static function isAvailable(): bool
    {
        return class_exists(\kornrunner\Keccak::class);
    }

    /**
     * @param array $data order_code, customer_name, whatsapp_number, car_model,
     *                    package_name, custom_notes, total_amount
     * @return string|null hash 0x-prefixed, atau null jika library keccak belum terpasang
     */
    public static function hash(array $data): ?string
    {
        if (!self::isAvailable()) {
            return null;
        }

        $packed = (string) ($data['order_code'] ?? '')
            . (string) ($data['customer_name'] ?? '')
            . (string) ($data['whatsapp_number'] ?? '')
            . (string) ($data['car_model'] ?? '')
            . (string) ($data['package_name'] ?? '')
            . (string) ($data['custom_notes'] ?? '')
            . self::uint256((string) ($data['total_amount'] ?? '0'));

        return '0x' . \kornrunner\Keccak::hash($packed, 256);
    }

    /**
     * Encode bilangan bulat non-negatif (string desimal) menjadi 32 byte big-endian.
     */
    private static function uint256(string $decimal): string
    {
        $decimal = ltrim(preg_replace('/\D/', '', explode('.', $decimal)[0]), '0');
        $hex = '';
        if ($decimal === '') {
            $hex = '0';
        } elseif (function_exists('gmp_init')) {
            $hex = gmp_strval(gmp_init($decimal, 10), 16);
        } else {
            $hex = dechex((int) $decimal); // cukup untuk nominal rupiah
        }
        return hex2bin(str_pad($hex, 64, '0', STR_PAD_LEFT));
    }
}
