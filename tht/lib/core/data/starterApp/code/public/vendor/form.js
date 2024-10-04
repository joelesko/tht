
window.ThtForm = (function() {

    let STATE = {};
    let CSRF_TOKEN = '';

    let style = document.createElement('style');
    style.innerHTML = '/* Form */\n*:invalid,*:valid{outline:0;box-shadow:inherit;}.form-interacted:invalid{border:solid 2px #ec6f6f}.is-submitting *{opacity:0.7} .ldots>div{width:0.3em;height:0.3em;background-color:currentColor;margin:0 0.25em;border-radius:50%;display:inline-block;animation:dots 1200ms infinite ease-in-out;position: relative;top:-.15em;opacity:1}.ldots .ld2{animation-delay:200ms}.ldots .ld3{animation-delay:350ms}@keyframes dots{0%,100%,15%,55%{transform:scale(1.0)}30%{transform:scale(1.6)}}';
    document.body.appendChild(style);

    return {

        register: function(args) {

            for (var arg of ['formId', 'schema', 'csrfToken', 'fillData']) {
                if (!args[arg]) {
                    console.error('Missing register() argument: `' + arg + '`');
                }
            }

            CSRF_TOKEN = args.csrfToken;

            let form = document.getElementById(args.formId);
            if (!form) {
                console.error('Form id=`' + args.formId + '` not found in DOM.');
            }

            if (!this.attr(form, 'method')) {
                this.attr(form, 'method', 'post');
            }

            this.initFields(form, args.schema);

            this.fillForm(form, args.fillData);

            this.addListeners(form, args);
        },

        addListeners(form, args) {

            let showHide = form.querySelector('.form-show-password');

            if (showHide) {
                showHide.addEventListener('click', (e)=>{
                    let pass = showHide.parentNode.querySelector('[type="password"],[type="text"]');
                    let type = pass.type;
                    pass.type = type == 'text' ? 'password' : 'text';
                    showHide.querySelector('input').checked = type == 'text' ? '' : 'checked';
                });
            }

            form.addEventListener('submit', (e)=>{
                e.target.reportValidity();
                e.preventDefault();
                this.submitForm(form);
                return false;
            });

            form.addEventListener('keyup', (e)=>{
                let el = e.target;
                if (this.attr(el, 'check-password') && el.value.length >= 8) {
                    let pwError = this.checkNewPasswordStrength(el, el.value.trim());
                    el.setCustomValidity(pwError);
                }
            }, true);

            form.addEventListener('blur', (e)=>{

                let el = e.target;
                if (!el.validationMessage) {
                    el.value = el.value.trim();
                }

                el.classList.add('form-interacted');

            }, true);

            form.addEventListener('change', (e)=> {
                let el = e.target;
                if (el.type == 'file') {
                    let sizeMb = Math.round(el.files[0].size / (1024 * 1024), 1);
                    if (sizeMb > args.schema.maxFileSizeMb) {
                       el.setCustomValidity('File size (' + sizeMb + ' MB) is too large. Max Size: ' + args.schema.maxFileSizeMb + ' MB');
                    }
                    else {
                        el.setCustomValidity('');
                    }
                }
            }, true);
        },

        initFields(form, formSchema) {

            var fields = form.querySelectorAll('*[name]');

            for (var i=0; i < fields.length; i++) {

                let field = fields[i];
                let name = this.attr(field, 'name');
                if (field.type == 'hidden') { continue; }

                if (formSchema[name]) {

                    let rule = formSchema[name].rule;
                    if (rule) {

                        this.addConstraint(field, rule, 'max', 'max');
                        this.addConstraint(field, rule, 'max', 'maxLength');
                        this.addConstraint(field, rule, 'min', 'min');
                        this.addConstraint(field, rule, 'min', 'minLength');
                        this.addConstraint(field, rule, 'step', 'step');
                        this.addConstraint(field, rule, 'regex', 'pattern');

                        if (rule['newPassword']) {
                            this.attr(field, 'check-password', 'true');
                        }
                    }

                    if (!rule || !rule['optional']) {
                        this.attr(field, 'required', 'required');
                    }
                }
            }
        },

        addConstraint(field, rule, ruleId, constraint) {

            console.log(rule, ruleId);
            if (rule[ruleId]) {
                this.attr(field, constraint, rule[ruleId]);
            }
        },

        // TODO: localization
        // See:  https://github.com/danielmiessler/SecLists/blob/master/Passwords/Common-Credentials/10-million-password-list-top-10000.txt
        checkNewPasswordStrength(el, p) {

            let fp = p.toLowerCase();

            if (p.length < 8) {
                return 'Password needs to be at least 8 letters long.';
            }
            else if (!p.match(/[a-zA-Z]/)) {
                return 'Password needs at least one letter (a-z).';
            }
            else if (!p.match(/[^a-zA-Z]/)) {
                return 'Password needs at least 1 number or symbol (# ! + $, etc.)';
            }
            else if (p.match(/^[a-zA-Z]+(1|123|666|69)$/)) {
                return "Password has a number that is too easy to guess.";
            }
            else if (fp.match(/q[0-9]*w[0-9]*e[0-9]*r/) || fp.match(/a[0-9]*b[0-9]*c[0-9]*d/) || fp.match(/1qaz|zaq1|12qwas|1234|asdf|zxcv|zxasqw|qweasd/)) {
                return "Password has a keyboard pattern that is too easy to guess.";
            }
            else if (fp.match(/trustn[0o]1|jordan23|rush2112|blink182|ncc1701|babylon5|1232323q|p[@a4][s5][s5]w[o0]r/)) {
                return "Password is too easy to guess.";
            }

            let hparts = location.hostname.toLowerCase().split(/\./);
            let host = hparts.length > 2 && hparts[1] != 'co' ? hparts[1] : hparts[0];

            if (fp.indexOf(host) == 0 && host.length >= 3) {
                return "Password can not have the website name in it.";
            }

            return '';
        },

        fillForm(form, fieldValues) {

            for (let field in fieldValues) {
                let elField = form.querySelector('[name="' + field + '"]');
                this.fillField(form, elField, fieldValues[field]);
            }

            // Check first radio if no value yet
            let radios = form.querySelectorAll('[type="radio"]');
            let hasCheck = {};

            // Get radios that are already checked
            radios.forEach((r)=>{
                if (r.checked) {
                    hasCheck[r.name] = true;
                }
            });

            // Check first radio if not checked
            radios.forEach((r)=>{
                if (!hasCheck[r.name]) {
                    r.checked = true;
                    hasCheck[r.name] = true;
                }
            });
        },

        fillField(form, elField, value) {

            if (!elField) { return; }

            if (Array.isArray(value)) {
                value.forEach((v)=>{
                    let elCheck = form.querySelector('[name="' + elField.name + '"][value="' + v + '"]');
                    elCheck.checked = true;
                });
            }
            else if (elField.type == 'radio') {
                elField = form.querySelector('[name="' + elField.name + '"][value="' + value + '"]');
                elField.checked = true;
            }
            else {
                elField.value = value;
            }
        },

        attr(el, name, val) {

            if (val) {
                el.setAttribute(name, val);
                return el;
            }
            else {
                return el.getAttribute(name);
            }
        },

        submitForm(form) {

            if (STATE.isSubmitting) {
                return false;
            }

            STATE.isSubmitting = true;

            let restoreState = this.startFormProgress(form);

            var formData = new FormData(form);
            formData.append('csrfToken', CSRF_TOKEN);
            formData.append('formId', form.id);

            let sub = this.submitRemote(form.action, formData);

            sub.then(responseData => {
                this.handleResponse(form, responseData);
            }).catch((error)=>{
                this.stopFormProgress(form, restoreState);
            });
        },

        handleResponse(form, data) {

            let stopProgress = true;

            let event = new CustomEvent('formSubmitDone', {
                detail: data,
                bubbles: true,
                cancelable: true,
            });

            if (!form.dispatchEvent(event)) {
                return false;
            }

            if (data.status == 'ok') {

                if (data.redirect) {
                    stopProgress = false;
                    setTimeout(()=>{
                        window.location = data.redirect;
                    }, 200);
                } else if (data.html) {
                    form.outerHTML = data.html;
                }
            }
            else {
                let el = form.querySelector('[name="' + data.error.field + '"]');

                console.log('Server-Side Validation Fail: ', data.error, el);

                el.setCustomValidity(data.error.message);
                el.reportValidity();

                // Clear error after making a change
                // Listening to entire form as a simple way to handle checkbox sets
                form.addEventListener(
                    'change',
                    ()=>{ el.setCustomValidity(''); },
                    { once: true }
                );
            }

            if (stopProgress) {
                this.stopFormProgress(form, restoreState);
            }
        },

        // TODO: default to url-formencoded to be a bit more efficient, unless
        // there is a file input.  (Have to encode via UrlSearchParams)
        async submitRemote(actionUrl, formData) {

            let response = await fetch(actionUrl, {
                method: 'POST',
                cache: 'no-cache',
                body: formData,
            });

            return response.json();
        },

        startFormProgress: function(form) {

            var button = form.querySelector('*[type="submit"]');
            if (!button) { return false; }

            // Remember what the button style was before adding the loading anim
            let preLoadingState = {
                button: button,
                value: button.innerHTML,
            };

            let rect = button.getBoundingClientRect();
            button.style.minWidth = Math.ceil(rect.right - rect.left) + 'px';

            // Add loading dots
            button.classList.add('ldots');
            button.innerHTML = '<div></div><div class="ld2"></div><div class="ld3"></div>';

            form.classList.add('is-submitting');
            button.style.opacity = 1;

            return preLoadingState;
        },

        stopFormProgress(form, preLoadingState) {

            STATE.isSubmitting = false;
            if (!preLoadingState) { return; }

            clearInterval(this.buttonAnim);
            preLoadingState.button.innerHTML = preLoadingState.value;
            form.classList.remove('is-submitting');
        },

    };

})();
