<?php
/**
 * CouponCode
 *
 * Copyright (c) 2023 Arnaud Zurawczak. All rights reserved.
 * Copyright (c) 2014 Atelier Disko. All rights reserved.
 *
 * Use of this source code is governed by a BSD-style
 * license that can be found in the LICENSE file.
 */

namespace CouponCode;

use Exception;

class CouponCode
{
    /**
     * Number of parts of the code.
     */
    private int $parts = 3;

    /**
     * Length of each part.
     */
    private int $partLength = 4;

    /**
     * Alphabet used when generating codes. Already leaves
     * easy to confuse letters out.
     *
     * @var array<string>
     */
    private array $symbols = [
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K',
        'L', 'M', 'N', 'P', 'Q', 'R', 'T', 'U', 'V', 'W',
        'X', 'Y'
    ];

    /**
     * ROT13 encoded list of bad words.
     *
     * @var array<string>
     */
    private array $badWords = [
        'SHPX', 'PHAG', 'JNAX', 'JNAT', 'CVFF', 'PBPX', 'FUVG', 'GJNG', 'GVGF', 'SNEG', 'URYY',
        'ZHSS', 'QVPX', 'XABO', 'NEFR', 'FUNT', 'GBFF', 'FYHG', 'GHEQ', 'FYNT', 'PENC', 'CBBC',
        'OHGG', 'SRPX', 'OBBO', 'WVFZ', 'WVMM', 'CUNG'
    ];

    /**
     * Constructor.
     *
     * @param array $config Available options are `parts` and `partLength`.
     */
    public function __construct(array $config = [])
    {
        $config += [
            'parts' => null,
            'partLength' => null
        ];
        if (isset($config['parts'])) {
            $this->parts = $config['parts'];
        }
        if (isset($config['partLength'])) {
            $this->partLength = $config['partLength'];
        }
    }

    /**
     * Generates a coupon code using the format `XXXX-XXXX-XXXX`.
     *
     * The 4th character of each part is a checkdigit.
     *
     * Not all letters and numbers are used, so if a person enters the letter 'O' we
     * can automatically correct it to the digit '0' (similarly for I => 1, S => 5, Z
     * => 2).
     *
     * The code generation algorithm avoids 'undesirable' codes. For example any code
     * in which transposed characters happen to result in a valid checkdigit will be
     * skipped.  Any generated part which happens to spell an 'inappropriate' 4-letter
     * word (e.g.: 'P00P') will also be skipped.
     *
     * @param ?string $random Allows to directly support a plaintext i.e. for testing.
     *
     * @return string Dash separated and normalized code.
     *
     * @throws Exception
     */
    public function generate(?string $random = null): string
    {
        $results = [];

        $plaintext = $this->convert($random ?: random_bytes(8));
        // String is already normalized by used alphabet.

        $part = $try = 0;
        while (count($results) < $this->parts) {
            $result = substr($plaintext, $try * $this->partLength, $this->partLength - 1);

            if (!$result || strlen($result) !== $this->partLength - 1) {
                throw new \RuntimeException('Ran out of plaintext.');
            }
            $result .= $this->checkDigitAlg1($part + 1, $result);

            $try++;
            if ($this->isBadWord($result)) {
                continue;
            }
            $part++;

            $results[] = $result;
        }
        return implode('-', $results);
    }

    /**
     * Validates given code. Codes are not case sensitive and
     * certain letters i.e. `O` are converted to digit equivalents
     * i.e. `0`.
     *
     * @param $code string Potentially unnormalized code.
     *
     * @return boolean
     */
    public function validate(string $code): bool
    {
        $code = $this->_normalize($code, ['clean' => true, 'case' => true]);

        if (strlen($code) !== ($this->parts * $this->partLength)) {
            return false;
        }
        $parts = str_split($code, $this->partLength);

        foreach ($parts as $number => $part) {
            $expected = substr($part, -1);
            $result = $this->checkDigitAlg1($number + 1, $x = substr($part, 0, strlen($part) - 1));

            if ($result !== $expected) {
                return false;
            }
        }
        return true;
    }

    /**
     * Implements the check digit algorithm #1 as used by the original library.
     *
     * @param integer $partNumber Number of the part.
     * @param string $value Actual part without the check digit.
     *
     * @return string The check digit symbol.
     */
    private function checkDigitAlg1(int $partNumber, string $value): string
    {
        $symbolsFlipped = array_flip($this->symbols);
        $result = $partNumber;

        foreach (str_split($value) as $char) {
            $result = $result * 19 + $symbolsFlipped[$char];
        }
        return $this->symbols[$result % (count($this->symbols) - 1)];
    }

    /**
     * Verifies that a given value is a bad word.
     */
    private function isBadWord(string $value): bool
    {
        return isset($this->badWords[str_rot13($value)]);
    }

    /**
     * Normalizes a given code using dash separators.
     */
    public function normalize(string $string): string
    {
        $string = $this->_normalize($string, ['clean' => true, 'case' => true]);

        return implode('-', str_split($string, $this->partLength));
    }

    /**
     * Converts givens string using symbols.
     */
    private function convert(string $string): string
    {
        $symbols = $this->symbols;

        $result = array_map(static fn ($value) => $symbols[ord($value) & (count($symbols) - 1)], str_split(hash('sha1', $string)));

        return implode('', $result);
    }

    /**
     * Internal method to normalize given strings.
     */
    private function _normalize(string $string, array $options = []): string
    {
        $options += [
            'clean' => false,
            'case' => false
        ];
        if ($options['case']) {
            $string = strtoupper($string);
        }
        $string = strtr($string, [
            'I' => 1,
            'O' => 0,
            'S' => 5,
            'Z' => 2,
        ]);

        if ($options['clean']) {
            $string = preg_replace('/[^0-9A-Z]+/', '', $string);
        }

        return $string;
    }
}
