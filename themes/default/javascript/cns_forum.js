(function ($cms) {
    'use strict';

    $cms.views.CnsForumTopicWrapper = CnsForumTopicWrapper;
    /**
     * @memberof $cms.views
     * @class
     * @extends $cms.View
     */
    function CnsForumTopicWrapper() {
        CnsForumTopicWrapper.base(this, 'constructor', arguments);
    }

    $cms.inherits(CnsForumTopicWrapper, $cms.View, /**@lends CnsForumTopicWrapper#*/{
        events: function () {
            return {
                'click .js-click-mark-all-topics': 'markAllTopics',
                'change .js-select-change-submit-form': 'changeSubmit',
                'click .js-click-btn-add-form-marked-posts': 'addFormMarkedPosts'
            };
        },

        markAllTopics: function () {
            $cms.dom.$$('input[type="checkbox"][name^="mark_"]').forEach(function (checkbox) {
                checkbox.click();
            });
        },

        changeSubmit: function (e, select) {
            $cms.dom.submit(select.form);
        },

        addFormMarkedPosts: function (e, btn) {
            if ($cms.form.addFormMarkedPosts(btn.form, 'mark_')) {
                $cms.ui.disableButton(btn);
            } else {
                $cms.ui.alert('{!NOTHING_SELECTED;}');
                e.preventDefault();
            }
        }
    });
    
    $cms.functions.moduleTopicsPostJavascript = function moduleTopicsPostJavascript(size, stub) {
        var form = document.getElementById('post').form;
        form.addEventListener('submit', function () {
            var post = form.elements['post'],
                textValue;

            if ($cms.form.isWysiwygField(post)) {
                try {
                    textValue = window.CKEDITOR.instances['post'].getData();
                } catch (ignore) { }
            } else {
                if (!post.value && post[1]) {
                    post = post[1];
                }
                textValue = post.value;
            }

            if (textValue.length > size) {
                $cms.ui.alert('{!cns:POST_TOO_LONG;}');
                return false;
            }

            if (stub != '') {
                var df = stub;
                var pv = post.value;
                if (post && (pv.substring(0, df.length) == df)) {
                    pv = pv.substring(df.length, pv.length);
                }
                post.value = pv;
            }
        })
    };

    $cms.functions.moduleTopicsPostJavascriptForceGuestNames = function moduleTopicsPostJavascriptForceGuestNames() {
        var posterNameIfGuest = document.getElementById("poster_name_if_guest");
        if (posterNameIfGuest) {
            var crf = function () {
                if (posterNameIfGuest.value == "{!GUEST;}") {
                    posterNameIfGuest.value = "";
                }
            };
            crf();
            posterNameIfGuest.addEventListener("blur", crf);
        }
    };

    $cms.functions.moduleTopicsAddPoll = function moduleTopicsAddPoll() {
        var existing = document.getElementById('existing'),
            form = existing.form;

        form.addEventListener('change', pollFormElementsChangeListener);

        function pollFormElementsChangeListener() {
            var disableAll = (existing.selectedIndex !== 0);
            for (var i = 0; i < form.elements.length; i++) {
                if ((form.elements[i] !== existing) && (form.elements[i].id !== 'perform_keywordcheck') && ((form.elements[i].getAttribute('type') === 'checkbox') || (form.elements[i].getAttribute('type') === 'text'))) {
                    $cms.form.setRequired(form.elements[i].name, (!disableAll) && ((form.elements[i].id === 'question') || (form.elements[i].id === 'answer_0')));
                    $cms.form.setLocked(form.elements[i], disableAll);
                }
            }
        }
    };

    $cms.functions.moduleAdminCnsForums = function moduleAdminCnsForums() {
        if (document.getElementById('delete')) {
            var form = document.getElementById('delete').form;
            var crf = function () {
                form.elements['target_forum'].disabled = (!form.elements['delete'].checked);
                form.elements['delete_topics'].disabled = (!form.elements['delete'].checked);
            };
            crf();
            form.elements['delete'].addEventListener('change', crf);
        }
    };

    $cms.functions.moduleAdminCnsForumGroupings = function moduleAdminCnsForumGroupings() {
        if (document.getElementById('delete')) {
            var form = document.getElementById('delete').form;
            var crf = function () {
                form.elements['target_forum_grouping'].disabled = (!form.elements['delete'].checked);
            };
            crf();
            form.elements['delete'].addEventListener('change', crf);
        }
    };

    $cms.templates.cnsVirtualForumFiltering = function cnsVirtualForumFiltering() {
        var container = this;

        $cms.dom.on(container, 'change', '.js-select-change-form-submit', function (e, select) {
            $cms.dom.submit(select.form);
        });
    };

    $cms.templates.cnsForumInGrouping = function cnsForumInGrouping(params, container) {
        var forumRulesUrl = params.forumRulesUrl,
            introQuestionUrl = params.introQuestionUrl;

        $cms.dom.on(container, 'click', '.js-click-open-forum-rules-popup', function () {
            $cms.ui.open($cms.maintainThemeInLink(forumRulesUrl), '', 'width=600,height=auto,status=yes,resizable=yes,scrollbars=yes');
        });

        $cms.dom.on(container, 'click', '.js-click-open-intro-question-popup', function () {
            $cms.ui.open($cms.maintainThemeInLink(introQuestionUrl), '', 'width=600,height=auto,status=yes,resizable=yes,scrollbars=yes');
        });
    };

    $cms.templates.cnsTopicScreen = function (params, /**Element*/container) {
        var markedPostActionsForm = container.querySelector('form.js-form-marked-post-actions');

        if ((params.serializedOptions !== undefined) && (params.hash !== undefined)) {
            window.commentsSerializedOptions = params.serializedOptions;
            window.commentsHash = params.hash;
        }

        $cms.dom.on(container, 'click', '.js-click-check-marked-form-and-submit', function (e, clicked) {
            if (!$cms.form.addFormMarkedPosts(markedPostActionsForm, 'mark_')) {
                $cms.ui.alert('{!NOTHING_SELECTED;}');
                e.preventDefault();
                return;
            }

            if (document.getElementById('mpa_type').selectedIndex === -1) {
                e.preventDefault();
                return;
            }

            $cms.ui.disableButton(clicked);
        });

        $cms.dom.on(container, 'click', '.js-click-require-tma-type-selection', function (e, btn) {
            if ($cms.dom.$('#tma_type').selectedIndex !== -1) {
                $cms.ui.disableButton(btn);
            } else {
                e.preventDefault();
            }
        })
    };

    $cms.templates.cnsTopicPoll = function (params) {
        var form = this,
            minSelections = +params.minimumSelections || 0,
            maxSelections = +params.maximumSelections || 0,
            error  = (minSelections === maxSelections) ? $cms.format('{!cns:POLL_NOT_ENOUGH_ERROR_2;^}', minSelections) : $cms.format('{!cns:POLL_NOT_ENOUGH_ERROR;^}', [minSelections, maxSelections]);

        $cms.dom.on(form, 'submit', function (e) {
            if (cnsCheckPoll() === false) {
                e.preventDefault();
            }
        });

        function cnsCheckPoll() {
            var j = 0;
            for (var i = 0; i < form.elements.length; i++) {
                if (form.elements[i].checked && ((form.elements[i].type === 'checkbox') || (form.elements[i].type === 'radio'))) {
                    j++;
                }
            }
            var answer = ((j >= minSelections) && (j <= maxSelections));
            if (!answer) {
                $cms.ui.alert(error);
                return false;
            }

            $cms.ui.disableButton(form.elements['poll_vote_button']);
        }
    };

    $cms.templates.cnsNotification = function (params) {
        var container = this,
            ignoreUrl = params.ignoreUrl2;

        $cms.dom.on(container, 'click', '.js-click-ignore-notification', function () {
            var el = this;
            $cms.doAjaxRequest(ignoreUrl, function () {
                var o = el.parentNode.parentNode.parentNode.parentNode;
                o.parentNode.removeChild(o);

                var nots = document.querySelector('.cns_member_column_pts');
                if (nots && (document.querySelectorAll('.cns_notification').length === 0)) {
                    nots.parentNode.removeChild(nots);
                }
            });
        });
    };

    $cms.templates.cnsPrivateTopicLink = function (params, container) {
        $cms.dom.on(container, 'click', '.js-click-poll-for-notifications', function () {
            pollForNotifications(true, true);
        });
    };

    $cms.templates.cnsTopicPost = function cnsTopicPost(params, container) {
        var id = strVal(params.id),
            cell = $cms.dom.$('#cell_mark_' + id);


        $cms.dom.on(container, 'click', '.js-click-checkbox-set-cell-mark-class', function (e, checkbox) {
            cell.classList.toggle('cns_on', checkbox.checked);
            cell.classList.toggle('cns_off', !checkbox.checked);
        });
    };

    $cms.templates.cnsTopicMarker = function cnsTopicMarker(params, container) {
        $cms.dom.on(container, 'click', '.js-click-checkbox-set-row-mark-class', function (e, checkbox) {
            var row = $cms.dom.closest(checkbox, 'tr');
            row.classList.toggle('cns_on', checkbox.checked);
            row.classList.toggle('cns_off', !checkbox.checked);
        });
    };
}(window.$cms));
