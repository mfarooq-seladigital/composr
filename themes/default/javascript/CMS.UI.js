(function ($cms, $util, $dom) {
    'use strict';
    /**
     * @namespace $cms.ui
     */
    $cms.ui = {};
    
    /**
     * Toggle a ToggleableTray
     * @memberof $cms.ui
     * @param elOrOptions
     * @return {boolean} - true when it is opened, false when it is closed
     */
    $cms.ui.toggleableTray = function toggleableTray(elOrOptions) {
        var options, el, animate,
            $IMG_expand = '{$IMG;,1x/trays/expand}',
            $IMG_expand2 = '{$IMG;,1x/trays/expand2}',
            $IMG_contract = '{$IMG;,1x/trays/contract}',
            $IMG_contract2 = '{$IMG;,1x/trays/contract2}';


        // TODO: We have expcon and expcon2 theme images, for use during animation. Are we removing this? If so those theme images should be deleted fully.

        if ($util.isPlainObj(elOrOptions)) {
            options = elOrOptions;
            el =  options.el;
            animate = $cms.configOption('enable_animations') ? boolVal(options.animate, true) : false;
        } else {
            el = elOrOptions;
            animate = $cms.configOption('enable_animations');
        }

        el = $dom.elArg(el);

        var pic = $dom.$(el.parentNode, '.toggleable_tray_button img') || $dom.$('img#e_' + el.id),
            isThemeWizard = Boolean(pic && pic.src && pic.src.includes('themewizard.php'));

        if ($dom.notDisplayed(el)) {
            el.setAttribute('aria-expanded', 'true');

            if (animate) {
                $dom.slideDown(el);
            } else {
                $dom.fadeIn(el);
            }

            if (pic) {
                setTrayThemeImage('expand', 'contract', $IMG_expand, $IMG_contract, $IMG_contract2);
                pic.setAttribute('alt', pic.getAttribute('alt').replace('{!EXPAND;^}', '{!CONTRACT;^}'));
                pic.title = '{!CONTRACT;^}';
            }

            $dom.triggerResize(true);

            return true;
        } else {
            el.setAttribute('aria-expanded', 'false');

            if (animate) {
                $dom.slideUp(el);
            } else {
                $dom.hide(el);
            }

            if (pic) {
                setTrayThemeImage('contract', 'expand', $IMG_contract, $IMG_expand, $IMG_expand2);
                pic.setAttribute('alt', pic.getAttribute('alt').replace('{!CONTRACT;^}', '{!EXPAND;^}'));
                pic.title = '{!EXPAND;^}';
            }

            $dom.triggerResize(true);

            return false;
        }

        // Execution ends here

        function setTrayThemeImage(beforeThemeImg, afterThemeImg, before1Url, after1Url, after2Url) {
            var is1 = $dom.matchesThemeImage(pic.src, before1Url);

            if (is1) {
                if (isThemeWizard) {
                    pic.src = pic.src.replace(beforeThemeImg, afterThemeImg);
                } else {
                    pic.src = $cms.img(after1Url);
                }
            } else {
                if (isThemeWizard) {
                    pic.src = pic.src.replace(beforeThemeImg + '2', afterThemeImg + '2');
                } else {
                    pic.src = $cms.img(after2Url);
                }
            }
        }
    };

    /**
     * @memberof $cms.ui
     * @param options
     * @returns { $cms.views.ModalWindow }
     */
    $cms.ui.openModalWindow = function openModalWindow(options) {
        return new $cms.views.ModalWindow(options);
    };

    /**
     * Enforcing a session using AJAX
     * @memberof $cms.ui
     * @returns { Promise } - Resolves with a boolean indicating whether session confirmed or not
     */
    $cms.ui.confirmSession = function confirmSession() {
        var scriptUrl = '{$FIND_SCRIPT_NOHTTP;,confirm_session}' + $cms.keep(true);

        return new Promise(function (resolvePromise) {
            $cms.doAjaxRequest(scriptUrl).then(function (xhr) {
                var username = xhr.responseText;

                if (username === '') { // Blank means success, no error - so we can call callback
                    resolvePromise(true);
                    return;
                }

                // But non blank tells us the username, and there is an implication that no session is confirmed for this login
                if (username === '{!GUEST;^}') { // Hmm, actually whole login was lost, so we need to ask for username too
                    $cms.ui.prompt('{!USERNAME;^}', '', null, '{!_LOGIN;^}').then(function (prompt) {
                        _confirmSession(function (bool) {
                            resolvePromise(bool);
                        }, prompt);
                    });
                    return;
                }

                _confirmSession(function (bool) {
                    resolvePromise(bool);
                }, username);
            });
        });


        function _confirmSession(callback, username) {
            $cms.ui.prompt(
                $cms.configOption('js_overlays') ? '{!ENTER_PASSWORD_JS_2;^}' : '{!ENTER_PASSWORD_JS;^}', '', null, '{!_LOGIN;^}', 'password'
            ).then(function (prompt) {
                if (prompt != null) {
                    $cms.doAjaxRequest(scriptUrl, null, 'login_username=' + encodeURIComponent(username) + '&password=' + encodeURIComponent(prompt)).then(function (xhr) {
                        if (xhr.responseText === '') { // Blank means success, no error - so we can call callback
                            callback(true);
                        } else {
                            _confirmSession(callback, username); // Recurse
                        }
                    });
                } else {
                    callback(false);
                }
            });
        }
    };

    /**
     * @memberof $cms.ui
     * @param id
     * @param tab
     * @param fromUrl
     * @param automated
     * @returns {boolean}
     */
    $cms.ui.selectTab = function selectTab(id, tab, fromUrl, automated) {
        id = strVal(id);
        tab = strVal(tab);
        fromUrl = !!fromUrl;
        automated = !!automated;

        if (!fromUrl) {
            var tabMarker = $dom.$id('tab__' + tab.toLowerCase());
            if (tabMarker) {
                // For URL purposes, we will change URL to point to tab
                // HOWEVER, we do not want to cause a scroll so we will be careful
                tabMarker.id = '';
                window.location.hash = '#tab__' + tab.toLowerCase();
                tabMarker.id = 'tab__' + tab.toLowerCase();
            }
        }

        var tabs = [], i, element;

        element = $dom.$id('t_' + tab);

        if (!element) {
            $util.fatal('$cms.ui.selectTab(): "#t_' + tab + '" element not found');
        }

        for (i = 0; i < element.parentElement.children.length; i++) {
            if (element.parentElement.children[i].id && (element.parentElement.children[i].id.substr(0, 2) === 't_')) {
                tabs.push(element.parentElement.children[i].id.substr(2));
            }
        }

        for (i = 0; i < tabs.length; i++) {
            element = $dom.$id(id + '_' + tabs[i]);
            if (element) {
                $dom.toggle(element, (tabs[i] === tab));

                if (tabs[i] === tab) {
                    if (window['load_tab__' + tab] === undefined) {
                        $dom.fadeIn(element);
                    }
                }
            }

            element = $dom.$id('t_' + tabs[i]);
            if (element) {
                element.classList.toggle('tab_active', tabs[i] === tab);
            }
        }

        if (window['load_tab__' + tab] !== undefined) {
            // Usually an AJAX loader
            window['load_tab__' + tab](automated, $dom.$id(id + '_' + tab));
        }
    };

    /**
     * Tabs
     * @memberof $cms.ui
     * @param [hash]
     */
    $cms.ui.findUrlTab = function findUrlTab(hash) {
        hash = strVal(hash) || window.location.hash;

        if (hash.replace(/^#!?/, '') !== '') {
            var tab = hash.replace(/^#/, '').replace(/^tab__/, '');

            if ($dom.$id('g_' + tab)) {
                $cms.ui.selectTab('g', tab);
            } else if ((tab.indexOf('__') !== -1) && ($dom.$id('g_' + tab.substr(0, tab.indexOf('__'))))) {
                var old = hash;
                $cms.ui.selectTab('g', tab.substr(0, tab.indexOf('__')));
                window.location.hash = old;
            }
        }
    };

    /**
     * Tooltips that can work on any element with rich HTML support
     * @memberof $cms.ui
     * @param el - the element
     * @param event - the event handler
     * @param tooltip - the text for the tooltip
     * @param width - width is in pixels (but you need 'px' on the end), can be null or auto
     * @param pic - the picture to show in the top-left corner of the tooltip; should be around 30px x 30px
     * @param height - the maximum height of the tooltip for situations where an internal but unusable scrollbar is wanted
     * @param bottom - set to true if the tooltip should definitely appear upwards; rarely use this parameter
     * @param noDelay - set to true if the tooltip should appear instantly
     * @param lightsOff - set to true if the image is to be dimmed
     * @param forceWidth - set to true if you want width to not be a max width
     * @param win - window to open in
     * @param haveLinks - set to true if we activate/deactivate by clicking due to possible links in the tooltip or the need for it to work on mobile
     */
    $cms.ui.activateTooltip = function activateTooltip(el, event, tooltip, width, pic, height, bottom, noDelay, lightsOff, forceWidth, win, haveLinks) {
        event || (event = {});
        width = strVal(width) || 'auto';
        pic = strVal(pic);
        height = strVal(height) || 'auto';
        bottom = !!bottom;
        noDelay = !!noDelay;
        lightsOff = !!lightsOff;
        forceWidth = !!forceWidth;
        win || (win = window);
        haveLinks = !!haveLinks;

        if ((el.deactivatedAt) && (Date.now() - el.deactivatedAt < 200)) {
            return;
        }

        if (!tooltip) {
            return;
        }

        if (window.isDoingADrag) {
            // Don't want tooltips appearing when doing a drag and drop operation
            return;
        }

        if (!el) {
            return;
        }

        if (!haveLinks && $cms.browserMatches('touch_enabled')) {
            return; // Too erratic
        }

        $cms.ui.clearOutTooltips(el.tooltipId);

        // Add in move/leave events if needed
        if (!haveLinks) {
            $dom.on(el, 'mouseout.cmsTooltip', function (e) {
                if (!e.relatedTarget || !el.contains(e.relatedTarget)) {
                    $cms.ui.deactivateTooltip(el);
                }
            });

            $dom.on(el, 'mousemove.cmsTooltip', function () {
                $cms.ui.repositionTooltip(el, event, false, false, null, false, win);
            });
        } else {
            $dom.on(window, 'click.cmsTooltip', function (e) {
                if ($dom.$id(el.tooltipId) && $dom.isDisplayed($dom.$id(el.tooltipId))) {
                    $cms.ui.deactivateTooltip(el);
                }
            });
        }

        if (typeof tooltip === 'function') {
            tooltip = tooltip();
        }

        tooltip = strVal(tooltip);

        if (!tooltip) {
            return;
        }

        el.isOver = true;
        el.deactivatedAt = null;
        el.tooltipOn = false;
        el.initialWidth = width;
        el.haveLinks = haveLinks;

        var children = el.querySelectorAll('img');
        for (var i = 0; i < children.length; i++) {
            children[i].setAttribute('title', '');
        }

        var tooltipEl;
        if ((el.tooltipId != null) && ($dom.$id(el.tooltipId))) {
            tooltipEl = $dom.$id(el.tooltipId);
            tooltipEl.style.display = 'none';
            $dom.empty(tooltipEl);
            setTimeout(function () {
                $cms.ui.repositionTooltip(el, event, bottom, true, tooltipEl, forceWidth);
            }, 0);
        } else {
            tooltipEl = document.createElement('div');
            tooltipEl.role = 'tooltip';
            tooltipEl.style.display = 'none';
            var rtPos = tooltip.indexOf('results_table');
            tooltipEl.className = 'tooltip ' + ((rtPos === -1 || rtPos > 100) ? 'tooltip_ownlayout' : 'tooltip_nolayout') + ' boxless_space' + (haveLinks ? ' have_links' : '');
            if (el.className.substr(0, 3) === 'tt_') {
                tooltipEl.className += ' ' + el.className;
            }
            if (tooltip.length < 50) {  // Only break words on long tooltips. Otherwise it messes with alignment.
                tooltipEl.style.wordWrap = 'normal';
            }
            if (forceWidth) {
                tooltipEl.style.width = width;
            } else {
                if (width === 'auto') {
                    var newAutoWidth = $dom.getWindowWidth(win) - 30 - window.currentMouseX;
                    if (newAutoWidth < 150) { // For tiny widths, better let it slide to left instead, which it will as this will force it to not fit
                        newAutoWidth = 150;
                    }
                    tooltipEl.style.maxWidth = newAutoWidth + 'px';
                } else {
                    tooltipEl.style.maxWidth = width;
                }
                tooltipEl.style.width = 'auto';
            }
            if (height && (height !== 'auto')) {
                tooltipEl.style.maxHeight = height;
                tooltipEl.style.overflow = 'auto';
            }
            tooltipEl.style.position = 'absolute';
            tooltipEl.id = 't_' + $util.random();
            el.tooltipId = tooltipEl.id;
            $cms.ui.repositionTooltip(el, event, bottom, true, tooltipEl, forceWidth);
            document.body.appendChild(tooltipEl);
        }
        tooltipEl.ac = el;

        if (pic) {
            var img = win.document.createElement('img');
            img.src = pic;
            img.className = 'tooltip_img';
            if (lightsOff) {
                img.classList.add('faded_tooltip_img');
            }
            tooltipEl.appendChild(img);
            tooltipEl.classList.add('tooltip_with_img');
        }

        var eventCopy = { // Needs to be copied as it will get erased on IE after this function ends
            'pageX': +event.pageX || 0,
            'pageY': +event.pageY || 0,
            'clientX': +event.clientX || 0,
            'clientY': +event.clientY || 0,
            'type': event.type || ''
        };

        setTimeout(function () {
            if (!el.isOver) {
                return;
            }

            if ((!el.tooltipOn) || (tooltipEl.childNodes.length === 0)) { // Some other tooltip jumped in and wiped out tooltip on a delayed-show yet never triggers due to losing focus during that delay
                $dom.append(tooltipEl, tooltip);
            }

            el.tooltipOn = true;
            tooltipEl.style.display = 'block';
            if ((tooltipEl.style.width === 'auto') && ((tooltipEl.childNodes.length !== 1) || (tooltipEl.childNodes[0].nodeName.toLowerCase() !== 'img'))) {
                tooltipEl.style.width = ($dom.contentWidth(tooltipEl) + 1/*for rounding issues from em*/) + 'px'; // Fix it, to stop the browser retroactively reflowing ambiguous layer widths on mouse movement
            }

            if (!noDelay) {
                // If delayed we will sub in what the currently known global mouse coordinate is
                eventCopy.pageX = win.currentMouseX;
                eventCopy.pageY = win.currentMouseY;
            }

            $cms.ui.repositionTooltip(el, eventCopy, bottom, true, tooltipEl, forceWidth, win);
        }, noDelay ? 0 : 666);
    };

    /**
     * @param el
     * @param event
     * @param bottom
     * @param starting
     * @param tooltipElement
     * @param forceWidth
     * @param win
     */
    $cms.ui.repositionTooltip = function repositionTooltip(el, event, bottom, starting, tooltipElement, forceWidth, win) {
        bottom = !!bottom;
        win || (win = window);

        if (!el.isOver) {
            return;
        }

        if (!starting) { // Real JS mousemove event, so we assume not a screen-reader and have to remove natural tooltip
            if (el.getAttribute('title')) {
                el.setAttribute('title', '');
            }

            if ((el.parentElement.localName === 'a') && (el.parentElement.getAttribute('title')) && ((el.localName === 'abbr') || (el.parentElement.getAttribute('title').includes('{!LINK_NEW_WINDOW;^}')))) {
                el.parentElement.setAttribute('title', '');  // Do not want second tooltips that are not useful
            }
        }

        if (!el.tooltipId) {
            if (el.onmouseover) {
                el.onmouseover(event);
            }
            return;
        }

        tooltipElement || (tooltipElement = $dom.$id(el.tooltipId));

        if (!tooltipElement) {
            return;
        }

        var styleOffsetX = 9,
            styleOffsetY = (el.haveLinks) ? 18 : 9,
            x, y;

        // Find mouse position
        x = window.currentMouseX;
        y = window.currentMouseY;
        x += styleOffsetX;
        y += styleOffsetY;
        try {
            if (event.type) {
                if (event.type !== 'focus') {
                    el.doneNoneFocus = true;
                }

                if ((event.type === 'focus') && (el.doneNoneFocus)) {
                    return;
                }

                x = (event.type === 'focus') ? (win.pageXOffset + $dom.getWindowWidth(win) / 2) : (window.currentMouseX + styleOffsetX);
                y = (event.type === 'focus') ? (win.pageYOffset + $dom.getWindowHeight(win) / 2 - 40) : (window.currentMouseY + styleOffsetY);
            }
        } catch (ignore) {}
        // Maybe mouse position actually needs to be in parent document?
        try {
            if (event.target && (event.target.ownerDocument !== win.document)) {
                x = win.currentMouseX + styleOffsetX;
                y = win.currentMouseY + styleOffsetY;
            }
        } catch (ignore) {}

        // Work out which direction to render in
        var width = $dom.contentWidth(tooltipElement);
        if (tooltipElement.style.width === 'auto') {
            if (width < 200) {
                // Give some breathing room, as might already have painfully-wrapped when it found there was not much space
                width = 200;
            }
        }
        var height = tooltipElement.offsetHeight;
        var xExcess = x - $dom.getWindowWidth(win) - win.pageXOffset + width + 10/*magic tolerance factor*/;
        if (xExcess > 0) { // Either we explicitly gave too much width, or the width auto-calculated exceeds what we THINK is the maximum width in which case we have to re-compensate with an extra contingency to stop CSS/JS vicious disagreement cycles
            var xBefore = x;
            x -= xExcess + 20 + styleOffsetX;
            if (x < 100) { // Do not make it impossible to de-focus the tooltip
                x = (xBefore < 100) ? xBefore : 100;
            }
        }
        if (x < 0) {
            x = 0;
        }
        if (bottom) {
            tooltipElement.style.top = (y - height) + 'px';
        } else {
            var yExcess = y - $dom.getWindowHeight(win) - win.pageYOffset + height + styleOffsetY;
            if (yExcess > 0) {
                y -= yExcess;
            }
            var scrollY = win.pageYOffset;
            if (y < scrollY) {
                y = scrollY;
            }
            tooltipElement.style.top = y + 'px';
        }
        tooltipElement.style.left = x + 'px';
    };

    /**
     * @param el
     * @param tooltipElement
     */
    $cms.ui.deactivateTooltip = function deactivateTooltip(el, tooltipElement) {
        if (el.isOver) {
            el.deactivatedAt = Date.now();
        }
        el.isOver = false;

        if (el.tooltipId == null) {
            return;
        }

        tooltipElement || (tooltipElement = $dom.$('#' + el.tooltipId));

        if (tooltipElement) {
            $dom.off(tooltipElement, 'mouseout.cmsTooltip');
            $dom.off(tooltipElement, 'mousemove.cmsTooltip');
            // $dom.off(window, 'click.cmsTooltip');
            $dom.hide(tooltipElement);
        }
    };

    /**
     * @param tooltipBeingOpened
     */
    $cms.ui.clearOutTooltips = function clearOutTooltips(tooltipBeingOpened) {
        // Delete other tooltips, which due to browser bugs can get stuck
        var selector = '.tooltip';
        if (tooltipBeingOpened) {
            selector += ':not(#' + tooltipBeingOpened + ')';
        }
        $dom.$$(selector).forEach(function (el) {
            $cms.ui.deactivateTooltip(el.ac, el);
        });
    };

    $dom.ready.then(function () {
        // Tooltips close on browser resize
        $dom.on(window, 'resize', function () {
            $cms.ui.clearOutTooltips();
        });
    });

    /*

     This code does a lot of stuff relating to overlays...

     It provides callback-based *overlay*-driven substitutions for the standard browser windowing API...
     - alert
     - prompt
     - confirm
     - open (known as popups)
     - showModalDialog
     A term we are using for these kinds of 'overlay' is '(faux) modal window'.

     It provides a generic function to open a link as an overlay.

     It provides a function to open an image link as a 'lightbox' (we use the term lightbox exclusively to refer to images in an overlay).

     */

    /**
     * @memberof $cms.ui
     * @param question
     * @param callback
     * @param title
     * @param unescaped
     */
    $cms.ui.confirm = function confirm(question, callback, title, unescaped) {
        question = strVal(question);
        title = strVal(title) || '{!Q_SURE;^}';
        unescaped = boolVal(unescaped);

        return new Promise(function (resolveConfirm) {
            if (!$cms.configOption('js_overlays')) {
                var bool = window.confirm(question);
                if (callback != null) {
                    callback(bool);
                }
                resolveConfirm(bool);
                return;
            }

            var myConfirm = {
                type: 'confirm',
                text: unescaped ? question : $cms.filter.html(question).replace(/\n/g, '<br />'),
                yesButton: '{!YES;^}',
                noButton: '{!NO;^}',
                cancelButton: null,
                title: title,
                yes: function () {
                    if (callback != null) {
                        callback(true);
                    }
                    resolveConfirm(true);
                },
                no: function () {
                    if (callback != null) {
                        callback(false);
                    }
                    resolveConfirm(false);
                },
                width: '450'
            };
            $cms.ui.openModalWindow(myConfirm);
        });
    };

    var currentAlertNotice,
        currentAlertTitle,
        currentAlertPromise;
    /**
     * @memberof $cms.ui
     * @param notice
     * @param title
     * @param unescaped
     * @returns { Promise }
     */
    $cms.ui.alert = function alert(notice, title, unescaped) {
        var options,
            single = false;

        if ($util.isObj(notice)) {
            options = notice;
            notice = strVal(options.notice);
            title = strVal(options.title) || '{!MESSAGE;^}';
            unescaped = Boolean(options.unescaped);
            single = Boolean(options.single);
        } else {
            notice = strVal(notice);
            title = strVal(title) || '{!MESSAGE;^}';
            unescaped = Boolean(unescaped);
        }

        if (single && (currentAlertNotice === notice) && (currentAlertTitle === title)) {
            return currentAlertPromise;
        }

        currentAlertNotice = notice;
        currentAlertTitle = title;
        currentAlertPromise = new Promise(function (resolveAlert) {
            if (!$cms.configOption('js_overlays')) {
                window.alert(notice);
                currentAlertNotice = null;
                currentAlertTitle = null;
                currentAlertPromise = null;
                resolveAlert();
                return;
            }

            var myAlert = {
                type: 'alert',
                text: unescaped ? notice : $cms.filter.html(notice).replace(/\n/g, '<br />'),
                yesButton: '{!INPUTSYSTEM_OK;^}',
                width: '600',
                yes: function () {
                    currentAlertNotice = null;
                    currentAlertTitle = null;
                    currentAlertPromise = null;
                    resolveAlert();
                },
                title: title,
                cancelButton: null
            };

            $cms.ui.openModalWindow(myAlert);
        });

        return currentAlertPromise;
    };

    /**
     * @memberof $cms.ui
     * @param question
     * @param defaultValue
     * @param callback
     * @param title
     * @param inputType
     * @returns { Promise }
     */
    $cms.ui.prompt = function prompt(question, defaultValue, callback, title, inputType) {
        question = strVal(question);
        defaultValue = strVal(defaultValue);
        inputType = strVal(inputType);

        return new Promise(function (resolvePrompt) {
            if (!$cms.configOption('js_overlays')) {
                var value = window.prompt(question, defaultValue);
                if (callback != null) {
                    callback(value);
                }
                resolvePrompt(value);
                return;
            }

            var myPrompt = {
                type: 'prompt',
                text: $cms.filter.html(question).replace(/\n/g, '<br />'),
                yesButton: '{!INPUTSYSTEM_OK;^}',
                cancelButton: '{!INPUTSYSTEM_CANCEL;^}',
                defaultValue: defaultValue,
                title: title,
                yes: function (value) {
                    if (callback != null) {
                        callback(value);
                    }
                    resolvePrompt(value);
                },
                cancel: function () {
                    if (callback != null) {
                        callback(null);
                    }
                    resolvePrompt(null);
                },
                width: '450'
            };
            if (inputType) {
                myPrompt.inputType = inputType;
            }
            $cms.ui.openModalWindow(myPrompt);
        });
    };

    /**
     * @memberof $cms.ui
     * @param url
     * @param name
     * @param options
     * @param callback
     * @param target
     * @param cancelText
     * @returns { Promise }
     */
    $cms.ui.showModalDialog = function showModalDialog(url, name, options, callback, target, cancelText) {
        url = strVal(url);
        name = strVal(name);
        options = strVal(options);
        target = strVal(target);
        cancelText = strVal(cancelText) || '{!INPUTSYSTEM_CANCEL;^}';

        return new Promise(function (resolveModal) {
            if (!$cms.configOption('js_overlays')) {
                if (!window.showModalDialog) {
                    throw new Error('$cms.ui.showModalDialog(): window.showModalDialog is not supported by the current browser');
                }

                options = options.replace('height=auto', 'height=520');

                var timer = new Date().getTime(), result;
                try {
                    result = window.showModalDialog(url, name, options);
                } catch (ignore) {
                    // IE gives "Access is denied" if popup was blocked, due to var result assignment to non-real window
                }
                var timerNow = new Date().getTime();
                if (timerNow - 100 > timer) { // Not popup blocked
                    if (result == null) {
                        if (callback != null) {
                            callback(null);
                        }
                        resolveModal(null);
                    } else {
                        if (callback != null) {
                            callback(result);
                        }
                        resolveModal(result);
                    }
                }
                return;
            }

            var width = null, height = null,
                scrollbars = null, unadorned = null;

            if (options) {
                var parts = options.split(/[;,]/g), i;
                for (i = 0; i < parts.length; i++) {
                    var bits = parts[i].split('=');
                    if (bits[1] !== undefined) {
                        if ((bits[0] === 'dialogWidth') || (bits[0] === 'width')) {
                            width = bits[1].replace(/px$/, '');
                        }

                        if ((bits[0] === 'dialogHeight') || (bits[0] === 'height')) {
                            if (bits[1] === '100%') {
                                height = '' + ($dom.getWindowHeight() - 200);
                            } else {
                                height = bits[1].replace(/px$/, '');
                            }
                        }

                        if (((bits[0] === 'resizable') || (bits[0] === 'scrollbars')) && (scrollbars !== true)) {
                            scrollbars = ((bits[1] === 'yes') || (bits[1] === '1'))/*if either resizable or scrollbars set we go for scrollbars*/;
                        }

                        if (bits[0] === 'unadorned') {
                            unadorned = ((bits[1] === 'yes') || (bits[1] === '1'));
                        }
                    }
                }
            }

            if (url.includes(window.location.host)) {
                url += (!url.includes('?') ? '?' : '&') + 'overlay=1';
            }

            var myFrame = {
                type: 'iframe',
                finished: function (value) {
                    if (callback != null) {
                        callback(value);
                    }
                    resolveModal(value);
                },
                name: name,
                width: width,
                height: height,
                scrollbars: scrollbars,
                href: url.replace(/^https?:/, window.location.protocol),
                cancelButton: (unadorned !== true) ? cancelText : null
            };
            if (target) {
                myFrame.target = target;
            }
            $cms.ui.openModalWindow(myFrame);
        });
    };

    /**
     * @memberof $cms.ui
     * @param url
     * @param name
     * @param options
     * @param target
     * @param [cancelText]
     * @returns { Promise }
     */
    $cms.ui.open = function open(url, name, options, target, cancelText) {
        url = strVal(url);
        name = strVal(name);
        options = strVal(options);
        target = strVal(target);
        cancelText = strVal(cancelText) || '{!INPUTSYSTEM_CANCEL;^}';

        return new Promise(function (resolveOpen) {
            if (!$cms.configOption('js_overlays')) {
                options = options.replace('height=auto', 'height=520');
                window.open(url, name, options);
                resolveOpen();
                return;
            }

            $cms.ui.showModalDialog(url, name, options, null, target, cancelText);
            resolveOpen();
        });
    };

    var tempDisabledButtons = {};
    /**
     * @memberof $cms.ui
     * @param btn
     * @param [permanent]
     */
    $cms.ui.disableButton = function disableButton(btn, permanent) {
        permanent = Boolean(permanent);

        if (btn.form && (btn.form.target === '_blank')) {
            return;
        }

        var uid = $util.uid(btn),
            timeout, interval;

        setTimeout(function () {
            btn.style.cursor = 'wait';
            btn.disabled = true;
            if (!permanent) {
                tempDisabledButtons[uid] = true;
            }
        }, 20);

        if (!permanent) {
            timeout = setTimeout(enableDisabledButton, 5000);

            if (btn.form.target === 'preview_iframe') {
                interval = window.setInterval(function () {
                    if (window.frames['preview_iframe'].document && window.frames['preview_iframe'].document.body) {
                        if (interval != null) {
                            window.clearInterval(interval);
                            interval = null;
                        }
                        enableDisabledButton();
                    }
                }, 500);
            }

            $dom.on(window, 'pagehide', enableDisabledButton);
        }

        function enableDisabledButton() {
            if (timeout != null) {
                clearTimeout(timeout);
                timeout = null;
            }

            if (tempDisabledButtons[uid]) {
                btn.disabled = false;
                btn.style.removeProperty('cursor');
                delete tempDisabledButtons[uid];
            }
        }
    };

    /**
     * @memberof $cms.ui
     * @param form
     * @param permanent
     */
    $cms.ui.disableFormButtons = function disableFormButtons(form, permanent) {
        var buttons = $dom.$$(form, 'input[type="submit"], input[type="button"], input[type="image"], button');

        buttons.forEach(function (btn) {
            $cms.ui.disableButton(btn, permanent);
        });
    };

    /**
     * This is kinda dumb, ported from checking.js, originally named as disable_buttons_just_clicked()
     * @memberof $cms.ui
     * @param permanent
     */
    $cms.ui.disableSubmitAndPreviewButtons = function disableSubmitAndPreviewButtons(permanent) {
        // [accesskey="u"] identifies submit button, [accesskey="p"] identifies preview button
        var buttons = $dom.$$('input[accesskey="u"], button[accesskey="u"], input[accesskey="p"], button[accesskey="p"]');

        permanent = Boolean(permanent);

        buttons.forEach(function (btn) {
            if (!btn.disabled && !tempDisabledButtons[$util.uid(btn)]/*We do not want to interfere with other code potentially operating*/) {
                $cms.ui.disableButton(btn, permanent);
            }
        });
    };

    $cms.ui.enableSubmitAndPreviewButtons = function enableSubmitAndPreviewButtons() {
        // [accesskey="u"] identifies submit button, [accesskey="p"] identifies preview button
        var buttons = $dom.$$('input[accesskey="u"], button[accesskey="u"], input[accesskey="p"], button[accesskey="p"]');

        buttons.forEach(function (btn) {
            if (btn.disabled && !tempDisabledButtons[$util.uid(btn)]/*We do not want to interfere with other code potentially operating*/) {
                btn.style.cursor = '';
                btn.disabled = false;
            }
        });
    };

    /**
     * Originally _open_image_into_lightbox
     * @memberof $cms.ui
     * @param initialImgUrl
     * @param description
     * @param x
     * @param n
     * @param hasFullButton
     * @param isVideo
     * @returns { $cms.views.ModalWindow }
     */
    $cms.ui.openImageIntoLightbox = function openImageIntoLightbox(initialImgUrl, description, x, n, hasFullButton, isVideo) {
        hasFullButton = !!hasFullButton;
        isVideo = !!isVideo;

        // Set up overlay for Lightbox
        var lightboxCode = /** @lang HTML */'' +
           '<div style="text-align: center">' +
                '<p class="ajax_loading" id="lightbox_image"><img src="' + $cms.img('{$IMG*;,loading}') + '" /></p>' +
                '<p id="lightbox_meta" style="display: none" class="associated_link associated_links_block_group">' +
                    '<span id="lightbox_description">' + description + '</span>' +
                    ((n == null) ? '' : ('<span id="lightbox_position_in_set"><span id="lightbox_position_in_set_x">' + x + '</span> / <span id="lightbox_position_in_set_n">' + n + '</span></span>')) +
                    (isVideo ? '' : ('<span id="lightbox_full_link"><a href="' + $cms.filter.html(initialImgUrl) + '" target="_blank" title="{$STRIP_TAGS;^,{!SEE_FULL_IMAGE}} {!LINK_NEW_WINDOW;^}">{!SEE_FULL_IMAGE;^}</a></span>')) +
                '</p>' +
            '</div>';

        // Show overlay
        var myLightbox = {
                type: 'lightbox',
                text: lightboxCode,
                cancelButton: '{!INPUTSYSTEM_CLOSE;^}',
                width: '450', // This will be updated with the real image width, when it has loaded
                height: '300' // "
            },
            modal = $cms.ui.openModalWindow(myLightbox);

        // Load proper image
        setTimeout(function () { // Defer execution until the HTML was parsed
            if (isVideo) {
                var video = document.createElement('video');
                video.id = 'lightbox_image';
                video.className = 'lightbox_image';
                video.controls = 'controls';
                video.autoplay = 'autoplay';
                $dom.html(video, initialImgUrl);
                video.addEventListener('loadedmetadata', function () {
                    $cms.ui.resizeLightboxDimensionsImg(modal, video, hasFullButton, true);
                });
            } else {
                var img = modal.topWindow.document.createElement('img');
                img.className = 'lightbox_image';
                img.id = 'lightbox_image';
                img.onload = function () {
                    $cms.ui.resizeLightboxDimensionsImg(modal, img, hasFullButton, false);
                };
                img.src = initialImgUrl;
            }
        }, 0);

        return modal;
    };

    /**
     * @memberof $cms.ui
     * @param modal
     * @param img
     * @param hasFullButton
     * @param isVideo
     */
    $cms.ui.resizeLightboxDimensionsImg = function resizeLightboxDimensionsImg(modal, img, hasFullButton, isVideo) {
        if (!modal.el) {
            /* Overlay closed already */
            return;
        }

        var realWidth = isVideo ? img.videoWidth : img.width,
            width = realWidth,
            realHeight = isVideo ? img.videoHeight : img.height,
            height = realHeight,
            lightboxImage = modal.topWindow.$dom.$id('lightbox_image'),
            lightboxMeta = modal.topWindow.$dom.$id('lightbox_meta'),
            lightboxDescription = modal.topWindow.$dom.$id('lightbox_description'),
            lightboxPositionInSet = modal.topWindow.$dom.$id('lightbox_position_in_set'),
            lightboxFullLink = modal.topWindow.$dom.$id('lightbox_full_link'),
            sup = lightboxImage.parentNode;
        sup.removeChild(lightboxImage);
        if (sup.firstChild) {
            sup.insertBefore(img, sup.firstChild);
        } else {
            sup.appendChild(img);
        }
        sup.className = '';
        sup.style.textAlign = 'center';
        sup.style.overflow = 'hidden';

        dimsFunc();
        $dom.on(window, 'resize', dimsFunc);

        function dimsFunc() {
            lightboxDescription.style.display = (lightboxDescription.firstChild) ? 'inline' : 'none';
            if (lightboxFullLink) {
                var showLightboxFullLink = !!(!isVideo && hasFullButton && ((realWidth > maxWidth) || (realHeight > maxHeight)));
                $dom.toggle(lightboxFullLink, showLightboxFullLink);
            }
            var showLightboxMeta = !!((lightboxDescription.style.display === 'inline') || (lightboxPositionInSet !== null) || (lightboxFullLink && lightboxFullLink.style.display === 'inline'));
            $dom.toggle(lightboxMeta, showLightboxMeta);

            // Might need to rescale using some maths, if natural size is too big
            var maxDims = _getMaxLightboxImgDims(modal, hasFullButton),
                maxWidth = maxDims[0],
                maxHeight = maxDims[1];

            if (width > maxWidth) {
                width = maxWidth;
                height = parseInt(maxWidth * realHeight / realWidth - 1);
            }

            if (height > maxHeight) {
                width = parseInt(maxHeight * realWidth / realHeight - 1);
                height = maxHeight;
            }

            img.width = width;
            img.height = height;
            modal.resetDimensions('' + width, '' + height, false, true); // Temporarily forced, until real height is known (includes extra text space etc)

            setTimeout(function () {
                modal.resetDimensions('' + width, '' + height, false);
            });

            if (img.parentElement) {
                img.parentElement.parentElement.parentElement.style.width = 'auto';
                img.parentElement.parentElement.parentElement.style.height = 'auto';
            }

            function _getMaxLightboxImgDims(modal, hasFullButton) {
                var maxWidth = modal.topWindow.$dom.getWindowWidth() - 20,
                    maxHeight = modal.topWindow.$dom.getWindowHeight() - 60;

                if (hasFullButton) {
                    maxHeight -= 120;
                }

                return [maxWidth, maxHeight];
            }
        }
    };

    /**
     * Ask a user a question: they must click a button
     * 'Cancel' should come as index 0 and Ok/default-option should come as index 1. This is so that the fallback works right.
     * @param message
     * @param buttonSet
     * @param windowTitle
     * @param fallbackMessage
     * @param callback
     * @param dialogWidth
     * @param dialogHeight
     * @returns { Promise }
     */
    $cms.ui.generateQuestionUi = function generateQuestionUi(message, buttonSet, windowTitle, fallbackMessage, callback, dialogWidth, dialogHeight) {
        message = strVal(message);

        return new Promise(function (resolvePromise) {
            var imageSet = [],
                newButtonSet = [];
            for (var s in buttonSet) {
                newButtonSet.push(buttonSet[s]);
                imageSet.push(s);
            }
            buttonSet = newButtonSet;

            if ((window.showModalDialog !== undefined) || $cms.configOption('js_overlays')) {
                // @TODO: window.showModalDialog() was removed completely in Chrome 43, and Firefox 55. See WebKit bug 151885 for possible future removal from Safari.
                if (buttonSet.length > 4) {
                    dialogHeight += 5 * (buttonSet.length - 4);
                }

                // Intentionally FIND_SCRIPT and not FIND_SCRIPT_NOHTTP, because no needs-HTTPS security restriction applies to popups, yet popups do not know if they run on HTTPS if behind a transparent reverse proxy
                var url = $cms.maintainThemeInLink('{$FIND_SCRIPT;,question_ui}?message=' + encodeURIComponent(message) + '&image_set=' + encodeURIComponent(imageSet.join(',')) + '&button_set=' + encodeURIComponent(buttonSet.join(',')) + '&window_title=' + encodeURIComponent(windowTitle) + $cms.keep());
                if (dialogWidth == null) {
                    dialogWidth = 440;
                }
                if (dialogHeight == null) {
                    dialogHeight = 180;
                }
                $cms.ui.showModalDialog(url, null, 'dialogWidth=' + dialogWidth + ';dialogHeight=' + dialogHeight + ';status=no;unadorned=yes').then(function (result) {
                    if (result == null) {
                        if (callback != null) {
                            callback(buttonSet[0]); // just pressed 'cancel', so assume option 0
                        }
                        resolvePromise(buttonSet[0]);
                    } else {
                        if (callback != null) {
                            callback(result);
                        }
                        resolvePromise(result);
                    }
                });
                return;
            }

            if (buttonSet.length === 1) {
                $cms.ui.alert(fallbackMessage ? fallbackMessage : message, windowTitle).then(function () {
                    if (callback != null) {
                        callback(buttonSet[0]);
                    }
                    resolvePromise(buttonSet[0]);
                });
            } else if (buttonSet.length === 2) {
                $cms.ui.confirm(fallbackMessage ? fallbackMessage : message, null, windowTitle).then(function (result) {
                    if (callback != null) {
                        callback(result ? buttonSet[1] : buttonSet[0]);
                    }
                    resolvePromise(result ? buttonSet[1] : buttonSet[0]);
                });
            } else {
                if (!fallbackMessage) {
                    message += '\n\n{!INPUTSYSTEM_TYPE_EITHER;^}';
                    for (var i = 0; i < buttonSet.length; i++) {
                        message += buttonSet[i] + ',';
                    }
                    message = message.substr(0, message.length - 1);
                } else {
                    message = fallbackMessage;
                }

                $cms.ui.prompt(message, '', null, windowTitle).then(function (result) {
                    if (result == null) {
                        if (callback != null) {
                            callback(buttonSet[0]); // just pressed 'cancel', so assume option 0
                        }
                        resolvePromise(buttonSet[0]);
                        return;
                    } else {
                        if (result === '') {
                            if (callback != null) {
                                callback(buttonSet[1]); // just pressed 'ok', so assume option 1
                            }
                            resolvePromise(buttonSet[1]);
                            return;
                        }
                        for (var i = 0; i < buttonSet.length; i++) {
                            if (result.toLowerCase() === buttonSet[i].toLowerCase()) { // match
                                if (callback != null) {
                                    callback(result);
                                }
                                resolvePromise(result);
                                return;
                            }
                        }
                    }

                    // unknown
                    if (callback != null) {
                        callback(buttonSet[0]);
                    }
                    resolvePromise(buttonSet[0]);
                });
            }
        });
    };
}(window.$cms, window.$util, window.$dom));