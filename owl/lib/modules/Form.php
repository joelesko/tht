<?php

namespace o;

class u_Form extends StdModule {

    private $isOpen = false;
    private $isFileUpload = false;

    function openTag($name, $params, $getRaw=false) {
        if (!$this->isOpen && $name !== 'form') {
            Owl::error("Call `Form.open()` before adding form fields.");
        }
        $ps = [];
        foreach ($params as $k => $v) {
            if ($v === true) { $v = $k; }
            if ($v === false) { $v = ''; }
            $ps []= $k . '="' . htmlspecialchars($v) . '"';
        }
        $pss = implode(' ', $ps);
        $tag = "<$name $pss>";
        $pss .= ($name == 'input') ? ' /' : '';
        return $getRaw ? "<$name $pss>" : new HtmlLockString ("<$name $pss>");
    }

    function closeTag($name, $getRaw=false) {
        return $getRaw ? "</$name>" : new HtmlLockString ("</$name>");
    }

    function u_open ($actionUrl='', $atts=[], $honeyPot=true) {

        if ($this->isOpen) { Owl::error("A form is already open."); }
        $this->isOpen = true;

        // Target current page by default
        if (!$actionUrl) { $actionUrl = Owl::getPhpGlobal('server', 'SCRIPT_NAME'); }

        $atts = uv($atts);
        $atts['action'] = $actionUrl;

        // Extra attributes to allow file uploads
        if (isset($atts['fileUpload']) && $atts['fileUpload']) {
            if (!Owl::getConfig('allowFileUploads')) {
                Owl::error("To enable file uploads, you must also set `allowFileUploads: true` in `settings/app.jcon`");
            }
            $this->isFileUpload = true;
            $atts['enctype'] = "multipart/form-data";
            $atts['method'] = 'POST';
            unset($atts['files']);
        }

        // Default method: POST
        if (!isset($atts['method'])) { $atts['method'] = 'POST'; }


        $html = $this->openTag('form', $atts);

        if ($honeyPot) {
          //  $key = $this->honeyPotKey();
        //    print_r($key); exit();
        }

        return $html;
    }

    // prevent bots by:
    //   stopping <3s submission,
    //   separating token from main payload
    //   (triggering content-based spam check)
    // CSRF: - enforce id check
    // function honeyPotKey() {
    //
    //     $ssl = ssl_
    //
    //     $full = md5(Owl::getPhpGlobal('server', 'CLIENT_IP') . '###' . Owl::getPhpGlobal('server', 'SCRIPT_FILENAME'));
    //     $keyLen = 8;
    //     $key = [
    //         'key' => substr($full, 0, $keyLen),
    //         'value' => substr($full, $keyLen)
    //     ];
    //
    //     $chars = [];
    //     for ($i = 0; $i < strlen($key['value']); $i++) {
    //         $chars []= 'String.fromCharCode(' . ord($key['value'][$i]) . ')';
    //     }
    //     $key['js'] = implode('+', $chars);
    //
    //     return $key;
    // }
    //


    function u_close () {
        if (!$this->isOpen) { Owl::error("A form is not currently open."); }
        $this->isOpen = false;
        return $this->closeTag('form');
    }

    function u_text($name, $val='', $atts=[]) {
        return $this->u_input('text', $name, $val, $atts);
    }

    function u_email($name, $val='', $atts=[]) {
        return $this->u_input('email', $name, $val, $atts);
    }

    function u_password($name, $val='', $atts=[]) {
        return $this->u_input('email', $name, $val, $atts);
    }

    function u_hidden($name, $val='', $atts=[]) {
        return $this->u_input('hidden', $name, $val, $atts);
    }

    function u_button($val='', $atts=[]) {
        return $this->u_input('button', '', $val, $atts);
    }

    function u_submit($val, $atts=[]) {
        return $this->u_input('submit', '', $val, $atts);
    }

