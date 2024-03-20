<?php

namespace o;

class HtmlTemplateTransformer extends TemplateTransformer {

    private $currentTag = null;
    private $openTags = [];
    private $seenOpenTags = false;
    private $numTagsInLine = 0;
    private $lineHasContent = false;
    private $currentTagPos = 0;
    private $inQuote = '';
    private $indent = 0;
    public $reader = null;

    static $VOID_TAGS = [
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input',
        'keygen', 'link', 'meta', 'param', 'source', 'track', 'wbr'
    ];

    function error($msg, $pos=null, $skipDoc=false) {
        ErrorHandler::addSubOrigin('template');
        if (!$skipDoc) {
            ErrorHandler::setHelpLink('/reference/templates#html-templates', 'HTML Templates');
        }
        $this->reader->error($msg, $pos);
    }

    function transformNext () {

        $r = $this->reader;

        $c = $r->char();

        if ($c === '<') {
            // Start of tag
            return $this->readTagStart($r);
        }
        else if ($this->currentTag !== null) {
            // Inside of tag
            return $this->readTagMiddle($r);
        }
        else {

            // Content outside of tag
            if ($c === "\n") {
                $this->indent = 0;
                $strCloseTags = $this->closeTagsInLine();
                if ($strCloseTags) {
                    return $strCloseTags;
                }
            }
            else {
                if ($c === " " && $r->atStartOfLine()) {
                    // Absorb indent
                    $this->indent = $r->slurpChar(' ');
                    return '';
                }
            }

            // plaintext
            return false;
        }
    }

    // eg '<div'
    function readTagStart () {

        $this->currentContext = 'tag';

        $r = $this->reader;

        $r->updateTokenPos();
        $this->currentTagPos = $r->getTokenPos();

        $c = $r->char();
        $str = '';

        if ($r->nextChar() === '!') {
            // HTML comment <!-- -->
            $comment = $r->slurpUntil('>');
            $str .= $comment . '>';
        }
        else if ($r->nextChar() === '/') {

            // HTML closing tag. </...>
            // auto-fill tag name
            $r->next();
            $tagName = $this->readCloseTagName($r);
            $str .= $this->getClosingTag($tagName);

        } else {

            // HTML Open Tag
            $str .= $c;
            $r->updateTokenPos();

            $tagName = $this->readOpenTagName($r);
            $this->currentTag = $this->newTag($tagName, $r->getTokenPos());
            $str .= $this->currentTag['html'];
        }

        return $str;
    }

    // read inner part of tag
    function readTagMiddle() {

        $r = $this->reader;

        $c = $r->char();
        $str = '';

        $isSelfClosing = false;
        if ($c === '/' && $r->nextChar() === '>') {
            $isSelfClosing = true;
            $str .= '/';
            $r->next();
            $c = $r->char();
        }

        if ($c === '>') {
            $str .= $this->readTagEnd($isSelfClosing);
        }
        else if ($c === '=' && !$this->inQuote) {
            $nc = $r->nextChar();
            if ($nc == ' ') {
                $this->error('Please remove the space after: `=`');
            }
            else if ($r->prevChar() == ' ') {
                $this->error('Please remove the space before: `=`');
            }
            else if ($nc != '"' && $nc != "'") {
                $this->error('Missing double-quote `"` after: `=`');
            }
            $this->currentTag['html'] .= $c;
            $str .= $c;
            $r->next();
        }
        else {
            if ($c === "\n" && $this->inQuote) {
                $r->updateTokenPos();
                $this->error('Unexpected newline inside HTML tag parameter.');
            }
            else if ($c == '"' || $c == "'") {
                if ($this->inQuote) {
                    if ($c == $this->inQuote) {
                         $this->inQuote = '';
                    }
                }
                else {
                    $this->inQuote = $c;
                }
            }

            $this->currentTag['html'] .= $c;

            $str .= $c;
            $r->next();
        }

        return $str;
    }

