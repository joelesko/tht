<?php

namespace o;

abstract class TemplateConstants {
    // Tags that should be closed immediately. e.g. <br />
    static $VOID_TAGS = [
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input',
        'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'
    ];
}

class TemplateTransformer {
    protected $tokenizer = null;

    function __construct ($reader) {
        $this->reader = $reader;
    }

    function transformNext() {
        return false;
    }

    function onEndString($s) {
        return $s;
    }

    function onEndTemplateBody() {}
    function onEndFile() {}

    function cleanHtmlSpaces($str) {
        $str = preg_replace('#>\s+$#',      '>', $str);
        $str = preg_replace('#^\s+<#',      '<', $str);
        $str = preg_replace('#>\s*\n+\s*#', '>', $str);
        $str = preg_replace('#\s*\n+\s*<#', '<', $str);
        $str = preg_replace('#\s+</#',      '</', $str);

        return $str;
    }
}

class TextTemplateTransformer  extends TemplateTransformer {}
class JconTemplateTransformer  extends TemplateTransformer {}

class JsTemplateTransformer    extends TemplateTransformer {
    function onEndString($str) {
        if (Tht::getConfig('minifyJs')) {
            $str = Tht::module('Js')->u_minify($str);
        }
        return $str;
    }
}

class CssTemplateTransformer extends TemplateTransformer {
    function onEndString($str) {

        if (Tht::getConfig('tempParseCss')) {
            $str = Tht::module('Css')->u_parse($str);
        }
        if (Tht::getConfig('minifyCss')) {
            $str = Tht::module('Css')->u_minify($str);
        }

        return $str;
    }
}

class LiteTemplateTransformer extends TemplateTransformer {

    // preserve code within code fences, so there is no need to escape anything
    function transformNext() {
        $r = $this->reader;
        $c = $r->char1;

        if ($r->atStartOfLine()) {
            $this->indent = $r->slurpChar(' ');
            $c = $r->char1;
            if ($r->isGlyph("```")) {
                $s = $r->getLine() . "\n";
                while (true) {
                    $line = $r->slurpLine();
                    $s .= $line['fullText'] . "\n";
                    if (substr($line['text'], 0,3) == "```" || $line['text'] === null) {
                        break;
                    }
                }
                return $s;
            }
        }
        else if ($c === "`") {
            $c = "`" . $r->slurpUntil('`') . $r->char1;
            $r->next();
            return $c;
        }

        $r->next();
        return $c;
    }

    function onEndString($s) {
        $str = Tht::module('Litemark')->u_parse($s, ['html' => true])->u_unlocked();

        $str = $this->cleanHtmlSpaces($str);

        return $str;
    }
}

// StringReader interface: char(), next(), nextChar(), error()
class HtmlTemplateTransformer extends TemplateTransformer {

    private $currentTag = null;
    static private $openTags = [];
    private $numLineTags = 0;

    function transformNext () {

        $t = $this->reader;

        $c = $t->char();

        if ($c === "\n" && $this->numLineTags) {
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
        else if ($c === '<') {
            return $this->readTagStart($t);
        }
        else if ($this->currentTag !== null) {
            return $this->readTagMiddle($t);
        }
        else {
            // plaintext
            return false;
        }
    }

    // eg '<blah'
    function readTagStart () {

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
                    $t->error('Unexpected newline.');
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
                    $t->error('Unexpected newline.');
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
        else {
            if ($c === "\n") {
                $t->updateTokenPos();
                $t->error('Unexpected newline.');
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
                    $t->error('`<' . $this->currentTag['name'] . '>>>` should be followed by a newline.');
                }
                $t->next();
            }
            else {
                // One-liner '>>'
                $this->numLineTags += 1;

                if ($t->nextChar() !== ' ') {
                    $t->error('`<' . $this->currentTag['name'] . '>>` should be followed by a space.  (It will be trimmed.)');
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

        $this->validateTag($this->currentTag);

        if ($isSelfClosing) {
            if (!in_array($this->currentTag['name'], TemplateConstants::$VOID_TAGS)) {
                $str .= '</' . $this->currentTag['name'] . '>';
            }
        }
        else {
            if (in_array($this->currentTag['name'], TemplateConstants::$VOID_TAGS)) {
                $t->error("Tag should be self-closing. Ex: `<" . $this->currentTag['name'] ." />`");
            }
            HtmlTemplateTransformer::$openTags []= $this->currentTag;
        }


        // slurp in format block contents.  normalize indent & escape
        if ($inFormatBlock) {
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
                $t->error("Content inside of `" . $this->currentTag['name'] . "` must be indented." );
            }
            else if ($t->char() !== '<') {
                $t->error("Expected end tag for `" . $this->currentTag['name'] . "` block." );
            }

            $b = htmlspecialchars($b);
            $str .= v($b)->u_trim_indent();
        }

        $this->currentTag = null;


        return $str;
    }


    function getClosingTag ($seeTagName='') {

        $t = $this->reader;

        $t->updateTokenPos();

        if (!count(HtmlTemplateTransformer::$openTags)) {
            $t->error('Extra closing tag');
        }
        $tag = array_pop(HtmlTemplateTransformer::$openTags);
        if ($seeTagName && $seeTagName !== $tag['name']) {
            $t->error("Expected `</" . $tag['name'] . ">` but saw `</$seeTagName>` instead.");
        }
        return "</" . $tag['name'] . ">";
    }

    function validateTag ($tag) {

        $t = $this->reader;
        $name = $tag['name'];

        if (preg_match('/class\s*=.*class\s*=/i', $tag['html'])) {
            $t->error("Can't have both class name and class attribute for tag.", $tag['pos']);
        }
        if (preg_match('/#/', $name)) {
            $t->error("ID of `$name` should be in an `id` attribute, not in the tag name.", $tag['pos']);
        }
        if (!preg_match('/[a-z]/', $name)) {
            $t->error("Tag `$name` should not be all uppercase.", $tag['pos']);
        }
        if (substr($name, 0, 1) === '?' || substr($name, 0, 1) === '%') {
            $sigil = substr($name, 0, 1);
            $t->error("Tag `<$name ... $sigil>` should be replaced with `{{ ... }}` or `::`.", $tag['pos']);
        }
        if (preg_match('/[^a-zA-Z0-9\-]/', $name)) {
            $t->error("Tag `$name` can only contain letters (a-z), numbers (0-9), and dashes (-).", $tag['pos']);
        }
        if ($name == 'script' && !preg_match('/nonce/', $tag['html'])) {
            $t->error("`script` tags must have a secure `nonce` attribute.  Either add `nonce=\"{{ Web.nonce() }}\"` or include the script as a Js template.", $tag['pos']);
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
        return $this->cleanHtmlSpaces($str);
    }

    function onEndTemplateBody() {}

    function onEndFile() {
        $t = $this->reader;
        if (count(HtmlTemplateTransformer::$openTags)) {
            $tag = array_pop(HtmlTemplateTransformer::$openTags);
            $t->error('Unclosed tag: `' . $tag['name'] . '`', $tag['pos']);
        }
    }
}