    function u_input($type, $name, $val='', $atts=[]) {
        $atts = uv($atts);
        $atts['id'] = 'field_' . $name;
        $atts['name'] = $name;
        $atts['value'] = $val;
        $atts['type'] = $type;
        return $this->openTag('input', $atts);
    }

    function u_label($name, $text, $atts=[]) {
        $atts = uv($atts);
        $atts['for'] = 'field_' . $name;
        $html = $this->openTag('label', $atts, true) . $text . '</label>';
        return new HtmlLockString ($html);
    }

    function checkable($type, $name, $value, $label, $atts=[]) {
        $atts = uv($atts);
        if (isset($atts['on'])) {
            $atts['checked'] = 'checked';
            unset($atts['on']);
        }
        $html = '<label>';
        $html .= $this->u_input($type, $name, $value, $atts)->u_unlocked();
        $html .= '<span>' . $label . '</span></label>';
        return new HtmlLockString($html);
    }

    function u_checkbox($name, $value, $label, $atts=[]) {
        return $this->checkable('checkbox', $name, $value, $label, $atts);
    }

    function u_radio($name, $value, $label, $atts=[]) {
        return $this->checkable('radio', $name, $value, $label, $atts);
    }

    function u_textarea($name, $val='', $atts=[]) {
        $atts = uv($atts);
        $atts['id'] = 'field_' . $name;
        $atts['name'] = $name;
        $html = $this->openTag('textarea', $atts)->u_unlocked();
        $html .= $val . '</textarea>';
        return new HtmlLockString ($html);
    }

    function u_file($name, $atts=[]) {
        if (!$this->isFileUpload) {
            Owl::error('`Form.open()` needs `{ fileUpload: true }` to support file uploads.');
        }
        return $this->u_input('file', $name);
    }

    function u_select($name, $firstOption, $optionsHtml, $atts=[]) {
        $atts = uv($atts);
        $atts['name'] = $name;
        $html = $this->openTag('select', $atts, true);
        if ($firstOption) {
            $html .= $this->openTag('option', ['value' => ''], true) . htmlspecialchars($firstOption) . '</option>';
        }
        $html .= $optionsHtml->u_unlocked();
        $html .= '</select>';
        return new HtmlLockString ($html);
    }

    function u_options($items, $default=null, $key=null, $value=null) {
        $items = uv($items);
        if (v($items)->u_is_map()) {
            return $this->options_from_map($items, $default);
        } else if (!is_null($key)) {
            return $this->options_from_rows($items, $default, $key, $value);
        } else {
            return $this->options_from_list($items, $default);
        }
    }

    // Value = 'name' and 'value'
    function options_from_list($items, $default=null) {
        $ops = [];
        if (count($items)) {
            if (v(reset($items))->u_is_map()) {
                Owl::error('Need a `key` argument to create options from a List of Maps.');
            }
        }
        $num = 0;
        foreach ($items as $i) {
            $ops []= [ '_k' => $num, '_v' => $i ];
            $num += 1;
        }
        return $this->options($ops, $default, '_k', '_v');
    }

    // Key = 'name', value = 'value'
    function options_from_map($items, $default=null) {
        $ops = [];
        foreach ($items as $k => $v) {
            $ops []= [ '_k' => $k, '_v' => $v ];
        }
        return $this->options($ops, $default, '_k', '_v');
    }

    function options_from_rows($items, $default, $k, $v) {
        return $this->options($items, $default, $k, $v);
    }

    function options($items, $def=null, $ak=null, $av=null) {
        $html = '';
        foreach ($items as $i) {
            $i = uv($i);
            $ip = ['value' => $i[$ak]];
            if ($def === $i[$ak]) { $ip['selected'] = true; }
            $html .= $this->openTag('option', $ip, true);
            $html .= htmlspecialchars($i[$av]);
            $html .= '</option>';
        }
        return new HtmlLockString ($html);
    }

    // function honeypot() {
    //     $secret = Owl::getPhpGlobal('server');
    //
    //
    // }
    //


}



