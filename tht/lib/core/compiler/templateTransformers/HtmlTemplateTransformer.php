<?php

namespace o;

class HtmlTemplateTransformer extends TemplateTransformer {

    private $currentTag = null;
    static private $openTags = [];
    private $numLineTags = 0;

    static $VOID_TAGS = [
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input',
        'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'
    ];

    static function cleanHtmlSpaces($str) {
        $str = preg_replace('#>\s+$#',      '>', $str);
        $str = preg_replace('#^\s+<#',      '<', $str);
        $str = preg_replace('#>\s*\n+\s*#', '>', $str);
        $str = preg_replace('#\s*\n+\s*<#', '<', $str);
        $str = preg_replace('#\s+</#',      '</', $str);

        return $str;
    }

    function error($msg) {
        $this->reader->error('(Template) ' . $msg);
    }

    function transformNext () {

        $t = $this->reader;

        $c = $t->char();

        if ($c === "\n" && $this->numLineTags) {
            $this->indent = 0;
            return $this->closeLineEndTags();
        }
        else if ($c === '<') {
            return $this->readTagStart($t);
        }
        else if ($this->currentTag !== null) {
            return $this->readTagMiddle($t);
        }
        else {
            if ($c === "\n") {
                $this->indent = 0;
            } else if ($c === " ") {
                $this->indent += 1;
            }

            // plaintext
            return false;
        }
    }

    function closeLineEndTags() {
        $str = '';
        while (true) {
            $str .= $this->getClosingTag();
            $this->numLineTags -= 1;
            if (!$this->numLineTags) {
                break;
            }
        }
        return $str;
    }

    // eg '<blah'
    function readTagStart () {

        $this->currentContext = 'tag';

        $t = $this->reader;

        $c = $t->char();
        $str = '';

        if ($t->nextChar() === '!') {
            // HTML comment <!-- -->
            while (true) {
                $str .= $c;
                $t->next();
                if ($c === '>') {
                    break;
                }
                $c = $t->char();
            }
        }
        else if ($t->nextChar() === '/') {

            // HTML closing tag. </...>
            // auto-fill tag name
            $t->next();
            $tagName = '';
            while (true) {
                $t->next();
                $c = $t->char();
                if ($c === "\n") {
                    $t->updateTokenPos();
                    $this->error('Unexpected newline.');
                }
                if ($c === '>') {
                    $t->next();
                    break;
                } else {
                    $tagName .= $c;
                }
            }

            $str .= $this->getClosingTag($tagName);

        } else {

            // HTML Open Tag
            $str .= $c;
            $t->updateTokenPos();

            // read tag name
            $tagName = '';
            while (true) {
                $t->next();
                $c = $t->char();
                if ($c === "\n") {
                    $t->updateTokenPos();
                    $this->error('Unexpected newline.');
                }
                if ($c === ' ' || $c === '>') {
                    $this->currentTag = $this->newTag($tagName, $t->getTokenPos());
                    $str .= $this->currentTag['html'];
                    break;
                }
                else {
                    $tagName .= $c;
                    continue;
                }
            }
        }

        return $str;
    }

    // read inner part of tag
    function readTagMiddle() {

        $t = $this->reader;

        $c = $t->char();
        $str = '';

        $isSelfClosing = false;
        if ($c === '/' && $t->nextChar() === '>') {
            $isSelfClosing = true;
            $str .= '/';
            $t->next();
            $c = $t->char();
        }

        if ($c === '>') {
            $str .= $this->readTagEnd($isSelfClosing);
        }
        else if ($c === '=') {
            $nc = $t->nextChar();
            if ($nc == ' ') {
                $this->error('Please remove the space after `=`.');
            } else if ($t->prevChar() == ' ') {
                $this->error('Please remove the space before `=`.');
            }
            $str .= $c;
            $t->next();
        }
        else {
            if ($c === "\n") {
                $t->updateTokenPos();
                $this->error('Unexpected newline.');
            }
            $c = $t->escape($c, false, true);
            $this->currentTag['html'] .= $c;

            $str .= $c;
            $t->next();
        }

        return $str;
    }

    function readTagEnd($isSelfClosing) {

        $t = $this->reader;
        $c = $t->char();
        $str = '';

        $inFormatBlock = false;
        $formatBlockIndent = 0;
        $nc = $t->nextChar();

        // End in '>>'
        if ($nc === '>') {
            $t->next();
            if ($t->nextChar() === '>') {
                // Formatted block '>>>'
                $inFormatBlock = true;
                $formatBlockIndent = $t->indent();
                $t->next();
                if ($t->nextChar() !== "\n") {
                    $this->error('`<' . $this->currentTag['name'] . '>>>` should be followed by a newline.');
                }
                $t->next();
            }
            else {
                // One-liner '>>'
                $this->numLineTags += 1;

                if ($t->nextChar() !== ' ') {
                    $this->error('`<' . $this->currentTag['name'] . '>>` should be followed by a space.  (It will be trimmed.)');
                }
                else {
                    // slurp whitespace
                    while (true) {
                        if ($t->nextChar() === ' ') {
                            $t->next();
                        } else {
                            break;
                        }
                    }
                }
            }
        }

        $str .= $c;
        $t->next();

        $this->currentContext = 'none';
        $this->validateTag($this->currentTag);

        if ($isSelfClosing) {
            if (!in_array($this->currentTag['name'], self::$VOID_TAGS)) {
                $str .= '</' . $this->currentTag['name'] . '>';
            }
        }
        else {
            if (in_array($this->currentTag['name'], self::$VOID_TAGS)) {
                $this->error("Tag should be self-closing. Ex: `<" . $this->currentTag['name'] ." />`");
            }
            HtmlTemplateTransformer::$openTags []= $this->currentTag;
        }

        if ($inFormatBlock) {
            $str .= $this->readIndentedBlock($t, $formatBlockIndent);
        }

        $this->currentTag = null;

        return $str;
    }

