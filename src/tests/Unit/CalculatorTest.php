<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Services\Calculator;
use InvalidArgumentException; 

class CalculatorTest extends TestCase
{
    /**
     * 2つの数の引き算が出来るテスト.
     */
    #[Test]
    public function it_can_subtract_two_numbers()
    {
        $calculator = new Calculator();
        $result = $calculator->subtract(5, 3);
        $this->assertEquals(2, $result); // 結果が期待通りであることを確認

        $this->assertIsInt($result); // 結果が整数であることを確認
        $this->assertTrue($result > 0); // 結果が正の数であることを確認
        $this->assertFalse($result < 0); // 結果が負の数でないことを確認
    }
    
    /**
     * 2つの数掛け算が出来るテスト.
     */
    #[Test]
    public function it_can_multiply_two_numbers()
    {
        $calculator = new Calculator();
        $result = $calculator->multiply(5, 3);
        $this->assertEquals(15, $result); // 結果が期待通りであることを確認
        $this->assertIsInt($result); // 結果が整数であることを確認
        $this->assertTrue($result > 0); // 結果が正の数であることを確認
        $this->assertFalse($result < 0); // 結果が負の数でないことを確認
        $this->assertGreaterThan(10, $result); // 結果が10より大きいことを確認
        $this->assertLessThan(20, $result); // 結果が20より小さいことを確認
        
    }
    /**
 * 割り算ができることをテストする
 */
#[Test]
public function it_can_divide_two_numbers()
{
    // Arrange
    $calculator = new Calculator();

    // Act
    $result = $calculator->divide(10, 2);

    // Assert
    $this->assertEquals(5, $result);
}
/**
 * 0で割ると例外が投げられることをテストする
 */
#[Test]
public function it_throws_exception_when_dividing_by_zero()
{
    // Arrange
    $calculator = new Calculator();

    // Assert - 例外が投げられることを期待
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('0で割ることはできません');

    // Act - 例外が投げられるはず
    $calculator->divide(10, 0);
}
}
