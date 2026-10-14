<?php

namespace App\Services;

use Countable;

class TodoList implements Countable
{
    private array $todos = [];

    /**
     * Todoを追加する
     */
    public function add(string $title): void
    {
        $this->todos[] = [
            'title' => $title,
            'completed' => false,
        ];
    }

    /**
     * 全てのTodoを取得する
     */
    public function getAll(): array
    {
        return $this->todos;
    }
    /**
     * Todoを完了にする
     */
    public function complete(int $index): void
    {
        if (isset($this->todos[$index])) {
            $this->todos[$index]['completed'] = true;
        }
    }
    /**
     * 未完了のTodoだけを取得する
     */
    public function getPending(): array
    {
        return array_values(array_filter(
            $this->todos,
            fn ($todo) => ! $todo['completed']
        ));
    }
    /**
     * 完了済みのTodoだけを取得する
     */
    public function getCompleted(): array
    {
        return array_values(array_filter(
            $this->todos,
            fn ($todo) => $todo['completed']
        ));
    }
    /**
     * Todoを削除する
     */
    public function remove(int $index): void
    {
        if (isset($this->todos[$index])) {
            unset($this->todos[$index]);
            $this->todos = array_values($this->todos); // インデックスを振り直す
        }
    }

    /**
     * Todoの総数を取得する
     */
    public function count(): int
    {
        return count($this->todos);
    }

    /**
     * 全てのTodoをクリアする
     */
    public function clear(): void
    {
        $this->todos = [];
    }
}