    // slurp in format block contents.  normalize indent & escape
    function readIndentedBlock($t, $formatBlockIndent) {

        $b = '';
        $hasContent = false;
        while (true) {

            // get indent
            $indent = $t->slurpChar(' ');
            $b .= str_repeat(' ', $indent);
            $t->updateTokenPos();

            if ($indent <= $formatBlockIndent) {
                // outdented
                if ($t->char() === "\n") {
                    // blank line
                    $b .= "\n";
                    $t->next();
                    continue;
                }
                else {
                    // end of block
                    break;
                }
            }
            // get line content
            $hasContent = true;
            $line = $t->slurpUntil("\n");
            $b .= $line . "\n";
        }

        if (!$hasContent) {
            $this->error("Content inside of `" . $this->currentTag['name'] . "` must be indented." );
        }
        else if ($t->char() !== '<') {
            $this->error("Expected end tag for `" . $this->currentTag['name'] . "` block." );
        }

        $b = Security::escapeHtml($b);
        return v($b)->u_trim_indent();
    }

    function getClosingTag ($seeTagName='') {

        $t = $this->reader;

        $t->updateTokenPos();

        if (!count(HtmlTemplateTransformer::$openTags)) {
            $this->error('Extra closing tag');
        }
        $tag = array_pop(HtmlTemplateTransformer::$openTags);
        if ($seeTagName && $seeTagName !== $tag['name']) {
            $this->error("Expected `</" . $tag['name'] . ">` but saw `</$seeTagName>` instead.");
        }
        return "</" . $tag['name'] . ">";
    }

    function validateTag ($tag) {

        $t = $this->reader;
        $name = $tag['name'];

        if (!strlen($name)) {
            $this->error("Missing tag name", $tag['pos']);
        }
        else if (preg_match('/class\s*=.*class\s*=/i', $tag['html'])) {
            $this->error("Can't have both class name and class attribute for tag.", $tag['pos']);
        }
        else if (preg_match('/#/', $name)) {
            $this->error("ID of `$name` should be in an `id` attribute instead.", $tag['pos']);
        }
        else if (!preg_match('/[a-z]/', $name) && preg_match('/[A-Z]/', $name)) {
            $this->error("Tag `$name` should be all lowercase.", $tag['pos']);
        }
        else if (substr($name, 0, 1) === '?' || substr($name, 0, 1) === '%') {
            $sigil = substr($name, 0, 1);
            $this->error("Tag `<$name ... $sigil>` should be replaced with `{{ ... }}` or `::`.", $tag['pos']);
        }
        else if (preg_match('/[^a-zA-Z0-9\-]/', $name)) {
            $this->error("Tag `$name` can only contain letters (a-z), numbers (0-9), and dashes (-).", $tag['pos']);
        }
        else if ($name == 'script' && !preg_match('/nonce/', $tag['html'])) {
            $this->error("`script` tags must have a secure `nonce` attribute.  Either add `nonce=\"{{ Web.nonce() }}\"` or include the script as a Js template.", $tag['pos']);
        }
    }

    function newTag ($tagName, $pos) {

        $tagHtml = $tagName;
        if (strpos($tagName, '.') !== false) {
            $classes = explode('.', $tagName);
            $aTag = 'div';
            $id = '';
            if ($tagName[0] !== '.') {
                $aTag1 = array_shift($classes);
                if ($aTag1[0] === '#') {
                    $id = ' id="' . substr($aTag1, 1) . '"';
                } else {
                    $aTag = $aTag1;
                }
            }

            $tagHtml = $aTag . $id . " class='" . trim(implode(' ', $classes)) . "'";
            $tagName = $aTag;
        }

        return [
            'html'  => $tagHtml,
            'name'  => $tagName,
            'pos'   => $pos,
        ];
    }


    function onEndString($str) {
        return self::cleanHtmlSpaces($str);
    }

    function onEndTemplateBody() {}

    function onEndFile() {
        $t = $this->reader;
        if (count(HtmlTemplateTransformer::$openTags)) {
            $tag = array_pop(HtmlTemplateTransformer::$openTags);
            $this->error('Unclosed tag: `' . $tag['name'] . '`', $tag['pos']);
        }
    }
}
