<?php

namespace PhpZip\Util;

use PhpZip\Exception\ZipException;

/**
 * Pack util
 *
 * @author Ne-Lexa alexey@nelexa.ru
 * @license MIT
 */
class PackUtil
{

    /**
     * @param int|string $longValue
     * @return string
     */
    public static function packLongLE($longValue)
    {
        if (PHP_INT_SIZE === 8 && PHP_VERSION_ID >= 506030) {
            return pack("P", $longValue);
        }

        $left = 0xffffffff00000000;
        $right = 0x00000000ffffffff;

        $r = ($longValue & $left) >> 32;
        $l = $longValue & $right;

        return pack('VV', $l, $r);
    }

    /**
     * @param string|int $value
     * @return int
     * @throws ZipException
     */
    public static function unpackLongLE($value)
    {
        if (PHP_INT_SIZE === 8 && PHP_VERSION_ID >= 506030) {
            return unpack('P', $value)[1];
        }
        $unpack = unpack('Va/Vb', $value);
        return $unpack['a'] + ($unpack['b'] << 32);
    }

    /**
     * Cast to signed int 32-bit
     *
     * @param int $int
     * @return int
     */
    public static function toSignedInt32($int)
    {
        if (PHP_INT_SIZE === 8) {
            $int = $int & 0xffffffff;
            if ($int & 0x80000000) {
                return $int - 0x100000000;
            }
        }
        return $int;
    }
}
