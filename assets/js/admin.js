jQuery(document).ready(function ($) {
    'use strict';

    check_conditions();
    $("input").change(function (e) {
        check_conditions();
    });

    $("select").change(function (e) {
        check_conditions();
    });

    $("textarea").change(function (e) {
        check_conditions();
    });


    /*conditional fields*/
    function check_conditions() {
        var value;
        var showIfConditionMet = true;

        $(".condition-check").each(function (e) {
            var question = 'burst_' + $(this).data("condition-question");
            var condition_type = 'AND';

            if (question == undefined) return;

            var condition_answer = $(this).data("condition-answer");

            //remove required attribute of child, and set a class.
            var input = $(this).find('input[type=checkbox]');
            if (!input.length) {
                input = $(this).find('input');
            }
            if (!input.length) {
                input = $(this).find('textarea');
            }
            if (!input.length) {
                input = $(this).find('select');
            }

            if (input.length && input[0].hasAttribute('required')) {
                input.addClass('is-required');
            }

            //cast into string
            condition_answer += "";

            if (condition_answer.indexOf('NOT ') !== -1) {
                condition_answer = condition_answer.replace('NOT ', '');
                showIfConditionMet = false;
            } else {
                showIfConditionMet = true;
            }
            var condition_answers = [];
            if (condition_answer.indexOf(' OR ') !== -1) {
                condition_answers = condition_answer.split(' OR ');
                condition_type = 'OR';
            } else {
                condition_answers = [condition_answer];
            }

            var container = $(this);
            var conditionMet = false;
            condition_answers.forEach(function (condition_answer) {
                value = get_input_value(question);

                if ($('select[name=' + question + ']').length) {
                    value = Array($('select[name=' + question + ']').val());
                }

                if ($("input[name='" + question + "[" + condition_answer + "]" + "']").length){
                    if ($("input[name='" + question + "[" + condition_answer + "]" + "']").is(':checked')) {
                        conditionMet = true;
                        value = [];
                    } else {
                        conditionMet = false;
                        value = [];
                    }
                }

                if (showIfConditionMet) {

                    //check if the index of the value is the condition, or, if the value is the condition
                    if (conditionMet || value.indexOf(condition_answer) != -1 || (value == condition_answer)) {

                        container.removeClass("hidden");
                        //remove required attribute of child, and set a class.
                        if (input.hasClass('is-required')) input.prop('required', true);
                        //prevent further checks if it's an or statement
                        if (condition_type === 'OR') conditionMet = true;

                    } else {
                        container.addClass("hidden");
                        if (input.hasClass('is-required')) input.prop('required', false);
                        //prevent further checks if it's an or statement
                        if (condition_type === 'OR') return;
                    }
                } else {

                    if (conditionMet || value.indexOf(condition_answer) != -1 || (value == condition_answer)) {
                        container.addClass("hidden");
                        if (input.hasClass('is-required')) input.prop('required', false);

                    } else {
                        container.removeClass("hidden");
                        if (input.hasClass('is-required')) input.prop('required', true);
                    }
                }
            });

        });
    }


    /**
        get checkbox values, array proof.
    */

    function get_input_value(fieldName) {

        if ($('input[name=' + fieldName + ']').attr('type') == 'text') {
            return $('input[name^=' + fieldName + ']').val();
        } else {
            var checked_boxes = [];
            $('input[name=' + fieldName + ']:checked').each(function () {
                checked_boxes[checked_boxes.length] = $(this).val();
            });
            return checked_boxes;
        }
    }

    //select2 dropdown
    if ($('.burst-select2-page-field').length) {
        burstInitSelect2()
    }

    function burstInitSelect2() {
        // multiple select with AJAX search
        var fieldName = $('.burst-select2-page-field').attr("name");
        var queryName = fieldName + '_query_settings';
        console.log(queryName);
        $('.burst-select2-page-field').select2({
            ajax: {
                    url: ajaxurl, // AJAX URL is predefined in WordPress admin
                    dataType: 'json',
                    delay: 250, // delay in ms while typing when to perform a AJAX search
                    data: function (params) {
                        return {
                            q: params.term, // search query
                            query_settings: window[queryName],
                            action: 'burst_get_posts' // AJAX action for admin-ajax.php
                        };
                    },
                    processResults: function( data ) {
                    var options = [];
                    if ( data ) {
     
                        // data is the array of arrays, and each of them contains ID and the Label of the option
                        $.each( data, function( index, text ) { // do not forget that "index" is just auto incremented value
                            options.push( { id: text[0], text: text[1]  } );
                        });
                        console.log(options);
     
                    }
                    return {
                        results: options
                    };
                },
                cache: true
            },
            minimumInputLength: 2, // the minimum of symbols to input before perform a search
            debug: true, //@todo remove debug
            width:'100%',
        });
    }

    $(document).on('click', '.burst-experiment-action', function (e) {
        e.preventDefault();
        var btn = $(this);
        btn.closest('tr').css('background-color', 'red');
        var experiment_id = btn.data('id');
        var type = btn.data('action');
        $.ajax({
            type: "POST",
            url: burst.ajaxurl,
            dataType: 'json',
            data: ({
                action: 'burst_experiment_action',
                experiment_id: experiment_id,
                type: type
            }),
            success: function (response) {
                if (response.success) {
                    btn.closest('tr').remove();
                }
            }
        });
    });
});

function burstLocalizeString(str) {
    var strings = burst.strings;
    for (var k in strings) {
        if (strings.hasOwnProperty(k)) {
            str = str.replaceAll(k, strings[k]);
        }
    }
    return str;
}