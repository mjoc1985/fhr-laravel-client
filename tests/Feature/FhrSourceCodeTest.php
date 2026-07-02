<?php

use Mjoc1985\Fhr\Enums\FhrSourceCode;

describe('FhrSourceCode', function () {
    describe('fromDiscountPercentage (non-strict / default)', function () {
        it('returns SPS0 for 0% discount', function () {
            expect(FhrSourceCode::fromDiscountPercentage(0))->toBe(FhrSourceCode::SPS0);
        });

        it('returns SPS0 for negative percentages', function () {
            expect(FhrSourceCode::fromDiscountPercentage(-5))->toBe(FhrSourceCode::SPS0);
        });

        it('returns SPS30 for any positive discount', function () {
            expect(FhrSourceCode::fromDiscountPercentage(1))->toBe(FhrSourceCode::SPS35);
            expect(FhrSourceCode::fromDiscountPercentage(10))->toBe(FhrSourceCode::SPS35);
            expect(FhrSourceCode::fromDiscountPercentage(15))->toBe(FhrSourceCode::SPS35);
            expect(FhrSourceCode::fromDiscountPercentage(25))->toBe(FhrSourceCode::SPS35);
        });
    });

    describe('fromDiscountPercentage (strict)', function () {
        it('returns SPS0 for 0%', function () {
            expect(FhrSourceCode::fromDiscountPercentage(0, strict: true))->toBe(FhrSourceCode::SPS0);
        });

        it('maps to tiered source codes', function () {
            expect(FhrSourceCode::fromDiscountPercentage(5, strict: true))->toBe(FhrSourceCode::SPS10);
            expect(FhrSourceCode::fromDiscountPercentage(10, strict: true))->toBe(FhrSourceCode::SPS10);
            expect(FhrSourceCode::fromDiscountPercentage(15, strict: true))->toBe(FhrSourceCode::SPS15);
            expect(FhrSourceCode::fromDiscountPercentage(20, strict: true))->toBe(FhrSourceCode::SPS20);
            expect(FhrSourceCode::fromDiscountPercentage(25, strict: true))->toBe(FhrSourceCode::SPS25);
            expect(FhrSourceCode::fromDiscountPercentage(30, strict: true))->toBe(FhrSourceCode::SPS30);
            expect(FhrSourceCode::fromDiscountPercentage(35, strict: true))->toBe(FhrSourceCode::SPS35);
        });
    });

    describe('maxDiscountPercentage', function () {
        it('returns correct max percentages for each source code', function () {
            expect(FhrSourceCode::SPS0->maxDiscountPercentage())->toBe(0);
            expect(FhrSourceCode::SPS10->maxDiscountPercentage())->toBe(10);
            expect(FhrSourceCode::SPS15->maxDiscountPercentage())->toBe(15);
            expect(FhrSourceCode::SPS20->maxDiscountPercentage())->toBe(20);
            expect(FhrSourceCode::SPS25->maxDiscountPercentage())->toBe(25);
            expect(FhrSourceCode::SPS30->maxDiscountPercentage())->toBe(30);
            expect(FhrSourceCode::SPS35->maxDiscountPercentage())->toBe(35);
        });
    });

    describe('value', function () {
        it('returns the correct string value', function () {
            expect(FhrSourceCode::SPS0->value)->toBe('SPS0');
            expect(FhrSourceCode::SPS10->value)->toBe('SPS10');
            expect(FhrSourceCode::SPS15->value)->toBe('SPS15');
            expect(FhrSourceCode::SPS20->value)->toBe('SPS20');
            expect(FhrSourceCode::SPS25->value)->toBe('SPS25');
            expect(FhrSourceCode::SPS30->value)->toBe('SPS30');
            expect(FhrSourceCode::SPS35->value)->toBe('SPS35');
        });
    });
});
