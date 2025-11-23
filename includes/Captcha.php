<?php
/**
 * 验证码生成类 - 符合黑客仓库XSS平台配色
 */
class Captcha {
    private $width = 150;
    private $height = 50;
    private $length = 4;
    private $fontSize = 20;
    
    // 黑客仓库配色 - 绿色主题
    private $bgColor = [15, 23, 42];      // 深色背景
    private $textColor = [0, 255, 65];     // 霓虹绿
    private $lineColor = [30, 50, 80];     // 线条颜色
    
    /**
     * 生成验证码
     */
    public function generate() {
        session_start();
        
        // 创建画布
        $image = imagecreatetruecolor($this->width, $this->height);
        
        // 设置背景色
        $bgColor = imagecolorallocate($image, $this->bgColor[0], $this->bgColor[1], $this->bgColor[2]);
        imagefill($image, 0, 0, $bgColor);
        
        // 生成验证码文本
        $code = $this->generateCode();
        $_SESSION['captcha'] = strtolower($code);
        $_SESSION['captcha_time'] = time();
        
        // 添加干扰线（科技感）
        $this->addLines($image);
        
        // 添加干扰点（星空效果）
        $this->addNoise($image);
        
        // 绘制验证码文本
        $this->drawText($image, $code);
        
        // 输出图像
        header('Content-Type: image/png');
        imagepng($image);
        imagedestroy($image);
    }
    
    /**
     * 生成随机验证码字符串
     */
    private function generateCode() {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // 排除易混淆字符
        $code = '';
        for ($i = 0; $i < $this->length; $i++) {
            $code .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $code;
    }
    
    /**
     * 添加干扰线 - 科技感斜线
     */
    private function addLines($image) {
        $lineColor = imagecolorallocate($image, $this->lineColor[0], $this->lineColor[1], $this->lineColor[2]);
        
        // 绘制3条斜线
        for ($i = 0; $i < 3; $i++) {
            $x1 = mt_rand(0, $this->width / 2);
            $y1 = mt_rand(0, $this->height);
            $x2 = mt_rand($this->width / 2, $this->width);
            $y2 = mt_rand(0, $this->height);
            imageline($image, $x1, $y1, $x2, $y2, $lineColor);
        }
    }
    
    /**
     * 添加干扰点 - 星空效果
     */
    private function addNoise($image) {
        // 绿色星点
        $greenColor = imagecolorallocate($image, 0, 255, 65);
        $darkGreen = imagecolorallocate($image, 0, 180, 45);
        
        for ($i = 0; $i < 50; $i++) {
            $x = mt_rand(0, $this->width);
            $y = mt_rand(0, $this->height);
            $color = ($i % 2 == 0) ? $greenColor : $darkGreen;
            imagesetpixel($image, $x, $y, $color);
        }
    }
    
    /**
     * 绘制验证码文本
     */
    private function drawText($image, $code) {
        $textColor = imagecolorallocate($image, $this->textColor[0], $this->textColor[1], $this->textColor[2]);
        
        $x = 15;
        for ($i = 0; $i < strlen($code); $i++) {
            // 随机角度和位置，增加难度
            $angle = mt_rand(-15, 15);
            $y = mt_rand($this->height / 2 + 5, $this->height / 2 + 15);
            
            // 使用内置字体
            imagestring($image, 5, $x, $y - 10, $code[$i], $textColor);
            $x += 30;
        }
    }
    
    /**
     * 验证验证码
     */
    public static function verify($input) {
        session_start();
        
        if (!isset($_SESSION['captcha']) || !isset($_SESSION['captcha_time'])) {
            return false;
        }
        
        // 验证码5分钟过期
        if (time() - $_SESSION['captcha_time'] > 300) {
            unset($_SESSION['captcha']);
            unset($_SESSION['captcha_time']);
            return false;
        }
        
        $result = (strtolower($input) === $_SESSION['captcha']);
        
        // 验证后清除
        unset($_SESSION['captcha']);
        unset($_SESSION['captcha_time']);
        
        return $result;
    }
}