    function readTagEnd($isSelfClosing) {

        $r = $this->reader;
        $str = '>';

       $inFormatBlock = false;
       $formatBlockIndent = 0;

        // End in '>>>'
        if ($r->char(3, 0) === '>>>') {
            // Formatted block '>>>'
            $inFormatBlock = true;
            $formatBlockIndent = $r->indent();
            $r->next(3);
            if ($r->char() !== "\n") {
                $this->error('`<' . $this->currentTag['name'] . '>>>` should be followed by a newline.', $this->currentTagPos);
            }
        }
        else {
            $r->next(); // '>'
        }

        // remove spaces after
        $r->slurpChar(' ');

        // Look ahead to see if there is any other content on this line
        if ($this->numTagsInLine == 0) {
            $c = $r->char();
            if ($c == "\n") {
                $this->lineHasContent = false;
            } else {
                $this->lineHasContent = true;
            }
        }

        // Validate script tag
        if ($this->currentTag['name'] == 'script' && !preg_match('/nonce\s*=/i', $this->currentTag['html'])) {
            ErrorHandler::setHelpLink('/manual/module/web/nonce', 'Web.nonce');
            $this->error(
                'Tag needs a `nonce` parameter to safely allow it to execute. Try: `nonce="{{ Web.nonce() }}"`',
                $this->currentTagPos, true
            );
        }

        $this->currentContext = 'none';
        $this->validateTag($this->currentTag);

        // Close self-closing tags
        if ($isSelfClosing || in_array($this->currentTag['name'], self::$VOID_TAGS)) {
            if (!in_array($this->currentTag['name'], self::$VOID_TAGS)) {
                $str .= '</' . $this->currentTag['name'] . '>';
            }
        }
        else {
            $this->numTagsInLine += 1;
            $this->openTags []= $this->currentTag;
            $this->seenOpenTags = true;
        }

        if ($inFormatBlock) {
            $str .= $this->readIndentedBlock($r, $formatBlockIndent);
        }

        $this->currentTag = null;

        return $str;
    }

    function closeTagsInLine() {

        if (!$this->lineHasContent) {
            $this->numTagsInLine = 0;
            return '';
        }

        $str = '';
        while (true) {
            if (!$this->numTagsInLine) {
                break;
            }
            $str .= $this->getClosingTag() . "\n";
        }

        $this->lineHasContent = false;

        return $str;
    }

    function readCloseTagName($r) {

        $tagName = '';
        while (true) {

            $r->next();
            $c = $r->char();

            if ($c === "\n") {
                $r->updateTokenPos();
                $this->error('Unexpected newline.');
            }

            if ($c === '>') {
                $r->next();
                break;
            } else {
                $tagName .= $c;
            }
        }

        return trim($tagName);
    }

    function readOpenTagName($r) {
        $tagName = '';
        while (true) {

            $r->next();
            $c = $r->char();

            if ($c === "\n" || $c === ' ' || $c === '>') {
                break;
            }
            else {
                $tagName .= $c;
                continue;
            }
        }

        return $tagName;
    }

    // slurp in format block contents.  normalize indent & escape
    function readIndentedBlock($r, $formatBlockIndent) {

        $b = '';
        $hasContent = false;
        while (true) {

            // get indent
            $indent = $r->slurpChar(' ');
            $b .= str_repeat(' ', $indent);
            $r->updateTokenPos();

            if ($indent <= $formatBlockIndent) {
                // outdented
                if ($r->char() === "\n") {
                    // blank line
                    $b .= "\n";
                    $r->next();
                    continue;
                }
                else {
                    // end of block
                    break;
                }
            }
            // get line content
            $hasContent = true;
            $line = $r->slurpUntil("\n");
            $b .= $line . "\n";
        }

        if (!$hasContent) {
            $this->error("Content inside of `" . $this->currentTag['name'] . "` must be indented." );
        }
        else if ($r->char() !== '<') {
            $this->error("Expected end tag for `" . $this->currentTag['name'] . "` block." );
        }

        $b = Security::escapeHtml($b);

        return v($b)->u_trim_indent(OMap::create(['keepRelative' => true]));
    }

