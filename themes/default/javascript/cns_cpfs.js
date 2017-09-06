(function ($cms) {
    'use strict';

    $cms.functions.moduleAdminCnsCustomProfileFields_createSelectionListChooseTable = function (formId) {
        formId = strVal(formId);

        var selectElements = document.getElementById(formId).getElementsByTagName('select');
        var selectSubmit = document.getElementById('selection_submit');
        var selectElementLength = selectElements.length;

        for (var counter = 0; counter < selectElementLength; counter++) {
            selectElements[counter].addEventListener('change', function () {
                $cms.dom.submit(selectElements[0].form);
            });
        }
        selectSubmit.style.display = 'none';
    };
}(window.$cms));
