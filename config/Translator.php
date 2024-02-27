<?php
// source: https://stackoverflow.com/questions/1974505/php-simple-translation-approach-your-opinion
class Translator {
    private $lang = array();
    private function findString($str,$lang) {
        if (array_key_exists($str, $this->lang[$lang])) {
            return $this->lang[$lang][$str];
        }
        return $str;
    }
    private function splitStrings($str) {
        return explode('=',trim($str));
    }
    public function __($str,$lang) {
        if (!array_key_exists($lang, $this->lang)) {
            $filepath = 'i18n/'.$lang.'.txt';
            if (file_exists($filepath)) {
                $strings = array_map(array($this,'splitStrings'),file($filepath));
                foreach ($strings as $k => $v) {
                    $this->lang[$lang][$v[0]] = $v[1];
                }
                return $this->findString($str, $lang);
            }
            else {
                return $str;
            }
        }
        else {
            return $this->findString($str, $lang);
        }
    }
}
?>