(function ($cms) {
    $cms.templates.memberTooltip = function (params, container) {
        var submitter = strVal(params.submitter);

        $cms.dom.on(container, 'mouseover', '.js-mouseover-activate-member-tooltip', function (e, el) {
            el.cancelled = false;
            $cms.loadSnippet('member_tooltip&member_id=' + submitter, null, true).then(function (result) {
                if (!el.cancelled) {
                    $cms.ui.activateTooltip(el, e, result.responseText, 'auto', null, null, false, true);
                }
            });
        });

        $cms.dom.on(container, 'mouseout', '.js-mouseout-deactivate-member-tooltip', function (e, el) {
            $cms.ui.deactivateTooltip(el);
            el.cancelled = true;
        });
    };

    $cms.templates.resultsLauncherContinue = function resultsLauncherContinue(params, link) {
        var max = params.max,
            urlStub = params.urlStub,
            numPages = params.numPages,
            message = $cms.format('{!javascript:ENTER_PAGE_NUMBER;^}', numPages);

        $cms.dom.on(link, 'click', function () {
            $cms.ui.prompt(message, numPages, function (res) {
                if (!res) {
                    return;
                }

                res = parseInt(res);
                if ((res >= 1) && (res <= numPages)) {
                    $cms.navigate(urlStub + (urlStub.includes('?') ? '&' : '?') + 'start=' + (max * (res - 1)));
                }
            }, '{!JUMP_TO_PAGE;^}');
        });
    };

    $cms.templates.doNextItem = function doNextItem(params, container) {
        var rand = params.randDoNextItem,
            url = params.url,
            target = params.target,
            warning = params.warning,
            autoAdd = params.autoAdd;

        $cms.dom.on(container, 'click', function (e) {
            var clickedLink = $cms.dom.closest(e.target, 'a', container);

            if (!clickedLink) {
                $cms.navigate(url, target);
                return;
            }

            if (autoAdd) {
                e.preventDefault();
                $cms.ui.confirm('{!KEEP_ADDING_QUESTION;^}', function (answer) {
                    var append = '';
                    if (answer) {
                        append += url.includes('?') ? '&' : '?';
                        append += autoAdd + '=1';
                    }
                    $cms.navigate(url + append, target);
                });
                return;
            }

            if (warning && clickedLink.classList.contains('js-click-confirm-warning')) {
                e.preventDefault();
                $cms.ui.confirm(warning, function (answer) {
                    if (answer) {
                        $cms.navigate(url, target);
                    }
                });
            }
        });

        var docEl = document.getElementById('doc_' + rand),
            helpEl = document.getElementById('help');

        if (docEl && helpEl) {
            /* Do-next document tooltips */
            $cms.dom.on(container, 'mouseover', function () {
                if ($cms.dom.html(docEl) !== '') {
                    window.orig_helper_text = $cms.dom.html(helpEl);
                    $cms.dom.html(helpEl, $cms.dom.html(docEl));
                    $cms.dom.clearTransitionAndSetOpacity(helpEl, 0.0);
                    $cms.dom.fadeTransition(helpEl, 100, 30, 4);

                    helpEl.classList.remove('global_helper_panel_text');
                    helpEl.classList.add('global_helper_panel_text_over');
                }
            });

            $cms.dom.on(container, 'mouseout', function () {
                if (window.orig_helper_text !== undefined) {
                    $cms.dom.html(helpEl, window.orig_helper_text);
                    $cms.dom.clearTransitionAndSetOpacity(helpEl, 0.0);
                    $cms.dom.fadeTransition(helpEl, 100, 30, 4);

                    helpEl.classList.remove('global_helper_panel_text_over');
                    helpEl.classList.add('global_helper_panel_text');
                }
            });
        }

        if (autoAdd) {
            var links = $cms.dom.$$(container, 'a');

            links.forEach(function (link) {
                link.onclick = function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    $cms.ui.confirm(
                        '{!KEEP_ADDING_QUESTION;^}',
                        function (test) {
                            if (test) {
                                link.href += link.href.includes('?') ? '&' : '?';
                                link.href += autoAdd + '=1';
                            }

                            $cms.navigate(link);
                        }
                    );
                    return false;
                };
            });
        }
    };

    $cms.templates.internalizedAjaxScreen = function internalizedAjaxScreen(params, element) {
        internaliseAjaxBlockWrapperLinks(params.url, element, ['.*'], {}, false, true);

        if (params.changeDetectionUrl && (Number(params.refreshTime) > 0)) {
            window.detect_interval = window.setInterval(function () {
                detectChange(params.changeDetectionUrl, params.refreshIfChanged, function () {
                    if ((!document.getElementById('post')) || (document.getElementById('post').value === '')) {
                        $cms.callBlock(params.url, '', element, false, true, null, true).then(function () {
                            detectedChange();
                        });
                    }
                });
            }, params.refreshTime * 1000);
        }
    };

    $cms.templates.ajaxPagination = function ajaxPagination(params) {
        var wrapperEl = $cms.dom.$id(params.wrapperId),
            blockCallUrl = params.blockCallUrl,
            infiniteScrollCallUrl = params.infiniteScrollCallUrl,
            infiniteScrollFunc;

        internaliseAjaxBlockWrapperLinks(blockCallUrl, wrapperEl, ['[^_]*_start', '[^_]*_max'], {});

        if (infiniteScrollCallUrl) {
            infiniteScrollFunc = internaliseInfiniteScrolling.bind(undefined, infiniteScrollCallUrl, wrapperEl);

            $cms.dom.on(window, {
                scroll: infiniteScrollFunc,
                touchmove: infiniteScrollFunc,
                keydown: infiniteScrollingBlock,
                mousedown: infiniteScrollingBlockHold,
                mousemove: function () {
                    // mouseup/mousemove does not work on scrollbar, so best is to notice when mouse moves again (we know we're off-scrollbar then)
                    infiniteScrollingBlockUnhold(infiniteScrollFunc);
                }
            });

            infiniteScrollFunc();
        }
    };

    $cms.templates.confirmScreen = function confirmScreen(params) {};

    $cms.templates.warnScreen = function warnScreen() {
        if ((window.$cms.dom.triggerResize != null) && (window.top !== window)) {
            $cms.dom.triggerResize();
        }
    };

    $cms.templates.fatalScreen = function fatalScreen() {
        if ((window.$cms.dom.triggerResize != null) && (window.top !== window)) {
            $cms.dom.triggerResize();
        }
    };

    $cms.templates.columnedTableScreen = function columnedTableScreen(params) {
        if (params.jsFunctionCalls != null) {
            $cms.executeJsFunctionCalls(params.jsFunctionCalls);
        }
    };

    $cms.templates.questionUiButtons = function questionUiButtons(params, container) {
        $cms.dom.on(container, 'click', '.js-click-close-window-with-val', function (e, clicked) {
            window.returnValue = clicked.dataset.tpReturnValue;

            if (window.faux_close !== undefined) {
                window.faux_close();
            } else {
                try {
                    window.$cms.getMainCmsWindow().focus();
                } catch (ignore) {}

                window.close();
            }
        });
    };

    function detectChange(changeDetectionUrl, refreshIfChanged, callback) {
        $cms.doAjaxRequest(changeDetectionUrl, function (result) {
            var response = strVal(result.responseText);
            if (response === '1') {
                window.clearInterval(window.detect_interval);
                $cms.log('detectChange(): Change detected');
                callback();
            }
        }, 'refresh_if_changed=' + encodeURIComponent(refreshIfChanged));
    }

    function detectedChange() {
        $cms.log('detectedChange(): Change notification running');

        try {
            window.focus();
        } catch (e) {}

        if (window.soundManager !== undefined) {
            var soundUrl = 'data/sounds/message_received.mp3',
                baseUrl = (!soundUrl.includes('data_custom') && !soundUrl.includes('uploads/')) ? $cms.$BASE_URL_NOHTTP : $cms.$CUSTOM_BASE_URL_NOHTTP,
                soundObject = window.soundManager.createSound({ url: baseUrl + '/' + soundUrl });

            if (soundObject && document.hasFocus()/*don't want multiple tabs all pinging*/) {
                soundObject.play();
            }
        }
    }
}(window.$cms));
