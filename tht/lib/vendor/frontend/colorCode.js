
window.colorCode = function (theme='light', sel='pre, .tht-color-code') {

    if (document.body.classList.contains('no-color-code')) {
        return;
    }

    var css = document.createElement('style');
    css.innerHTML = colorCodeCss();
    document.body.appendChild(css);

    var themeClass = "theme-" + theme;
    var hiClass = 'has-cc';

    var codes = document.querySelectorAll(sel);

    for (var i=0; i < codes.length; i++) {

        var block = codes[i];
        var classes = block.classList;
        if (classes.contains(hiClass) || classes.contains('no-color-code')) {
            continue;
        }

        classes.add(hiClass);
        classes.add(themeClass);

        var c = block.innerHTML;

        // keywords
        let kw = colorCodeKeywords();
        let rx = new RegExp('\\b(' + kw + ')\\b([^=:])', 'gi');
        c = c.replace(rx, '<span class=(qq)cc-keyword(qq)>$1</span>$2');

        // HTML tags
        c = c.replace(/(&lt;\S.*?(&gt;)+)/g, '<span class=(qq)cc-tag(qq)>$1</span>');

        // numbers
        c = c.replace(/\b(-?\d[\d\.]*)/g, '<span class=(qq)cc-value(qq)>$1</span>');

        // booleans
        c = c.replace(/\b(true|false)\b/gi, '<span class=(qq)cc-value(qq)>$1</span>');

        // strings
        c = c.replace(/('''([\w\W]*?)''')/gm, '<span class=(qq)cc-value(qq)>$1</span>');
        c = c.replace(/("(.*?)")/g, '<span class=(qq)cc-value(qq)>$1</span>');
        c = c.replace(/('([^\n]*?)')/g, '<span class=(qq)cc-value(qq)>$1</span>');

        // -flags
        c = c.replace(/(\s|\(|,|\||\[)(-[a-z][a-zA-Z0-9]*)/g, '$1<span class=(qq)cc-value(qq)>$2</span>');

        // command prompt ($ or %)
        c = c.replace(/(^|\n)(\$|\%)(\s+)/gi, '<span class=(qq)cc-prompt(qq)>$1$2$3</span>');

        // block comments
        c = c.replace(/(\/\*([\w\W]*?)\*\/)/gm, '<span class=(qq)cc-comment(qq)>$1</span>');

        // single-line comments
        c = c.replace(/(^|\s)(\/\/[^\/].*)/gm, '$1<span class=(qq)cc-comment(qq)>$2</span>');

        // HTML comments
        c = c.replace(/(&lt;!--.*?--&gt;)/gm, '<span class=(qq)cc-comment(qq)>$1</span>');

        // template: single-line code
        c = c.replace(/(^|\s)((---)\s+.*)/g, '$1<span class=(qq)cc-template-code(qq)>$2</span>');

        // template: expression
        // c = c.replace(/(\{\{(.*?)\}\})/g, '<span class=(qq)cc-template-expr(qq)>$1</span>');

        // replace quotes
        c = c.replace(/\(qq\)/g, '"');

        block.innerHTML = c;
    }
};

function colorCodeKeywords() {
    return 'let|var|const|constant|function|tm|fn|def|for|foreach|loop|while|do|array|new|if|else|elsif|elif|this|break|continue|return|require|import|load|class|static|public|private|protected|final|int|double|boolean|bool|string|float|long|in|as|try|catch|throw|finally|select|from|join|inner join|outer join|cross join|insert|delete|update|where|switch|match|keep|use|fields|inner|outer';
}

function colorCodeCss() {
    return `

.has-cc .cc-value span,
.has-cc .cc-comment span,
.has-cc .cc-prompt,
.has-cc .cc-template-code span {
    color: inherit !important;
    font-weight: inherit !important;
}
.has-cc .cc-prompt {
    opacity: 0.5;
    user-select: none;
}

/* Light Theme (Default) */

.has-cc {
    color: #000;
}
.has-cc .cc-comment {
    color: #575757;
}
.has-cc .cc-value {
    color: #ad1818;
}
.has-cc .cc-tag,
.has-cc .cc-keyword {
    color: #005d8c;
}
.has-cc .cc-template-code {
    color: #8331a0;
}
.has-cc .cc-highlight-line {
    color: #ffe;
}

/* Dark Theme */

.has-cc.theme-dark {
    background-color: #282828;
    color: #eee;
    border: 0;
}
.has-cc.theme-dark .cc-comment {
    color: #b4b4b4;
}
.has-cc.theme-dark .cc-value {
    color: #b4e7b2;
}
.has-cc.theme-dark .cc-tag,
.has-cc.theme-dark .cc-keyword {
    color: #A6CBED;
}

.has-cc.theme-dark .cc-template-code {
    color: #b8efe0;
}

`;
}
