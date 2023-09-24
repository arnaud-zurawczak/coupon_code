<?php
/**
 * CouponCode
 *
 * Copyright (c) 2023 Arnaud Zurawczak. All rights reserved.
 * Copyright (c) 2014 Marius Wilms. All rights reserved.
 *
 * Use of this source code is governed by a BSD-style
 * license that can be found in the LICENSE file.
 */

use CouponCode\CouponCode;
use PHPUnit\Framework\TestCase;

class CouponCodeTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testGenerateUsingSeed(): void
    {
        $subject = new CouponCode();

        $result0 = $subject->generate('123456890');
        $this->assertEquals('QLMM-J46Q-46RT', $result0);

        $result1 = $subject->generate('12345689A');
        $this->assertEquals('G3L2-J1PF-RRKT', $result1);
    }

    public function testGenerateDiffer(): void
    {
        $subject = new CouponCode();

        $result0 = $subject->generate();
        $result1 = $subject->generate();

        $this->assertNotEquals($result0, $result1);
    }

    public function testGenerateFormat(): void
    {
        $subject = new CouponCode();

        $result = $subject->generate('123456890');
        $this->assertMatchesRegularExpression(
            '/^[0-9A-Z-]+$/',
            $result,
            'code comprises uppercase letter, digits and dashes'
        );
        $this->assertMatchesRegularExpression(
            '/^\w{4}\-\w{4}\-\w{4}$/',
            $result,
            'pattern is XXXX-XXXX-XXXX'
        );
    }

    public function testNormalize(): void
    {
        $subject = new CouponCode();

        $this->assertEquals('1K7Q-CTFM-LMTC', $subject->normalize('1k7q-ctfm-lmtc'));
        $this->assertEquals('190D-V467-8D52', $subject->normalize('I9oD-V467-8D52'));
    }

    public function testNormalize3PartsRandomGenerated(): void
    {
        $subject = new CouponCode(['parts' => 3]);

        for ($i = 0; $i < 200; $i++) {
            $code = $subject->generate();
            $this->assertEquals($subject->normalize($code), $code);
        }
    }

    public function testValidate(): void
    {
        $subject = new CouponCode();
        $this->assertTrue($subject->validate('1K7Q-CTFM-LMTC'));
        $this->assertFalse($subject->validate('1K7Q-CTFM'));
        $this->assertTrue($subject->validate('1k7q-ctfm-lmtc'));

        $subject = new CouponCode(['parts' => 1]);
        $this->assertTrue($subject->validate('1K7Q'));
        $this->assertFalse($subject->validate('1K7C'));

        $subject = new CouponCode(['parts' => 2]);
        $this->assertTrue($subject->validate('1K7Q-CTFM'));
        $this->assertFalse($subject->validate('1K7C-CTFW'));

        $subject = new CouponCode(['parts' => 3]);
        $this->assertTrue($subject->validate('1K7Q-CTFM-LMTC'));
        $this->assertFalse($subject->validate('1K7C-CTFW-LMT1'));
    }

    public function testValidate3PartsRandomGenerated(): void
    {
        $subject = new CouponCode(['parts' => 3]);

        for ($i = 0; $i < 200; $i++) {
            $code = $subject->generate();
            $this->assertTrue($subject->validate($code), 'Code was ' . $code);
        }
    }

    public function testValidateOrderMatters(): void
    {
        $subject = new CouponCode(['parts' => 2]);

        $this->assertTrue($subject->validate('1K7Q-CTFM'));
        $this->assertFalse($subject->validate('CTFM-1K7Q'));
    }

    public function testValidateShortCode(): void
    {
        $subject = new CouponCode();
        $this->assertFalse($subject->validate('1K7Q-CTFM'));

        $subject = new CouponCode(['parts' => 2]);
        $this->assertTrue($subject->validate('1K7Q-CTFM'));
    }
}
