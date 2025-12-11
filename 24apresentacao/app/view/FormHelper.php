<?php
class FormHelper {
    public static function hiddenInputs($params) {
        $html = '';
        foreach ($params as $key => $value) {
            if (!empty($value)) {
                $html .= '<input type="hidden" name="' . htmlspecialchars($key) . '" 
                          value="' . htmlspecialchars($value) . '">';
            }
        }
        return $html;
    }
    
    public static function manterParametros($exclude = []) {
        $params = $_GET;
        foreach ($exclude as $key) {
            unset($params[$key]);
        }
        return self::hiddenInputs($params);
    }
}
?>