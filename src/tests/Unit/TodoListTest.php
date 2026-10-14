<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Services\TodoList;

class TodoListTest extends TestCase
{
    /**
     * Todoを追加できることをテストする
     */
    #[Test]
    public function it_can_add_a_todo()
    {
        // Arrange
        $todoList = new TodoList();

        // Act
        $todoList->add('買い物に行く');

        // Assert
        $todos = $todoList->getAll();
        $this->assertCount(1, $todos);
        $this->assertEquals('買い物に行く', $todos[0]['title']);
    }
    /**
     * Todoを完了できることをテストする
     */
    #[Test]
    public function it_can_complete_a_todo()
    {
        // Arrange
        $todoList = new TodoList();
        $todoList->add('買い物に行く');

        // Act
        $todoList->complete(0); // インデックス0のTodoを完了にする

        // Assert
        $todos = $todoList->getAll();
        $this->assertTrue($todos[0]['completed']);
    }
    /**
     * 未完了のTodoだけを取得できることをテストする
     */
    #[Test]
    public function it_can_get_pending_todos_only()
    {
        // Arrange
        $todoList = new TodoList();
        $todoList->add('買い物に行く');
        $todoList->add('掃除をする');
        $todoList->add('勉強する');
        $todoList->complete(1); // 2つ目を完了にする

        // Act
        $pending = $todoList->getPending();

        // Assert
        $this->assertCount(2, $pending); // 未完了は2つ
        $this->assertEquals('買い物に行く', $pending[0]['title']);
        $this->assertEquals('勉強する', $pending[1]['title']);
    }

    /**
     * 完了済みのTodoだけを取得できることをテストする
     */
    #[Test]
    public function it_can_get_completed_todos_only()
    {
        // Arrange
        $todoList = new TodoList();
        $todoList->add('買い物に行く');
        $todoList->add('掃除をする');
        $todoList->add('勉強する');
        $todoList->complete(0); // 1つ目を完了にする
        $todoList->complete(2); // 3つ目を完了にする

        // Act
        $completed = $todoList->getCompleted();

        // Assert
        $this->assertCount(2, $completed); // 完了済みは2つ
        $this->assertEquals('買い物に行く', $completed[0]['title']);
        $this->assertEquals('勉強する', $completed[1]['title']);
        $this->assertTrue($completed[0]['completed']);
        $this->assertTrue($completed[1]['completed']);
    }
    /**
     * Todoを削除できることをテストする
     */
    #[Test]
    public function it_can_remove_a_todo()
    {
        // Arrange
        $todoList = new TodoList();
        $todoList->add('買い物に行く');
        $todoList->add('掃除をする');

        // Act
        $todoList->remove(0);

        // Assert
        $todos = $todoList->getAll();
        $this->assertCount(1, $todos);
        $this->assertEquals('掃除をする', $todos[0]['title']);
    }

    /**
     * Todoの総数を取得できることをテストする
     */
    #[Test]
    public function it_can_count_todos()
    {
        // Arrange
        $todoList = new TodoList();
        $this->assertSame(0, $todoList->count());

        // Act
        $todoList->add('買い物に行く');
        $todoList->add('掃除をする');

        // Assert
        $this->assertSame(2, $todoList->count());
        $this->assertCount(2, $todoList); // Countableインターフェースの動作も確認

        // 削除後も正しく反映されること
        $todoList->remove(0);
        $this->assertSame(1, $todoList->count());
        $this->assertCount(1, $todoList);
    }

    /**
     * 全てのTodoをクリアできることをテストする
     */
    #[Test]
    public function it_can_clear_all_todos()
    {
        // Arrange
        $todoList = new TodoList();
        $todoList->add('買い物に行く');
        $todoList->add('掃除をする');
        $this->assertSame(2, $todoList->count());

        // Act
        $todoList->clear();

        // Assert
        $this->assertSame(0, $todoList->count());
        $this->assertEmpty($todoList->getAll());
    }
}