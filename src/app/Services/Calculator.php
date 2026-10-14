<?php

namespace App\Services;

class Calculator
{
    /**
     * 2つの数を引く
     * @param int|float $a 第一の数
     * @param int|float $b 第二の数
     * @return int|float 引き算結果
     */
    public function subtract(int|float $a, int|float $b): int|float
    {
        return $a - $b;
    }
    /**
     * 2つの数を掛ける
     * @param int|float $a 第一の数
     * @param int|float $b 第二の数
     * @return int|float 掛け算結果
     */
    public function multiply(int|float $a, int|float $b): int|float
    {
        return $a * $b;
    }

    /**
     * 2つの数を割る
     * @param int|float $a 第一の数
     * @param int|float $b 第二の数
     * @return int|float 割り算結果
     */
    public function divide(int|float $a, int|float $b): int|float
    {
        if ($b === 0) {
            throw new \InvalidArgumentException('0で割ることはできません');
        }
        return $a / $b;
    }
}