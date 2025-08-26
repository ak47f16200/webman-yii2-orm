<?php

namespace Webman\Yii2Orm\Tests;

use PHPUnit\Framework\TestCase;
use Webman\Yii2Orm\Validator\Validator;

class ValidatorTest extends TestCase
{
    public function testRequiredValidation()
    {
        $validator = new Validator([
            'username' => '',
            'email' => 'test@example.com'
        ], [
            'username' => 'required',
            'email' => 'required|email'
        ]);
        
        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->fails());
        
        $errors = $validator->errors();
        $this->assertArrayHasKey('username', $errors);
        $this->assertNotEmpty($errors['username']);
    }
    
    public function testEmailValidation()
    {
        $validator = new Validator([
            'email' => 'invalid-email'
        ], [
            'email' => 'email'
        ]);
        
        $this->assertFalse($validator->passes());
        
        // 测试有效邮箱
        $validValidator = new Validator([
            'email' => 'test@example.com'
        ], [
            'email' => 'email'
        ]);
        
        $this->assertTrue($validValidator->passes());
    }
    
    public function testStringLengthValidation()
    {
        $validator = new Validator([
            'username' => 'ab'  // 太短
        ], [
            'username' => 'string|min:3|max:50'
        ]);
        
        $this->assertFalse($validator->passes());
        
        // 测试有效长度
        $validValidator = new Validator([
            'username' => 'abcd'
        ], [
            'username' => 'string|min:3|max:50'
        ]);
        
        $this->assertTrue($validValidator->passes());
    }
    
    public function testIntegerValidation()
    {
        $validator = new Validator([
            'age' => 'not-a-number'
        ], [
            'age' => 'integer'
        ]);
        
        $this->assertFalse($validator->passes());
        
        // 测试有效整数
        $validValidator = new Validator([
            'age' => 25
        ], [
            'age' => 'integer|min:18|max:100'
        ]);
        
        $this->assertTrue($validValidator->passes());
    }
    
    public function testInValidation()
    {
        $validator = new Validator([
            'status' => 'invalid'
        ], [
            'status' => 'in:active,inactive,pending'
        ]);
        
        $this->assertFalse($validator->passes());
        
        // 测试有效值
        $validValidator = new Validator([
            'status' => 'active'
        ], [
            'status' => 'in:active,inactive,pending'
        ]);
        
        $this->assertTrue($validValidator->passes());
    }
}