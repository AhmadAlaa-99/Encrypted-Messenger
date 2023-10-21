<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CryptionController extends Controller
{
    public function encryptMessage($message, $key)
    {
        // تحويل المفتاح إلى الحجم الصحيح لاستخدامه في عملية التشفير
        $key = str_pad($key, 32, "\0");
        
        // تشفير الرسالة باستخدام الخوارزمية AES
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC'));
        $encryptedMessage = openssl_encrypt($message, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        
        // إرجاع النص المشفر والقيمة المبدئية للتوازن
        return base64_encode($iv . $encryptedMessage);
    }
    public function decryptMessage($encryptedMessage, $key)
    {
        // تحويل المفتاح إلى الحجم الصحيح لاستخدامه في عملية فك التشفير
        $key = str_pad($key, 32, "\0");
        
        // فك تشفير الرسالة باستخدام الخوارزمية AES
        $encryptedMessage = base64_decode($encryptedMessage);
        $ivLength = openssl_cipher_iv_length('AES-256-CBC');
        $iv = substr($encryptedMessage, 0, $ivLength);
        $encryptedMessage = substr($encryptedMessage, $ivLength);
        $decryptedMessage = openssl_decrypt($encryptedMessage, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return $decryptedMessage;   
    }
}
