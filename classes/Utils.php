<?php

namespace BST;

class Utils
{
    /**
     * Use this to quantize the output of processes to ensure the buffer is separated back into the original chunks.
     *
     * @param string $output
     * @return string
     */
    public static function quantize(string $output): string
    {
        return '<qq:' . strlen($output) . '>' . $output;
    }

    /**
     * @param string $output
     * @return array
     */
    public static function dequantize(string $output): array
    {
        if (empty($output)) {
            return [];
        }

        if (preg_match('/<qq:(?P<length>\d+)>/', $output, $matches, PREG_OFFSET_CAPTURE)) {
            $length = (int) $matches['length'][0];
            $offset = (int) $matches['length'][1];
            $lengthLength = strlen($matches['length'][0]);

            return array_merge(
                0 < $offset - 4 ? [substr($output, 0, $offset - 4)] : [],
                [substr($output, $offset + $lengthLength + 1, $length)],
                self::dequantize(substr($output, $offset + $length + $lengthLength + 1))
            );
        }

        return [$output];
    }
}