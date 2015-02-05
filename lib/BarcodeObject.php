<?php

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'encoders' . DIRECTORY_SEPARATOR . 'BarcodeEncoderBase.inc';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'encoders' . DIRECTORY_SEPARATOR . 'BarcodeEncoderEAN.inc';

class BarcodeObject extends Wire
{

    protected $code, $encoder;
    protected $barColor, $backgroundColor, $textColor, $fontPath;

    public function __construct($code, BarcodeEncoderBase $encoder = null)
    {
        $this->code = $code;

        if (!$encoder instanceof BarcodeEncoderBase) {
            $this->encoder = new BarcodeEncoderEAN();
        } else {
            $this->encoder = $encoder;
        }
    }

    public function __set($x,$y) {
        $this->$x = $y;
    }

    public function base64String($scale = 2)
    {
        if ($scale < 1) $scale = 2;
        $totalY = (int)$scale * 60;
        $space = array('top' => 2 * $scale, 'bottom' => 2 * $scale, 'left' => 2 * $scale, 'right' => 2 * $scale);

        // Encode Bars
        $this->encoder->code = $this->code;
        $text = $this->encoder->text();
        $bars = $this->encoder->bars();

        // Detect total width
        $xPos = 0;
        $width = true;
        for ($i = 0; $i < strlen($bars); $i++) {
            $val = strtolower($bars[$i]);
            if ($width) {
                $xPos += ($val * $scale);
                $width = false;
                continue;
            }
            if (preg_match("#[a-z]#", $val)) {
                /* tall bar */
                $val = ord($val) - ord('a') + 1;
            }
            $xPos += $val * $scale;
            $width = true;
        }

        // Allocate images
        $totalX = ($xPos) + $space['right'] + $space['right'];
        $xPos = $space['left'];

        if (!function_exists('imagecreate')) return null;

        $im = imagecreate($totalX, $totalY);
        $colBg = ImageColorAllocate($im, $this->backgroundColor[0], $this->backgroundColor[1], $this->backgroundColor[2]);
        $colBar = ImageColorAllocate($im, $this->barColor[0], $this->barColor[1], $this->barColor[2]);
        $colText = ImageColorAllocate($im, $this->textColor[0], $this->textColor[1], $this->textColor[2]);
        $height = round($totalY - ($scale * 10));
        $height2 = round($totalY - $space['bottom']);

        // Paint the bars
        $width = true;
        for ($i = 0; $i < strlen($bars); $i++) {
            $val = strtolower($bars[$i]);
            if ($width) {
                $xPos += $val * $scale;
                $width = false;
                continue;
            }
            if (preg_match("#[a-z]#", $val)) {
                /* tall bar */
                $val = ord($val) - ord('a') + 1;
                $h = $height2;
            } else $h = $height;
            imagefilledrectangle($im, $xPos, $space['top'], $xPos + ($val * $scale) - 1, $h, $colBar);
            $xPos += $val * $scale;
            $width = true;
        }

        // Write the text
        $chars = explode(" ", $text);
        reset($chars);
        while (list($n, $v) = each($chars)) {
            if (trim($v)) {
                $inf = explode(":", $v);
                $fontSize = $scale * ($inf[1] / 1.8);
                $fontHeight = $totalY - ($fontSize / 2.7) + 2;
                @imagettftext($im, $fontSize, 0, $space['left'] + ($scale * $inf[0]) + 2,
                    $fontHeight, $colText, $this->fontPath, $inf[2]);
            }
        }

        ob_start();
        imagejpeg($im);
        $b64 = base64_encode(ob_get_clean());

        return "data:image/jpeg;base64,$b64";
    }


    public function __toString() {
        return $this->base64String(2);
    }

    public function scale($i) {
        return $this->base64String($i);
    }
} 