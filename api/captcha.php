<?php
/**
 * 验证码生成端点
 */
require_once __DIR__ . '/../includes/Captcha.php';

$captcha = new Captcha();
$captcha->generate();
