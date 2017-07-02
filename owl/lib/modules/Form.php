<?php

namespace o;

class u_Form extends StdModule {

    private $isOpen = false;
    private $isFileUpload = false;

    function openTag($name, $params, $getRaw=false) {
        if (!$this->isOpen && $name !== 'form') {
            Owl::error("Call Form.open() before adding form fields.");
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

    function u_open ($url='', $p=[], $honeyPot=true) {

        if ($this->isOpen) { Owl::error("A form is already open."); }
        $this->isOpen = true;

        if (!$url) { $url = Owl::getPhpGlobal('server', 'SCRIPT_NAME'); }

        $p = uv($p);
        $p['action'] = $url;

        if (!isset($p['files'])) {
            $this->isFileUpload = true;
            $p['enctype'] = "multipart/form-data";
            $p['method'] = 'post';
            unset($p['files']);
        }
        if (!isset($p['method'])) { $p['method'] = 'post'; }
        $html = $this->openTag('form', $p);

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
        $this->isOpen = false;
        return $this->closeTag('form');
    }

    function u_text($name, $val='', $p=[]) {
        return $this->u_input('text', $name, $val, $p);
    }

    function u_email($name, $val='', $p=[]) {
        return $this->u_input('email', $name, $val, $p);
    }

    function u_password($name, $val='', $p=[]) {
        return $this->u_input('email', $name, $val, $p);
    }

    function u_hidden($name, $val='', $p=[]) {
        return $this->u_input('hidden', $name, $val, $p);
    }

    function u_button($val='', $p=[]) {
        return $this->u_input('button', '', $val, $p);
    }

    function u_submit($val, $p=[]) {
        return $this->u_input('submit', '', $val, $p);
    }

    function u_input($type, $name, $val='', $p=[]) {
        $p = uv($p);
        $p['id'] = 'field_' . $name;
        $p['name'] = $name;
        $p['value'] = $val;
        $p['type'] = $type;
        return $this->openTag('input', $p);
    }

    function u_label($name, $text, $p=[]) {
        $p = uv($p);
        $p['for'] = 'field_' . $name;
        $html = $this->openTag('label', $p, true) . $text . '</label>';
        return new HtmlLockString ($html);
    }

    function checkable($type, $name, $value, $label, $p=[]) {
        $p = uv($p);
        if (isset($p['on'])) { $p['checked'] = 'checked'; unset($p['on']); }
        $html = '<label>';
        $html .= $this->u_input($type, $name, $value, $p)->u_unlocked();
        $html .= '<span>' . $label . '</span></label>';
        return new HtmlLockString($html);
    }

    function u_checkbox($name, $value, $label, $p=[]) {
        return $this->checkable('checkbox', $name, $value, $label, $p);
    }

    function u_radio($name, $value, $label, $p=[]) {
        return $this->checkable('radio', $name, $value, $label, $p);
    }

    function u_textarea($name, $val='', $p=[]) {
        $p = uv($p);
        $p['id'] = 'field_' . $name;
        $p['name'] = $name;
        $html = $this->openTag('textarea', $p)->u_unlocked();
        $html .= $val . '</textarea>';
        return new HtmlLockString ($html);
    }

    function u_options_range($min, $max) {
        $ops = [];
        for ($i = $min; $i <= $max; $i++) {
            $ops[$i] = $i;
        }
        return $ops;
    }

    function u_options_from_list($al, $def=null) {
        $al = uv($al);
        $l = [];
        foreach ($al as $i) {
            $l []= [ 'k' => $i, 'v' => $i ];
        }
        return $this->options($l, $def, 'k', 'v');
    }

    function u_options_from_map($al, $def=null) {
        $al = uv($al);
        $l = [];
        foreach ($al as $k => $v) {
            $l []= [ 'k' => $k, 'v' => $v ];
        }
        return $this->options($l, $def, 'k', 'v');
    }

    function u_options_from_rows($al, $def, $k, $v) {
        return $this->options($al, $def, $k, $v);
    }

    function options($items, $def=null, $ak=null, $av=null) {
        $html = '';
        $items = uv($items);
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

    function u_select($name, $startLabel, $optionsHtml, $p=[]) {
        $p = uv($p);
        $p['name'] = $name;
        $html = $this->openTag('select', $p, true);
        if ($startLabel) {
            $html .= $this->openTag('option', ['value' => ''], true) . htmlspecialchars($startLabel) . '</option>';
        }
        $html .= $optionsHtml->u_unlocked();
        $html .= '</select>';
        return new HtmlLockString ($html);
    }

    function honeypot() {
        $secret = Owl::getPhpGlobal('server');


    }
    // function u_file($name, $p=[]) {
    //     if (!$this->isFileUpload) {
    //         Owl::error('Form needs to be opened with { files: true } to support file uploads.');
    //     }
    //     return $this->u_input('file', $name);
    // }
}



