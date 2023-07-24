(function (root) {

    var ps = root.ps || {};
    var config = {};
    var translation = null;

    function loadTemplates(templateNames) {

        for (var templateIndex in templateNames) {
            var templateName = templateNames[templateIndex];
            jQuery.ajax({
                type: 'GET',
                url: config.templatePath + templateNames[templateIndex] + '.dust',
                async: false,
                dataType: "text",
                success: function (testTempl) {
                    dust.loadSource(dust.compile(testTempl, templateName));
                },
                error: function (xhr) {
                    config.onError(xhr);
                }
            });
        }
    }

    ps.questionary = function (settings) {

        config = {
            translation: settings.translation || null,
            templatePath: settings.templatePath || '/questionary/templates/',
            container: settings.container,
            buildUrl: settings.buildUrl,
            saveUrl: settings.saveUrl,
            currentTab: 'document-0',
            errorClass: 'questionnaire-error-msg',
            hideAnswered: settings.hideAnswered || false,
            beforeReload: settings.beforeReload || function () {
                // empty by default
            },
            afterReload: settings.afterReload || function () {
                // empty by default
            },
            onFinish: settings.onFinish || function () {
                // empty by default
            },
            onQuestionaryNotFound: settings.onQuestionaryNotFound || function () {
                // empty by default
            },
            onError: settings.onError || function () {
                // empty by default
            }
        };

        if (config.translation === null)
        {
            config.translation = config.templatePath;
            config.translation = config.translation.split('templates')[0] + "translations/de.json";
        }

        var data = {
            conditions: {},
            variables: {}
        };

        function initEventListeners() {
            if (!ps.listenersInitialized) {
                // Save button
                jQuery('body').on('click', '.saveQuestionnaire', function () {
                    saveAnswer(this);
                });

                // On choose radion option in a radio question
                jQuery('body').on('change', '.question input[rel=radio]', function () {
                    saveAnswer(this);
                });

                // On choose radion option in a yes/no question
                jQuery('body').on('change', '.question input[rel=yesno]', function () {
                    saveAnswer(this);
                });

                jQuery('body').on('click', '.editVariables', function () {
                    jQuery('.variables-container').addClass('edit');
                    variablesShowSaveButton(jQuery(this));
                });

                // On select document in the left bar
                jQuery('body').on('click', '.questionnaire-nav a', function () {
                    tabsNavigation(this);
                });

                // On click save variables button in a variables section
                jQuery('body').on('click', '.saveVariables', function () {
                    var inputs;
                    var variables = jQuery(this).parent();
                    var isValid = true;

                    jQuery(variables).each(function (key, variableM) {
                        if (hasContainerValues(jQuery(variableM))) {
                            //remove error class
                            jQuery(variableM).removeClass('error-focus');

                            //collect data from inputs or textareas
                            if (jQuery(variableM).find('textarea').length > 0) {
                                //textarea
                                var textarea = jQuery(variableM).find('textarea');
                                collectVariableData(textarea);
                            }
                            inputs = jQuery(variableM).find('input');
                            jQuery.each(jQuery(inputs), function (key, input) {

                                collectVariableData(input);
                            });
                        } else {
                            isValid = false;
                            variableError(jQuery(variableM));
                            jQuery(variableM).addClass('error-focus');
                        }
                    });
                    if (isValid) {
                        ps.sendAnswers();
                    }
                });

                // Main questionary save button
                jQuery('body').on('click', '#submitQuestionnaire', function (e) {
                    if (jQuery('#submitQuestionnaire').hasClass('active')) {
                        config.onFinish();
                    }
                });

                // Edit answered question
                jQuery('body').on('click', '.editQuestionnaire', function () {
                    var questionHolder = jQuery(this).parent();
                    jQuery(questionHolder).addClass('edit');
                    disableAllVariableContainers(true);
                    editQuestion(this);
                });

                // On click "Forward" button at the and of the document
                jQuery('body').on('click', '.nextDocument', function () {
                    var nextTabInt = parseInt(classConvertor(config.currentTab)) + 1;
                    var nextTabRel = selectRightDocument(nextTabInt, true);
                    var navElement = jQuery('[rel="' + nextTabRel + '"]');
                    tabsNavigation(navElement);
                });

                // On click "Back" button at the and of the document
                jQuery('body').on('click', '.prevDocument', function () {
                    var prevTabInt = parseInt(classConvertor(config.currentTab)) - 1;
                    var prevTabRel = selectRightDocument(prevTabInt, false);
                    ;
                    var navElement = jQuery('[rel="' + prevTabRel + '"]');
                    tabsNavigation(navElement);
                });

                // Change show only unanswered questions option
                jQuery('body').on('change', '#hideAnswered', function () {
                    config.hideAnswered = jQuery(this).is(':checked');
                    loadQuestionary();
                });

                ps.listenersInitialized = true;
            }
        }

        function init() {

            jQuery.ajax({
                type: 'GET',
                url: config.translation,
                success: function(res) {
                    translation = res;
                    loadTemplates(['questionary', 'question', 'variable']);
                    loadQuestionary();
                },
                error: function(err) {
                    console.log(err);
                }
            });

            initEventListeners();
        }

        function loadQuestionary() {

            config.beforeReload();

            jQuery.ajax({
                type: 'GET',
                url: config.buildUrl,
                dataType: "json",
                success: function (response) {

                    filterQuestions(response.content.documents);
                    
                    if ( typeof( translation ) == 'string' ) {
                        translation = jQuery.parseJSON( translation );
                    }
                    
                    dust.render('questionary', {documents: response.content.documents, config: config, tr: translation}, function (err, out) {
                        if (err) {
                            config.onError(err);
                        }
                        jQuery(config.container).html(out);
                        
                    });

                    initTabDocuments();
                    initFields();
                    //Tabs 
                    var navElement = jQuery('[rel="' + config.currentTab + '"]');

                    showTab(navElement);

                    var prevNavElement = navElement.parent();
                    if (docValidation(config.currentTab)) {
                        jQuery('.nextDocument').removeClass('inactive').addClass('active');
                    }
                    else {
                        jQuery('.nextDocument').removeClass('active').addClass('inactive');
                    }

                    var complete = isEveryThingAnswered();
                    if (complete === false) {
                        jQuery('#hide-answered-warning').hide();
                    }

                    config.afterReload();

                },
                error: function (xhr) {
                    config.onQuestionaryNotFound(xhr);
                }
            });

        }

        function initTabDocuments() {
            var documents = jQuery('.questionnaire-document');

            documents.each(function (index, element) {
                var classNames = jQuery(element).attr('class');
                var documentData = classNames.split(' ');
                var navElement = jQuery('[rel="' + documentData[1] + '"]');

                if (isDocumentEmpty(element)) {
                    navElement.parent().hide();
                } else {
                    navElement.parent().show();
                }
            });
        }

        function isDocumentEmpty(container) {

            var questions = jQuery(container).find('.question');
            var variables = jQuery(container).find('.variables-container');

            if (!questions.length > 0 && !variables.length > 0) {
                return true;
            }

            return false;
        }

        /*
         * Tabs initialization
         */
        function initTabs() {

            var documents = jQuery('.questionnaire-document');
            var docNavElements = jQuery('.questionnaire-nav li a:visible');

            hideDocuments();

            var isFirst = true;
            documents.each(function (index, document) {
                if (!isDocumentEmpty(document) && isFirst) {
                    jQuery(document).show();
                    isFirst = false;
                }
            });

            //select first navigation tab 
            docNavElements.first().addClass('selected');

            //sets the first tab as current
            setCurrentTab(docNavElements.first().attr('rel'));

            jQuery('.questionnaire-nav a').click(function () {
                tabsNavigation(this);
            });

            colorizeTabs();
            isEveryThingAnswered();
        }

        function hideDocuments() {
            jQuery('.questionnaire-document').hide();
        }

        /**
         * Sets current tab
         * @param {string} value
         */
        function setCurrentTab(value) {
            config.currentTab = value;
        }

        /*
         * Colorize tabs 
         */
        function colorizeTabs() {
            jQuery('.questionnaire-nav li a').each(function (index, element) {
                var className = jQuery(element).attr('rel');
                if (docValidation(className)) {
                    jQuery(element).parent().addClass('tab-success');
                }
            });
        }

        /**
         * Tabs navigation /forward, backward/
         * 
         * @param {DOM} navElement
         */
        function tabsNavigation(navElement) {
            var questionContainer = jQuery(navElement).attr('rel');
            if (classConvertor(questionContainer) > classConvertor(config.currentTab)) {
                //next tab
                //var documentContainer = jQuery('.' + config.currentTab);
                //var questions = jQuery('.' + config.currentTab + ' .question');
                //var variables = jQuery('.' + config.currentTab + ' .question-variable');

                setCurrentTab(questionContainer);
                showTab(jQuery(navElement));
                jQuery("html, body").animate({
                    scrollTop: jQuery(".questionary-container").offset().top
                }, 500);

            } else if (classConvertor(questionContainer) === classConvertor(config.currentTab)) {
                //current tab

            } else {
                //previous tab
                setCurrentTab(questionContainer);
                showTab(jQuery(navElement));
            }

            initButtonNavigation();

        }

        /*
         * Convert string name to int 
         * example: string "document-88" to int "88"
         * @param {className} className
         * @returns {int}
         */
        function classConvertor(className) {
            if (/-/.test(className)) {
                var paramParts = className.split('-');
                return parseInt(paramParts[1]);
            }
        }

        /**
         * Hides all documents and remove 
         * class selection from navigation
         * @param {object} tabNav
         */
        function showTab(tabNav) {
            hideDocuments();
            removeClassNav();
            tabNav.addClass('selected');
            //shows tab container
            jQuery('.' + tabNav.attr('rel')).show();
        }

        function removeClassNav() {
            jQuery('.questionnaire-nav li a').removeClass('selected');
        }

        /**
         * Check every document for answers
         */
        function isEveryThingAnswered() {
            var answered = true;
            jQuery('.questionnaire-nav li').each(function (key, container) {
                var button = jQuery(container).find('a');
                var docElementClass = jQuery(button).attr('rel');
                if (docValidation(docElementClass)) {
                    jQuery(container).addClass('tab-success');

                } else {
                    jQuery(container).removeClass('tab-success');
                    answered = false;
                }
            });

            if (answered) {
                jQuery('#submitQuestionnaire').removeClass('inactive').addClass('active');
            }

            return answered;
        }

        /*
         * Validates document section 
         * @param {string} docElementClass
         * @returns {Boolean}
         */
        function docValidation(docElementClass) {
            var isValid = true;
            var questions = jQuery('.' + docElementClass + ' .question');
            if (!validateQuestions(questions, true)) {
                isValid = false;
            }

            var allVariableContainers = jQuery('.' + docElementClass + ' .question-variable');
            allVariableContainers.each(function (index, variableContainer) {
                if (!hasContainerValues(jQuery(variableContainer))) {
                    isValid = false;
                    return false;
                }
            });

            return isValid;
        }

        function isSaved(documentContainer) {
            var dataContainer;
            var isValid = true;
            var sections = documentContainer.find('.questionnaire-section');
            sections.each(function (index, section) {
                dataContainer = jQuery(section).children();
                dataContainer.each(function (key, container) {
                    if (!jQuery(container).hasClass('answered')) {
                        if (jQuery(container).hasClass('question'))
                        {
                            questionError(jQuery(container));
                        }
                        if (jQuery(container).hasClass('variables-container'))
                        {
                            var variableContainers = jQuery(container).find('.question-variable');
                            variableContainers.each(function (index, varContainer) {
                                variableError(jQuery(varContainer));
                            });
                        }

                        jQuery(container).addClass('error-focus');
                        isValid = false;
                    }
                });
            });

            return isValid;
        }

        /**
         * Has container selected answers
         * @param {string} container
         * @returns {Boolean}
         */
        function hasContainerSelection(container) {
            return jQuery(container).find('input').is(':checked');
        }

        /**
         * Checks for valid question data of the current tab 
         * Checks for valida data when is submit 
         * @param {string} container
         * @param {boolean} removeError
         * @returns {Boolean}
         */
        function validateQuestions(container, removeError) {
            var isValid = true;

            //Checks the radio/checkbox group for selection
            jQuery.each(container, function (key, element) {
                if (!hasContainerSelection(element)) {
                    if (!removeError) {
                        jQuery(element).addClass('error-focus');
                    }
                    isValid = false;
                }
            });

            return isValid;
        }

        /**
         * Checks for valid variable data of the current tab 
         * @param {object} container
         * @returns {Boolean}
         */
        function validateVariables(container) {
            var isValid = true;
            if (!jQuery.isEmptyObject(container)) {
                container.each(function (key, element) {
                    if (!hasContainerValues(jQuery(element))) {
                        jQuery(element).addClass('error-focus');
                        isValid = false;
                    }

                });
            }

            return isValid;
        }

        function initButtonNavigation()
        {
            var btnPrev = jQuery('.prevDocument');
            var btnNext = jQuery('.nextDocument');
            var btnFinish = jQuery('#submitQuestionnaire');

            var countedDocs = [];

            jQuery('.questionnaire-document').each(function (index, document) {
                if (!isDocumentEmpty(document)) {
                    countedDocs.push(document);
                }
            });

            var documentFirstData = jQuery(countedDocs).first().attr('class').split(' ');
            var documentLastData = jQuery(countedDocs).last().attr('class').split(' ');

            if (countedDocs.length > 1) {
                if (classConvertor(config.currentTab) === classConvertor(documentFirstData[1])) {
                    btnPrev.hide();
                    btnFinish.hide();
                    btnNext.show();
                    initNextButton();
                } else if (classConvertor(config.currentTab) === classConvertor(documentLastData[1])) {
                    btnNext.hide();
                    btnPrev.show();
                    btnFinish.show();
                } else {
                    btnNext.show();
                    btnPrev.show();
                    btnFinish.hide();
                    initNextButton();
                }
            } else {
                btnNext.hide();
                btnPrev.hide();
                btnFinish.show();
            }

        }

        function initNextButton() {
            if (docValidation(config.currentTab)) {
                jQuery('.nextDocument').removeClass('inactive').addClass('active');
            }
            else {
                jQuery('.nextDocument').removeClass('active').addClass('inactive');
            }
        }

        /**
         * 
         * @param {object} container
         * @returns {Boolean} 
         */
        function hasContainerValues(container) {
            var hasValue = false;
            if (container.find('input').attr('rel') === 'checkbox' || container.find('input').attr('rel') === 'radio') {
                if (container.find('input').is(':checked')) {
                    hasValue = true;
                }
            } else {
                hasValue = true;

                if (container.find('input').length > 0) {
                    container.find('input').each(function (index, element) {
                        if (!jQuery(element).val().length > 0) {
                            hasValue = false;
                        }
                    });
                } else if (container.find('textarea').length > 0) {
                    container.find('textarea').each(function (index, element) {
                        if (!jQuery(element).val().length > 0) {
                            hasValue = false;
                        }
                    });
                }
            }

            return hasValue;
        }

        function questionError(container) {

            if (container.find('input').length > 1) {
                var element = container.find('.' + config.errorClass);
                element.show();
            }
        }

        function variableError(container) {

            if (container.find('input').length > 1) {
                var element = container.find('.' + config.errorClass);
                element.show();
            }
        }

        function selectRightDocument(tabInt, increase) {
            if (typeof (increase) === 'undefined')
                increase = true;

            var document = '.document-' + tabInt;

            if (jQuery(document).length > 0 && isDocumentEmpty(document)) {
                if (increase) {
                    return selectRightDocument(parseInt(tabInt + 1));
                } else {
                    return selectRightDocument(parseInt(tabInt - 1));
                }
            }

            return 'document-' + tabInt;
        }

        function saveAnswer(self) {
            var inputs;
            var questionContainer = jQuery(self).parent();
            if (hasContainerSelection(questionContainer)) {
                inputs = jQuery(questionContainer).find('input');
                jQuery.each(jQuery(inputs), function (key, input) {
                    collectQuestionData(input);
                });
                ps.sendAnswers();
            } else {
                questionError(questionContainer);
                jQuery(questionContainer).addClass('error-focus');
            }
        }

        /**
         * Create number and date flieds
         * Disable fields
         * Colorize divs
         */
        function initFields() {
            initButtonNavigation();
            createNumberFields();
            disableQuestions();
            disableAllVariableContainers();
            colorizeAnswered();
            colorizeVariables();
        }

        /**
         * Collect the answered question from the user
         * Sets the post data 
         * @param {DOM element} inputElement
         */
        function collectQuestionData(inputElement) {
            var inputName, inputValue, inputType, isSelected;

            inputType = getInputType(inputElement);
            isSelected = jQuery(inputElement).is(':checked');

            if (inputType == 'checkbox') {
                inputName = jQuery(inputElement).attr('name');

            } else if (inputType == 'yesno' && isSelected) {
                inputName = jQuery(inputElement).attr('name');
                inputValue = jQuery(inputElement).attr('value');
            } else if (inputType == 'radio') {
                inputName = jQuery(inputElement).attr('value');
            }

            if (inputType == 'checkbox' || inputType == 'radio') {
                if (isSelected) {
                    inputValue = '1';
                } else {
                    inputValue = '0';
                }
            }

            if (typeof inputName !== 'undefined') {
                data.conditions[inputName] = inputValue;
            }
        }

        /**
         * Collects all anwsers from draft or submit
         */
        function getAllAnswers() {
            data = {conditions: {}, variables: {}};
            jQuery.each(jQuery('.question'), function (key, element) {
                if (hasContainerSelection(element)) {
                    var inputElements = jQuery(element).find('input');

                    inputElements.each(function (key, input) {
                        collectQuestionData(input);
                    });
                }
            });
        }

        /**
         * Get question type /radio, checkbox or yesno/
         * @param {string} element
         * @returns {string}
         */
        function getInputType(element) {
            return jQuery(element).attr('rel');
        }

        /*
         * Send and draft save answers
         */
        ps.sendAnswers = function () {

            config.beforeReload();

            getAllAnswers();
            getAllVariables();

            jQuery.ajax({
                type: 'POST',
                url: config.saveUrl,
                dataType: "json",
                data: {answers: data},
                success: function (response) {
                    loadQuestionary();
                }
            });
        };

        /*
         * Collects all variables 
         */
        function getAllVariables() {

            jQuery.each(jQuery('.question-variable'), function (key, element) {
                if (hasContainerValues(jQuery(element))) {

                    //collect data from inputs or textareas
                    if (jQuery(element).find('textarea').length > 0) {
                        //textarea
                        var textarea = jQuery(element).find('textarea');
                        collectVariableData(textarea);
                    }

                    var inputs = jQuery(element).find('input');
                    jQuery.each(jQuery(inputs), function (key, input) {
                        collectVariableData(input);
                    });

                }
            });
        }

        /**
         * Disable determinations in a Section
         * except the first or if previous is answered
         *
         */
        function disableQuestions() {
            var sections = jQuery('.questionnaire-section');
            jQuery(sections).each(function (index, section) {
                var questions = jQuery(section).find('div.question');

                jQuery(questions).each(function (index, element) {

                    if (hasContainerSelection(element))
                    {
                        //mark as disabled all input fields
                        var inputFields = jQuery(element).find(':input');
                        jQuery(inputFields).each(function (index, element) {
                            jQuery(element).attr('disabled', true);
                        });
                        showEditButton(element);

                    }
                    else if (index != 0 && (!hasContainerSelection(element) && !hasContainerSelection(jQuery(this).prev()))) {
                        lockContainer(element);
                    }
                });
            });
        }

        /**
         * Enable edit button
         */
        function showEditButton(question) {
            var buttonSave = jQuery(question).find('.saveQuestionnaire');
            var buttonEdit = jQuery(question).find('.editQuestionnaire');
            buttonSave.addClass('disabled');
            buttonEdit.show();
            buttonEdit.removeAttr('disabled');
        }

        /**
         * Enable save button
         */
        function showSaveButton(question) {
            question.removeClass('answered');
            var buttonEdit = jQuery(question).find('.editQuestionnaire');
            var buttonSave = jQuery(question).find('.saveQuestionnaire');

            buttonEdit.hide();
            buttonSave.removeClass('disabled');
            buttonSave.removeAttr('disabled');
        }

        /**
         * Edit question
         * (enable input fields and 
         * set different background color)
         */
        function editQuestion(button) {
            //curent question
            var questionHolder = jQuery(button).parent();

            enableQuestionInputs(questionHolder);
            showSaveButton(questionHolder);
            disableCheckBoxesIfNoneOfTheAboveIsActive(questionHolder);

            //lock other questions
            var questions = jQuery('div.question');
            jQuery(questions).each(function (index, element) {

                if (!jQuery(element).hasClass('edit')) {
                    lockContainer(element);
                }
            });
            jQuery(questionHolder).addClass('edit');
        }

        /**
         * Enable question input fields
         */
        function enableQuestionInputs(question) {
            var inputFields = jQuery(question).find(':input');
            jQuery(inputFields).each(function (index, element) {
                jQuery(element).removeAttr('disabled');
            });
        }

        /**
         * Set question as disabled
         */
        function lockContainer(question) {
//            jQuery(question).fadeTo('slow', .6);
//            jQuery(question).append('<div style="position: absolute;top:0;left:0;width: 100%;height:100%;z-index:2;opacity:0.4;filter: alpha(opacity = 50)"></div>');
        }

        /**
         * Set color for answered questions 
         *
         */
        function colorizeAnswered() {
            jQuery('.question').each(function (index, questionContainer) {
                if (hasContainerSelection(questionContainer)) {
                    jQuery(questionContainer).addClass('answered');
                }
            });
        }

        /**
         * Create input only numeric input fields
         */
        function createNumberFields() {
            jQuery('input.numberInput').bind('keypress', function (e) {
                // Allow only certain characters
                // a-z
                var a2z = (e.which >= 97 && e.which <= 122);
                // A-Z
                var A2Z = (e.which >= 65 && e.which <= 90);
                return !(a2z || A2Z);
            });
        }

        /**
         * @returns {undefined}
         */
        function disableAllVariableContainers(force) {
            var sections = jQuery('.questionnaire-section');
            jQuery(sections).each(function (index, section) {
                if (!validateSection(section) || force == true) {
                    var variables = jQuery(section).find('div.question-variable');

                    jQuery(variables).each(function (index, element) {
                        lockContainer(jQuery(element).parent());
                    });
                } else {

                }
            });
        }

        /**
         * Validate answers by section
         * 
         * @param {DOM element} section
         * @returns {Boolean}
         */
        function validateSection(section) {
            var isValid = true;
            var questions = jQuery(section).find('div.question');

            jQuery(questions).each(function (index, question) {
                jQuery.each(jQuery(question), function (key, field) {
                    if (!hasContainerSelection(field)) {
                        isValid = false;
                    }
                });
            });

            return isValid;
        }

        /**
         * Collect the answered variables from the user
         * Sets the post data 
         * @param {DOM element} element
         */

        function collectVariableData(element) {
            var inputType = jQuery(element).attr('rel');
            var inputName = jQuery(element).attr('name');
            // TODO: remove date fix after proper date validation
            if ((inputType == 'text' || inputType == 'date') && jQuery(element).val().length > 0) {
                data.variables[inputName] = jQuery(element).val();
            } else if (inputType == "radio") {
                var checkedInput = jQuery(element).is(':checked');
                if (checkedInput) {
                    data.variables[inputName] = jQuery(element).attr('value');
                }
            } else if (inputType == "checkbox") {
                var checkedInput = jQuery(element).is(':checked');
                var inputValue = jQuery(element).attr('value');

                if (checkedInput) {
                    if (!data.variables[inputName]) {
                        data.variables[inputName] = new Array();
                    }
                    if (jQuery.inArray(inputValue, data.variables[inputName]) == -1) {
                        data.variables[inputName].push(jQuery(element).attr('value'));
                    }

                } else {
                    //remove item if is unchecked and has value in array
                    var indexToRemove = jQuery.inArray(inputValue, data.variables[inputName]);
                    if (indexToRemove != -1) {
                        data.variables[inputName].splice(indexToRemove, 1);
                    }

                    //delete array if is empty
                    if (data.variables[inputName] && data.variables[inputName].length == 0) {
                        delete data.variables[inputName];
                    }
                }
            }
        }

        /**
         * 
         */
        function colorizeVariables() {
            var variablesContainer = jQuery('.variables-container');
            variablesContainer.each(function (index, element) {
                if (hasContainerValues(jQuery(element))) {
                    jQuery(element).addClass('answered');
                    variablesShowEditButton(jQuery(element));
                }
            });
        }

        /**
         * Enable edit button
         * @param {object} container
         */
        function variablesShowEditButton(container) {
            var buttonSave = container.find('.saveVariables');
            var buttonEdit = container.find('.editVariables');

            buttonSave.addClass('disabled');
            buttonSave.attr('disabled', true);
            buttonEdit.show();
            buttonEdit.removeAttr('disabled');

            disableAllVariableInputs(container, true);
        }

        /**
         * Disable or enable variable inputs
         * 
         * @param {object} container
         * @param {boolean} flag
         */
        function disableAllVariableInputs(container, flag) {
            var variables = container.find('.question-variable');
            variables.each(function (index, element) {
                var inputs = jQuery(variables).find('input');
                jQuery(inputs).each(function (key, input) {
                    jQuery(input).attr('disabled', flag);
                });

                var textarea = jQuery(variables).find('textarea');
                jQuery(textarea).each(function (key, input) {
                    jQuery(input).attr('disabled', flag);
                });
            });
        }

        /**
         * Show save button 
         * in variables container
         * 
         * @param {object} button
         */
        function variablesShowSaveButton(button) {
            button.hide();

            var parent = button.parent();
            parent.removeClass('answered');
            var buttonSave = parent.find('.saveVariables');

            buttonSave.removeAttr('disabled');
            buttonSave.removeClass('disabled');

            disableAllVariableInputs(button.parent(), false);

            //lock all questions
            var questions = jQuery('div.question');
            jQuery(questions).each(function (index, element) {
                lockContainer(element);
            });
        }

        function isEmail(email) {
            var regExp = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+jQuery/;
            return regExp.test(email);
        }

        function filterQuestions(documents) {

            if (config.hideAnswered) {
                jQuery.each(documents, function (docIndex, doc) {
                    jQuery.each(doc.sections, function (sectionIndex, section) {
                        // If any question/variable is shown in this section,
                        // it should set the flag to false in the iteration below
                        section.hidden = true;

                        // Hide questions
                        jQuery.each(section.questions, function (qIntex, question) {
                            question.hidden = false;
                            switch (question.type) {
                                case "radio":
                                    jQuery.each(question.options, function (optIntex, option) {
                                        if (option.value == "1") {
                                            question.hidden = true;
                                        }
                                    });
                                    break;
                                case "yesno":
                                    jQuery.each(question.options, function (optIntex, option) {
                                        if (option.value !== undefined) {
                                            question.hidden = true;
                                        }
                                    });
                                    break;
                                case "checkbox":
                                    jQuery.each(question.options, function (optIntex, option) {
                                        if (option.value == "1") {
                                            question.hidden = true;
                                        }
                                    });
                                    break;
                            }
                            if (!question.hidden) {
                                section.hidden = false;
                            }
                        });
                        // Hide variables
                        jQuery.each(section.variables, function (varIntex, variable) {
                            variable.hidden = false;
                            switch (variable.type) {
                                case "singleline":
                                case "multiline":
                                case "number":
                                case "date":
                                    if (typeof variable.value !== "undefined") {
                                        variable.hidden = true;
                                    }
                                    break;
                                case "radio":
                                    jQuery.each(variable.options, function (optIntex, option) {
                                        if (option.key === variable.value) {
                                            variable.hidden = true;
                                        }
                                    });
                                    break;
                                case "checkbox":
                                    jQuery.each(variable.options, function (optIntex, option) {
                                        if (jQuery.inArray(option.key, variable.value) >= 0) {
                                            variable.hidden = true;
                                        }
                                    });
                                    break;
                            }
                            if (!variable.hidden) {
                                section.hidden = false;
                            }
                        });
                    });
                });
            }
        }

        function disableCheckBoxesIfNoneOfTheAboveIsActive(questionContainer) {
            questionContainer.find('.checkList').each(function(){
                if (jQuery(this).find('input').attr('id').search('noneOfTheAbove') > 0 && jQuery(this).find('input').is(':checked')) {
                    questionContainer.find('.checkList input').attr('disabled', true);
                    questionContainer.find('.checkList input').prop('checked', false);

                    jQuery(this).find('input').attr('disabled', false);
                    jQuery(this).find('input').prop('checked', true);;
                }
            });
        }

        return init();

    };

    ps.documents = function (settings) {

        config = {
            templatePath: settings.templatePath || '/questionary/templates/',
            container: settings.container,
            listUrl: settings.listUrl,
            downloadUrl: settings.downloadUrl
        };

        function init() {

            loadTemplates(['documents']);

            jQuery.ajax({
                type: 'GET',
                url: config.listUrl,
                async: false,
                dataType: "json",
                success: function (list) {

                    dust.render('documents', {documents: list.content.documents, downloadUrl: config.downloadUrl}, function (err, out) {
                        if (err) {
                            return console.log(err);
                        }
                        jQuery(config.container).html(out);
                    });
                },
                error: function (xhr) {
                    alert('Error!  Status = ' + xhr.status);
                }
            });
        }

        return init();
    };


    root.ps = ps;
}
)(this);