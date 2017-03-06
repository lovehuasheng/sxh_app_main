<?php
    $_exec="cry";
    function ip($type = 0, $adv = false)
    {
        $type      = $type ? 1 : 0;
        static $ip = null;
        if (null !== $ip) {
            return $ip[$type];
        }

        if ($adv) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos = array_search('unknown', $arr);
                if (false !== $pos) {
                    unset($arr[$pos]);
                }

                $ip = trim($arr[0]);
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }
    function _service($path,$arr){
        $dir  = '../db/service/'.$path.'.php';
        if(file_exists($dir)){
            $return['errCode'] = 0;
            include  "../db/common.php";
            $return = include ($dir);
        }else{
            $return['errCode'] = 1;
            $return['msg']    = '服务不存在';
        }
        return $return;
    }


    function rsa_decode($input){
        $private_key_resource = '-----BEGIN PRIVATE KEY-----
MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAK8tIMgj+bQtrzJG
iVPMxnKhdFDgIeARk6HOmx+dYGXMXl4NceIeUJDwaB0noAxvRJMVRBhTTxeCV7HJ
mHQiVRKkJtAnsgVvtJGTsmgpIPDqApv/HE6IfHZtx+kseMtFw1xaIt5Xpt6+hCUP
94tGZvJuGUt4O0ot+mLNCx+0CNdpAgMBAAECgYBrR0DHMLjwLfYX3Pim2EZD1zqL
eOdl+H2n3wZC0zdAwGqeQK+YoaYHTSMFj8nFM7MUPDbKiuJp7EnWODZkEM51qr7y
R3OO/2q4cHSncNw50hSiNfhuqIe5kk4rQQvUnyNWCzOUp0ckIenKHsvGU5kQ/b68
g9aq5OtZvjGP5iu+AQJBAOaPys9+egKgFfqv77is5ZxSdCcnXXD56jJwe/ziu8O8
PezfIIaFGdITOLXAoCljNUL4MjtJ0NJvfbQUKp48lUECQQDCgPoKEo1kDMWrdkQT
Okhhu5WBOyICMnCc4f0nLCRLqhMtmn1YHu9ANCYhmZEsq9b/ATeiWnpppNATtPVu
aPApAj9a9mANfNimMIJ7ZO4u7geopN8uk1lKOU8slzRTkSCDGMFVsrIiYGDPgMXe
7yBBM+LPiRxIR9cbLuFpKoul4kECQQCazxE0ZyPGWCwUlqMEMsVdlHIgU7Jz0TW0
iGJ3hTi2SH3PNEFDnAuNLHSVFadoyLTsbkmbnSwFXbqHlOYrpLZRAkEAyqtiWufu
pp0nC/v6+7ybn5U5AfdVSFwzazopezBNCuivLbPVP6zpCmeGHVWofOfA3Gf9C4ou
aysy1HMICNALAg==
-----END PRIVATE KEY-----';
     $public_key = '-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCvLSDII/m0La8yRolTzMZyoXRQ
4CHgEZOhzpsfnWBlzF5eDXHiHlCQ8GgdJ6AMb0STFUQYU08XglexyZh0IlUSpCbQ
J7IFb7SRk7JoKSDw6gKb/xxOiHx2bcfpLHjLRcNcWiLeV6bevoQlD/eLRmbybhlL
eDtKLfpizQsftAjXaQIDAQAB
-----END PUBLIC KEY-----';
        openssl_private_decrypt(base64_decode($input),$output,$private_key_resource);
        return $output;
    }
     

    function returnAction($type = 0,$msg = '',$data=''){
        return array('errCode'=>$type,'msg'=>$msg,'data'=>$data);
    };