    function getClosingTag ($seeTagName='') {

        $r = $this->reader;

        if ($seeTagName == '...') {
            $this->openTags = [];
            return;
        }

        // Allow mismatch if at the top of the template and user is closing
        // tags opened in another template
        if (!count($this->openTags)) {
            if ($this->seenOpenTags) {
                $this->error('Extra closing tag.', $this->currentTagPos);
            }
            return "</$seeTagName>";
        }

        $r->updateTokenPos();

       // $seeTagNameBase = preg_replace('/\.\.\.$/', '', $seeTagName);

        $tag = array_pop($this->openTags);

        if ($seeTagName && $seeTagName !== $tag['name']) {
            $this->error("Expected `</" . $tag['name'] . ">` but saw `</$seeTagName>` instead.", $this->currentTagPos);
        }

        if ($this->numTagsInLine) {
            $this->numTagsInLine -= 1;
        }

        return "</" . $tag['name'] . ">";

    }

    function validateTag ($tag) {

        $r = $this->reader;
        $name = $tag['name'];

        if (!strlen($name)) {
            $this->error("Missing tag name", $tag['pos']);
        }
        else if (preg_match('/class\s*=.*class\s*=/i', $tag['html'])) {
            $this->error("Can not have both class name and class attribute for tag.", $tag['pos']);
        }
        else if (preg_match('/#/', $name)) {
            $this->error("ID of `$name` should be in an `id` attribute instead.", $tag['pos']);
        }
        else if (!preg_match('/[a-z]/', $name) && preg_match('/[A-Z]/', $name)) {
            $this->error("Tag `$name` should be all lowercase.", $tag['pos']);
        }
        else if (substr($name, 0, 1) === '?' || substr($name, 0, 1) === '%') {
            $sigil = substr($name, 0, 1);
            $this->error("Tag `<$name ... $sigil>` should be replaced with: `{{ ... }}` or `::`", $tag['pos']);
        }
        else if (preg_match('/[^a-zA-Z0-9\-]/', $name)) {
            $this->error("Tag `$name` can only contain letters (a-z), numbers (0-9), and dashes (-).", $tag['pos']);
        }
        else if ($this->inQuote) {
            $this->error("Tag `$name` is missing a closing double-quote: `\"`", $tag['pos']);
        }
    }

    function newTag ($tagName, $pos) {

        $tagHtml = $tagName;

        // .class shortcut
        if (strpos($tagName, '.') !== false) {
            $classes = explode('.', $tagName);
            $aTag = 'div';
            if ($tagName[0] !== '.') {
                $aTag = array_shift($classes);
            }

            $tagHtml = $aTag . ' class="' . trim(implode(' ', $classes)) . '"';
            $tagName = $aTag;
        }

        if ($tagName == 'img' || $tagName == 'iframe') {
            $tagHtml .= ' loading="lazy"';
        }

        return [
            'html'  => $tagHtml,
            'name'  => $tagName,
            'pos'   => $pos,
        ];
    }

    function onEndChunk($str) {
        return self::cleanHtmlSpaces($str);
    }

    // Mostly for readability in dynmically generated source
    static function cleanHtmlSpaces($str) {

        // spaces before closing tag
        $str = preg_replace('#\s+</#', '</', $str);

        // newlines between tags
        $str = preg_replace('#>\n\s*<#', "><", $str);

        return $str;
    }

    function onEndBody() {

        if (count($this->openTags)) {
            $tag = array_pop($this->openTags);
            $this->error('Missing closing tag `</>` within block: `<' . $tag['name'] . '>` Try: If this is intentional, add a continue tag `</...>`', $tag['pos']);
        }
    }

    function onEndFile() {}
}
